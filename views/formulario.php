<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$db = db();
$tipos = $db->query("SELECT id_tipo_formulario, nome_formulario FROM tab_tipos_formularios ORDER BY nome_formulario")->fetchAll();

$funcionarios = $db->query("
    SELECT cod_funcionario, nome_funcionario, cpf
    FROM tab_cadastro_funcionarios
    WHERE cod_empresa IN (26, 523, 68, 364, 365, 157, 234) AND situacao_empregado = 'Ativo'
    ORDER BY nome_funcionario
")->fetchAll();

?>

<div class="page-header">
  <h1>Preencher Formulário</h1>
  <p>Selecione o tipo de formulário, preencha os dados e assine digitalmente</p>
</div>

<!-- Passo 1: Seleção -->
<div class="card" id="stepSelecao">
  <div class="card-title">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
    Passo 1 — Selecione o Formulário e Funcionário
  </div>
  <div class="form-grid">
    <div class="field">
      <label>Tipo de Formulário <span class="req">*</span></label>
      <select id="sel_tipo">
        <option value="">— selecione —</option>
        <?php foreach ($tipos as $t): ?>
        <option value="<?= $t['id_tipo_formulario'] ?>"><?= htmlspecialchars($t['nome_formulario']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Funcionário <span class="req">*</span></label>
      <select id="sel_funcionario">
        <option value="">— selecione —</option>
        <?php foreach ($funcionarios as $f): ?>
        <option value="<?= $f['cod_funcionario'] ?>"><?= htmlspecialchars($f['nome_funcionario']) ?> — <?= $f['cpf'] ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="mt-2">
    <button class="btn btn-primary" onclick="avancarPasso()">Avançar →</button>
  </div>
</div>

<div id="preview-container" class="mb-3 d-none">
    <div class="card-title">Prévia do Conteúdo</div>
    <div id="documentPreview" class="document-preview-box">
    </div>
</div>

<!-- Passo 2: Campos dinâmicos + Assinatura -->
<div id="stepFormulario" style="display:none;">
  <div class="card mb-2" id="cardCampos">
    <div class="card-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Passo 2 — Dados do Formulário
    </div>
    <div class="form-grid" id="camposDinamicos"></div>
  </div>

  <div class="card">
    <div class="card-title">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
      Passo 3 — Assinatura Digital
    </div>
    <div class="field" style="max-width:580px;">
      <div class="canvas-wrap" id="canvasWrap">
        <canvas id="canvasAssinatura" style="height:130px; display:block; width:100%;"></canvas>
        <div class="canvas-placeholder">Assine aqui com o mouse ou dedo</div>
      </div>
      <div class="canvas-actions mt-1">
        <button class="btn btn-outline btn-sm" onclick="limparCanvas()">Limpar</button>
      </div>
    </div>
    <hr class="divider">
    <div class="flex gap-1">
      <button class="btn btn-outline" onclick="voltarPasso()">← Voltar</button>
      <button class="btn btn-primary btn-lg" id="btnGerar" onclick="gerarDocumento()">
        <span class="btn-label">Gerar Documento</span>
        <span class="spinner"></span>
      </button>
    </div>
  </div>
</div>

<!-- Passo 3: Resultado -->
<div id="stepResultado" style="display:none;">
  <div class="card" style="text-align:center; padding:3rem 2rem;">
    <div style="font-size:3rem; margin-bottom:1rem;">✅</div>
    <h2 style="font-size:1.25rem; font-weight:700; margin-bottom:.5rem;">Documento gerado com sucesso!</h2>
    <p class="text-muted mb-2">O arquivo foi salvo e registrado no sistema.</p>
    <div class="flex gap-1" style="justify-content:center; flex-wrap:wrap;">
      <a id="linkDownload" href="#" class="btn btn-primary" download>⬇ Baixar Documento</a>
      <button class="btn btn-outline" onclick="novoFormulario()">Preencher outro</button>
      <a href="/generate_word/admin.php" class="btn btn-outline">Ver no Admin</a>
    </div>
  </div>
</div>

<script>
var sigCanvas = null;

function avancarPasso() {
  var idTipo = document.getElementById('sel_tipo').value;
  var idFunc = document.getElementById('sel_funcionario').value;
  if (!idTipo) { toast('error', 'Selecione o tipo de formulário.'); return; }
  if (!idFunc) { toast('error', 'Selecione o funcionário.'); return; }
  carregarCampos(idTipo);
}

function carregarCampos(idTipo) {
  fetch('actions/get_campos.php?id_tipo=' + idTipo)
    .then(function(r) { return r.json(); })
    .then(function(d) {
      if (!d.sucesso) { toast('error', d.mensagem); return; }

      var grid = document.getElementById('camposDinamicos');
      grid.innerHTML = '';

      d.campos.forEach(function(campo) {
        var div = document.createElement('div');
        div.className = 'field' + (campo.type === 'textarea' ? ' span-2' : '');
        var req     = campo.required !== false;
        var reqAttr = req ? 'required' : '';
        var input   = '';

        if (campo.type === 'select' && campo.options) {
          var opts = campo.options.map(function(o) {
            return '<option value="' + o + '">' + o + '</option>';
          }).join('');
          input = '<select name="campo_' + campo.name + '" ' + reqAttr + '><option value="">— selecione —</option>' + opts + '</select>';
        } else if (campo.type === 'textarea') {
          input = '<textarea name="campo_' + campo.name + '" rows="3" placeholder="' + campo.label + '" ' + reqAttr + '></textarea>';
        } else {
          var defVal = campo.default !== undefined ? 'value="' + campo.default + '"' : '';
          input = '<input type="' + campo.type + '" name="campo_' + campo.name + '" placeholder="' + campo.label + '" ' + defVal + ' ' + reqAttr + '>';
        }

        div.innerHTML = '<label>' + campo.label + (req ? ' <span class="req">*</span>' : '') + '</label>' + input;
        grid.appendChild(div);

        textoBasePreview = d.texto_preview || "";

        renderizarPreview(textoBasePreview);

        vincularEventosPreview();
      });

      document.getElementById('stepSelecao').style.display    = 'none';
      document.getElementById('stepFormulario').style.display = 'block';

      setTimeout(function() {
        sigCanvas = initCanvas('canvasAssinatura');
      }, 250);
    })
    .catch(function(e) { toast('error', 'Erro ao carregar campos: ' + e.message); });
}

function renderizarPreview(textoBasePreview) {
    const box = document.getElementById('documentPreview');

    document.getElementById('preview-container').classList.remove('d-none');

    if (!textoBasePreview || !box) return;

    // 1. Limpa o "espaço duro" do Word (\u00a0) que quebra o parser de tabelas
    let md = textoBasePreview.replace(/\u00a0/g, " ");

    // 2. Substitui variáveis por spans
    md = md.replace(/\$?\s*\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
        return `<span class="preview-tag" data-key="${key}">${key}</span>`;
    });

    // 3. Renderiza com Marked
    // Importante: use marked.parse() se estiver na versão mais nova
    marked.setOptions({ 
        gfm: true, 
        tables: true, 
        breaks: true 
    });
    
    box.innerHTML = marked.parse(md);
    
    // 4. Sincroniza campos
    sincronizarSpansPreview(box);
}

function sincronizarSpansPreview(container) {
    document.querySelectorAll('#camposDinamicos input, #camposDinamicos select, #camposDinamicos textarea').forEach(input => {
        const key = input.name.replace('campo_', '');
        const valor = input.value.trim();
        const spans = container.querySelectorAll(`.preview-tag[data-key="${key}"]`);
        
        if (valor) {
            spans.forEach(s => {
                s.textContent = valor;
                s.classList.add('filled');
            });
        }
    });
}

function vincularEventosPreview() {
  const container = document.getElementById('camposDinamicos');
  
  container.addEventListener('input', (e) => {
      const input = e.target;
      if (!input.name) return;

      const key = input.name.replace('campo_', '');
      const valor = input.value.trim();

      const spans = document.querySelectorAll(`.preview-tag[data-key="${key}"]`);
      
      spans.forEach(span => {
          if (valor) {
              span.textContent = valor;
              span.classList.add('filled');
          } else {
              span.textContent = key;
              span.classList.remove('filled');
          }
      });
  });
}

function limparCanvas() {
  if (sigCanvas) sigCanvas.clear();
}

function voltarPasso() {
  document.getElementById('stepFormulario').style.display = 'none';
  document.getElementById('stepSelecao').style.display    = 'block';
}

function novoFormulario() {
  document.getElementById('stepResultado').style.display  = 'none';
  document.getElementById('stepSelecao').style.display    = 'block';
  document.getElementById('sel_tipo').value        = '';
  document.getElementById('sel_funcionario').value = '';
  document.getElementsByClassName('preview-container')[0].classList.add('d-none');
  sigCanvas = null;
}

function gerarDocumento() {
  if (!sigCanvas || sigCanvas.isEmpty()) {
    toast('error', 'Por favor, assine o documento antes de continuar.');
    return;
  }

  var valido = true;
  document.querySelectorAll('#camposDinamicos [required]').forEach(function(el) {
    if (!el.value.trim()) { el.style.borderColor = 'var(--red)'; valido = false; }
    else el.style.borderColor = '';
  });
  if (!valido) { toast('error', 'Preencha todos os campos obrigatórios.'); return; }

  var btn = document.getElementById('btnGerar');
  btnLoading(btn, true);

  var fd = new FormData();
  fd.append('id_tipo_formulario', document.getElementById('sel_tipo').value);
  fd.append('cod_funcionario',    document.getElementById('sel_funcionario').value);
  fd.append('assinatura_base64',  sigCanvas.toBase64());

  document.querySelectorAll('#camposDinamicos input, #camposDinamicos select, #camposDinamicos textarea')
    .forEach(function(el) { if (el.name) fd.append(el.name, el.value); });

  fetch('actions/gerar_documento.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(texto => {
      console.log("RESPOSTA DO SERVIDOR:", texto);
      try {
        const d = JSON.parse(texto);
        if (d.sucesso) {
          document.getElementById('linkDownload').href = d.url_download;
          document.getElementById('stepFormulario').style.display = 'none';
          document.getElementById('stepResultado').style.display  = 'block';
        } else {
          toast('error', d.mensagem);
        }
      } catch (e) {
        console.error("Erro ao converter JSON:", texto);
        toast('error', 'Erro no servidor. Veja o console (F12)');
      }
    })
    .catch(function(e) { toast('error', 'Erro: ' + e.message); })
    .finally(function()  { btnLoading(btn, false); });
}
</script>
