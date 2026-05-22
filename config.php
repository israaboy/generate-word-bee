<?php

require_once __DIR__ . '/vendor/autoload.php';

setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// ── Banco de dados ─────────────────────────────────────────────────────────
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_CHARSET', 'utf8mb4');

// ── Caminhos ───────────────────────────────────────────────────────────────
define('BASE_PATH',  __DIR__);
define('BASE_URL',   '/app/generate_word');
define('UPLOAD_DIR', BASE_PATH . '/uploads/');

define('DIR_TEMPLATES',  UPLOAD_DIR . 'templates/');
define('DIR_ASSINATURAS',UPLOAD_DIR . 'assinaturas/');
define('DIR_GERADOS',    UPLOAD_DIR . 'gerados/');

// ── PHPWord ────────────────────────────────────────────────────────────────
define('PHPWORD_AUTOLOAD', $_ENV['PHPWORD_AUTOLOAD']??
    '/home/plugben1/public_html/app/generate_word/includes/phpword_loader.php'
);

// ── Timezone ───────────────────────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');
