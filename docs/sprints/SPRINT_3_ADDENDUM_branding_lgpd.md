# ADDENDUM — Branding por Cliente + LGPD
**Complementa:** SPRINT_3, SPRINT_4, SPRINT_1  
**Impacto:** Schema do banco · HOTSPOT_CONFIG · CSS do portal · Super-admin

---

## 1. Estratégia de Personalização

O `hotspot/login.html` é um arquivo **estático** servido pelo MikroTik. Ele não roda PHP. Toda personalização deve ocorrer de uma de duas formas:

| Método | Como funciona | Quando usar |
|---|---|---|
| **Bloco HOTSPOT_CONFIG** | JS lê configuração e aplica em runtime (cores via CSS vars, logo via img.src, textos via innerHTML) | Branding dinâmico sem reescrever HTML |
| **Pacote gerado pelo super-admin** | Super-admin gera um ZIP do `hotspot/` com valores já embutidos no HTML/CSS | Garante funcionamento mesmo sem internet no boot |

**Decisão: usar ambos.** O `HOTSPOT_CONFIG` aplica tudo em runtime (mais flexível). O super-admin gera o ZIP personalizado para download e upload no MikroTik.

---

## 2. HOTSPOT_CONFIG Expandido (spec definitiva)

Este é o único bloco que o agente deve editar no `login.html` para cada cliente:

```javascript
const HOTSPOT_CONFIG = {
  // ── Integração API ──────────────────────────────────
  apiUrl : "https://api.megatecnologias.com/api/v1/sync.php",
  token  : "UUID-DO-ESTABELECIMENTO",

  // ── Identidade Visual ────────────────────────────────
  nome          : "Bar do João",
  logoUrl       : "https://api.megatecnologias.com/logos/bar-do-joao.png",
  // Cores em HEX — aplicadas via CSS variables
  corPrimaria   : "#6C63FF",   // botões, abas ativas, links, foco
  corSecundaria : "#4CAF50",   // acentos opcionais
  corFundo1     : "#0f0f1a",   // início do gradiente de fundo
  corFundo2     : "#1a1a3e",   // fim do gradiente de fundo

  // ── LGPD ────────────────────────────────────────────
  lgpd: {
    nomeEmpresa  : "Bar do João Ltda.",
    cnpj         : "00.000.000/0001-00",
    emailDpo     : "privacidade@barjoao.com.br",  // Data Protection Officer
    urlPolitica  : "",   // deixar vazio se não tiver página própria
    // Texto exibido acima do checkbox — suporta HTML simples
    textoConsentimento: "Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por <strong>Bar do João Ltda.</strong> para fins de comunicação comercial e relacionamento, conforme a <strong>Lei Geral de Proteção de Dados (LGPD — Lei 13.709/2018)</strong>.",
    textoRodape  : "Seus dados são armazenados com segurança e não serão compartilhados com terceiros sem sua autorização."
  },

  // ── Mensagem de boas-vindas (opcional) ──────────────
  boasVindas: "Bem-vindo! Conecte-se ao Wi-Fi grátis."
};
```

---

## 3. Como o JavaScript aplica o branding

Adicionar à função `initUI()` do `login.html`:

```javascript
function initUI() {
  // Nome e logo
  document.querySelector('.hs-nome-estab').textContent = HOTSPOT_CONFIG.nome;
  if (HOTSPOT_CONFIG.logoUrl) {
    const img = document.getElementById('hs-logo');
    img.src = HOTSPOT_CONFIG.logoUrl;
    img.alt = HOTSPOT_CONFIG.nome;
    img.classList.remove('hidden');
  }

  // Mensagem de boas-vindas
  if (HOTSPOT_CONFIG.boasVindas) {
    document.querySelector('.hs-subtitulo').textContent = HOTSPOT_CONFIG.boasVindas;
  }

  // ── Aplicar paleta de cores via CSS variables ──────────
  const root = document.documentElement;
  if (HOTSPOT_CONFIG.corPrimaria)   root.style.setProperty('--cor-primaria',   HOTSPOT_CONFIG.corPrimaria);
  if (HOTSPOT_CONFIG.corSecundaria) root.style.setProperty('--cor-secundaria', HOTSPOT_CONFIG.corSecundaria);
  if (HOTSPOT_CONFIG.corFundo1 && HOTSPOT_CONFIG.corFundo2) {
    document.body.style.background =
      `linear-gradient(135deg, ${HOTSPOT_CONFIG.corFundo1} 0%, ${HOTSPOT_CONFIG.corFundo2} 100%)`;
  }

  // ── Renderizar bloco LGPD ──────────────────────────────
  renderizarLgpd();
}

function renderizarLgpd() {
  const lgpd = HOTSPOT_CONFIG.lgpd;
  if (!lgpd) return;

  const linkPolitica = lgpd.urlPolitica
    ? `<a href="${lgpd.urlPolitica}" target="_blank" rel="noopener">Política de Privacidade</a>`
    : 'Política de Privacidade';

  const html = `
    <div class="hs-lgpd-bloco">
      <label class="hs-lgpd-check">
        <input type="checkbox" id="lgpd-consent" required />
        <span>${lgpd.textoConsentimento}</span>
      </label>
      <p class="hs-lgpd-rodape">
        ${lgpd.textoRodape}
        ${lgpd.urlPolitica ? `· Leia nossa ${linkPolitica}.` : ''}
      </p>
    </div>
  `;

  // Inserir antes do botão em ambas as abas
  document.getElementById('form-novo').insertAdjacentHTML('beforeend', html);
}
```

