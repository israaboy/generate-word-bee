<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
$db = db();

$id = intval($_POST['id'] ?? 0);
$texto = $_POST['texto_preview'] ?? '';

if (!$id) responder(false, 'ID inválido.');

try {
    $stmt = $db->prepare("UPDATE tab_tipos_formularios SET texto_preview = ? WHERE id_tipo_formulario = ?");
    $stmt->execute([$texto, $id]);
    
    responder(true, 'Preview atualizado com sucesso!');
} catch (Exception $e) {
    responder(false, 'Erro ao salvar: ' . $e->getMessage());
}