# Referência da API (v1)

A API do MegaHotSpot é baseada em REST e comunica-se exclusivamente via JSON.

## 🔑 Autenticação

Existem dois métodos de autenticação:
1.  **X-Auth-Token:** Usado pelo Portal Captivo. O token é o UUID do estabelecimento.
2.  **Sessão PHP:** Usado pelos painéis administrativos após o login.

---

## 📡 Endpoints Públicos (Captive Portal)

### `POST /api/v1/sync.php`
Envia os dados do lead capturado no portal para o servidor central.

**Headers:**
*   `X-Auth-Token`: `UUID-DO-ESTABELECIMENTO`
*   `Content-Type`: `application/json`

**Body:**
```json
{
  "nome": "Fulano de Tal",
  "cpf": "123.456.789-00",
  "whatsapp": "11999998888",
  "email": "fulano@email.com",
  "mac": "AA:BB:CC:DD:EE:FF",
  "senha_hotspot": "8888"
}
```

**Respostas:**
*   `201 Created`: Lead salvo. `hotspot_user_created` indica se o Push para o MikroTik funcionou.
*   `400 Bad Request`: Dados inválidos ou campos obrigatórios ausentes.
*   `403 Forbidden`: Token inválido.

---

## 📊 Endpoints Administrativos (Requer Sessão)

### `GET /api/v1/leads.php`
Lista os leads do estabelecimento logado com paginação e busca.

**Parâmetros:**
*   `page` (int): Número da página.
*   `per_page` (int): Itens por página (padrão 50).
*   `busca` (string): Termo de pesquisa.

---

### `GET /api/v1/export.php`
Gera o download de um arquivo CSV com todos os leads do estabelecimento.

---

## 🛠️ Endpoints de Infra (MikroTik Sync)

### `GET /api/v1/pending_users.php`
Consultado pelo Scheduler do MikroTik para buscar usuários que ainda não foram criados localmente.

---

### `POST /api/v1/confirm_sync.php`
Chamado pelo MikroTik para confirmar que os usuários pendentes foram criados com sucesso, alterando o status no banco de dados para `synced`.
