<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$id = intval($_GET['id'] ?? 0);
$db = db();

$stmt = $db->prepare("SELECT texto_preview FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
$stmt->execute([$id]);
$res = $stmt->fetch();

echo json_encode(['texto' => $res['texto_preview'] ?? '']);