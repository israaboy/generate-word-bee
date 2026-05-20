<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

$db = db();

// Ver detalhe
$ver = intval($_GET['ver'] ?? 0);
$detalhe = null;
if ($ver) {
    $stmt = $db->prepare("
        SELECT fp.*, tf.nome_formulario, fc.nome_funcionario, fc.cpf, fc.cod_cargo
        FROM tab_formularios_preenchidos fp
        LEFT JOIN tab_tipos_formularios tf ON tf.id_tipo_formulario = fp.id_tipo_formulario_fk
        LEFT JOIN tab_cadastro_funcionarios fc ON fc.cod_funcionario = fp.cod_funcionario_fk
        WHERE fp.id_formulario_preenchido = ?
    ");
    $stmt->execute([$ver]);
    $detalhe = $stmt->fetch();
}

// Filtros
$filtro_tipo   = intval($_GET['tipo']   ?? 0);
$filtro_status = $_GET['status'] ?? '';
$filtro_busca  = trim($_GET['busca']  ?? '');

$where = ['1=1'];
$params = [];
if ($filtro_tipo)   { $where[] = 'fp.id_tipo_formulario_fk = ?'; $params[] = $filtro_tipo; }
if ($filtro_status) { $where[] = 'fp.status = ?';                 $params[] = $filtro_status; }
if ($filtro_busca)  { $where[] = 'fc.nome_funcionario LIKE ?';    $params[] = "%$filtro_busca%"; }

$sql = "
    SELECT fp.id_formulario_preenchido, fp.data_preenchimento, fp.status,
           fp.caminho_documento_gerado, fp.assinatura_digital_path,
           tf.nome_formulario, fc.nome_funcionario, fc.cpf
    FROM tab_formularios_preenchidos fp
    LEFT JOIN tab_tipos_formularios tf ON tf.id_tipo_formulario = fp.id_tipo_formulario_fk
    LEFT JOIN tab_cadastro_funcionarios fc ON fc.cod_funcionario = fp.cod_funcionario_fk
    WHERE " . implode(' AND ', $where) . "
    ORDER BY fp.data_preenchimento DESC
    LIMIT 50
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$registros = $stmt->fetchAll();

$tipos = $db->query("SELECT id_tipo_formulario, nome_formulario FROM tab_tipos_formularios ORDER BY nome_formulario")->fetchAll();

layout_head('Admin');
?>

<div class="page-header-row">
  <div class="page-header">
    <h1>Painel Admin</h1>
    <p>Documentos gerados e assinados</p>
  </div>
</div>

<?php if ($detalhe): ?>
<!-- Detalhe do documento -->
<div class="card mb-2">
  <div class="flex-between mb-2">
    <div class="card-title" style="margin-bottom:0;">Documento #<?= $ver ?></div>
    <a href="<?= BASE_URL ?>/admin.php" class="btn btn-outline btn-sm">← Voltar</a>
  </div>

  <div class="form-grid">
    <div class="field">
      <label>Formulário</label>
      <input type="text" value="<?= htmlspecialchars($detalhe['nome_formulario'] ?? '—') ?>" readonly>
    </div>
    <div class="field">
      <label>Funcionário</label>
      <input type="text" value="<?= htmlspecialchars($detalhe['nome_funcionario'] ?? '—') ?>" readonly>
    </div>
    <div class="field">
      <label>CPF</label>
      <input type="text" value="<?= htmlspecialchars($detalhe['cpf'] ?? '—') ?>" readonly>
    </div>
    <div class="field">
      <label>Cargo</label>
      <input type="text" value="<?= htmlspecialchars($detalhe['cargo'] ?? '—') ?>" readonly>
    </div>
    <div class="field">
      <label>Data de Preenchimento</label>
      <input type="text" value="<?= date('d/m/Y H:i', strtotime($detalhe['data_preenchimento'])) ?>" readonly>
    </div>
    <div class="field">
      <label>Status</label>
      <input type="text" value="<?= htmlspecialchars($detalhe['status']) ?>" readonly>
    </div>
  </div>

  <?php if ($detalhe['dados_preenchidos_json']): ?>
  <hr class="divider">
  <div class="card-title" style="margin-bottom:.75rem; font-size:.9rem;">Dados Preenchidos</div>
  <?php
    $dados = json_decode($detalhe['dados_preenchidos_json'], true);
    if (is_array($dados)):
  ?>
  <div class="form-grid">
    <?php foreach ($dados as $k => $v): ?>
    <div class="field">
      <label><?= htmlspecialchars($k) ?></label>
      <input type="text" value="<?= htmlspecialchars($v) ?>" readonly>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($detalhe['assinatura_digital_path']): ?>
  <hr class="divider">
  <div class="card-title" style="margin-bottom:.75rem; font-size:.9rem;">Assinatura Digital</div>
  <div style="border:1px solid var(--gray-200); border-radius:var(--radius); padding:.75rem; display:inline-block; background:#fff;">
    <img src="/<?= htmlspecialchars($detalhe['assinatura_digital_path']) ?>"
         alt="Assinatura" style="max-width:300px; max-height:100px; display:block;">
  </div>
  <?php endif; ?>

  <?php if ($detalhe['caminho_documento_gerado']): ?>
  <hr class="divider">
  <a href="/<?= htmlspecialchars($detalhe['caminho_documento_gerado']) ?>"
     class="btn btn-primary" download>⬇ Baixar Documento .docx</a>
  <?php endif; ?>
</div>

<?php else: ?>
<!-- Lista com filtros -->
<div class="card mb-2">
  <form method="get" action="">
    <div class="form-grid cols-3">
      <div class="field">
        <label>Funcionário</label>
        <input type="text" name="busca" value="<?= htmlspecialchars($filtro_busca) ?>" placeholder="Buscar por nome...">
      </div>
      <div class="field">
        <label>Tipo de Formulário</label>
        <select name="tipo">
          <option value="">Todos</option>
          <?php foreach ($tipos as $t): ?>
          <option value="<?= $t['id_tipo_formulario'] ?>" <?= $filtro_tipo == $t['id_tipo_formulario'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($t['nome_formulario']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="">Todos</option>
          <option value="Concluído" <?= $filtro_status === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
          <option value="Pendente"  <?= $filtro_status === 'Pendente'  ? 'selected' : '' ?>>Pendente</option>
        </select>
      </div>
    </div>
    <div class="flex gap-1 mt-1">
      <button class="btn btn-primary btn-sm" type="submit">Filtrar</button>
      <a href="<?= BASE_URL ?>/admin.php" class="btn btn-outline btn-sm">Limpar</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="flex-between mb-2">
    <div class="card-title" style="margin-bottom:0;">
      Documentos
      <span class="badge badge-gray" style="font-size:.72rem; font-weight:500;"><?= count($registros) ?></span>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Formulário</th>
          <th>Funcionário</th>
          <th>CPF</th>
          <th>Data</th>
          <th>Status</th>
          <th>Doc</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($registros)): ?>
        <tr><td colspan="8" class="text-muted" style="text-align:center; padding:2rem;">Nenhum registro encontrado.</td></tr>
        <?php else: foreach ($registros as $r): ?>
        <tr>
          <td class="text-mono"><?= $r['id_formulario_preenchido'] ?></td>
          <td><?= htmlspecialchars($r['nome_formulario'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['nome_funcionario'] ?? '—') ?></td>
          <td class="text-mono text-muted"><?= htmlspecialchars($r['cpf'] ?? '—') ?></td>
          <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['data_preenchimento'])) ?></td>
          <td>
          <?php
            if ($r['status'] === 'Concluído')     $badge = 'green';
            elseif ($r['status'] === 'Pendente')  $badge = 'yellow';
            else                                   $badge = 'gray';
          ?>
            <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
          </td>
          <td>
            <?php if ($r['caminho_documento_gerado']): ?>
            <a href="/<?= htmlspecialchars($r['caminho_documento_gerado']) ?>" class="btn btn-outline btn-sm" download>⬇</a>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-right">
            <a href="?ver=<?= $r['id_formulario_preenchido'] ?>" class="btn btn-outline btn-sm">Ver</a>
            <button class="btn btn-outline btn-sm btn-danger" onclick="excluir(<?= $r['id_formulario_preenchido'] ?>)">✕</button>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
async function excluir(id) {
  if (!confirm('Excluir este registro? O arquivo gerado também será removido.')) return;
  const fd = new FormData();
  fd.append('acao', 'excluir_registro');
  fd.append('id', id);
  const r = await fetch('<?= BASE_URL ?>/actions/admin_action.php', { method: 'POST', body: fd });
  const d = await r.json();
  if (d.sucesso) { toast('success', 'Registro excluído.'); setTimeout(() => location.reload(), 1200); }
  else toast('error', d.mensagem);
}
</script>
<?php layout_foot(); ?>
