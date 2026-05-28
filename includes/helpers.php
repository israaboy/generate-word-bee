<?php

function responder(bool $sucesso, string $mensagem, array $extra = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        array_merge(['sucesso' => $sucesso, 'mensagem' => $mensagem], $extra),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

function garantir_pasta(string $path): void {
    if (!is_dir($path)) mkdir($path, 0755, true);
}

function formatar_bytes(int $bytes): string {
    if ($bytes < 1024)        return $bytes . ' B';
    if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 2) . ' MB';
}

function slug(string $str): string {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[áàãâä]/u', 'a', $str);
    $str = preg_replace('/[éèêë]/u',  'e', $str);
    $str = preg_replace('/[íìîï]/u',  'i', $str);
    $str = preg_replace('/[óòõôö]/u', 'o', $str);
    $str = preg_replace('/[úùûü]/u',  'u', $str);
    $str = preg_replace('/[ç]/u',     'c', $str);
    $str = preg_replace('/[^a-z0-9]+/', '_', $str);
    return trim($str, '_');
}

function extrair_placeholders_docx(string $caminho): array {
    if (!class_exists('ZipArchive')) return [];
    $zip = new ZipArchive();
    if ($zip->open($caminho) !== true) return [];
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!$xml) return [];
    preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $xml, $m);
    return array_values(array_unique($m[1]));
}

function obter_data_extenso() {
    // Requer extensão 'intl' ativa no PHP
    $fmt = new IntlDateFormatter(
        'pt_BR',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'America/Sao_Paulo',
        IntlDateFormatter::GREGORIAN
    );
    return $fmt->format(new DateTime());
}

function obter_campos_globais($func = []) {
    return [
        'data_hoje_extenso'       => "São Paulo, " . obter_data_extenso(),
        'data_hoje'               => date('d/m/Y'),
        'ano_atual'               => date('Y'),
        'empresa_nome'            => 'Sua Empresa LTDA', 
        
        // Dados do Funcionário
        'nome_funcionario'        => $func['nome_funcionario'] ?? '',
        'cpf'                     => $func['cpf'] ?? '',
        'cargo'                   => $func['cargo_nome'] ?? ($func['cod_cargo'] ?? ''),
        
        // Dados Fixos de Signatários (Texto)
        'nome_willian'            => 'WILLIAN JUN KOBAYASHI',
        'cargo_willian'           => 'CEO',
        'nome_test1'              => 'JULIANA QUEIROZ DOS SANTOS',
        'nome_test2'              => 'ROSEMEIRE MANGILI DE FIGUEIREDO',

        // Assinaturas (Caminho da Imagem)
        // Certifique-se de que esses arquivos existem na pasta assets/images/
        'assinatura_willian'      => BASE_PATH . '/assets/images/assinatura_willian.png',
        'assinatura_test1'        => BASE_PATH . '/assets/images/assinatura_test1.png',
        'assinatura_test2'        => BASE_PATH . '/assets/images/assinatura_test2.png',
    ];
}

function aplicar_formatacao(string $valor, string $formato) {
    if (empty($valor)) return '';
    switch ($formato) {
        case 'uppercase': return mb_strtoupper($valor);
        case 'lowercase': return mb_strtolower($valor);
        case 'date_br':   return date('d/m/Y', strtotime($valor));
        case 'money':     return 'R$ ' . number_format((float)$valor, 2, ',', '.');
        case 'cpf':
            $valor = preg_replace('/[^0-9]/', '', $valor);
            return substr($valor,0,3).'.'.substr($valor,3,3).'.'.substr($valor,6,3).'-'.substr($valor,9,2);
        default: return $valor;
    }
}