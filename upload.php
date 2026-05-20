<?php
require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

$db = db();
$templates = $db->query("
    SELECT id_tipo_formulario, nome_formulario, template_word_path, json_estrutura_campos,
           (SELECT COUNT(*) FROM tab_formularios_preenchidos WHERE id_tipo_formulario_fk = t.id_tipo_formulario) AS usos
    FROM tab_tipos_formularios t
    ORDER BY id_tipo_formulario DESC
")->fetchAll();

layout_head('Templates');
?>

<div class="page-header-row">
  <div class="page-header">
    <h1>Templates Word</h1>
    <p>Gerencie os modelos de documentos do sistema</p>
  </div>
</div>

<!-- Form upload -->
<div class="card mb-2">
  <div class="card-title">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    Novo Template
  </div>

  <div class="form-grid">
    <div class="field span-2">
      <label>Nome do Formulário <span class="req">*</span></label>
      <input type="text" id="nome_formulario" placeholder="Ex: Termo de Uso — Camisa Polo" maxlength="120">
    </div>

    <div class="field span-2">
      <label>Arquivo .docx <span class="req">*</span></label>
      <div class="dropzone" id="dropzone">
        <input type="file" id="arquivo_docx" accept=".docx">
        <span class="dropzone-icon">📄</span>
        <div class="dropzone-title">Arraste o arquivo aqui</div>
        <div class="dropzone-sub">ou clique para selecionar &nbsp;·&nbsp; <strong>somente .docx</strong></div>
      </div>
      <div class="file-preview" id="filePreview">
        <span class="file-preview-icon">📝</span>
        <div class="file-preview-info">
          <div class="file-preview-name" id="previewName">—</div>
          <div class="file-preview-size" id="previewSize">—</div>
        </div>
        <button class="file-preview-remove" id="btnRemoveFile">✕</button>
      </div>
      <!-- Placeholders detectados -->
      <div id="placeholdersWrap" style="display:none; margin-top:.75rem;">
        <div class="text-muted mb-1" style="font-size:.78rem; font-weight:500;">Placeholders detectados:</div>
        <div class="placeholder-wrap" id="placeholderTags"></div>
        <div class="field-hint mt-1">Campos em cinza são preenchidos automaticamente pelo sistema.</div>
      </div>
    </div>
  </div>

  <hr class="divider">

  <!-- Builder de campos -->
  <div class="card-title" style="margin-bottom:.75rem;">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
    Campos do Formulário
  </div>
  <div class="field-hint mb-2">
    Defina os campos que o usuário vai preencher. O <code style="font-family:var(--mono);font-size:.78rem;background:var(--gray-100);padding:1px 4px;border-radius:3px;">name</code> deve ser igual ao placeholder no Word.
  </div>

  <div class="campos-header">
    <span>name (placeholder)</span>
    <span>label</span>
    <span>tipo</span>
    <span></span>
  </div>
  <div id="camposList"></div>
  <button class="btn-add-campo" id="btnAddCampo">+ Adicionar campo</button>

  <hr class="divider">

  <button class="btn btn-primary btn-lg" id="btnSalvar" onclick="salvarTemplate()">
    <span class="btn-label">Salvar Template</span>
    <span class="spinner"></span>
  </button>
</div>

<!-- Lista de templates -->
<?php if (!empty($templates)): ?>
<div class="card">
  <div class="card-title">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
    Templates Cadastrados
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Campos</th>
          <th>Usos</th>
          <th>Arquivo</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($templates as $t):
          $campos = json_decode($t['json_estrutura_campos'] ?? '[]', true);
        ?>
        <tr>
          <td class="text-mono"><?= $t['id_tipo_formulario'] ?></td>
          <td><?= htmlspecialchars($t['nome_formulario']) ?></td>
          <td class="text-muted"><?= count($campos) ?> campo(s)</td>
          <td><?= $t['usos'] ?></td>
          <td class="text-mono text-muted" style="font-size:.75rem;"><?= $t['template_word_path'] ? basename($t['template_word_path']) : '—' ?></td>
          <td class="text-right">
            <button class="btn btn-outline btn-sm btn-danger" onclick="excluirTemplate(<?= $t['id_tipo_formulario'] ?>, '<?= htmlspecialchars(addslashes($t['nome_formulario'])) ?>')">Excluir</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
const FIXOS = ['nome_funcionario','cpf','cargo','data_assinatura','assinatura_digital'];
let arquivoSelecionado = null;
let campoCount = 0;

// ── Dropzone ───────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initDropzone('dropzone', 'arquivo_docx', '.docx', processarArquivo);
  document.getElementById('btnRemoveFile').addEventListener('click', removerArquivo);
  document.getElementById('btnAddCampo').addEventListener('click', () => adicionarCampo());
});

