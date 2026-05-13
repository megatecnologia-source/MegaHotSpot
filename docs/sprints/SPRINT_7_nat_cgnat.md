# SPRINT 7 — Suporte a NAT/CGNAT (Pull-Based Sync)

**Pré-requisito:** Sprints 1–6 concluídas  
**Entrega:** Sistema de sincronização invertida (MikroTik → Servidor)  
**Problema resolvido:** Quando o MikroTik do cliente está atrás de NAT ou CGNAT, o servidor não consegue fazer chamadas REST diretamente para o roteador. Esta sprint inverte a arquitetura: **o MikroTik consulta o servidor** (conexão sainte, sem necessidade de IP público).

---

## Contexto e Arquitetura

**Fluxo ANTES (Sprint 2 — funciona apenas com IP público):**
```
[Celular] → POST /api/v1/sync.php → Servidor salva lead → Servidor chama MikroTik REST API
                                                                           ↑ FALHA em CGNAT
```

**Fluxo DEPOIS (Sprint 7 — funciona em qualquer cenário):**
```
[Celular] → POST /api/v1/sync.php → Servidor salva lead com status "pending"

[MikroTik Scheduler, a cada 30s] → GET /api/v1/pending_users.php → Recebe lista de pendentes
[MikroTik Script] → Cria usuários localmente → POST /api/v1/confirm_sync.php → Marca como "synced"
```

**Regra de ouro:** O MikroTik NUNCA recebe conexões de entrada. Ele apenas faz conexões de saída para `api.megatecnologias.com` (já liberado no Walled Garden).

---

## Task 7.1 — Migração do Banco de Dados

**Arquivo:** `sql/migration_sprint7.sql`  
**Ação:** CRIAR  
**Como executar:** `mysql -u USER -p mega_hotspot < sql/migration_sprint7.sql`

```sql
-- Sprint 7: Suporte a NAT/CGNAT
-- Adiciona colunas de controle de sincronização com MikroTik

ALTER TABLE `leads`
  ADD COLUMN `senha_hotspot`        VARCHAR(10)  DEFAULT NULL COMMENT '4 últimos dígitos do WhatsApp',
  ADD COLUMN `mikrotik_sync_status` ENUM('pending','synced','error') NOT NULL DEFAULT 'pending'
      COMMENT 'pending=aguardando sync, synced=usuário criado no MikroTik, error=falha',
  ADD COLUMN `mikrotik_synced_at`   TIMESTAMP NULL DEFAULT NULL
      COMMENT 'Quando o MikroTik confirmou a criação do usuário',
  ADD INDEX `idx_sync_status` (`estabelecimento_id`, `mikrotik_sync_status`);
```

**Verificação:**
```bash
mysql -u USER -p mega_hotspot -e "DESCRIBE leads;" | grep -E "senha_hotspot|mikrotik"
# Deve mostrar as 3 novas colunas
```

---

## Task 7.2 — Modificar `api/v1/sync.php`

**Arquivo:** `/api/v1/sync.php`  
**Ação:** MODIFICAR — substituir a lógica de push direto pela gravação com status `pending`

**Localizar e substituir SOMENTE a seção da chamada ao MikroTikRouter (a partir da linha `// 2. Sincronizar com o MikroTik`):**

Substituir este bloco:
```php
// 2. Sincronizar com o MikroTik (Graceful degradation)
$hotspotCreated = false;
if (!empty($estabelecimento['mikrotik_ip'])) {
    $router = new MikroTikRouter(
        $estabelecimento['mikrotik_ip'],
        (int) $estabelecimento['mikrotik_port'],
        $estabelecimento['mikrotik_api_user'],
        $estabelecimento['mikrotik_api_pass']
    );
    
    // Remove pontuação do CPF para o username do MikroTik
    $cpfLimpo = preg_replace('/\D/', '', $cpf);
    
    $result = $router->criarOuAtualizarUsuario(
        $cpfLimpo,
        $senhaHotspot,
        $estabelecimento['mikrotik_profile']
    );
    
    $hotspotCreated = $result['ok'];
}

// Retorna Sucesso (201 Created)
http_response_code(201);
echo json_encode([
    "status" => "success",
    "hotspot_user_created" => $hotspotCreated
]);
```

