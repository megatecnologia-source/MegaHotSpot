# SPRINT 1 — Banco de Dados
**Pré-requisito:** Nenhum  
**Entrega:** Schema SQL completo, seed de dados, script de setup  
**Spec de referência:** `SPEC.md` — Seção 5 (Modelo de Dados)

---

## Contexto
Esta sprint cria a fundação do banco de dados. O arquivo `sql/migrations.sql` e `sql/update_sprint3.sql` existentes serão **substituídos** por um único `schema.sql` idempotente e um `seed.sql` para dados de teste.

---

## Task 1.1 — Criar `sql/schema.sql`

**Arquivo:** `/sql/schema.sql`  
**Ação:** CRIAR (substituir os arquivos antigos)  
**Características:** Idempotente (pode ser reexecutado sem erro), usa `DROP TABLE IF EXISTS` na ordem correta para respeitar as foreign keys.

**Conteúdo exato esperado:**

```sql
-- Mega Hotspot SaaS — Schema completo
-- Executar com: mysql -u USER -p mega_hotspot < schema.sql

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `leads`;
DROP TABLE IF EXISTS `usuarios_admin`;
DROP TABLE IF EXISTS `estabelecimentos`;
DROP TABLE IF EXISTS `superadmins`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `superadmins` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `email`         VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `criado_em`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `estabelecimentos` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_token`     CHAR(36) NOT NULL UNIQUE COMMENT 'UUID v4',
  `nome`              VARCHAR(255) NOT NULL,
  `logo_url`          VARCHAR(500) DEFAULT NULL,
  `email_login`       VARCHAR(255) NOT NULL UNIQUE,
  `password_hash`     VARCHAR(255) NOT NULL,
  -- Integração MikroTik
  `mikrotik_ip`       VARCHAR(45)  NOT NULL DEFAULT '',
  `mikrotik_port`     SMALLINT UNSIGNED NOT NULL DEFAULT 443,
  `mikrotik_api_user` VARCHAR(100) NOT NULL DEFAULT '',
  `mikrotik_api_pass` VARCHAR(255) NOT NULL DEFAULT '',
  `mikrotik_profile`  VARCHAR(100) NOT NULL DEFAULT 'hotspot-guest',
  -- Branding do portal captivo
  `cor_primaria`      VARCHAR(7)   NOT NULL DEFAULT '#6C63FF' COMMENT 'HEX — botões, abas ativas',
  `cor_secundaria`    VARCHAR(7)   NOT NULL DEFAULT '#4CAF50' COMMENT 'HEX — acentos',
  `cor_fundo1`        VARCHAR(7)   NOT NULL DEFAULT '#0f0f1a' COMMENT 'HEX — início do gradiente',
  `cor_fundo2`        VARCHAR(7)   NOT NULL DEFAULT '#1a1a3e' COMMENT 'HEX — fim do gradiente',
  `boas_vindas`       VARCHAR(255) DEFAULT NULL COMMENT 'Mensagem de boas-vindas no portal',
  -- LGPD (Lei 13.709/2018)
  `lgpd_nome_empresa`        VARCHAR(255) DEFAULT NULL,
  `lgpd_cnpj`                VARCHAR(18)  DEFAULT NULL,
  `lgpd_email_dpo`           VARCHAR(255) DEFAULT NULL COMMENT 'E-mail do encarregado de dados',
  `lgpd_url_politica`        VARCHAR(500) DEFAULT NULL COMMENT 'URL da política de privacidade',
  `lgpd_texto_consentimento` TEXT         DEFAULT NULL COMMENT 'Texto exibido no checkbox',
  -- Controle
  `ativo`             TINYINT(1) NOT NULL DEFAULT 1,
  `data_criacao`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios_admin` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `estabelecimento_id`  INT NOT NULL,
  `login`               VARCHAR(100) NOT NULL UNIQUE,
  `password_hash`       VARCHAR(255) NOT NULL,
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leads` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `estabelecimento_id`  INT NOT NULL,
  `cpf`                 VARCHAR(14) NOT NULL COMMENT 'Formato: 999.999.999-99',
  `nome`                VARCHAR(255) NOT NULL,
  `whatsapp`            VARCHAR(20) NOT NULL,
  `email`               VARCHAR(255) DEFAULT NULL,
  `mac_address`         VARCHAR(17) DEFAULT NULL,
  `data_cadastro`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao`    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_lead_por_estabelecimento` (`estabelecimento_id`, `cpf`),
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Task 1.2 — Criar `sql/seed.sql`

**Arquivo:** `/sql/seed.sql`  
**Ação:** CRIAR  
**Propósito:** Dados de teste que permitem rodar `mock_mikrotik.sh` imediatamente após o setup.

**Conteúdo:**

