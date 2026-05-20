<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// Captura qualquer output inesperado (warnings/notices antes do JSON)
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Captura erros fatais e os converte em JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Erro fatal: ' . $err['message'] . ' em ' . basename($err['file']) . ':' . $err['line']
        ]);
    }
});

try {

// ── Recebe POST ────────────────────────────────────────────────────────────
$id_tipo         = intval($_POST['id_tipo_formulario'] ?? 0);
$cod_funcionario = intval($_POST['cod_funcionario']    ?? 0);
$assinatura64    = $_POST['assinatura_base64']          ?? '';

if (!$id_tipo || !$cod_funcionario) responder(false, 'Parâmetros obrigatórios ausentes.');
if (empty($assinatura64))           responder(false, 'Assinatura não recebida.');

$db = db();

// ── Carrega tipo de formulário ─────────────────────────────────────────────
$stmt = $db->prepare("SELECT nome_formulario, template_word_path, json_estrutura_campos FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
$stmt->execute([$id_tipo]);
$tipo = $stmt->fetch();
if (!$tipo) responder(false, 'Tipo de formulário não encontrado. ID: ' . $id_tipo);

if (empty($tipo['template_word_path'])) {
    responder(false, 'Este formulário não tem template Word cadastrado. Faça o upload em Templates.');
}

$caminho_template = BASE_PATH . '/' . $tipo['template_word_path'];
if (!file_exists($caminho_template)) {
    responder(false, 'Arquivo .docx não encontrado: ' . $caminho_template);
}

// ── Carrega funcionário ────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT nome_funcionario, cpf, cod_cargo FROM tab_cadastro_funcionarios WHERE cod_funcionario = ?");
$stmt->execute([$cod_funcionario]);
$func = $stmt->fetch();
if (!$func) responder(false, 'Funcionário não encontrado. COD: ' . $cod_funcionario);

// ── Salva assinatura PNG ───────────────────────────────────────────────────
garantir_pasta(DIR_ASSINATURAS);
$nome_assin = 'sig_' . $id_tipo . '_' . $cod_funcionario . '_' . time() . '.png';
$path_assin = DIR_ASSINATURAS . $nome_assin;
$rel_assin  = 'uploads/assinaturas/' . $nome_assin;

$b64_limpo = preg_replace('#^data:image/\w+;base64,#', '', $assinatura64);
$bytes     = base64_decode($b64_limpo);
if ($bytes === false || strlen($bytes) < 50) {
    responder(false, 'Assinatura base64 inválida.');
}
file_put_contents($path_assin, $bytes);

// ── Campos dinâmicos do POST ───────────────────────────────────────────────
$estrutura        = json_decode($tipo['json_estrutura_campos'] ?? '[]', true);
$campos_dinamicos = [];
$erros            = [];

foreach ((array)$estrutura as $campo) {
    $name     = $campo['name']     ?? '';
    $required = $campo['required'] ?? true;
    $default  = (string)($campo['default']  ?? '');
    $valor    = trim($_POST['campo_' . $name] ?? $default);

    if ($required && $valor === '') {
        $erros[] = 'Campo obrigatório: ' . ($campo['label'] ?? $name);
        continue;
    }

    $tipo_campo = $campo['type'] ?? 'text';
    $campos_dinamicos[$name] = ($tipo_campo === 'number') ? intval($valor) : htmlspecialchars($valor);
}
if (!empty($erros)) responder(false, implode(' | ', $erros));

// ── PHPWord ────────────────────────────────────────────────────────────────
if (!file_exists(PHPWORD_AUTOLOAD)) {
    responder(false, 'PHPWord autoload não encontrado. Caminho: ' . PHPWORD_AUTOLOAD);
}
require_once PHPWORD_AUTOLOAD;

$tpl = new \PhpOffice\PhpWord\TemplateProcessor($caminho_template);

// Campos fixos do funcionário
$tpl->setValue('nome_funcionario', $func['nome_funcionario'] ?? '');
$tpl->setValue('cpf',              $func['cpf']              ?? '');
$tpl->setValue('cargo',            $func['cargo']            ?? '');
$tpl->setValue('data_assinatura',  date('d/m/Y H:i'));

// Campos dinâmicos
foreach ($campos_dinamicos as $k => $v) {
    $tpl->setValue($k, is_array($v) ? implode(', ', $v) : (string)$v);
}

// Assinatura como imagem — só insere se o placeholder existir no template
$variaveis = $tpl->getVariables();
if (in_array('assinatura_digital', $variaveis)) {
    $tpl->setImageValue('assinatura_digital', [
        'path'   => $path_assin,
        'width'  => 200,
        'height' => 80,
        'ratio'  => false,
    ]);
}

// ── Salva DOCX ────────────────────────────────────────────────────────────
garantir_pasta(DIR_GERADOS);
$nome_saida = 'doc_' . $cod_funcionario . '_' . $id_tipo . '_' . time() . '.docx';
$path_saida = DIR_GERADOS . $nome_saida;
$rel_saida  = 'uploads/gerados/' . $nome_saida;

$tpl->saveAs($path_saida);

if (!file_exists($path_saida)) {
    responder(false, 'PHPWord não gerou o arquivo. Verifique permissão da pasta: ' . DIR_GERADOS);
}

// ── Registra no banco ──────────────────────────────────────────────────────
$stmt = $db->prepare("
    INSERT INTO tab_formularios_preenchidos
        (id_tipo_formulario_fk, cod_funcionario_fk, data_preenchimento,
         dados_preenchidos_json, caminho_documento_gerado, assinatura_digital_path, status)
    VALUES (?, ?, NOW(), ?, ?, ?, 'Concluído')
");
$stmt->execute([
    $id_tipo,
    $cod_funcionario,
    json_encode($campos_dinamicos, JSON_UNESCAPED_UNICODE),
    $rel_saida,
    $rel_assin,
]);
$id_gerado = $db->lastInsertId();

// Limpa qualquer output residual antes de responder
ob_clean();
responder(true, 'Documento gerado com sucesso.', [
    'id_gerado'    => (int) $id_gerado,
    'url_download' => BASE_URL . '/' . $rel_saida,
    'nome_arquivo' => $nome_saida,
]);

} catch (Exception $e) {
    ob_clean();
    responder(false, 'Exceção: ' . $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']');
} catch (Error $e) {
    ob_clean();
    responder(false, 'Erro: ' . $e->getMessage() . ' [' . basename($e->getFile()) . ':' . $e->getLine() . ']');
}
