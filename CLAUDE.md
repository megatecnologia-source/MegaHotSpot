# Contexto do Projeto: Mega Hotspot SaaS

## 🎯 Visão Geral
Plataforma SaaS minimalista para captura de leads via MikroTik Hotspot. O roteador autentica localmente e sincroniza os dados via Webhook. O sistema oferece um Dashboard para os donos de estabelecimentos visualizarem seus leads.

## 💻 Stack Tecnológica
- **Ambiente:** Ubuntu Server (LAMP Stack).
- **Linguagem:** PHP 8.x nativo (Estilo funcional/limpo).
- **Banco de Dados:** MySQL (InnoDB).
- **Frontend Dashboard:** Conceito de SPA (Single Page Application) consumindo API JSON.

## 🏗️ Arquitetura Multi-Tenant
- **Isolamento:** Cada estabelecimento possui um `uuid` único (cliente_token).
- **Relacionamento:** Toda tabela de dados (`leads`, `usuarios_admin`) deve obrigatoriamente possuir uma chave estrangeira para `estabelecimentos`.
- **Segurança de Dados:** Consultas ao banco de dados devem sempre filtrar pelo `estabelecimento_id` da sessão ativa.

## 📏 Regras de Codificação
- **DB:** Uso obrigatório de PDO com Prepared Statements. Proibido concatenar variáveis em SQL.
- **Respostas:** Endpoints de API devem retornar cabeçalhos `application/json` e códigos de status HTTP corretos (200, 201, 401, 403, 500).
- **Sessão:** Autenticação do Dashboard via `PHP Session` segura.

## 🗂️ Estrutura de Pastas
/public_html
  /api
    /v1
      sync.php          <-- Recepção de dados do MikroTik
      auth.php          <-- Login do Dashboard
      leads.php         <-- Listagem de leads (filtrada por cliente)
  /dashboard            <-- Interface administrativa
/config
  db.php                <-- Singleton de conexão PDO
/sql
  migrations.sql        <-- Estrutura do banco
