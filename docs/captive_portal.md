# Guia do Portal Captivo (Hotspot)

Este documento explica como configurar e customizar os arquivos que rodam dentro do roteador MikroTik.

## 📂 Arquivos no Roteador

A pasta `/hotspot` do repositório deve ser enviada para a memória do MikroTik (via FTP ou WinBox).

*   `login.html`: Arquivo principal. Contém o formulário de captura e a lógica de branding.
*   `css/style.css`: Estilização visual (Dark Mode por padrão).
*   `md5.js`: Necessário para o protocolo de autenticação CHAP do MikroTik.

## ⚙️ Configuração via `HOTSPOT_CONFIG`

Dentro do `login.html`, existe um bloco JavaScript que define o comportamento do portal para cada cliente. O Super Admin gera esse bloco automaticamente ao criar o pacote ZIP.

```javascript
const HOTSPOT_CONFIG = {
  apiUrl : "https://api.megatecnologias.com/api/v1/sync.php",
  token  : "UUID-DO-ESTABELECIMENTO",
  nome   : "Nome do Estabelecimento",
  corPrimaria : "#6C63FF",
  lgpd: {
    nomeEmpresa : "Empresa Ltda.",
    textoConsentimento: "Autorizo o uso dos meus dados..."
  }
};
```

## 🛠️ Procedimento de Instalação no MikroTik

1.  **Habilitar REST API:**
    ```routeros
    /ip/service set www-ssl disabled=no port=443
    ```
2.  **Criar Usuário da API:**
    Crie um usuário com permissão `full` ou `write` para que o servidor possa criar usuários no hotspot.
3.  **Walled Garden:**
    Libere o domínio da API para que o usuário consiga enviar os dados antes de estar autenticado:
    ```routeros
    /ip/hotspot/walled-garden/ip add dst-host=api.megatecnologias.com action=accept
    ```
4.  **Upload de Arquivos:**
    Envie a pasta `hotspot/` para o roteador.
5.  **Configurar Server Profile:**
    No MikroTik, aponte o `HTML Directory` do seu Hotspot Server Profile para a pasta que você subiu.

## ⚖️ Conformidade LGPD

O portal captivo do MegaHotSpot foi projetado para ser 100% aderente à LGPD:
*   O botão de "Conectar" só é habilitado após o usuário marcar o checkbox de consentimento.
*   O texto de consentimento inclui o nome jurídico da empresa e a finalidade do uso dos dados.
*   Links para a Política de Privacidade completa podem ser configurados via `HOTSPOT_CONFIG`.
