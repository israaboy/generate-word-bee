<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

$db = db();

$total_tipos      = $db->query("SELECT COUNT(*) FROM tab_tipos_formularios")->fetchColumn();
$total_preenchidos = $db->query("SELECT COUNT(*) FROM tab_formularios_preenchidos")->fetchColumn();
$total_concluidos  = $db->query("SELECT COUNT(*) FROM tab_formularios_preenchidos WHERE status = 'Concluído'")->fetchColumn();

$recentes = $db->query("
    SELECT fp.id_formulario_preenchido, fp.data_preenchimento, fp.status,
           tf.nome_formulario,
           fc.nome_funcionario
    FROM tab_formularios_preenchidos fp
    LEFT JOIN tab_tipos_formularios tf     ON tf.id_tipo_formulario = fp.id_tipo_formulario_fk
    LEFT JOIN tab_cadastro_funcionarios fc ON fc.cod_funcionario    = fp.cod_funcionario_fk
    ORDER BY fp.data_preenchimento DESC
    LIMIT 8
")->fetchAll();

layout_head('Dashboard');
?>

<div class="page-header-row">
  <div class="page-header">
    <h1>Dashboard</h1>
    <p>Visão geral do sistema de formulários</p>
  </div>
  <div class="flex gap-1">
    <a href="<?= BASE_URL ?>/upload.php"     class="btn btn-outline btn-sm">+ Novo Template</a>
    <a href="<?= BASE_URL ?>/formulario.php" class="btn btn-primary btn-sm">+ Preencher</a>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-card-label">Tipos de Formulário</div>
    <div class="stat-card-value blue"><?= $total_tipos ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-label">Documentos Gerados</div>
    <div class="stat-card-value"><?= $total_preenchidos ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-card-label">Concluídos</div>
    <div class="stat-card-value green"><?= $total_concluidos ?></div>
  </div>
</div>

<div class="card">
  <div class="card-title">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
    Documentos Recentes
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Formulário</th>
          <th>Funcionário</th>
          <th>Data</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentes)): ?>
        <tr><td colspan="6" class="text-muted" style="text-align:center;padding:2rem;">Nenhum documento gerado ainda.</td></tr>
        <?php else: foreach ($recentes as $r): ?>
        <tr>
          <td class="text-mono"><?= $r['id_formulario_preenchido'] ?></td>
          <td><?= htmlspecialchars($r['nome_formulario'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['nome_funcionario'] ?? '—') ?></td>
          <td class="text-muted"><?= date('d/m/Y H:i', strtotime($r['data_preenchimento'])) ?></td>
          <td>
            <?php
              if ($r['status'] === 'Concluído')     $badge = 'green';
              elseif ($r['status'] === 'Pendente')  $badge = 'yellow';
              else                                   $badge = 'gray';
            ?>
            <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
          </td>
          <td class="text-right">
            <a href="<?= BASE_URL ?>/admin.php?ver=<?= $r['id_formulario_preenchido'] ?>" class="btn btn-outline btn-sm">Ver</a>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php layout_foot(); ?>