Por este novo bloco:
```php
// 2. Gravar senha_hotspot para sincronização posterior (modelo Pull)
// O MikroTik consultará /api/v1/pending_users.php para buscar os pendentes.
$stmtSenha = $pdo->prepare("
    UPDATE leads SET senha_hotspot = :senha, mikrotik_sync_status = 'pending'
    WHERE estabelecimento_id = :estab_id AND cpf = :cpf
");
$stmtSenha->execute([
    ':senha'    => $senhaHotspot,
    ':estab_id' => $estabelecimentoId,
    ':cpf'      => $cpf
]);

// 3. Tentativa imediata de push (funciona se o MikroTik tiver IP público configurado)
$hotspotCreated = false;
if (!empty($estabelecimento['mikrotik_ip'])) {
    require_once __DIR__ . '/router.php';
    try {
        $router = new MikroTikRouter(
            $estabelecimento['mikrotik_ip'],
            (int) $estabelecimento['mikrotik_port'],
            $estabelecimento['mikrotik_api_user'],
            $estabelecimento['mikrotik_api_pass']
        );
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        $result = $router->criarOuAtualizarUsuario($cpfLimpo, $senhaHotspot, $estabelecimento['mikrotik_profile']);
        
        if ($result['ok']) {
            // Push direto funcionou: marcar como synced imediatamente
            $hotspotCreated = true;
            $stmtSynced = $pdo->prepare("
                UPDATE leads SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW()
                WHERE estabelecimento_id = :estab_id AND cpf = :cpf
            ");
            $stmtSynced->execute([':estab_id' => $estabelecimentoId, ':cpf' => $cpf]);
        }
        // Se falhar, o status permanece 'pending' e o Scheduler vai resolver
    } catch (Throwable $routerEx) {
        // Silenciosamente ignora — lead já está salvo como pending
    }
}

// 4. Retorna Sucesso (201)
// O portal já pode redirecionar para o login direto no MikroTik.
// O usuário pode não existir ainda se for CGNAT, mas o Scheduler sync em até 30s.
http_response_code(201);
echo json_encode([
    "status"               => "success",
    "hotspot_user_created" => $hotspotCreated,
    "sync_mode"            => $hotspotCreated ? "push" : "pull_pending"
]);
```

> **Atenção ao agente:** Remova o `require_once __DIR__ . '/router.php';` do início do arquivo, pois ele agora é carregado condicionalmente dentro do bloco.

---

## Task 7.3 — Criar `api/v1/pending_users.php`

**Arquivo:** `/api/v1/pending_users.php`  
**Ação:** CRIAR  
**Auth:** Header `X-Auth-Token` com o `cliente_token` do estabelecimento  
**Método:** GET  
**Resposta:** JSON — lista de usuários a serem criados no MikroTik

```php
<?php
/**
 * api/v1/pending_users.php
 * Consultado pelo Scheduler do MikroTik (a cada 30s).
 * Retorna lista de leads pendentes de sincronização.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error"]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

// Auth via token no header
$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token ausente."]);
    exit;
}

try {
    $pdo = DB::getInstance();

    // Valida token e busca o profile do estabelecimento
    $stmtEst = $pdo->prepare("
        SELECT id, mikrotik_profile 
        FROM estabelecimentos 
        WHERE cliente_token = :token AND ativo = 1 
        LIMIT 1
    ");
    $stmtEst->execute([':token' => $token]);
    $est = $stmtEst->fetch();

    if (!$est) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido."]);
        exit;
    }

    // Busca leads pendentes (máx 50 por vez para não sobrecarregar o script)
    $stmt = $pdo->prepare("
        SELECT cpf, senha_hotspot
        FROM leads
        WHERE estabelecimento_id = :id
          AND mikrotik_sync_status = 'pending'
          AND senha_hotspot IS NOT NULL
        ORDER BY data_cadastro ASC
        LIMIT 50
    ");
    $stmt->execute([':id' => $est['id']]);
    $pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formata a resposta: CPF sem formatação (usado como username no MikroTik)
    $usuarios = array_map(function ($row) use ($est) {
        return [
            "username" => preg_replace('/\D/', '', $row['cpf']), // Apenas números
            "password" => $row['senha_hotspot'],
            "profile"  => $est['mikrotik_profile']
        ];
    }, $pendentes);

    echo json_encode([
        "status"  => "success",
        "total"   => count($usuarios),
        "usuarios" => $usuarios
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
```

---

## Task 7.4 — Criar `api/v1/confirm_sync.php`

