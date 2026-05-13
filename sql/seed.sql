-- Seed de dados de teste
-- Senha do superadmin: superadmin123
-- Senha do admin do estabelecimento: admin123

-- Superadmin
INSERT INTO `superadmins` (`email`, `password_hash`) VALUES (
  'admin@megatecnologias.com',
  '$2y$12$PTfA0RGAjS6pAlX9gqOr8eEk2TiU2X1TnbYelyPrYBx/e9qE04vhy'
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
  '$2y$12$/gWGvqgnywWBpmfUckgtpua3HNbH6VwIc.ftdtrMH29MgiHna2Ixe',
  '192.168.88.1', 443, 'api-megahotspot', 'senhateste', 'hotspot-guest',
  '#6C63FF', '#4CAF50', '#0f0f1a', '#1a1a3e',
  'Bem-vindo ao Bar do Teste! Wi-Fi grátis para você.',
  'Bar do Teste Ltda.', '00.000.000/0001-00', 'privacidade@bardoteste.com.br', '',
  'Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por Bar do Teste Ltda. para fins de comunicação comercial e relacionamento, conforme a LGPD — Lei 13.709/2018.'
);

-- Lead de exemplo
INSERT INTO `leads` (`estabelecimento_id`, `cpf`, `nome`, `whatsapp`, `email`, `mac_address`, `senha_hotspot`, `mikrotik_sync_status`, `mikrotik_synced_at`)
VALUES (1, '529.982.247-25', 'João Teste', '11999990000', 'joao@teste.com', 'AA:BB:CC:DD:EE:FF', '0000', 'synced', CURRENT_TIMESTAMP);
