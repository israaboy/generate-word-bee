<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$id = intval($_GET['id_tipo'] ?? 0);
if (!$id) responder(false, 'id_tipo inválido.');

$stmt = db()->prepare("SELECT json_estrutura_campos, texto_preview FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) responder(false, 'Formulário não encontrado.');

$campos = json_decode($row['json_estrutura_campos'] ?? '[]', true);
responder(true, 'OK', ['campos' => is_array($campos) ? $campos : [], 'texto_preview' => $row['texto_preview']]);
