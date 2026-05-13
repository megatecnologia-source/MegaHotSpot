# Dicionário do Banco de Dados

O banco de dados `mega_hotspot` utiliza a engine **InnoDB** e codificação **utf8mb4_unicode_ci**.

## 🏗️ Esquema de Tabelas

### 1. `estabelecimentos`
Armazena os dados dos clientes SaaS e suas configurações de integração.

| Coluna | Tipo | Descrição |
| :--- | :--- | :--- |
| `id` | INT (PK) | Identificador único interno. |
| `cliente_token` | CHAR(36) | UUID v4 usado para autenticação da API. |
| `nome` | VARCHAR(255) | Nome fantasia do estabelecimento. |
| `email_login` | VARCHAR(255) | E-mail usado para acessar o painel admin. |
| `password_hash` | VARCHAR(255) | Senha criptografada (Bcrypt). |
| `mikrotik_ip` | VARCHAR(45) | IP ou DDNS do roteador MikroTik. |
| `cor_primaria` | VARCHAR(7) | Cor dos botões no portal captivo (HEX). |
| `ativo` | TINYINT(1) | Status do contrato (1=Ativo, 0=Bloqueado). |

### 2. `leads`
Contém os dados capturados dos usuários finais.

| Coluna | Tipo | Descrição |
| :--- | :--- | :--- |
| `id` | INT (PK) | Identificador único. |
| `estabelecimento_id` | INT (FK) | Relacionamento com a tabela estabelecimentos. |
| `cpf` | VARCHAR(14) | CPF do usuário (formatado). |
| `nome` | VARCHAR(255) | Nome completo do lead. |
| `whatsapp` | VARCHAR(20) | WhatsApp (utilizado para gerar a senha). |
| `mikrotik_sync_status`| ENUM | Status da sincronização (`pending`, `synced`, `error`). |

### 3. `superadmins`
Contas de acesso total à plataforma (Mega Tecnologias).

---

## 🔒 Relacionamentos e Integridade

*   **Isolamento:** A coluna `estabelecimento_id` é obrigatória em quase todas as tabelas de dados.
*   **Unique Keys:** A tabela `leads` possui uma chave única composta `(estabelecimento_id, cpf)`, impedindo que o mesmo usuário tenha dois cadastros no mesmo estabelecimento, mas permitindo que ele se cadastre em estabelecimentos diferentes.
*   **Cascata:** Ao deletar um estabelecimento (via Super Admin), todos os leads vinculados são removidos automaticamente via `ON DELETE CASCADE`.
