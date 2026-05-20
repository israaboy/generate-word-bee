<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$db   = db();
$acao = $_POST['acao'] ?? '';

if ($acao === 'excluir_registro') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) responder(false, 'ID inválido.');

    $stmt = $db->prepare("SELECT caminho_documento_gerado, assinatura_digital_path FROM tab_formularios_preenchidos WHERE id_formulario_preenchido = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) responder(false, 'Registro não encontrado.');

    // Remove arquivos físicos
    foreach (['caminho_documento_gerado', 'assinatura_digital_path'] as $campo) {
        if (!empty($row[$campo])) {
            $f = BASE_PATH . '/' . $row[$campo];
            if (file_exists($f)) @unlink($f);
        }
    }

    $db->prepare("DELETE FROM tab_formularios_preenchidos WHERE id_formulario_preenchido = ?")->execute([$id]);
    responder(true, 'Registro excluído.');
}

responder(false, 'Ação inválida.');