**Arquivo:** `/api/v1/confirm_sync.php`  
**Ação:** CRIAR  
**Auth:** Header `X-Auth-Token`  
**Método:** POST  
**Body JSON:** `{"usernames": ["52998224725", "11999990000"]}`  
**Ação:** Marca os leads como `synced` no banco

```php
<?php
/**
 * api/v1/confirm_sync.php
 * Chamado pelo Scheduler do MikroTik após criar os usuários localmente.
 * Marca leads como 'synced' no banco central.
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error"]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
if (empty($token)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token ausente."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$usernames = $input['usernames'] ?? [];

if (empty($usernames) || !is_array($usernames)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campo 'usernames' obrigatório (array)."]);
    exit;
}

try {
    $pdo = DB::getInstance();

    // Valida token
    $stmtEst = $pdo->prepare("SELECT id FROM estabelecimentos WHERE cliente_token = :token AND ativo = 1 LIMIT 1");
    $stmtEst->execute([':token' => $token]);
    $est = $stmtEst->fetch();

    if (!$est) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Token inválido."]);
        exit;
    }

    // Monta CPFs formatados a partir dos usernames (números puros)
    // O banco guarda CPF formatado (999.999.999-99), username é só números
    $updated = 0;
    foreach ($usernames as $username) {
        $username = preg_replace('/\D/', '', $username); // Garante que é só números
        if (strlen($username) !== 11) continue;

        // Formata no padrão do banco
        $cpf = substr($username, 0, 3) . '.' . substr($username, 3, 3) . '.' 
             . substr($username, 6, 3) . '-' . substr($username, 9, 2);

        $stmt = $pdo->prepare("
            UPDATE leads
            SET mikrotik_sync_status = 'synced', mikrotik_synced_at = NOW()
            WHERE estabelecimento_id = :estab_id
              AND cpf = :cpf
              AND mikrotik_sync_status = 'pending'
        ");
        $stmt->execute([':estab_id' => $est['id'], ':cpf' => $cpf]);
        $updated += $stmt->rowCount();
    }

    echo json_encode(["status" => "success", "synced" => $updated]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
```

---

## Task 7.5 — Script RouterOS para o MikroTik

**Onde configurar:** WinBox → System → Scripts → `[+]`  
**Nome do script:** `mega-hotspot-sync`

Este script deve ser cadastrado em **cada MikroTik de cliente**. Substitua os dois valores marcados.

```routeros
# ============================================================
# MEGA HOTSPOT SYNC — Script de Sincronização Pull
# Configurar via: System > Scripts > [+]
# Nome: mega-hotspot-sync
# ============================================================

:local apiBase "https://api.megatecnologias.com/api/v1"
:local clienteToken "SUBSTITUIR_PELO_TOKEN_UUID_DO_CLIENTE"

# --- 1. Buscar usuários pendentes ---
:local resPendentes [/tool fetch check-certificate=no \
    url=($apiBase . "/pending_users.php") \
    http-method=get \
    http-header-field="X-Auth-Token: $clienteToken" \
    output=user \
    as-value \
    keep-result=yes]

:if (($resPendentes->"status") != "finished") do={
    :log warning "MegaSync: Falha ao buscar pendentes. Abortando."
    :error "fetch failed"
}

:local bodyPendentes ($resPendentes->"data")

# Deserializa JSON (RouterOS 7+)
:local parsed [:deserialize value=$bodyPendentes from=json]

:if ([:typeof $parsed] = "nothing") do={
    :log warning "MegaSync: Resposta inválida da API."
    :error "invalid json"
}

:local statusResp ($parsed->"status")
:if ($statusResp != "success") do={
    :log warning "MegaSync: API retornou erro."
    :error "api error"
}

:local usuarios ($parsed->"usuarios")
:local total [:len $usuarios]

:if ($total = 0) do={
    :log info "MegaSync: Nenhum usuário pendente."
    :error "no pending"
}

:log info ("MegaSync: Processando " . $total . " usuário(s) pendente(s).")

# --- 2. Criar ou atualizar usuários no hotspot ---
:local confirmados ""
:local separador ""

:foreach u in=$usuarios do={
    :local uname ($u->"username")
    :local upass  ($u->"password")
    :local uprof  ($u->"profile")

    # Verificar se usuário já existe
    :local existe [/ip hotspot user find name=$uname]

    :if ([:len $existe] > 0) do={
        # Atualiza senha
        /ip hotspot user set [find name=$uname] password=$upass profile=$uprof
        :log info ("MegaSync: Atualizado usuario " . $uname)
    } else={
        # Cria novo
        /ip hotspot user add name=$uname password=$upass profile=$uprof \
            comment="Mega Hotspot Sync"
        :log info ("MegaSync: Criado usuario " . $uname)
    }

    # Acumula para confirmar em batch
    :set confirmados ($confirmados . $separador . "\"" . $uname . "\"")
    :set separador ","
}

# --- 3. Confirmar sincronização para o servidor central ---
:local bodyConfirm ("{\"usernames\":[" . $confirmados . "]}")

/tool fetch check-certificate=no \
    url=($apiBase . "/confirm_sync.php") \
    http-method=post \
    http-header-field="X-Auth-Token: $clienteToken\r\nContent-Type: application/json" \
    http-data=$bodyConfirm \
    output=none

:log info ("MegaSync: Confirmados " . $total . " usuario(s) no servidor.")
```

