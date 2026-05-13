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
  `senha_hotspot`       VARCHAR(10) DEFAULT NULL COMMENT '4 últimos dígitos do WhatsApp',
  `mikrotik_sync_status` ENUM('pending','synced','error') NOT NULL DEFAULT 'pending' COMMENT 'pending=aguardando sync, synced=usuário criado no MikroTik, error=falha',
  `mikrotik_synced_at`  TIMESTAMP NULL DEFAULT NULL COMMENT 'Quando o MikroTik confirmou a criação do usuário',
  UNIQUE KEY `uq_lead_por_estabelecimento` (`estabelecimento_id`, `cpf`),
  INDEX `idx_sync_status` (`estabelecimento_id`, `mikrotik_sync_status`),
  FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
