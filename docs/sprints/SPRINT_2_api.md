# SPRINT 2 — Camada de API
**Pré-requisito:** Sprint 1 concluída (banco com schema e seed)  
**Entrega:** `router.php` (novo) · `sync.php` (expandido) · `leads.php` (novo)  
**Spec de referência:** `SPEC.md` — Seção 6 (Contratos de API)

---

## Task 2.1 — Criar `api/v1/router.php`

**Arquivo:** `/api/v1/router.php`  
**Ação:** CRIAR  
**Propósito:** Service de comunicação com a REST API do MikroTik RouterOS 7. Nunca lança exceção — sempre retorna array estruturado.

**Interface pública:**
```php
class MikroTikRouter {
    public function __construct(string $ip, int $port, string $apiUser, string $apiPass)
    public function criarOuAtualizarUsuario(string $cpf, string $senha, string $profile): array
    // retorna: ['ok' => bool, 'message' => string, 'action' => 'created'|'updated'|'error']
}
```

**Implementação de `criarOuAtualizarUsuario`:**
1. Fazer GET `https://{ip}:{port}/rest/ip/hotspot/user?name={cpf}` com basic auth
2. Se resposta vazia (usuário não existe): fazer PUT `https://{ip}:{port}/rest/ip/hotspot/user` com body `{"name":"{cpf}","password":"{senha}","profile":"{profile}"}`
3. Se usuário existe (tem `.id` na resposta): fazer PATCH `https://{ip}:{port}/rest/ip/hotspot/user/{.id}` com body `{"password":"{senha}"}`
4. Em qualquer falha de rede ou timeout: retornar `['ok' => false, 'message' => 'Timeout ou erro de conexão', 'action' => 'error']`

**Configurações de cURL obrigatórias:**
```php
CURLOPT_TIMEOUT        => 5          // não pode travar o portal
CURLOPT_SSL_VERIFYPEER => false      // cert auto-assinado no MikroTik
CURLOPT_SSL_VERIFYHOST => false
CURLOPT_USERPWD        => "$apiUser:$apiPass"
CURLOPT_HTTPAUTH       => CURLAUTH_BASIC
CURLOPT_RETURNTRANSFER => true
CURLOPT_HTTPHEADER     => ['Content-Type: application/json']
```

**Logging de erros:**
- Em caso de falha, gravar em `logs/router_errors.log` (criar pasta `logs/` com `.gitignore` e `.htaccess deny all`)
- Formato de log: `[YYYY-MM-DD HH:MM:SS] IP:{ip} CPF:{cpf} ERROR:{mensagem}`

---

## Task 2.2 — Expandir `api/v1/sync.php`

**Arquivo:** `/api/v1/sync.php`  
**Ação:** MODIFICAR (manter a estrutura existente, adicionar lógica nova)

**Mudanças necessárias:**

### 2.2.1 — Novos campos no payload recebido
O endpoint agora aceita e exige `cpf` e `senha_hotspot`. Atualizar validação:
```php
$nome           = $input['nome'] ?? null;
$cpf            = $input['cpf'] ?? null;           // NOVO
$whatsapp       = $input['whatsapp'] ?? null;
$email          = $input['email'] ?? null;
$mac            = $input['mac'] ?? null;
$senhaHotspot   = $input['senha_hotspot'] ?? null; // NOVO

// Validação — obrigatórios:
if (empty($nome) || empty($cpf) || empty($whatsapp) || empty($senhaHotspot)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Campos obrigatórios ausentes: nome, cpf, whatsapp, senha_hotspot."]);
    exit;
}
```

### 2.2.2 — UPSERT (substituir o INSERT simples atual)
Substituir o INSERT simples por:
```sql
INSERT INTO leads
  (estabelecimento_id, cpf, nome, whatsapp, email, mac_address)
VALUES
  (:estabelecimento_id, :cpf, :nome, :whatsapp, :email, :mac_address)
ON DUPLICATE KEY UPDATE
  nome          = VALUES(nome),
  whatsapp      = VALUES(whatsapp),
  email         = VALUES(email),
  mac_address   = VALUES(mac_address)
```

### 2.2.3 — Buscar dados do MikroTik do estabelecimento
Após validar o token, buscar também os campos de MikroTik:
```sql
SELECT id, mikrotik_ip, mikrotik_port, mikrotik_api_user, mikrotik_api_pass, mikrotik_profile
FROM estabelecimentos
WHERE cliente_token = :token LIMIT 1
```

