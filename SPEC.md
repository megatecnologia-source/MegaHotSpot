# SPEC — Mega Hotspot SaaS
**Metodologia:** Specification-Driven Development (SDD)  
**Versão:** 1.0 — Congelada  
**Data:** 2026-05-12  

---

## 1. Visão Geral do Produto

Plataforma SaaS multi-tenant para **captura de leads via portal captivo Wi-Fi** com MikroTik Hotspot.

### Problema que resolve
Estabelecimentos comerciais (restaurantes, salões, clínicas) têm clientes conectando ao Wi-Fi sem capturar nenhum dado. Este sistema transforma cada conexão em um lead com nome, CPF, WhatsApp e e-mail — accessível via painel web com link direto para WhatsApp.

### Atores do sistema
| Ator | Descrição |
|---|---|
| **Usuário Final** | Cliente do estabelecimento que quer Wi-Fi grátis |
| **Dono do Estabelecimento** | Acessa o painel para ver seus leads |
| **Super Admin** | Você (Mega Tecnologias) — cadastra e gerencia estabelecimentos |

---

## 2. Stack Tecnológica

| Camada | Tecnologia |
|---|---|
| Linguagem | PHP 8.x (funcional/procedural limpo, sem frameworks) |
| Banco de dados | MySQL (InnoDB, utf8mb4) |
| Servidor | Hostinger Business Plan |
| Domínio API | https://api.megatecnologias.com |
| Domínio Painel | https://painel.megatecnologias.com |
| Router | MikroTik RouterOS 7.22.3 |
| Frontend | HTML + Vanilla CSS + Vanilla JS (sem frameworks, sem CDN externo) |

---

## 3. Arquitetura do Sistema

```
┌─────────────────────────────────────────────────────────┐
│                   MIKROTIK ROUTEROS 7                   │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Hotspot Server → serve /hotspot/*.html          │   │
│  │  REST API → :443/rest/ip/hotspot/user            │   │
│  └─────────────────────────────────────────────────┘   │
└───────────────────┬──────────────────────┬──────────────┘
                    │ browser do usuário    │ cURL do servidor
                    ▼                       ▼
┌─────────────────────────────────────────────────────────┐
│         HOSTINGER — api.megatecnologias.com             │
│                                                         │
│  /api/v1/sync.php    ← recebe lead do portal captivo   │
│  /api/v1/login.php   ← auth do painel admin            │
│  /api/v1/leads.php   ← listagem JSON de leads          │
│  /api/v1/export.php  ← exportação CSV                  │
│  /api/v1/router.php  ← service de integração MikroTik  │
│                                                         │
│  /admin/             ← painel dos clientes              │
│  /superadmin/        ← painel do super-admin (você)    │
│                                                         │
│  MySQL: banco mega_hotspot                              │
└─────────────────────────────────────────────────────────┘
                    ▲
                    │ login via browser
┌─────────────────────────────────────────────────────────┐
│     painel.megatecnologias.com                          │
│     Dono do estabelecimento + Super Admin               │
└─────────────────────────────────────────────────────────┘
```

---

## 4. Fluxo de Negócio Completo

### 4.1 Primeiro acesso do usuário final
```
1. Usuário conecta no Wi-Fi do estabelecimento
2. MikroTik intercepta qualquer HTTP e redireciona para login.html
3. login.html exibe aba "Primeiro Acesso"
4. Usuário preenche: Nome, CPF, WhatsApp (obrigatório), E-mail (opcional)
5. JavaScript no browser:
   a. Valida CPF (algoritmo dos dois dígitos verificadores)
   b. Valida WhatsApp (10-11 dígitos numéricos)
   c. Extrai senha = últimos 4 dígitos numéricos do WhatsApp
   d. POST para /api/v1/sync.php com o payload completo
6. sync.php:
   a. Valida X-Auth-Token → identifica estabelecimento
   b. UPSERT lead no MySQL (chave única: estabelecimento_id + cpf)
   c. Chama MikroTik REST API → cria/atualiza usuário (CPF / senha4)
   d. Retorna 201
7. JavaScript recebe 201 → submete form oculto → MikroTik autentica → Internet liberada
```

### 4.2 Retorno do usuário (mesmo celular)
```
1. MikroTik redireciona para login.html
2. Usuário clica aba "Já tenho cadastro"
3. Digita CPF + 4 dígitos (últimos do WhatsApp)
4. JavaScript submete form MikroTik diretamente (sem chamar nossa API)
5. MikroTik valida credenciais já salvas → Internet liberada
```

