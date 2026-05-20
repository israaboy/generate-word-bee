<?php
// ── Banco de dados ─────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'plugben1_plugandplay_db_app_sistema');
define('DB_USER', 'plugben1_user_app');
define('DB_PASS', 'LIe|F.Dv17');
define('DB_CHARSET', 'utf8mb4');

// ── Caminhos ───────────────────────────────────────────────────────────────
define('BASE_PATH',  __DIR__);
define('BASE_URL',   '/app/generate_word');
define('UPLOAD_DIR', BASE_PATH . '/uploads/');

define('DIR_TEMPLATES',  UPLOAD_DIR . 'templates/');
define('DIR_ASSINATURAS',UPLOAD_DIR . 'assinaturas/');
define('DIR_GERADOS',    UPLOAD_DIR . 'gerados/');

// ── PHPWord ────────────────────────────────────────────────────────────────
// define('PHPWORD_AUTOLOAD','/public_html/app/generate_word/includes/PHPWord-1.4.0/vendor/autoload.php'
// );
// ✅ Correto — ajuste o caminho para onde o PHPWord realmente está
// define('PHPWORD_AUTOLOAD',
//     '/home/plugben1/public_html/app/generate_word/includes/PhpOffice/vendor/autoload.php'
// );

define('PHPWORD_AUTOLOAD',
    '/home/plugben1/public_html/app/generate_word/includes/phpword_loader.php'
);

// ── Timezone ───────────────────────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');
