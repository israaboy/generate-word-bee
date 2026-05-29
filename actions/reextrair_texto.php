<?php
// Ativa exibição de erros para o log do servidor
ini_set('display_errors', 0); 
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');

try {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) throw new Exception("ID do template não fornecido.");

    $db = db();
    $stmt = $db->prepare("SELECT template_word_path FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetch();

    if (!$res) throw new Exception("Template não encontrado no banco de dados.");

    $caminho = BASE_PATH . '/' . $res['template_word_path'];

    // Verifica se o arquivo físico existe
    if (!file_exists($caminho)) {
        throw new Exception("Arquivo .docx não encontrado no caminho: " . $caminho);
    }

    // Verifica se a classe ZipArchive existe no servidor
    if (!class_exists('ZipArchive')) {
        throw new Exception("A extensão PHP 'zip' não está ativa neste servidor cPanel.");
    }

    $markdown = extrair_markdown_docx($caminho);

    echo json_encode([
        'sucesso' => true, 
        'markdown' => $markdown
    ]);

} catch (Exception $e) {
    // Retorna o erro em formato JSON para o seu SweetAlert ler
    http_response_code(500);
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => $e->getMessage()
    ]);
}