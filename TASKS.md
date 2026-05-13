# 📋 Mega Hotspot SaaS — Task Tracker

> Metodologia: SDD (Specification-Driven Development)  
> Spec principal: `SPEC.md` | Sprints: `docs/sprints/`

---

## Sprint 1 — Banco de Dados [`docs/sprints/SPRINT_1_database.md`]
- [x] **1.1** Criar `sql/schema.sql` — incluir colunas de branding (`cor_primaria`, `cor_fundo1/2`, `boas_vindas`) e LGPD (`lgpd_nome_empresa`, `lgpd_cnpj`, `lgpd_email_dpo`, `lgpd_url_politica`, `lgpd_texto_consentimento`) — ver addendum
- [x] **1.2** Criar `sql/seed.sql` (1 superadmin + 1 estabelecimento + 1 lead de teste)
- [x] **1.3** Criar `scripts/setup_db.sh` (setup interativo com geração de hash bcrypt)

## Sprint 2 — API [`docs/sprints/SPRINT_2_api.md`]
- [x] **2.1** Criar `api/v1/router.php` (MikroTikRouter — REST API integration service)
- [x] **2.2** Expandir `api/v1/sync.php` (UPSERT + novos campos + chamada ao MikroTik)
- [x] **2.3** Criar `api/v1/leads.php` (listagem paginada, auth por sessão)
- [x] **2.4** Criar `logs/` com `.htaccess` e `.gitignore`

## Sprint 3 — Portal Captivo [`docs/sprints/SPRINT_3_captive_portal.md`] + [`SPRINT_3_ADDENDUM_branding_lgpd.md`]
- [x] **3.1** Reescrever `hotspot/login.html` (dual-mode + HOTSPOT_CONFIG expandido com branding + LGPD)
- [x] **3.2** Reescrever `hotspot/css/style.css` (dark mode + estilos LGPD + classes legadas preservadas)

## Sprint 4 — Super-Admin Panel [`docs/sprints/SPRINT_4_superadmin.md`]
- [x] **4.1** Criar `superadmin/auth.php`
- [x] **4.2** Criar `superadmin/login.php` + `api/v1/superlogin.php`
- [x] **4.3** Criar `superadmin/index.php` + `api/v1/estabelecimento_toggle.php`
- [x] **4.4** Criar `superadmin/novo.php` + `api/v1/estabelecimento_create.php` (com seções Identidade Visual + LGPD)
- [x] **4.5** Criar `superadmin/editar.php` + `api/v1/estabelecimento_update.php` + `api/v1/router_test.php`
- [x] **4.6** Criar `superadmin/logout.php`
- [x] **4.7** Criar `api/v1/gerar_pacote.php` (gera ZIP personalizado do hotspot para download)

## Sprint 5 — Documentação de Deploy [`docs/sprints/SPRINT_5_deploy.md`]
- [x] **5.1** Criar `docs/deploy-hostinger.md`
- [x] **5.2** Criar `docs/deploy-mikrotik.md`
- [x] **5.3** Criar `docs/novo-cliente.md`

## Sprint 6 — Melhorias do Painel Admin [`docs/sprints/SPRINT_6_admin_improvements.md`]
- [x] **6.1** Redesenhar `admin/index.php` (cards de métricas + tabela via fetch)
- [x] **6.2** Criar `api/v1/export.php` (CSV com BOM UTF-8)
- [x] **6.3** Atualizar este `TASKS.md` ao concluir

## Sprint 7 — Suporte NAT/CGNAT [`docs/sprints/SPRINT_7_nat_cgnat.md`]
- [x] **7.1** Criar `sql/migration_sprint7.sql` (ALTER TABLE leads — 3 novas colunas)
- [x] **7.2** Modificar `api/v1/sync.php` (salvar senha_hotspot + tentativa hybrid push/pull)
- [x] **7.3** Criar `api/v1/pending_users.php` (consultado pelo Scheduler do MikroTik)
- [x] **7.4** Criar `api/v1/confirm_sync.php` (MikroTik confirma criação dos usuários)
- [x] **7.5** Cadastrar Script RouterOS `mega-hotspot-sync` em cada MikroTik de cliente
- [x] **7.6** Criar Scheduler RouterOS (interval=30s)
- [x] **7.7** Atualizar `docs/deploy-mikrotik.md` com Passo 9 (Pull Sync)
- [x] **7.8** Atualizar `admin/index.php` (card Pendentes + coluna Sync status na tabela)

---

## Já prontos (não tocar)
- [x] `config/db.php`
- [x] `api/v1/login.php`
- [x] `admin/login.php`
- [x] `admin/auth.php`
- [x] `admin/logout.php`
- [x] `tests/mock_mikrotik.sh`
- [x] `hotspot/alogin.html`
- [x] `hotspot/status.html`
- [x] `hotspot/logout.html`
- [x] `hotspot/error.html`
- [x] `hotspot/md5.js`
- [x] `hotspot/img/`

---

## Ordem de execução recomendada

```
Sprint 1 → Sprint 2 → Sprint 3 → Sprint 4 → Sprint 5 → Sprint 6
```

Sprint 4 pode ser feita em paralelo com Sprint 3 (dependem apenas da Sprint 1).  
Sprint 6 pode ser feita em qualquer momento após Sprint 2.