### 4.3 Re-cadastro (trocou de celular/WhatsApp)
```
1. Usuário usa aba "Primeiro Acesso" com o mesmo CPF
2. sync.php detecta CPF existente → UPDATE (atualiza whatsapp, senha)
3. MikroTik REST API atualiza a senha do usuário
4. Internet liberada com novas credenciais
```

---

## 5. Modelo de Dados — Schema Completo

### Tabela: `superadmins`
```sql
CREATE TABLE `superadmins` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `email`         VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `criado_em`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `estabelecimentos`
```sql
CREATE TABLE `estabelecimentos` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_token`     CHAR(36) NOT NULL UNIQUE COMMENT 'UUID v4 — usado como X-Auth-Token',
  `nome`              VARCHAR(255) NOT NULL,
  `logo_url`          VARCHAR(500) DEFAULT NULL,
  `email_login`       VARCHAR(255) NOT NULL UNIQUE,
  `password_hash`     VARCHAR(255) NOT NULL,
  -- MikroTik
  `mikrotik_ip`       VARCHAR(45) NOT NULL DEFAULT '',
  `mikrotik_port`     SMALLINT UNSIGNED NOT NULL DEFAULT 443,
  `mikrotik_api_user` VARCHAR(100) NOT NULL DEFAULT '',
  `mikrotik_api_pass` VARCHAR(255) NOT NULL DEFAULT '',
  `mikrotik_profile`  VARCHAR(100) NOT NULL DEFAULT 'hotspot-guest',
  -- Branding (portal captivo)
  `cor_primaria`      VARCHAR(7)   NOT NULL DEFAULT '#6C63FF' COMMENT 'HEX — botões, abas, links',
  `cor_secundaria`    VARCHAR(7)   NOT NULL DEFAULT '#4CAF50',
  `cor_fundo1`        VARCHAR(7)   NOT NULL DEFAULT '#0f0f1a' COMMENT 'Início do gradiente',
  `cor_fundo2`        VARCHAR(7)   NOT NULL DEFAULT '#1a1a3e' COMMENT 'Fim do gradiente',
  `boas_vindas`       VARCHAR(255) DEFAULT NULL,
  -- LGPD
  `lgpd_nome_empresa`        VARCHAR(255) DEFAULT NULL,
  `lgpd_cnpj`                VARCHAR(18)  DEFAULT NULL,
  `lgpd_email_dpo`           VARCHAR(255) DEFAULT NULL,
  `lgpd_url_politica`        VARCHAR(500) DEFAULT NULL,
  `lgpd_texto_consentimento` TEXT         DEFAULT NULL,
  -- Controle
  `ativo`             TINYINT(1) NOT NULL DEFAULT 1,
  `data_criacao`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `leads`
```sql
CREATE TABLE `leads` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `estabelecimento_id`  INT NOT NULL,
  `cpf`                 VARCHAR(14) NOT NULL COMMENT 'Formato: 999.999.999-99',
  `nome`                VARCHAR(255) NOT NULL,
  `whatsapp`            VARCHAR(20) NOT NULL,
  `email`               VARCHAR(255) DEFAULT NULL,
  `mac_address`         VARCHAR(17) DEFAULT NULL,
  `data_cadastro`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao`    TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_lead_por_estabelecimento` (`estabelecimento_id`, `cpf`),
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `usuarios_admin` (reserva futura)
```sql
CREATE TABLE `usuarios_admin` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `estabelecimento_id`  INT NOT NULL,
  `login`               VARCHAR(100) NOT NULL UNIQUE,
  `password_hash`       VARCHAR(255) NOT NULL,
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Contratos de API

### POST `/api/v1/sync.php`
**Auth:** Header `X-Auth-Token: {cliente_token}`  
**Content-Type:** `application/json`

**Request body:**
```json
{
  "nome":          "João Silva",
  "cpf":           "123.456.789-09",
  "whatsapp":      "11999990000",
  "email":         "joao@email.com",
  "mac":           "AA:BB:CC:DD:EE:FF",
  "senha_hotspot": "0000"
}
```

**Responses:**
| Código | Body | Quando |
|---|---|---|
| 201 | `{"status":"success","hotspot_user_created":true}` | Lead salvo + usuário criado no MikroTik |
| 201 | `{"status":"success","hotspot_user_created":false}` | Lead salvo, MikroTik falhou (não bloquear) |
| 400 | `{"status":"error","message":"..."}` | Campos obrigatórios ausentes |
| 401 | `{"status":"error","message":"Token ausente"}` | Sem header |
| 403 | `{"status":"error","message":"Token inválido"}` | Token não encontrado no banco |
| 405 | `{"status":"error","message":"Method Not Allowed"}` | Não é POST |
| 500 | `{"status":"error","message":"Erro interno"}` | Exceção PHP/PDO |

### POST `/api/v1/login.php`
**Auth:** Nenhuma (endpoint público)  
**Content-Type:** `application/json` ou `multipart/form-data`

**Request body:**
```json
{ "email": "admin@bar.com", "password": "minhasenha" }
```

**Responses:**
| Código | Body | Quando |
|---|---|---|
| 200 | `{"status":"success"}` | Login OK, sessão iniciada |
| 400 | `{"status":"error","message":"Preencha todos os campos."}` | Campos vazios |
| 401 | `{"status":"error","message":"E-mail ou senha incorretos."}` | Credenciais inválidas |

### GET `/api/v1/leads.php`
**Auth:** `$_SESSION['estabelecimento_id']` (sessão PHP ativa)  
**Query params:** `?page=1&per_page=50&busca=joao`

**Response 200:**
```json
{
  "total": 120,
  "pagina": 1,
  "por_pagina": 50,
  "leads": [
    {
      "id": 1,
      "cpf": "123.456.789-09",
      "nome": "João Silva",
      "whatsapp": "11999990000",
      "email": "joao@email.com",
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "data_cadastro": "2026-05-12T15:00:00",
      "data_atualizacao": null
    }
  ]
}
```

**Response 401:** `{"status":"error","message":"Não autenticado."}`

### GET `/api/v1/export.php`
**Auth:** `$_SESSION['estabelecimento_id']`  
**Response:** Arquivo CSV com header `Content-Disposition: attachment; filename="leads-YYYY-MM-DD.csv"`  
**Colunas CSV:** `Nome,CPF,WhatsApp,Email,Data Cadastro,Ultima Atualizacao`

---

## 7. Especificação do Portal Captivo (MikroTik)

### Variáveis disponíveis no template MikroTik (RouterOS 7)
```
$(mac)              — MAC address do dispositivo
$(ip)               — IP do dispositivo
$(username)         — username preenchido
$(link-login-only)  — URL de submit do login
$(link-orig)        — URL original requisitada
$(link-orig-esc)    — URL original URL-encoded
$(chap-id)          — ID do CHAP challenge
$(chap-challenge)   — CHAP challenge string
$(error)            — mensagem de erro do MikroTik
```

### Bloco de configuração por cliente — HOTSPOT_CONFIG (gerado automaticamente pelo super-admin)

> Este bloco é o **único trecho do `login.html` que muda por cliente**. É gerado pelo `api/v1/gerar_pacote.php` com os valores do banco. Ver spec completa em `docs/sprints/SPRINT_3_ADDENDUM_branding_lgpd.md`.

```html
<script>
const HOTSPOT_CONFIG = {
  // Integração API
  apiUrl : "https://api.megatecnologias.com/api/v1/sync.php",
  token  : "UUID-DO-ESTABELECIMENTO",

  // Identidade visual
  nome          : "Nome do Estabelecimento",
  logoUrl       : "https://api.megatecnologias.com/logos/nome-slug.png",
  corPrimaria   : "#6C63FF",  // botões, abas ativas, links, foco
  corSecundaria : "#4CAF50",
  corFundo1     : "#0f0f1a",  // início do gradiente de fundo
  corFundo2     : "#1a1a3e",  // fim do gradiente de fundo
  boasVindas    : "Bem-vindo! Conecte-se ao Wi-Fi grátis.",

  // LGPD (Lei 13.709/2018)
  lgpd: {
    nomeEmpresa : "Empresa Ltda.",
    cnpj        : "00.000.000/0001-00",
    emailDpo    : "privacidade@empresa.com.br",
    urlPolitica : "",  // vazio se não tiver página própria
    textoConsentimento: "Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) "
      + "por <strong>Empresa Ltda.</strong> para fins de comunicação comercial e "
      + "relacionamento, conforme a <strong>LGPD — Lei 13.709/2018</strong>.",
    textoRodape : "Dados protegidos. Não compartilhamos com terceiros sem autorização."
  }
};
</script>
```

### Pré-requisitos no MikroTik de cada cliente
```
1. REST API habilitada: /ip/service www-ssl port=443 enabled=yes
2. Usuário API: /user add name=api-megahotspot group=full password=SENHA
3. Walled garden: /ip/hotspot/walled-garden/ip add dst-host=api.megatecnologias.com action=accept
4. Profile criado: /ip/hotspot/user/profile add name=hotspot-guest rate-limit=5M/5M session-timeout=2h
5. Arquivos hotspot/ upados via FTP/WinBox
6. Hotspot profile apontando para a pasta: html-directory=hotspot login-by=http-chap
```

---

## 8. Regras de Codificação (Invariáveis)

1. **PDO obrigatório** com Prepared Statements em toda query SQL — zero concatenação
2. **Respostas JSON** com `Content-Type: application/json` e HTTP status correto
3. **Sessão segura**: `session_regenerate_id(true)` após qualquer login
4. **Isolamento multi-tenant**: toda query filtrada por `estabelecimento_id` da sessão
5. **Senhas**: sempre `password_hash($pass, PASSWORD_DEFAULT)` / `password_verify()`
6. **Frontend sem CDN**: todos os assets do portal captivo devem ser locais (o MikroTik pode não ter internet no boot)
7. **Graceful degradation na API do MikroTik**: falha de conectividade não deve impedir o salvamento do lead nem travar o portal
8. **Timeout MikroTik**: cURL para o MikroTik com `CURLOPT_TIMEOUT = 5` segundos
9. **LGPD**: checkbox de consentimento é OBRIGATÓRIO no portal captivo — não submeter o form sem ele marcado
10. **Cores**: sempre validar formato HEX (`#RRGGBB`) antes de salvar no banco — rejeitar valores inválidos