**Validação do checkbox LGPD na submissão:**
```javascript
// Dentro do submit do form-novo, ANTES de fazer o fetch:
const consentimento = document.getElementById('lgpd-consent');
if (!consentimento || !consentimento.checked) {
  showAlert('Você precisa aceitar os termos de uso e privacidade para continuar.');
  return;
}
```

---

## 4. CSS para o bloco LGPD

Adicionar ao `hotspot/css/style.css`:

```css
/* ── LGPD ── */
.hs-lgpd-bloco {
  margin-top: 1rem;
  padding: 0.9rem 1rem;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px;
}

.hs-lgpd-check {
  display: flex;
  gap: 0.6rem;
  align-items: flex-start;
  cursor: pointer;
  font-size: 0.78rem;
  color: var(--cor-texto-suave);
  line-height: 1.5;
  margin-bottom: 0;  /* override do label legado */
  background: transparent;
  border: none;
  padding: 0;
}

.hs-lgpd-check input[type="checkbox"] {
  margin-top: 2px;
  flex-shrink: 0;
  width: 16px;
  height: 16px;
  accent-color: var(--cor-primaria);
  cursor: pointer;
}

.hs-lgpd-check span strong { color: var(--cor-texto); }

.hs-lgpd-rodape {
  margin-top: 0.5rem;
  font-size: 0.7rem;
  color: rgba(255,255,255,0.3);
  line-height: 1.4;
}

.hs-lgpd-rodape a {
  color: var(--cor-primaria);
  text-decoration: none;
}
```

---

## 5. Novos campos no banco — `estabelecimentos`

Adicionar ao `sql/schema.sql` (Task 1.1):

```sql
-- Branding
`cor_primaria`    VARCHAR(7)   DEFAULT '#6C63FF'  COMMENT 'HEX p/ botões e destaques',
`cor_secundaria`  VARCHAR(7)   DEFAULT '#4CAF50',
`cor_fundo1`      VARCHAR(7)   DEFAULT '#0f0f1a'  COMMENT 'Início do gradiente',
`cor_fundo2`      VARCHAR(7)   DEFAULT '#1a1a3e'  COMMENT 'Fim do gradiente',
`boas_vindas`     VARCHAR(255) DEFAULT NULL        COMMENT 'Mensagem personalizada',

-- LGPD
`lgpd_nome_empresa`  VARCHAR(255) DEFAULT NULL,
`lgpd_cnpj`          VARCHAR(18)  DEFAULT NULL,
`lgpd_email_dpo`     VARCHAR(255) DEFAULT NULL,
`lgpd_url_politica`  VARCHAR(500) DEFAULT NULL,
`lgpd_texto_consentimento` TEXT   DEFAULT NULL,
```

---

## 6. Super-Admin — Novos campos no formulário

Adicionar às Tasks 4.4 e 4.5 (formulário de cadastro/edição):

### Seção "Identidade Visual"
| Campo | Input | Default |
|---|---|---|
| Cor primária | `<input type="color">` | #6C63FF |
| Cor secundária | `<input type="color">` | #4CAF50 |
| Cor fundo (início) | `<input type="color">` | #0f0f1a |
| Cor fundo (fim) | `<input type="color">` | #1a1a3e |
| Mensagem de boas-vindas | text | vazio |

### Seção "LGPD / Privacidade"
| Campo | Input | Obrigatório |
|---|---|---|
| Nome jurídico da empresa | text | sim |
| CNPJ | text (máscara) | sim |
| E-mail do responsável pelos dados | email | sim |
| URL da Política de Privacidade | url | não |
| Texto de consentimento | textarea | sim (tem default) |

**Texto de consentimento padrão** (pre-preencher no form):
```
Autorizo o uso dos meus dados (nome, CPF, WhatsApp e e-mail) por [NOME_EMPRESA] 
para fins de comunicação comercial e relacionamento, conforme a Lei Geral de 
Proteção de Dados (LGPD — Lei 13.709/2018).
```

---

## 7. Geração do Pacote MikroTik (Download ZIP)

### Nova feature no super-admin: botão "Download Pacote MikroTik"

Na página `superadmin/editar.php`, adicionar botão que chama `api/v1/gerar_pacote.php?id=X`.

### Task nova — `api/v1/gerar_pacote.php`

**Auth:** `$_SESSION['superadmin_id']`  
**Método:** GET  
**Resposta:** Download de um arquivo ZIP

