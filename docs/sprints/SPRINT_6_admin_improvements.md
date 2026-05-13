# SPRINT 6 — Melhorias do Painel Admin
**Pré-requisito:** Sprint 2 (`api/v1/leads.php` funcionando)  
**Entrega:** `admin/index.php` redesenhado + `api/v1/export.php`  
**Esta sprint é opcional — o produto é funcional sem ela**

---

## Task 6.1 — Redesenhar `admin/index.php`

### Cards de métricas (topo da página)

Queries necessárias:
```sql
-- Total de leads
SELECT COUNT(*) FROM leads WHERE estabelecimento_id = :id

-- Leads hoje
SELECT COUNT(*) FROM leads
WHERE estabelecimento_id = :id AND DATE(data_cadastro) = CURDATE()

-- Leads esta semana
SELECT COUNT(*) FROM leads
WHERE estabelecimento_id = :id AND data_cadastro >= DATE_SUB(NOW(), INTERVAL 7 DAY)

-- Leads este mês
SELECT COUNT(*) FROM leads
WHERE estabelecimento_id = :id AND MONTH(data_cadastro) = MONTH(NOW())
  AND YEAR(data_cadastro) = YEAR(NOW())
```

Layout dos cards (4 cards em linha):
```
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│  TOTAL   │  │   HOJE   │  │ 7 DIAS   │  │  MÊS     │
│   120    │  │    5     │  │   38     │  │   72     │
│  leads   │  │  leads   │  │  leads   │  │  leads   │
└──────────┘  └──────────┘  └──────────┘  └──────────┘
```

### Tabela de leads (carregada via fetch)

Substituir a renderização PHP atual por tabela carregada via `fetch('/api/v1/leads.php')`.

**Controles da tabela:**
- Input de busca (nome, CPF, WhatsApp) com debounce de 400ms
- Paginação (botões Anterior / Próximo)
- Botão "Exportar CSV" → redireciona para `../api/v1/export.php`

**Colunas:**

| Nome | CPF | WhatsApp | E-mail | Cadastro | Atualização | Ação |
|---|---|---|---|---|---|---|
| João Silva | 123.456.789-09 | (11) 99999-0000 | — | 12/05/26 15h30 | — | [WhatsApp] |

**Botão WhatsApp:**
```javascript
// CPF com link direto
const numeroLimpo = lead.whatsapp.replace(/\D/g, '');
const url = `https://wa.me/${numeroLimpo}`;
```

**JavaScript da tabela:**
```javascript
let paginaAtual = 1;
let buscaTimer = null;

async function carregarLeads() {
  const busca = document.getElementById('input-busca').value.trim();
  const res = await fetch(
    `/api/v1/leads.php?page=${paginaAtual}&per_page=25&busca=${encodeURIComponent(busca)}`
  );
  const data = await res.json();
  renderizarTabela(data.leads);
  atualizarPaginacao(data.total, data.pagina, data.por_pagina);
}

document.getElementById('input-busca').addEventListener('input', () => {
  clearTimeout(buscaTimer);
  paginaAtual = 1;
  buscaTimer = setTimeout(carregarLeads, 400);
});

document.addEventListener('DOMContentLoaded', carregarLeads);
```

---

## Task 6.2 — Criar `api/v1/export.php`

**Arquivo:** `/api/v1/export.php`  
**Auth:** `$_SESSION['estabelecimento_id']`  
**Método:** GET  

```php
<?php
session_start();
if (!isset($_SESSION['estabelecimento_id'])) {
    http_response_code(401); exit;
}

require_once __DIR__ . '/../../config/db.php';
$id = (int) $_SESSION['estabelecimento_id'];

$pdo  = DB::getInstance();
$stmt = $pdo->prepare("
    SELECT nome, cpf, whatsapp, email, mac_address, data_cadastro, data_atualizacao
    FROM leads
    WHERE estabelecimento_id = :id
    ORDER BY data_cadastro DESC
");
$stmt->execute([':id' => $id]);
$leads = $stmt->fetchAll();

$filename = 'leads-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

// BOM para Excel reconhecer UTF-8
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['Nome', 'CPF', 'WhatsApp', 'E-mail', 'MAC', 'Cadastro', 'Atualização'], ';');

foreach ($leads as $lead) {
    fputcsv($out, [
        $lead['nome'],
        $lead['cpf'],
        $lead['whatsapp'],
        $lead['email'] ?? '',
        $lead['mac_address'] ?? '',
        $lead['data_cadastro'],
        $lead['data_atualizacao'] ?? ''
    ], ';');
}
fclose($out);
```

---

## Task 6.3 — Atualizar `TASKS.md` ao final

Ao concluir todas as sprints, atualizar o `TASKS.md` na raiz do projeto marcando cada item como concluído e adicionando os novos itens.

---

## Critérios de Aceitação

- [ ] Cards de métricas exibem valores corretos para o estabelecimento logado
- [ ] Tabela carrega leads via fetch (sem reload de página)
- [ ] Busca com debounce filtra leads em tempo real
- [ ] Paginação funciona (Anterior/Próximo)
- [ ] Botão WhatsApp abre `https://wa.me/` no número correto
- [ ] Exportar CSV → download do arquivo com BOM UTF-8
- [ ] Abrir CSV no Excel → caracteres acentuados corretos
- [ ] CSV contém apenas leads do estabelecimento logado (isolamento multi-tenant)