---

## Task 7.6 — Criar Scheduler no MikroTik

Após cadastrar o script acima, criar o agendador:

```routeros
/system scheduler
add name=mega-hotspot-sync \
    interval=30s \
    on-event=mega-hotspot-sync \
    policy=read,write,test \
    comment="Mega Hotspot - Sincronizacao de usuarios (Pull Mode)"
```

**Verificar:**
```routeros
/system scheduler print
# Deve aparecer o scheduler com interval=30s
```

---

## Task 7.7 — Atualizar `docs/deploy-mikrotik.md`

**Arquivo:** `/docs/deploy-mikrotik.md`  
**Ação:** ADICIONAR ao final do arquivo o seguinte conteúdo:

```markdown
---

## Passo 9 — Configurar Sincronização Pull (CGNAT)

> ✅ Este passo é **obrigatório** para roteadores atrás de NAT ou CGNAT.
> Opcional (mas recomendado) para roteadores com IP público.

### 9.1 — Cadastrar o Script de Sync

1. WinBox → System → Scripts → botão `[+]`
2. **Name:** `mega-hotspot-sync`
3. **Policy:** marcar `read`, `write`, `test`
4. **Source:** copiar o script da seção correspondente em `docs/sprints/SPRINT_7_nat_cgnat.md`
5. Substituir `SUBSTITUIR_PELO_TOKEN_UUID_DO_CLIENTE` pelo Token exibido no Super-Admin
6. Clicar **OK**

### 9.2 — Criar o Scheduler

Via Terminal:
```
/system scheduler add name=mega-hotspot-sync interval=30s on-event=mega-hotspot-sync policy=read,write,test
```

### 9.3 — Testar manualmente

No Terminal do WinBox:
```
/system script run mega-hotspot-sync
```

Verificar os logs:
```
/log print where topics~"info"
# Deve aparecer: "MegaSync: Nenhum usuário pendente." (se não houver leads)
# Ou: "MegaSync: Criado usuario 52998224725"
```
```

---

## Task 7.8 — Adicionar coluna `mikrotik_sync_status` ao Admin Dashboard

**Arquivo:** `admin/index.php`  
**Ação:** MODIFICAR — adicionar card de "Pendentes de Sync" e coluna na tabela

### 7.8.1 — Adicionar query de métricas no PHP (após as queries existentes):

```php
// Pendentes de sync MikroTik
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE estabelecimento_id = ? AND mikrotik_sync_status = 'pending'");
$stmt->execute([$idEstab]);
$stats['pendentes'] = $stmt->fetchColumn();
```

### 7.8.2 — Adicionar card HTML (após os 4 cards existentes):

```html
<div class="col-12 col-lg-3">
    <div class="card card-stat p-3 h-100 <?= $stats['pendentes'] > 0 ? 'border-start border-warning border-4' : '' ?>">
        <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-arrow-repeat"></i></div>
        <div class="small text-muted mb-1">Pendentes de Sync</div>
        <div class="h3 fw-bold mb-0 <?= $stats['pendentes'] > 0 ? 'text-warning' : 'text-success' ?>">
            <?= $stats['pendentes'] ?>
        </div>
        <small class="text-muted"><?= $stats['pendentes'] > 0 ? 'aguardando MikroTik' : 'tudo sincronizado' ?></small>
    </div>
</div>
```

### 7.8.3 — Adicionar coluna `sync_status` na tabela de leads:

No `<thead>`, após `Data Cadastro`:
```html
<th>Sync</th>
```

