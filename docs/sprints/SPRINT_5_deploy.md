# SPRINT 5 — Documentação de Deploy
**Pré-requisito:** Sprints 1–4 concluídas  
**Entrega:** 3 guias em `docs/`: deploy Hostinger · deploy MikroTik · onboarding novo cliente

---

## Task 5.1 — `docs/deploy-hostinger.md`

```markdown
# Deploy na Hostinger Business Plan

## Pré-requisitos
- Conta Hostinger Business ativa
- Domínio `megatecnologias.com` apontado para a Hostinger
- Acesso ao hPanel

---

## Passo 1 — Configurar PHP 8.x
1. hPanel → Hosting → Gerenciar → PHP Configuration
2. Selecionar PHP 8.1 ou superior
3. Extensões necessárias (verificar se estão ativas): `pdo_mysql`, `curl`, `json`, `mbstring`

---

## Passo 2 — Criar banco de dados MySQL
1. hPanel → Banco de Dados → MySQL
2. Criar banco: nome sugerido `u123456_megahotspot`
3. Criar usuário: `u123456_dbadmin` com senha forte
4. Vincular usuário ao banco com "Todos os privilégios"
5. Anotar: host (geralmente `localhost`), nome do banco, usuário, senha

---

## Passo 3 — Upload dos arquivos via FTP

Estrutura no servidor (dentro de `public_html/`):
```
public_html/
├── api/                ← pasta api/ do projeto
├── admin/              ← pasta admin/ do projeto
├── superadmin/         ← pasta superadmin/ do projeto
└── config/             ← pasta config/ do projeto
```

Credenciais FTP: hPanel → FTP Accounts

**Atenção:** A pasta `hotspot/` NÃO vai para o servidor — ela vai para o MikroTik.

---

## Passo 4 — Configurar credenciais do banco

Editar `config/db.php` com os dados reais:
```php
$host = '127.0.0.1';
$db   = 'u123456_megahotspot';
$user = 'u123456_dbadmin';
$pass = 'SUA_SENHA_AQUI';
```

Ou usar variáveis de ambiente via `.htaccess` na raiz de `public_html/`:
```apache
SetEnv DB_HOST 127.0.0.1
SetEnv DB_NAME u123456_megahotspot
SetEnv DB_USER u123456_dbadmin
SetEnv DB_PASS SUA_SENHA_AQUI
```

---

## Passo 5 — Executar o Schema SQL

1. hPanel → Banco de Dados → phpMyAdmin
2. Selecionar o banco criado
3. Aba "Importar" → selecionar `sql/schema.sql` → Executar
4. Aba "Importar" → selecionar `sql/seed.sql` → Executar

**Verificar:** Aba "Estrutura" deve mostrar 4 tabelas: `superadmins`, `estabelecimentos`, `leads`, `usuarios_admin`

---

## Passo 6 — Configurar subdomínio `painel`

1. hPanel → Domínios → Subdomínios
2. Criar subdomínio: `painel.megatecnologias.com`
3. Apontar para `/public_html/` (mesma raiz)
4. Aguardar propagação DNS (até 30 min)

O subdomínio `api.megatecnologias.com` aponta para a mesma pasta raiz — os arquivos em `/public_html/api/` ficam acessíveis como `api.megatecnologias.com/api/v1/...`

---

## Passo 7 — Proteger pasta de logs

Verificar se `/public_html/logs/.htaccess` existe com `Deny from all`. Se não existir:
1. hPanel → File Manager → navegar até `public_html/logs/`
2. Criar arquivo `.htaccess` com conteúdo: `Deny from all`

---

## Passo 8 — Verificar SSL (HTTPS)

1. hPanel → SSL → Let's Encrypt
2. Ativar para `megatecnologias.com`, `www.megatecnologias.com`, `painel.megatecnologias.com`, `api.megatecnologias.com`
3. Aguardar emissão (até 10 min)

---

## Passo 9 — Testar

```bash
# Testar API de sync (substituir TOKEN pelo seed)
curl -i -X POST "https://api.megatecnologias.com/api/v1/sync.php" \
  -H "Content-Type: application/json" \
  -H "X-Auth-Token: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12" \
  -d '{"nome":"Teste Deploy","cpf":"529.982.247-25","whatsapp":"11999990000","mac":"00:00:00:00:00:01","senha_hotspot":"0000"}'
# Esperado: HTTP 201

# Testar painel admin
# Acessar: https://painel.megatecnologias.com/admin/login.php
# Login: barteste@megatecnologias.com / admin123

# Testar super-admin
# Acessar: https://painel.megatecnologias.com/superadmin/login.php
# Login: admin@megatecnologias.com / superadmin123
```
```

---

## Task 5.2 — `docs/deploy-mikrotik.md`

