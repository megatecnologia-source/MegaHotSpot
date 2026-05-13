# Checklist — Onboarding de Novo Cliente

Siga este processo operacional para ativar um novo estabelecimento na plataforma.

## 1. Coleta de Informações
Solicite ao cliente:
- Nome comercial e Razão Social (LGPD).
- CNPJ.
- E-mail para acesso ao painel admin.
- Logo (formato PNG transparente, se possível).
- Cores da marca (ou fotos do local para sugerir).

## 2. Configuração no Super-Admin
1. Acesse `https://painel.megatecnologias.com/superadmin/`
2. Clique em **+ Novo Cliente**.
3. Preencha os dados e escolha a paleta de cores usando o **Preview ao Vivo**.
4. Salve e **copie o Token Gerado**.

## 3. Preparação do Pacote
1. Vá em **Editar** o cliente recém-criado.
2. Clique no botão **📦 Download Pacote ZIP**.
3. O sistema gerará o pacote já com as cores, logo e token corretos.

## 4. Instalação no Local (MikroTik)
1. Acesse o roteador do cliente.
2. Siga o guia `docs/deploy-mikrotik.md`.
3. Certifique-se de configurar o **Walled Garden** corretamente.
4. Crie o usuário `api-megahotspot` e defina a senha.

## 5. Finalização no Super-Admin
1. Volte ao cadastro do cliente no Super-Admin.
2. Preencha o **IP Público** (ou DNS DDNS) do MikroTik do cliente.
3. Insira o usuário e a senha da API criados no passo anterior.
4. Clique em **Testar Conexão**. Se aparecer "✅ Conexão OK", o sistema está pronto.

## 6. Treinamento do Cliente
1. Forneça o link: `https://painel.megatecnologias.com/admin/login.php`
2. Informe o usuário e a senha definidos no passo 2.
3. Mostre como visualizar os Leads capturados.