```sql
-- Seed de dados de teste
-- Senha do superadmin: superadmin123
-- Senha do admin do estabelecimento: admin123

-- Superadmin
INSERT INTO `superadmins` (`email`, `password_hash`) VALUES (
  'admin@megatecnologias.com',
  '$2y$10$GERAR_HASH_DE_superadmin123'  -- substituir pelo hash real
);

-- Estabelecimento de teste (com branding e LGPD de exemplo)
INSERT INTO `estabelecimentos` (
  `cliente_token`, `nome`, `logo_url`, `email_login`, `password_hash`,
  `mikrotik_ip`, `mikrotik_port`, `mikrotik_api_user`, `mikrotik_api_pass`, `mikrotik_profile`,
  `cor_primaria`, `cor_secundaria`, `cor_fundo1`, `cor_fundo2`, `boas_vindas`,
  `lgpd_nome_empresa`, `lgpd_cnpj`, `lgpd_email_dpo`, `lgpd_url_politica`, `lgpd_texto_consentimento`
) VALUES (
  'd2a23ebf-8f8d-4a11-82f5-db981f3b0e12',
  'Bar do Teste',
  '',
  'barteste@megatecnologias.com',
  '$2y$10$GERAR_HASH_DE_admin123',
  '192.168.88.1', 443, 'api-megahotspot', 'senhateste', 'hotspot-guest',
  '#6C63FF', '#4CAF50', '#0f0f1a', '#1a1a3e',
  'Bem-vindo ao Bar do Teste! Wi-Fi grátis para você.',
  'Bar do Teste Ltda.', '00.000.000/0001-00', 'privacidade@bardoteste.com.br', '',
  'Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por Bar do Teste Ltda. para fins de comunicação comercial e relacionamento, conforme a LGPD — Lei 13.709/2018.'
);

-- Lead de exemplo
INSERT INTO `leads` (`estabelecimento_id`, `cpf`, `nome`, `whatsapp`, `email`, `mac_address`)
VALUES (1, '529.982.247-25', 'João Teste', '11999990000', 'joao@teste.com', 'AA:BB:CC:DD:EE:FF');
```

**IMPORTANTE para o agente executar:** Os hashes `password_hash` devem ser gerados com PHP antes de inserir:
```php
echo password_hash('superadmin123', PASSWORD_DEFAULT); // para superadmin
echo password_hash('admin123', PASSWORD_DEFAULT);       // para o estabelecimento
```
Substitua os placeholders `GERAR_HASH_DE_*` pelos valores reais gerados.

---

## Task 1.3 — Criar `scripts/setup_db.sh`

**Arquivo:** `/scripts/setup_db.sh`  
**Ação:** CRIAR  
**Permissão:** Deve ser executável (`chmod +x`)

**Comportamento:**
1. Pergunta interativamente: host MySQL, usuário, senha
2. Cria o banco `mega_hotspot` se não existir
3. Executa `sql/schema.sql`
4. Gera os hashes bcrypt das senhas padrão via PHP
5. Substitui os placeholders no seed e executa `sql/seed.sql`
6. Exibe confirmação com as tabelas criadas

**Lógica esperada:**
```bash
#!/bin/bash
echo "=== Setup Mega Hotspot DB ==="
read -p "Host MySQL [localhost]: " HOST
HOST=${HOST:-localhost}
read -p "Usuário MySQL: " DBUSER
read -sp "Senha MySQL: " DBPASS
echo ""

# Gerar hashes
HASH_SUPER=$(php -r "echo password_hash('superadmin123', PASSWORD_DEFAULT);")
HASH_ADMIN=$(php -r "echo password_hash('admin123', PASSWORD_DEFAULT);")

# Criar banco
mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" -e "CREATE DATABASE IF NOT EXISTS mega_hotspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Schema
mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" mega_hotspot < "$(dirname "$0")/../sql/schema.sql"

# Seed (com substituição dos hashes)
SEED=$(cat "$(dirname "$0")/../sql/seed.sql")
SEED="${SEED//GERAR_HASH_DE_superadmin123/$HASH_SUPER}"
SEED="${SEED//GERAR_HASH_DE_admin123/$HASH_ADMIN}"
echo "$SEED" | mysql -h "$HOST" -u "$DBUSER" -p"$DBPASS" mega_hotspot

echo "✅ Banco configurado com sucesso!"
echo "   Superadmin: admin@megatecnologias.com / superadmin123"
echo "   Admin test: barteste@megatecnologias.com / admin123"
echo "   Token test: d2a23ebf-8f8d-4a11-82f5-db981f3b0e12"
```

---

## Critérios de Aceitação

- [ ] `schema.sql` executa sem erros em banco vazio
- [ ] `schema.sql` reexecutado não gera erros (idempotência)
- [ ] `seed.sql` insere 1 superadmin, 1 estabelecimento com branding e LGPD, 1 lead
- [ ] `setup_db.sh` completa sem erros e exibe credenciais de teste
- [ ] `DESCRIBE leads` mostra coluna `cpf`
- [ ] `DESCRIBE estabelecimentos` mostra colunas `cor_primaria`, `lgpd_cnpj`, etc.
- [ ] Dois INSERTs com mesmo `(estabelecimento_id, cpf)` → erro de duplicate key (constraint OK)
- [ ] `mock_mikrotik.sh` retorna `201` após o setup

---

## Comandos de Verificação

```bash
# Rodar setup
bash scripts/setup_db.sh

# Verificar tabelas
mysql -u USER -p mega_hotspot -e "SHOW TABLES;"
# Esperado: estabelecimentos, leads, superadmins, usuarios_admin

# Verificar colunas de branding
mysql -u USER -p mega_hotspot -e "DESCRIBE estabelecimentos;"
# Deve mostrar: cor_primaria, cor_secundaria, cor_fundo1, cor_fundo2, boas_vindas
# Deve mostrar: lgpd_nome_empresa, lgpd_cnpj, lgpd_email_dpo, lgpd_url_politica, lgpd_texto_consentimento

# Verificar CPF no leads
mysql -u USER -p mega_hotspot -e "DESCRIBE leads;"
# Deve mostrar coluna cpf com unique key

# Verificar seed
mysql -u USER -p mega_hotspot -e "SELECT nome, cor_primaria, lgpd_cnpj FROM estabelecimentos;"
# Esperado: Bar do Teste | #6C63FF | 00.000.000/0001-00

# Testar API
bash tests/mock_mikrotik.sh
# Esperado: HTTP 201 (requer sync.php da Sprint 2 já atualizado)
```
