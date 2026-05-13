# SPRINT 4 — Super-Admin Panel
**Pré-requisito:** Sprint 1 (banco com tabela `superadmins` e `estabelecimentos`)  
**Entrega:** Pasta `/superadmin/` completa (login, auth, index, novo, editar)  
**URL:** `https://painel.megatecnologias.com/superadmin/`

---

## Contexto
Você (Mega Tecnologias) usa este painel para cadastrar e gerenciar estabelecimentos clientes. É protegido por credenciais separadas das do admin comum. Usa sessão `$_SESSION['superadmin_id']` isolada.

---

## Task 4.1 — `superadmin/auth.php`

Middleware de proteção. Incluído no topo de todas as páginas protegidas do superadmin.

```php
<?php
session_start();
if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}
```

---

## Task 4.2 — `superadmin/login.php`

**Comportamento:**
- Se `$_SESSION['superadmin_id']` existe → redirecionar para `index.php`
- Exibe formulário HTML com campos `email` e `password`
- Submit via fetch AJAX para `../api/v1/superlogin.php`
- Sucesso → redirecionar para `index.php`

**Endpoint de autenticação:** criar `api/v1/superlogin.php` (ver Task 4.2.1)

**Design:** mesmo estilo do `admin/login.php` mas com label "Super Admin — Mega Tecnologias" e fundo diferenciado (usar `--cor-primaria: #e05c00` via CSS inline para diferenciar visualmente).

### Task 4.2.1 — `api/v1/superlogin.php`

```php
<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(["status"=>"error"]); exit;
}
require_once __DIR__ . '/../../config/db.php';

$data  = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? $_POST['email'] ?? '';
$pass  = $data['password'] ?? $_POST['password'] ?? '';

if (empty($email) || empty($pass)) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"Preencha todos os campos."]);
    exit;
}

try {
    $pdo  = DB::getInstance();
    $stmt = $pdo->prepare("SELECT id, password_hash FROM superadmins WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $row  = $stmt->fetch();

    if ($row && password_verify($pass, $row['password_hash'])) {
        $_SESSION['superadmin_id'] = $row['id'];
        session_regenerate_id(true);
        echo json_encode(["status"=>"success"]);
    } else {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Credenciais inválidas."]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>"Erro interno."]);
}
```

---

## Task 4.3 — `superadmin/index.php`

**Inclui:** `auth.php` no topo.

**Dados carregados:**
```sql
SELECT e.id, e.nome, e.email_login, e.mikrotik_ip, e.ativo, e.data_criacao,
       COUNT(l.id) AS total_leads
FROM estabelecimentos e
LEFT JOIN leads l ON l.estabelecimento_id = e.id
GROUP BY e.id
ORDER BY e.data_criacao DESC
```

**Interface HTML (Bootstrap 5 via CDN é permitido aqui — painel interno, não precisa funcionar offline):**

Elementos da página:
- Navbar: "Mega Hotspot — Super Admin" com link "Sair"
- Botão "+ Novo Cliente" → leva para `novo.php`
- Tabela com colunas: Nome · E-mail · IP MikroTik · Leads · Ativo · Cadastro · Ações
- Coluna "Ativo": badge verde/vermelho (Sim/Não)
- Coluna "Ações": botões `[Editar]` (→ `editar.php?id=X`) e `[Toggle Ativo]` via AJAX

**Toggle Ativo (AJAX inline):**
```javascript
async function toggleAtivo(id, btnEl) {
    const res = await fetch('../api/v1/estabelecimento_toggle.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id})
    });
    const data = await res.json();
    if (data.status === 'success') location.reload();
}
```

### Task 4.3.1 — `api/v1/estabelecimento_toggle.php`

Endpoint POST autenticado por `$_SESSION['superadmin_id']`:
```sql
UPDATE estabelecimentos SET ativo = NOT ativo WHERE id = :id
```
Retorna `{"status":"success","ativo": bool}`.

---

## Task 4.4 — `superadmin/novo.php`

**Inclui:** `auth.php` no topo.

**Formulário com campos (dividido em 3 seções):**

**Seção 1 — Dados de Acesso**
| Campo | Input | Validação |
|---|---|---|
| Nome do estabelecimento | text | obrigatório |
| E-mail de acesso ao painel | email | obrigatório, único |
| Senha inicial | password | obrigatório, mín. 8 chars |
| Confirmar senha | password | igual ao anterior |

**Seção 2 — Integração MikroTik**
| Campo | Input | Default |
|---|---|---|
| IP do MikroTik | text | — |
| Porta REST | number | 443 |
| Usuário API MikroTik | text | — |
| Senha API MikroTik | password | — |
| Profile hotspot | text | hotspot-guest |

**Seção 3 — Identidade Visual (Branding)**
| Campo | Input | Default |
|---|---|---|
| Logo URL | url | vazio |
| Cor primária (botões, links) | `<input type="color">` | #6C63FF |
| Cor secundária (acentos) | `<input type="color">` | #4CAF50 |
| Cor fundo início (gradiente) | `<input type="color">` | #0f0f1a |
| Cor fundo fim (gradiente) | `<input type="color">` | #1a1a3e |
| Mensagem de boas-vindas | text | vazio |