No JavaScript, na função de render, após a coluna de data, adicionar:
```javascript
// dentro do map de leads:
`<td>${getSyncBadge(lead.mikrotik_sync_status)}</td>`
```

E adicionar a função helper:
```javascript
function getSyncBadge(status) {
    if (status === 'synced')  return '<span class="badge bg-success">✓ Sync</span>';
    if (status === 'pending') return '<span class="badge bg-warning text-dark">⏳ Pendente</span>';
    return '<span class="badge bg-danger">✗ Erro</span>';
}
```

> **Nota:** Para que `leads.php` retorne o campo `mikrotik_sync_status`, adicionar a coluna no SELECT de `api/v1/leads.php`. Localizar a linha:
> ```php
> SELECT id, cpf, nome, whatsapp, email, mac_address, data_cadastro, data_atualizacao
> ```
> E adicionar `mikrotik_sync_status` após `data_atualizacao`.

---

## Critérios de Aceitação

- [ ] `migration_sprint7.sql` executado sem erros, tabela `leads` com as 3 novas colunas
- [ ] POST em `sync.php` com token válido → lead salvo com `mikrotik_sync_status = 'pending'`
- [ ] GET em `pending_users.php` com token válido → retorna JSON com leads pendentes
- [ ] POST em `confirm_sync.php` com lista de usernames → status muda para `synced`
- [ ] Script RouterOS executado manualmente → cria usuário em `/ip hotspot user` e confirma no servidor
- [ ] Scheduler a cada 30s → logs mostram "MegaSync" no `/log print`
- [ ] Novo lead cadastrado via portal → em até 30s aparece em `/ip hotspot user` no MikroTik
- [ ] Card "Pendentes de Sync" no admin: exibe 0 após a sincronização ocorrer
- [ ] Coluna "Sync" na tabela de leads mostra badge correto por status
- [ ] Se o MikroTik tiver IP público configurado → push direto funciona (status fica `synced` imediatamente)
- [ ] Se o IP do MikroTik estiver vazio/inacessível → lead fica `pending` sem erro no portal

---

## Comandos de Teste Rápido

```bash
# 1. Enviar lead de teste (simulando o portal captivo)
curl -i -X POST "https://api.megatecnologias.com/api/v1/sync.php" \
  -H "Content-Type: application/json" \
  -H "X-Auth-Token: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" \
  -d '{"nome":"Teste NAT","cpf":"529.982.247-25","whatsapp":"11988880000","mac":"AA:BB:CC:DD:EE:01","senha_hotspot":"0000"}'
# Esperado: {"status":"success","hotspot_user_created":false,"sync_mode":"pull_pending"}

# 2. Verificar pendentes
curl -H "X-Auth-Token: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" \
  "https://api.megatecnologias.com/api/v1/pending_users.php"
# Esperado: {"status":"success","total":1,"usuarios":[{"username":"52998224725",...}]}

# 3. Confirmar sync (simula o que o RouterOS faz)
curl -X POST "https://api.megatecnologias.com/api/v1/confirm_sync.php" \
  -H "Content-Type: application/json" \
  -H "X-Auth-Token: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" \
  -d '{"usernames":["52998224725"]}'
# Esperado: {"status":"success","synced":1}

# 4. Verificar no banco
mysql -u USER -p mega_hotspot -e \
  "SELECT cpf, mikrotik_sync_status, mikrotik_synced_at FROM leads WHERE cpf='529.982.247-25';"
# Esperado: status=synced, synced_at preenchido
```

---

## Notas Importantes para o Agente Executar

1. **RouterOS 7** suporta `:deserialize` nativamente. Não usar versões antigas do script que tentam parsear JSON manualmente.
2. **O scheduler só funciona** se o profile tiver as policies `read`, `write`, `test`. Sem `write`, não cria usuários.
3. **Token UUID** cadastrado no script RouterOS deve ser o mesmo exibido no Super-Admin (`superadmin/editar.php` → campo "Token do Cliente").
4. **A coluna `senha_hotspot` armazena apenas os 4 últimos dígitos do WhatsApp** (valor enviado pelo portal como `senha_hotspot`). Nunca armazena senhas completas.
5. **Fluxo hybrid:** O `sync.php` sempre tenta o push direto primeiro (compatibilidade retroativa). Somente se falhar, o lead fica como `pending` para o Scheduler buscar. Clientes com IP público funcionam exatamente como antes.