---

## 9. Estrutura de Arquivos Final

```
/HotspotLeads/
├── SPEC.md                          ← Este arquivo
├── CLAUDE.md                        ← Diretrizes originais
├── TASKS.md                         ← Tracking de progresso
│
├── docs/
│   └── sprints/
│       ├── SPRINT_1_database.md
│       ├── SPRINT_2_api.md
│       ├── SPRINT_3_captive_portal.md
│       ├── SPRINT_4_superadmin.md
│       ├── SPRINT_5_deploy.md
│       └── SPRINT_6_admin_improvements.md
│
├── hotspot/                         ← Upload para MikroTik
│   ├── login.html                   [SPRINT 3]
│   ├── css/style.css                [SPRINT 3]
│   ├── alogin.html, status.html,
│   │   logout.html, error.html,
│   │   md5.js, img/                 [MANTER - já prontos]
│
├── api/v1/
│   ├── sync.php                     [SPRINT 2 - expandir]
│   ├── login.php                    [PRONTO]
│   ├── leads.php                    [SPRINT 2 - criar]
│   ├── export.php                   [SPRINT 6 - criar]
│   └── router.php                   [SPRINT 2 - criar]
│
├── admin/
│   ├── index.php                    [SPRINT 6 - melhorar]
│   ├── login.php, auth.php,
│   │   logout.php                   [PRONTOS]
│
├── superadmin/
│   ├── login.php                    [SPRINT 4]
│   ├── auth.php                     [SPRINT 4]
│   ├── index.php                    [SPRINT 4]
│   ├── novo.php                     [SPRINT 4]
│   └── editar.php                   [SPRINT 4]
│
├── config/
│   └── db.php                       [PRONTO]
│
├── sql/
│   ├── schema.sql                   [SPRINT 1 - recriar]
│   └── seed.sql                     [SPRINT 1 - criar]
│
├── scripts/
│   └── setup_db.sh                  [SPRINT 1 - criar]
│
└── tests/
    └── mock_mikrotik.sh             [PRONTO]
```

---

## 10. Índice de Sprints

| Sprint | Arquivo | Descrição | Depende de |
|---|---|---|---|
| 1 | `SPRINT_1_database.md` | Schema SQL + seed + setup | — |
| 2 | `SPRINT_2_api.md` | router.php + sync.php + leads.php | Sprint 1 |
| 3 | `SPRINT_3_captive_portal.md` | login.html + CSS | Sprint 2 |
| 4 | `SPRINT_4_superadmin.md` | Painel super-admin | Sprint 1 |
| 5 | `SPRINT_5_deploy.md` | Docs de deploy Hostinger + MikroTik | Sprints 1-4 |
| 6 | `SPRINT_6_admin_improvements.md` | Melhorias painel admin | Sprint 2 |