> Ao alterar as cores, mostrar um **preview ao vivo** do botão CTA e do gradiente de fundo usando um `<div>` de demonstração que atualiza via JS `oninput`.

**Seção 4 — LGPD / Privacidade**
| Campo | Input | Obrigatório |
|---|---|---|
| Nome jurídico da empresa | text | sim |
| CNPJ | text (máscara `99.999.999/9999-99`) | sim |
| E-mail do responsável pelos dados (DPO) | email | sim |
| URL da Política de Privacidade | url | não |
| Texto de consentimento | `<textarea rows=4>` | sim (tem default) |

**Default do texto de consentimento** (pré-preencher com JS quando o campo "Nome jurídico" for preenchido):
```
Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por [NOME_EMPRESA]
para fins de comunicação comercial e relacionamento, conforme a
Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018).
```

**Submit via fetch para `../api/v1/estabelecimento_create.php`**

### Task 4.4.1 — `api/v1/estabelecimento_create.php`

Endpoint POST, autenticado por `$_SESSION['superadmin_id']`:

```php
// Gerar UUID v4
function uuid4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Validar HEX (Regra de codificação #10 do SPEC.md)
function validarHex(string $cor): string {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : '#6C63FF';
}

// INSERT com todos os campos
INSERT INTO estabelecimentos (
  cliente_token, nome, logo_url, email_login, password_hash,
  mikrotik_ip, mikrotik_port, mikrotik_api_user, mikrotik_api_pass, mikrotik_profile,
  cor_primaria, cor_secundaria, cor_fundo1, cor_fundo2, boas_vindas,
  lgpd_nome_empresa, lgpd_cnpj, lgpd_email_dpo, lgpd_url_politica, lgpd_texto_consentimento
) VALUES (
  :token, :nome, :logo_url, :email, :hash,
  :ip, :port, :api_user, :api_pass, :profile,
  :cor1, :cor2, :fundo1, :fundo2, :boas_vindas,
  :lgpd_nome, :lgpd_cnpj, :lgpd_dpo, :lgpd_url, :lgpd_texto
)
```

Retorna `{"status":"success","token":"UUID-GERADO","id":123}`.

Após criação, exibir no front:
```
✅ Estabelecimento criado!
Token API: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12
          [Copiar]
Próximo passo: configure o HOTSPOT_CONFIG no login.html e faça o deploy no MikroTik.
```

---

## Task 4.5 — `superadmin/editar.php`

**Inclui:** `auth.php`. Recebe `?id=X` via GET.

- Carrega dados do estabelecimento pelo ID
- Mesmo formulário do `novo.php`, pré-populado
- Campos de senha: mostrar vazio (não exibir hash). Se deixado em branco ao salvar → não altera a senha
- Botão "Testar Conexão MikroTik" → fetch para `../api/v1/router_test.php?id=X` → exibe resultado

### Task 4.5.1 — `api/v1/router_test.php`

Endpoint GET, autenticado por `$_SESSION['superadmin_id']`:
- Busca dados do estabelecimento pelo `?id=`
- Instancia `MikroTikRouter`
- Tenta GET `https://{ip}:{port}/rest/ip/hotspot/user` (listar usuários)
- Se resposta HTTP 200 → `{"status":"success","message":"Conexão OK"}`
- Se falha → `{"status":"error","message":"Não foi possível conectar: {detalhe}"}`

### Task 4.5.2 — `api/v1/estabelecimento_update.php`

Endpoint POST, autenticado por `$_SESSION['superadmin_id']`:
```sql
UPDATE estabelecimentos SET
  nome = :nome, logo_url = :logo_url, email_login = :email,
  mikrotik_ip = :ip, mikrotik_port = :port,
  mikrotik_api_user = :api_user, mikrotik_api_pass = :api_pass,
  mikrotik_profile = :profile,
  cor_primaria = :cor1, cor_secundaria = :cor2,
  cor_fundo1 = :fundo1, cor_fundo2 = :fundo2,
  boas_vindas = :boas_vindas,
  lgpd_nome_empresa = :lgpd_nome, lgpd_cnpj = :lgpd_cnpj,
  lgpd_email_dpo = :lgpd_dpo, lgpd_url_politica = :lgpd_url,
  lgpd_texto_consentimento = :lgpd_texto
  -- password_hash só atualiza se nova senha foi fornecida
WHERE id = :id
```

Validar todas as cores HEX com `validarHex()` antes de salvar (mesma função do `estabelecimento_create.php`).

---

## Task 4.6 — `superadmin/logout.php`

```php
<?php
session_start();
session_destroy();
header("Location: login.php");
exit;
```

---

## Task 4.7 — `api/v1/gerar_pacote.php`

**Arquivo:** `/api/v1/gerar_pacote.php`  
**Auth:** `$_SESSION['superadmin_id']`  
**Método:** GET `?id=X`  
**Resposta:** Download de arquivo ZIP