function processarArquivo(file) {
  if (!file.name.endsWith('.docx')) { toast('error', 'Apenas .docx são aceitos.'); return; }
  arquivoSelecionado = file;
  document.getElementById('previewName').textContent = file.name;
  document.getElementById('previewSize').textContent = formatBytes(file.size);
  document.getElementById('filePreview').classList.add('show');
  extrairPlaceholders(file);
}

function removerArquivo() {
  arquivoSelecionado = null;
  document.getElementById('arquivo_docx').value = '';
  document.getElementById('filePreview').classList.remove('show');
  document.getElementById('placeholdersWrap').style.display = 'none';
}

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
  return (b/1048576).toFixed(2) + ' MB';
}

async function extrairPlaceholders(file) {
  try {
    const buf = await file.arrayBuffer();
    const zip = await JSZip.loadAsync(buf);
    const xml = await zip.file('word/document.xml').async('string');
    const regex = /\{([a-zA-Z0-9_]+)\}/g;
    const found = new Set();
    let m;
    while ((m = regex.exec(xml)) !== null) found.add(m[1]);
    const vars = [...found];

    // Exibe tags
    const wrap = document.getElementById('placeholdersWrap');
    const tags = document.getElementById('placeholderTags');
    tags.innerHTML = '';
    vars.forEach(v => {
      const s = document.createElement('span');
      s.className = 'placeholder-tag' + (FIXOS.includes(v) ? ' fixo' : '');
      s.textContent = '{' + v + '}';
      tags.appendChild(s);
    });
    wrap.style.display = vars.length ? 'block' : 'none';

    // Sugere campos
    const existentes = [...document.querySelectorAll('.campo-name')].map(i => i.value.trim());
    vars.filter(v => !FIXOS.includes(v) && !existentes.includes(v))
        .forEach(v => adicionarCampo(v, v.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()), 'text'));
  } catch(e) { console.warn('Erro ao ler .docx:', e); }
}

// ── Campos builder ─────────────────────────────────────────────────────────
function adicionarCampo(name = '', label = '', tipo = 'text') {
  const id = ++campoCount;
  const list = document.getElementById('camposList');
  const row = document.createElement('div');
  row.className = 'campo-row';
  row.dataset.id = id;
  const tipos = ['text','date','number','select','textarea'];
  row.innerHTML = `
    <input class="campo-name"  type="text" placeholder="nome_campo" value="${name}">
    <input class="campo-label" type="text" placeholder="Label"      value="${label}">
    <select class="campo-tipo">
      ${tipos.map(t => `<option value="${t}" ${t===tipo?'selected':''}>${t}</option>`).join('')}
    </select>
    <button class="btn-remove-campo" onclick="this.closest('.campo-row').remove()">✕</button>
  `;
  list.appendChild(row);
}

// ── Submit ─────────────────────────────────────────────────────────────────
async function salvarTemplate() {
  const nome = document.getElementById('nome_formulario').value.trim();
  if (!nome)                { toast('error', 'Informe o nome do formulário.'); return; }
  if (!arquivoSelecionado)  { toast('error', 'Selecione um arquivo .docx.'); return; }

  const campos = [];
  let erros = [];
  document.querySelectorAll('.campo-row').forEach(row => {
    const n = row.querySelector('.campo-name').value.trim();
    const l = row.querySelector('.campo-label').value.trim();
    const t = row.querySelector('.campo-tipo').value;
    if (!n) { erros.push('Preencha o name de todos os campos.'); return; }
    campos.push({ name: n, label: l || n, type: t });
  });
  if (erros.length) { toast('error', erros[0]); return; }

  const btn = document.getElementById('btnSalvar');
  btnLoading(btn, true);

  const fd = new FormData();
  fd.append('acao', 'upload_template');
  fd.append('nome_formulario', nome);
  fd.append('arquivo_docx', arquivoSelecionado, arquivoSelecionado.name);
  fd.append('json_estrutura_campos', JSON.stringify(campos));

  try {
    const r = await fetch('<?= BASE_URL ?>/actions/upload_template.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.sucesso) {
      toast('success', 'Template salvo! ID: ' + d.id_tipo_formulario);
      setTimeout(() => location.reload(), 1500);
    } else {
      toast('error', d.mensagem);
    }
  } catch(e) { toast('error', 'Erro: ' + e.message); }
  finally { btnLoading(btn, false); }
}

async function excluirTemplate(id, nome) {
  if (!confirm(`Excluir o template "${nome}"? Documentos gerados não serão afetados.`)) return;
  const fd = new FormData();
  fd.append('acao', 'excluir_template');
  fd.append('id', id);
  const r = await fetch('<?= BASE_URL ?>/actions/upload_template.php', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.sucesso) { toast('success', 'Template excluído.'); setTimeout(() => location.reload(), 1200); }
  else toast('error', d.mensagem);
}
</script>
<?php layout_foot(); ?>
