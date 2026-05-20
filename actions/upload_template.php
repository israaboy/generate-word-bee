<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
$db   = db();
$acao = $_POST['acao'] ?? '';

// ── Excluir template ───────────────────────────────────────────────────────
if ($acao === 'excluir_template') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) responder(false, 'ID inválido.');

    $stmt = $db->prepare("SELECT template_word_path FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) responder(false, 'Template não encontrado.');

    // Remove arquivo
    if ($row['template_word_path']) {
        $path = BASE_PATH . '/' . $row['template_word_path'];
        if (file_exists($path)) @unlink($path);
    }

    $db->prepare("DELETE FROM tab_tipos_formularios WHERE id_tipo_formulario = ?")->execute([$id]);
    responder(true, 'Template excluído.');
}

// ── Upload template ────────────────────────────────────────────────────────
if ($acao !== 'upload_template') responder(false, 'Ação inválida.');

$nome = trim($_POST['nome_formulario'] ?? '');
$json = trim($_POST['json_estrutura_campos'] ?? '[]');

if (!$nome) responder(false, 'Nome do formulário obrigatório.');
if (empty($_FILES['arquivo_docx']['tmp_name'])) responder(false, 'Nenhum arquivo recebido.');

$arquivo  = $_FILES['arquivo_docx'];
$extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
if ($extensao !== 'docx')         responder(false, 'Apenas .docx são aceitos.');
if ($arquivo['error'] !== UPLOAD_ERR_OK) responder(false, 'Erro no upload: ' . $arquivo['error']);

$estrutura = json_decode($json, true);
if (!is_array($estrutura)) responder(false, 'JSON de campos inválido.');

garantir_pasta(DIR_TEMPLATES);
$nome_arquivo  = slug($nome) . '_' . time() . '.docx';
$caminho_final = DIR_TEMPLATES . $nome_arquivo;
$caminho_rel   = 'uploads/templates/' . $nome_arquivo;

if (!move_uploaded_file($arquivo['tmp_name'], $caminho_final)) {
    responder(false, 'Falha ao salvar o arquivo no servidor.');
}

// Extrai placeholders
$placeholders = extrair_placeholders_docx($caminho_final);

// Salva no banco
$stmt = $db->prepare("
    INSERT INTO tab_tipos_formularios (nome_formulario, template_word_path, json_estrutura_campos)
    VALUES (?, ?, ?)
");
$stmt->execute([$nome, $caminho_rel, json_encode($estrutura, JSON_UNESCAPED_UNICODE)]);
$id = $db->lastInsertId();

responder(true, 'Template salvo.', [
    'id_tipo_formulario' => (int) $id,
    'arquivo'            => $nome_arquivo,
    'placeholders'       => $placeholders,
]);
