ALTER TABLE `estabelecimentos` 
ADD COLUMN `email_login` VARCHAR(255) UNIQUE AFTER `nome`,
ADD COLUMN `password_hash` VARCHAR(255) AFTER `email_login`;
