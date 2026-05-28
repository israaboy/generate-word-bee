<?php
// 1. Loader e Dependências
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// 2. Prevenção de "Output Sujo" (Garante JSON limpo)
ob_start();
header('Content-Type: application/json; charset=utf-8');

// 3. Tratamento de Erros Fatais
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Erro crítico: ' . $err['message'] . ' em ' . basename($err['file']) . ':' . $err['line']
        ]);
    }
});

try {
    $db = db();

    // --- RECEBIMENTO E VALIDAÇÃO ---
    $id_tipo         = intval($_POST['id_tipo_formulario'] ?? 0);
    $cod_funcionario = intval($_POST['cod_funcionario']    ?? 0);
    $assinatura64    = $_POST['assinatura_base64']          ?? '';

    if (!$id_tipo || !$cod_funcionario) responder(false, 'Selecione o formulário e o funcionário.');
    if (empty($assinatura64))           responder(false, 'A assinatura digital é obrigatória.');

    // --- CARREGAMENTO DE DADOS (Template e Funcionário) ---
    $stmt = $db->prepare("SELECT nome_formulario, template_word_path, json_estrutura_campos FROM tab_tipos_formularios WHERE id_tipo_formulario = ?");
    $stmt->execute([$id_tipo]);
    $tipo = $stmt->fetch();
    if (!$tipo || empty($tipo['template_word_path'])) responder(false, 'Template Word não configurado para este formulário.');

    $stmt = $db->prepare("SELECT nome_funcionario, cpf, cod_cargo FROM tab_cadastro_funcionarios WHERE cod_funcionario = ?");
    $stmt->execute([$cod_funcionario]);
    $func = $stmt->fetch();
    if (!$func) responder(false, 'Dados do funcionário não encontrados.');

    $caminho_template = BASE_PATH . '/' . $tipo['template_word_path'];
    if (!file_exists($caminho_template)) responder(false, 'Arquivo base .docx não encontrado no servidor.');

    // --- PROCESSAMENTO DA ASSINATURA DIGITAL (Canvas) ---
    garantir_pasta(DIR_ASSINATURAS);
    $nome_assin = 'sig_' . $id_tipo . '_' . $cod_funcionario . '_' . time() . '.png';
    $path_assin = DIR_ASSINATURAS . $nome_assin;
    $rel_assin  = 'uploads/assinaturas/' . $nome_assin;

    $b64_limpo = preg_replace('#^data:image/\w+;base64,#', '', $assinatura64);
    $bytes     = base64_decode($b64_limpo);
    if (!$bytes) responder(false, 'Falha ao processar imagem da assinatura.');
    file_put_contents($path_assin, $bytes);

    // --- INICIALIZAÇÃO DO PHPWORD ---
    $tpl = new \PhpOffice\PhpWord\TemplateProcessor($caminho_template);
    $placeholdersNoWord = $tpl->getVariables();

    // 1. Preenchimento de Campos Globais (Texto e Assinaturas Fixas)
    $dados_automaticos = obter_campos_globais($func);
    foreach ($dados_automaticos as $chave => $valor) {
        if (in_array($chave, $placeholdersNoWord)) {
            
            // Lógica para Assinaturas Fixas (Imagens)
            // Verifica se a chave começa com 'assinatura_' e não é a digital do canvas
            if (str_starts_with($chave, 'assinatura_') && $chave !== 'assinatura_digital') {
                if (!empty($valor) && file_exists($valor)) {
                    $tpl->setImageValue($chave, [
                        'path'   => $valor,
                        'width'  => 180,
                        'height' => 60,
                        'ratio'  => true
                    ]);
                }
            } else {
                // Preenchimento de texto comum (Data, Empresa, Nomes, etc)
                $tpl->setValue($chave, $valor);
            }
        }
    }

    // 2. Preenchimento Dinâmico (Campos do Formulário vindo do POST)
    $estrutura = json_decode($tipo['json_estrutura_campos'] ?? '[]', true);
    $campos_salvar_db = []; 

    foreach ((array)$estrutura as $campo) {
        $name     = $campo['name'];
        $formato  = $campo['format'] ?? '';
        $valor    = trim($_POST['campo_' . $name] ?? $campo['default'] ?? '');

        if (($campo['required'] ?? true) && $valor === '') {
            responder(false, "O campo '{$campo['label']}' é obrigatório.");
        }

        $valor_formatado = aplicar_formatacao($valor, $formato);
        $tpl->setValue($name, $valor_formatado);
        $campos_salvar_db[$name] = $valor_formatado;
    }

    // 3. Inserção da Assinatura Digital (Canvas capturado agora)
    if (in_array('assinatura_digital', $placeholdersNoWord)) {
        $tpl->setImageValue('assinatura_digital', [
            'path' => $path_assin, 'width' => 200, 'height' => 80, 'ratio' => false
        ]);
    }

    // --- SALVAMENTO DO ARQUIVO FINAL ---
    garantir_pasta(DIR_GERADOS);
    $nome_saida = 'doc_' . $cod_funcionario . '_' . time() . '.docx';
    $path_saida = DIR_GERADOS . $nome_saida;
    $rel_saida  = 'uploads/gerados/' . $nome_saida;

    $tpl->saveAs($path_saida);

    if (!file_exists($path_saida)) responder(false, 'Erro ao gerar arquivo final. Verifique permissões de escrita.');

    // --- PERSISTÊNCIA NO BANCO DE DADOS ---
    $stmt = $db->prepare("
        INSERT INTO tab_formularios_preenchidos 
        (id_tipo_formulario_fk, cod_funcionario_fk, data_preenchimento, dados_preenchidos_json, caminho_documento_gerado, assinatura_digital_path, status)
        VALUES (?, ?, NOW(), ?, ?, ?, 'Concluído')
    ");
    $stmt->execute([
        $id_tipo, 
        $cod_funcionario, 
        json_encode($campos_salvar_db, JSON_UNESCAPED_UNICODE), 
        $rel_saida, 
        $rel_assin
    ]);
    
    $id_gerado = $db->lastInsertId();

    // --- RESPOSTA FINAL ---
    ob_clean(); 
    responder(true, 'Documento gerado com sucesso!', [
        'id_gerado'    => (int) $id_gerado,
        'url_download' => '/generate_word/' . $rel_saida
    ]);

} catch (Exception $e) {
    ob_clean();
    responder(false, 'Erro no processamento: ' . $e->getMessage());
}