**Lógica:**
1. Buscar dados do estabelecimento pelo `?id=`
2. Ler o arquivo `hotspot/login.html` como template
3. Substituir o bloco `HOTSPOT_CONFIG` com os valores do banco
4. Criar ZIP em memória com:
   - `login.html` (personalizado)
   - `css/style.css` (padrão, sem alteração)
   - `alogin.html`, `status.html`, `logout.html`, `error.html` (sem alteração)
   - `md5.js` (sem alteração)
   - `img/` (sem alteração)
5. Retornar ZIP com header `Content-Disposition: attachment; filename="hotspot-NOME-SLUG.zip"`

**Implementação PHP:**
```php
// Requer extensão zip (disponível na Hostinger)
$zip = new ZipArchive();
$tmpFile = tempnam(sys_get_temp_dir(), 'hotspot_');
$zip->open($tmpFile, ZipArchive::CREATE);

// Gerar login.html personalizado
$template = file_get_contents(__DIR__ . '/../../hotspot/login.html');
$configBlock = gerarConfigBlock($estabelecimento); // monta o JS HOTSPOT_CONFIG
$loginPersonalizado = preg_replace(
    '/const HOTSPOT_CONFIG = \{.*?\};/s',
    $configBlock,
    $template
);
$zip->addFromString('hotspot/login.html', $loginPersonalizado);

// Arquivos fixos
$fixos = ['css/style.css','alogin.html','status.html','logout.html',
          'error.html','md5.js','img/user.svg','img/password.svg'];
foreach ($fixos as $f) {
    $zip->addFile(__DIR__ . '/../../hotspot/' . $f, 'hotspot/' . $f);
}

$zip->close();

$slug = preg_replace('/[^a-z0-9]/', '-', strtolower($estabelecimento['nome']));
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="hotspot-' . $slug . '.zip"');
readfile($tmpFile);
unlink($tmpFile);
```

---

## 8. Impacto nos arquivos de sprint existentes

| Sprint | Alteração necessária |
|---|---|
| **SPRINT_1** | Adicionar colunas de branding e LGPD ao schema `estabelecimentos` |
| **SPRINT_3** | `login.html`: HOTSPOT_CONFIG expandido + LGPD checkbox + initUI com cores |
| **SPRINT_3** | `style.css`: adicionar `.hs-lgpd-bloco`, `.hs-lgpd-check`, `.hs-lgpd-rodape` |
| **SPRINT_4** | Formulários de novo/editar: seções "Identidade Visual" e "LGPD/Privacidade" |
| **SPRINT_4** | Nova task: `api/v1/gerar_pacote.php` + botão no `editar.php` |
| **SPRINT_5** | `novo-cliente.md`: documentar uso do botão "Download Pacote" |

---

## 9. Conformidade LGPD — Checklist Legal

O sistema deve garantir:

| Requisito LGPD | Como atendemos |
|---|---|
| **Base legal** — consentimento explícito | Checkbox obrigatório antes de submeter |
| **Informação** — o que é coletado | Texto de consentimento exibido no portal |
| **Finalidade** — para que será usado | "comunicação comercial e relacionamento" |
| **Responsável** — quem trata os dados | Nome + CNPJ + e-mail do DPO no portal |
| **Direito de acesso e exclusão** | Dono do estab. pode excluir lead pelo painel (Sprint 6+) |
| **Segurança** — dados protegidos | HTTPS obrigatório, PDO prepared statements, senhas com hash |
| **Minimização** — só o necessário | Nome, CPF, WhatsApp (obrigatório), E-mail (opcional) |

> [!NOTE]
> **Aviso jurídico:** Este sistema oferece suporte técnico à conformidade LGPD, mas cada estabelecimento deve publicar sua própria Política de Privacidade e, dependendo do volume de dados, nomear formalmente um DPO. Recomende ao cliente consultar um advogado especializado.

---

## 10. Preview visual do portal personalizado

```
┌─────────────────────────────────┐
│  [LOGO DO RESTAURANTE]          │ ← logoUrl
│  Restaurante Sabor & Arte       │ ← nome
│  Bem-vindo! Wi-Fi grátis aqui.  │ ← boasVindas
├─────────────────────────────────┤
│ [Primeiro Acesso][Já cadastrado]│ ← cor botão ativo = corPrimaria
├─────────────────────────────────┤
│  Nome completo    [__________]  │
│  CPF              [__________]  │
│  WhatsApp         [__________]  │
│  E-mail (opt.)    [__________]  │
│                                 │
│  ┌─────────────────────────┐    │
│  │ ☐ Autorizo o uso dos    │    │ ← lgpd.textoConsentimento
│  │   meus dados por        │    │
│  │   Restaurante Ltda...   │    │
│  │                         │    │
│  │ Dados protegidos. Leia  │    │ ← lgpd.textoRodape
│  │ nossa Política de Priv. │    │
│  └─────────────────────────┘    │
│                                 │
│  [  Quero o Wi-Fi Grátis!  ]    │ ← corPrimaria
└─────────────────────────────────┘
  Fundo: gradiente corFundo1→corFundo2
```
