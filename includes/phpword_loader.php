<?php
/**
 * Autoloader manual para PHPWord sem Composer.
 * Aponta para: public_html/app/generate_word/includes/PhpOffice/src/
 */

define('PHPWORD_SRC', __DIR__ . '/PhpOffice/src/PhpWord/');

spl_autoload_register(function (string $class): void {
    // Só processa classes do namespace PhpOffice\PhpWord
    if (strpos($class, 'PhpOffice\\PhpWord') !== 0) {
        return;
    }

    // Remove o prefixo do namespace e converte \ em /
    $relativo  = str_replace('PhpOffice\\PhpWord\\', '', $class);
    $relativo  = str_replace('\\', DIRECTORY_SEPARATOR, $relativo);
    $caminho   = PHPWORD_SRC . $relativo . '.php';

    if (file_exists($caminho)) {
        require_once $caminho;
    }
});

// Dependências diretas que o PHPWord precisa
// ZipArchive — extensão nativa do PHP, não precisa carregar
// XMLWriter  — extensão nativa do PHP, não precisa carregar

// Carrega os arquivos base que o TemplateProcessor depende
$arquivos_base = [
    PHPWORD_SRC . 'Settings.php',
    PHPWORD_SRC . 'Shared/ZipArchive.php',
    PHPWORD_SRC . 'Shared/String.php',
    PHPWORD_SRC . 'Shared/XMLWriter.php',
    PHPWORD_SRC . 'Shared/Converter.php',
    PHPWORD_SRC . 'Exception/Exception.php',
    PHPWORD_SRC . 'Exception/CopyFileException.php',
    PHPWORD_SRC . 'Exception/CreateTemporaryFileException.php',
    PHPWORD_SRC . 'TemplateProcessor.php',
];

foreach ($arquivos_base as $arquivo) {
    if (file_exists($arquivo)) {
        require_once $arquivo;
    }
}
