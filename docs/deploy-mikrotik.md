# Configuração do MikroTik — RouterOS 7.22.3

Siga este guia para configurar o roteador de cada novo estabelecimento. Todos os comandos podem ser executados via **Terminal** no WinBox.

---

## Passo 1 — Upload dos Arquivos
1. Abra o WinBox e conecte no roteador do cliente.
2. No menu lateral, clique em **Files**.
3. Baixe o pacote personalizado no Super-Admin do cliente.
4. Arraste a pasta `hotspot/` do ZIP para a raiz do Files no MikroTik.
   - Certifique-se de que o caminho final seja `flash/hotspot/` (em alguns modelos) ou apenas `hotspot/`.

---

## Passo 2 — Walled Garden (Liberação da API)
O roteador precisa acessar nosso servidor ANTES do usuário estar logado.
```routeros
/ip hotspot walled-garden ip
add dst-host=api.megatecnologias.com action=accept comment="Mega Hotspot API"
```

---

## Passo 3 — Criar Profile de Usuário
Define a velocidade e o tempo de sessão do Wi-Fi grátis.
```routeros
/ip hotspot user profile
add name=hotspot-guest rate-limit=5M/5M shared-users=1 session-timeout=2h \
    status-autorefresh=1m transparent-proxy=no
```

---

## Passo 4 — Habilitar REST API (SSL)
Nosso servidor usará esta API para criar o login do usuário dinamicamente.
```routeros
/ip service
set www-ssl disabled=no port=443
```
*Nota: Se o roteador não tiver certificado, ele usará um auto-assinado. Nossa API está configurada para ignorar a validação de SSL auto-assinado para facilitar o deploy.*

---

## Passo 5 — Usuário de API
Crie um usuário exclusivo para a integração.
```routeros
/user
add name=api-megahotspot password=COLOQUE_UMA_SENHA_FORTE group=full \
    comment="Integracao Mega Hotspot SaaS"
```
⚠️ **Atenção:** Você precisará cadastrar este IP, Usuário e Senha no Painel Super-Admin.

---

## Passo 6 — Configurar Hotspot Server Profile
Aponta o hotspot para usar nossa pasta e método de login.
```routeros
/ip hotspot profile
set [find name=default] html-directory=hotspot login-by=http-chap
```
*Nota: Substitua `default` pelo nome do profile que o cliente estiver usando.*

---

## Passo 7 — Configuração de Rede (Opcional)
Se o MikroTik estiver atrás de outro roteador da operadora, você precisará fazer um **Port Forward** da porta 443 para o IP do MikroTik para que nossa API consiga "enxergá-lo".

---

## Passo 8 — Validação
1. Conecte no Wi-Fi.
2. O portal deve abrir automaticamente (ou ao tentar navegar).
3. Faça o cadastro de teste.
4. No WinBox, vá em `/ip hotspot active` e veja se o usuário (CPF) apareceu logado.

---

## Passo 9 — Configurar Sincronização Pull (CGNAT)

> ✅ Este passo é **obrigatório** para roteadores atrás de NAT ou CGNAT.
> Opcional (mas recomendado) para roteadores com IP público.

### 9.1 — Cadastrar o Script de Sync

1. WinBox → System → Scripts → botão `[+]`
2. **Name:** `mega-hotspot-sync`
3. **Policy:** marcar `read`, `write`, `test`
4. **Source:** copiar o script da seção correspondente em `docs/sprints/SPRINT_7_nat_cgnat.md`
5. Substituir `SUBSTITUIR_PELO_TOKEN_UUID_DO_CLIENTE` pelo Token exibido no Super-Admin
6. Clicar **OK**

### 9.2 — Criar o Scheduler

Via Terminal:
```routeros
/system scheduler add name=mega-hotspot-sync interval=30s on-event=mega-hotspot-sync policy=read,write,test
```

### 9.3 — Testar manualmente

No Terminal do WinBox:
```routeros
/system script run mega-hotspot-sync
```

Verificar os logs:
```routeros
/log print where topics~"info"
# Deve aparecer: "MegaSync: Nenhum usuário pendente." (se não houver leads)
# Ou: "MegaSync: Criado usuario 52998224725"
```
