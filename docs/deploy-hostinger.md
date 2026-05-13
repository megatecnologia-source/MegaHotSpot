# Deploy na Hostinger Business Plan

Este guia detalha o processo de deploy da plataforma Mega Hotspot SaaS em ambiente Hostinger.

## Pré-requisitos
- Conta Hostinger Business ativa
- Domínio `megatecnologias.com` apontado para a Hostinger
- Acesso ao hPanel

---

## Passo 1 — Configurar PHP 8.x
1. hPanel → Hosting → Gerenciar → PHP Configuration
2. Selecionar PHP 8.1 ou superior (Recomendado: 8.2)
3. Extensões necessárias (verificar se estão ativas): `pdo_mysql`, `curl`, `json`, `mbstring`, `zip`.

---

## Passo 2 — Criar banco de dados MySQL
1. hPanel → Banco de Dados → MySQL
2. Criar banco: nome sugerido `u123456_megahotspot`
3. Criar usuário: `u123456_dbadmin` com senha forte
4. Vincular usuário ao banco com "Todos os privilégios"
5. Anotar: host (geralmente `localhost` ou `127.0.0.1`), nome do banco, usuário, senha.

---

## Passo 3 — Deploy Automático via Git (Recomendado)

Em vez de subir arquivos manualmente via FTP, utilize a integração nativa da Hostinger com o GitHub:

1.  **Acessar a ferramenta Git:** hPanel → Hospedagem → Gerenciar → Avançado → Git.
2.  **Configurar Repositório:**
    *   **Repository URL:** `https://github.com/megatecnologia-source/MegaHotSpot.git`
    *   **Branch:** `main`
    *   **Install Directory:** Deixe em branco para instalar na raiz (`public_html/`).
3.  **Configurar Webhook para Auto-Deploy:**
    *   Após configurar o repositório, clique em **"Auto Deployment"**.
    *   Copie a **Webhook URL** gerada pela Hostinger.
    *   Vá ao seu repositório no GitHub → Settings → Webhooks → Add webhook.
    *   Cole a URL, selecione `application/json` e escolha o evento `Just the push event`.
    *   Clique em **Add webhook**. Agora, cada `git push` na `main` atualizará o servidor automaticamente.

---

## Passo 4 — Upload Manual (Alternativa)
Estrutura no servidor (dentro de `public_html/`):
```
public_html/
├── admin/              ← Gerenciamento do estabelecimento
├── api/                ← Endpoints de sincronização e autenticação
├── config/             ← Configurações de banco
├── logs/               ← Logs de erro (protegidos)
├── sql/                ← Scripts de schema e seed
├── superadmin/         ← Gestão mestre da Mega Tecnologias
└── .htaccess           ← Redirecionamentos e segurança
```

**Atenção:** A pasta `hotspot/` NÃO vai para o servidor — ela é o pacote que será enviado para o roteador MikroTik do cliente.

---

## Passo 4 — Configurar credenciais do banco
Edite o arquivo `config/db.php` (ou crie se não existir) com os dados reais do hPanel:
```php
<?php
// config/db.php
class DB {
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            $host = '127.0.0.1';
            $db   = 'u123456_megahotspot'; // Nome do banco Hostinger
            $user = 'u123456_dbadmin';   // Usuário do banco Hostinger
            $pass = 'SUA_SENHA_FORTE';
            
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instance;
    }
}
```

---

## Passo 5 — Executar o Schema SQL
1. hPanel → Banco de Dados → phpMyAdmin
2. Selecionar o banco criado
3. Aba "Importar" → selecionar `sql/schema.sql` → Executar
4. Aba "Importar" → selecionar `sql/seed.sql` → Executar

---

## Passo 6 — Configurar subdomínios
Crie os subdomínios necessários no hPanel (Domínios → Subdomínios):
- `painel.megatecnologias.com` → aponta para `/public_html`
- `api.megatecnologias.com` → aponta para `/public_html`

Isso permite que você acesse o superadmin em `painel.megatecnologias.com/superadmin/` e a API em `api.megatecnologias.com/api/v1/sync.php`.

---

## Passo 7 — Ativar SSL (HTTPS)
1. hPanel → Segurança → SSL
2. Instalar certificados Let's Encrypt para todos os subdomínios criados.
3. **Importante:** A comunicação com a REST API do MikroTik exige HTTPS funcional no servidor.

---

## Passo 8 — Testar Instalação
Acesse `https://painel.megatecnologias.com/superadmin/login.php`
- Usuário: `admin@megatecnologias.com`
- Senha: `superadmin123` (conforme seed)
