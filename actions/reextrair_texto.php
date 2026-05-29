<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = intval($_GET['id'] ?? 0);
$db = db();

$stmt = $db->prepare("SELECT template_word_path FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res) die(json_encode(['sucesso' => false, 'mensagem' => 'Template não encontrado.']));

$caminho = BASE_PATH . '/' . $res['template_word_path'];
$markdown = extrair_markdown_docx($caminho);

echo json_encode(['sucesso' => true, 'markdown' => $markdown]);