**Propósito:** Gera um pacote ZIP com os arquivos do portal captivo já personalizados para o cliente. Substitui o bloco `HOTSPOT_CONFIG` no `login.html` com os valores do banco.

**No `superadmin/editar.php`:** adicionar botão:
```html
<a href="../api/v1/gerar_pacote.php?id=<?= $id ?>" class="btn btn-success">
  📦 Download Pacote MikroTik
</a>
```

**Lógica do endpoint:**

1. Buscar todos os dados do estabelecimento pelo `?id=`
2. Montar o bloco `HOTSPOT_CONFIG` com os valores do banco:
```php
function gerarConfigBlock(array $est): string {
    $lgpdTexto = $est['lgpd_texto_consentimento']
        ?? 'Autorizo o uso dos meus dados conforme a LGPD — Lei 13.709/2018.';
    return <<<JS
const HOTSPOT_CONFIG = {
  apiUrl : "https://api.megatecnologias.com/api/v1/sync.php",
  token  : "{$est['cliente_token']}",
  nome          : "{$est['nome']}",
  logoUrl       : "{$est['logo_url']}",
  corPrimaria   : "{$est['cor_primaria']}",
  corSecundaria : "{$est['cor_secundaria']}",
  corFundo1     : "{$est['cor_fundo1']}",
  corFundo2     : "{$est['cor_fundo2']}",
  boasVindas    : "{$est['boas_vindas']}",
  lgpd: {
    nomeEmpresa : "{$est['lgpd_nome_empresa']}",
    cnpj        : "{$est['lgpd_cnpj']}",
    emailDpo    : "{$est['lgpd_email_dpo']}",
    urlPolitica : "{$est['lgpd_url_politica']}",
    textoConsentimento: "{$lgpdTexto}",
    textoRodape : "Dados protegidos. Não compartilhamos com terceiros sem autorização."
  }
};
JS;
}
```

3. Ler `hotspot/login.html` (template base do projeto)
4. Substituir o bloco `HOTSPOT_CONFIG` existente pelo gerado:
```php
$template = file_get_contents(__DIR__ . '/../../hotspot/login.html');
$configBlock = 'const HOTSPOT_CONFIG = {' . "\n" . gerarConfigBlock($est);
$loginPersonalizado = preg_replace(
    '/const HOTSPOT_CONFIG\s*=\s*\{.*?\};/s',
    gerarConfigBlock($est),
    $template
);
```

5. Criar ZIP em memória com a extensão `ZipArchive` (disponível na Hostinger):
```php
$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'hotspot_');
$zip->open($tmpFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
$zip->addFromString('hotspot/login.html', $loginPersonalizado);
// Arquivos fixos (não mudam por cliente)
$fixos = [
    'css/style.css', 'alogin.html', 'status.html',
    'logout.html', 'error.html', 'md5.js',
    'img/user.svg', 'img/password.svg'
];
foreach ($fixos as $f) {
    $fullPath = __DIR__ . '/../../hotspot/' . $f;
    if (file_exists($fullPath)) {
        $zip->addFile($fullPath, 'hotspot/' . $f);
    }
}
$zip->close();
```

6. Servir o ZIP para download e apagar o arquivo temp:
```php
$slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($est['nome']));
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="hotspot-' . $slug . '.zip"');
header('Content-Length: ' . filesize($tmpFile));
readfile($tmpFile);
unlink($tmpFile);
```

---

## Critérios de Aceitação

- [ ] Acessar `/superadmin/index.php` sem sessão → redireciona para `login.php`
- [ ] Login com `admin@megatecnologias.com` / `superadmin123` → entra no painel
- [ ] Painel lista o estabelecimento do seed com total de leads correto
- [ ] "+ Novo Cliente" → formulário com 4 seções (Acesso, MikroTik, Branding, LGPD)
- [ ] Preview ao vivo das cores funciona ao alterar os `input[type=color]`
- [ ] Salvar → exibe token UUID gerado + botão copiar
- [ ] "Editar" → carrega dados pré-populados incluindo cores e LGPD → salva → reflete na listagem
- [ ] Cores HEX inválidas são rejeitadas ou normalizadas para o default
- [ ] "Toggle Ativo" → alterna status → badge atualiza após reload
- [ ] "Testar Conexão" com IP fake → mensagem de erro (sem crash)
- [ ] "Download Pacote MikroTik" → baixa ZIP com `hotspot/login.html` personalizado
- [ ] ZIP contém todos os arquivos: `login.html`, `css/style.css`, `alogin.html`, `status.html`, `logout.html`, `error.html`, `md5.js`, `img/`
- [ ] Abrir `login.html` do ZIP no browser → cores do cliente aplicadas via CSS vars
- [ ] Abrir `login.html` do ZIP no browser → texto de consentimento LGPD do cliente exibido
- [ ] Checkbox LGPD não marcado → botão "Conectar" não submete (validação JS)
- [ ] Logout destrói sessão e redireciona para login

