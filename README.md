# MegaHotSpot SaaS

Plataforma SaaS multi-tenant para captura de leads via portal captivo Wi-Fi com MikroTik Hotspot.

## 🚀 Visão Geral

O MegaHotSpot transforma a rede Wi-Fi de estabelecimentos comerciais em uma ferramenta de marketing. Em vez de conexões anônimas, o sistema captura dados valiosos (Nome, CPF, WhatsApp, E-mail) dos usuários, permitindo que o dono do negócio crie uma base de leads qualificada.

### Principais Funcionalidades

*   **Captura de Leads:** Formulário customizado com validação de CPF e WhatsApp.
*   **Multi-tenant:** Isolamento de dados por estabelecimento usando tokens UUID únicos.
*   **Integração MikroTik:** Sincronização automática de usuários no RouterOS via REST API.
*   **Painel do Estabelecimento:** Visualização e exportação de leads capturados.
*   **Super Admin:** Gestão centralizada de estabelecimentos e branding.
*   **Suporte a CGNAT:** Mecanismo de sincronização híbrida (Push/Pull) para redes complexas.

## 🛠️ Stack Tecnológica

*   **Backend:** PHP 8.x (Funcional/Procedural limpo)
*   **Banco de Dados:** MySQL (InnoDB)
*   **Frontend:** HTML5, Vanilla CSS, Vanilla JS (Sem frameworks ou CDNs externos)
*   **Infraestrutura:** Servidor Linux (LAMP) + MikroTik RouterOS v7.x

## 📂 Estrutura do Projeto

*   `/admin`: Painel administrativo para os donos de estabelecimentos.
*   `/api/v1`: Endpoints da API para sincronização e autenticação.
*   `/config`: Configurações globais (Singleton de conexão PDO).
*   `/docs`: Documentação técnica detalhada e especificações de sprints.
*   `/hotspot`: Arquivos do portal captivo para upload no MikroTik.
*   `/scripts`: Utilitários de linha de comando (ex: setup do banco).
*   `/sql`: Schemas, migrations e seeds do banco de dados.
*   `/superadmin`: Painel de gestão para a Mega Tecnologias.
*   `/tests`: Scripts de testes e mocks.

## ⚙️ Instalação e Configuração Local

### Pré-requisitos
*   Servidor Web (Apache/Nginx)
*   PHP 8.0+
*   MySQL 5.7+

### Passo a Passo

1.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/megatecnologia-source/MegaHotSpot.git
    cd MegaHotSpot
    ```

2.  **Configurar Banco de Dados:**
    Execute o script de setup interativo:
    ```bash
    bash scripts/setup_db.sh
    ```
    *Ou manualmente:* Crie o banco `mega_hotspot` e importe `sql/schema.sql`.

3.  **Variáveis de Ambiente:**
    O sistema utiliza `getenv()` no `config/db.php`. Certifique-se de configurar:
    *   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

4.  **Permissões:**
    Garanta que a pasta `logs/` tenha permissão de escrita para o servidor web.

## 🔒 Segurança e LGPD

O projeto segue boas práticas de segurança, incluindo:
*   Uso obrigatório de **PDO com Prepared Statements**.
*   Hardening de sessões PHP.
*   Prevenção contra XSS e CSRF.
*   Checkbox de consentimento obrigatório no portal captivo conforme a **LGPD (Lei 13.709/2018)**.

---

Desenvolvido por **Mega Tecnologias**.