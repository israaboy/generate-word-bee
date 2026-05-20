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