```markdown
# Configuração do MikroTik — RouterOS 7.22.3

Execute cada bloco via Terminal SSH ou WinBox → Terminal.

---

## Passo 1 — Upload dos arquivos do portal captivo

Via WinBox:
1. Conectar ao MikroTik com WinBox
2. Menu "Files" → arrastar toda a pasta `hotspot/` do projeto para o painel
3. Confirmar que existe `/hotspot/login.html` nos arquivos do router

Via SCP (SSH):
```bash
scp -r ./hotspot/ admin@IP_DO_MIKROTIK:/
```

---

## Passo 2 — Criar profile para usuários do hotspot

```routeros
/ip hotspot user profile
add name=hotspot-guest rate-limit=5M/5M shared-users=1 session-timeout=2h
```

---

## Passo 3 — Habilitar REST API

```routeros
/ip service
set www-ssl disabled=no port=443
```

Verificar certificado (RouterOS gera um auto-assinado por padrão):
```routeros
/certificate print
```

---

## Passo 4 — Criar usuário da API (para nosso servidor chamar)

```routeros
/user
add name=api-megahotspot password=SENHA_FORTE_AQUI group=full
```

⚠️ Guardar a senha — ela será cadastrada no super-admin panel.

---

## Passo 5 — Configurar Walled Garden (liberar nossa API antes do login)

```routeros
/ip hotspot walled-garden ip
add dst-host=api.megatecnologias.com action=accept comment="Mega Hotspot API"
```

---

## Passo 6 — Apontar hotspot para os arquivos customizados

```routeros
/ip hotspot profile
set [find] html-directory=hotspot login-by=http-chap
```

---

## Passo 7 — Anotar IP público do MikroTik

```routeros
/ip address print
```

O IP WAN é o que deve ser cadastrado no super-admin como "IP do MikroTik".  
Se o router estiver atrás de NAT, configurar port-forward da porta 443 para o MikroTik.

---

## Passo 8 — Testar do celular

1. Conectar celular no Wi-Fi do MikroTik
2. Abrir qualquer site HTTP (ex: `http://neverssl.com`)
3. Deve redirecionar para o portal captivo customizado
4. Preencher o formulário → internet deve liberar em até 5 segundos
5. Verificar no painel admin se o lead apareceu
```

---

## Task 5.3 — `docs/novo-cliente.md`

```markdown
# Checklist — Onboarding de Novo Cliente

## Dados necessários do cliente
- [ ] Nome do estabelecimento
- [ ] E-mail para acesso ao painel
- [ ] Logo em PNG/JPG (fundo transparente ideal)
- [ ] IP público do MikroTik (ou informa após configuração)
- [ ] Modelo e acesso ao MikroTik (WinBox ou SSH)

---

## Passo 1 — Cadastrar no Super-Admin

1. Acessar `https://painel.megatecnologias.com/superadmin/`
2. Clicar "+ Novo Cliente"
3. Preencher todos os campos (IP MikroTik pode ser preenchido depois)
4. **Anotar o Token UUID gerado** — ele identifica o cliente na API

---

## Passo 2 — Hospedar a logo

1. Fazer upload da logo em `public_html/logos/nome-slug.png` via FTP
2. URL pública: `https://api.megatecnologias.com/logos/nome-slug.png`

---

## Passo 3 — Personalizar o `hotspot/login.html`

Editar o bloco `HOTSPOT_CONFIG` no `login.html`:

```javascript
const HOTSPOT_CONFIG = {
  apiUrl:  "https://api.megatecnologias.com/api/v1/sync.php",
  token:   "UUID-GERADO-NO-PASSO-1",
  nome:    "Nome do Estabelecimento",
  logoUrl: "https://api.megatecnologias.com/logos/nome-slug.png"
};
```

---

## Passo 4 — Configurar o MikroTik

Seguir o guia completo em `docs/deploy-mikrotik.md`.

Dados do usuário API criado no MikroTik → voltar ao super-admin e preencher:
- IP do MikroTik
- Usuário API (`api-megahotspot`)
- Senha API

---

## Passo 5 — Testar o fluxo completo

1. [ ] Celular conectado ao Wi-Fi do cliente → portal abre
2. [ ] Preencher formulário → internet libera
3. [ ] Lead aparece no painel `painel.megatecnologias.com/admin/`
4. [ ] Aba "Já tenho cadastro" + CPF + 4 dígitos → reconecta sem cadastro

---

## Passo 6 — Entregar acesso ao cliente

- URL do painel: `https://painel.megatecnologias.com/admin/login.php`
- E-mail: o cadastrado no passo 1
- Senha: a definida no passo 1 (orientar a trocar no primeiro acesso)
```

---

## Critérios de Aceitação desta Sprint

- [ ] Seguir o `deploy-hostinger.md` do zero resulta no sistema funcionando
- [ ] Seguir o `deploy-mikrotik.md` do zero resulta no portal captivo aparecendo no celular
- [ ] Seguir o `novo-cliente.md` onboarda um novo estabelecimento em menos de 30 minutos
- [ ] Todos os comandos RouterOS foram testados em RouterOS 7.22.x