### 2.2.4 — Chamar o MikroTikRouter
```php
require_once __DIR__ . '/router.php';

$hotspotCreated = false;
if (!empty($estabelecimento['mikrotik_ip'])) {
    $router = new MikroTikRouter(
        $estabelecimento['mikrotik_ip'],
        (int) $estabelecimento['mikrotik_port'],
        $estabelecimento['mikrotik_api_user'],
        $estabelecimento['mikrotik_api_pass']
    );
    $result = $router->criarOuAtualizarUsuario(
        $cpf,
        $senhaHotspot,
        $estabelecimento['mikrotik_profile']
    );
    $hotspotCreated = $result['ok'];
}
```

### 2.2.5 — Resposta final atualizada
```php
http_response_code(201);
echo json_encode([
    "status" => "success",
    "hotspot_user_created" => $hotspotCreated
]);
```

### 2.2.6 — Remover debug em produção
Remover as linhas existentes:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

---

## Task 2.3 — Criar `api/v1/leads.php`

**Arquivo:** `/api/v1/leads.php`  
**Ação:** CRIAR  
**Auth:** Sessão PHP — verificar `$_SESSION['estabelecimento_id']`

**Implementação completa:**

```php
<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed."]);
    exit;
}

if (!isset($_SESSION['estabelecimento_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Não autenticado."]);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$estabelecimentoId = (int) $_SESSION['estabelecimento_id'];
$page     = max(1, (int) ($_GET['page'] ?? 1));
$perPage  = min(100, max(1, (int) ($_GET['per_page'] ?? 50)));
$busca    = trim($_GET['busca'] ?? '');
$offset   = ($page - 1) * $perPage;

try {
    $pdo = DB::getInstance();

    // Contar total
    $whereClause = "WHERE estabelecimento_id = :id";
    $params = [':id' => $estabelecimentoId];
    if ($busca !== '') {
        $whereClause .= " AND (nome LIKE :busca OR cpf LIKE :busca OR whatsapp LIKE :busca)";
        $params[':busca'] = "%$busca%";
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Buscar página
    $params[':limit']  = $perPage;
    $params[':offset'] = $offset;
    $stmt = $pdo->prepare("
        SELECT id, cpf, nome, whatsapp, email, mac_address,
               data_cadastro, data_atualizacao
        FROM leads $whereClause
        ORDER BY data_cadastro DESC
        LIMIT :limit OFFSET :offset
    ");
    // Bind int separado (PDO exige para LIMIT/OFFSET)
    $stmt->bindValue(':id', $estabelecimentoId, PDO::PARAM_INT);
    if ($busca !== '') {
        $stmt->bindValue(':busca', "%$busca%", PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $leads = $stmt->fetchAll();

    echo json_encode([
        "total"      => $total,
        "pagina"     => $page,
        "por_pagina" => $perPage,
        "leads"      => $leads
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erro interno."]);
}
```

---

## Task 2.4 — Criar pasta `logs/` com proteção

**Arquivos:** `/logs/.gitignore` e `/logs/.htaccess`

`.gitignore`:
```
*.log
```

`.htaccess`:
```
Deny from all
```

---

## Critérios de Aceitação

- [ ] `router.php` retorna `['ok' => true, 'action' => 'created']` ao criar usuário em MikroTik de teste
- [ ] `router.php` retorna `['ok' => true, 'action' => 'updated']` ao chamar com CPF já existente
- [ ] `router.php` retorna `['ok' => false, 'action' => 'error']` com timeout (testar com IP inválido)
- [ ] `sync.php` retorna `201` com `hotspot_user_created: true` quando MikroTik responde OK
- [ ] `sync.php` retorna `201` com `hotspot_user_created: false` quando MikroTik está inacessível
- [ ] `sync.php` retorna `400` quando `cpf` ou `senha_hotspot` estão ausentes
- [ ] Dois POSTs com mesmo token + mesmo CPF → segundo retorna `201` (UPSERT sem erro)
- [ ] `leads.php` retorna `401` sem sessão ativa
- [ ] `leads.php` retorna JSON paginado com leads apenas do estabelecimento logado
- [ ] `leads.php` com `?busca=joao` filtra por nome

## Comandos de Teste

```bash
# Testar sync.php com novo payload
curl -i -X POST "https://api.megatecnologias.com/api/v1/sync.php" \
  -H "Content-Type: application/json" \
  -H "X-Auth-Token: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" \
  -d '{
    "nome": "João Teste",
    "cpf": "529.982.247-25",
    "whatsapp": "11999990000",
    "email": "joao@teste.com",
    "mac": "AA:BB:CC:DD:EE:FF",
    "senha_hotspot": "0000"
  }'
# Esperado: HTTP 201, hotspot_user_created: true ou false

# Testar UPSERT (mesmo CPF, segunda vez)
# Mesmo comando acima → deve retornar 201 sem erro
```
