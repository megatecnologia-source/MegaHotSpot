CREATE TABLE `estabelecimentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cliente_token` CHAR(36) NOT NULL UNIQUE COMMENT 'UUID v4',
    `nome` VARCHAR(255) NOT NULL,
    `data_criacao` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios_admin` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `estabelecimento_id` INT NOT NULL,
    `login` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `leads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `estabelecimento_id` INT NOT NULL,
    `nome` VARCHAR(255) NOT NULL,
    `whatsapp` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `mac_address` VARCHAR(17) DEFAULT NULL,
    `data_cadastro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`estabelecimento_id`) REFERENCES `estabelecimentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
