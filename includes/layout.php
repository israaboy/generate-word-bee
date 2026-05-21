<?php
function layout_head(string $titulo = 'Formulários'): void { ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> — Plug Benefícios</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/generate_word/assets/css/app.css">
</head>
<body>
<nav class="navbar">
  <a class="navbar-brand" href="/generate_word/">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
    Formulários
  </a>
  <div class="navbar-links">
    <a href="<?= BASE_URL ?>/"              class="<?= (basename($_SERVER['PHP_SELF']) === 'index.php')   ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= BASE_URL ?>/formulario.php" class="<?= (basename($_SERVER['PHP_SELF']) === 'formulario.php') ? 'active' : '' ?>">Preencher</a>
    <a href="<?= BASE_URL ?>/upload.php"     class="<?= (basename($_SERVER['PHP_SELF']) === 'upload.php')     ? 'active' : '' ?>">Templates</a>
    <a href="<?= BASE_URL ?>/admin.php"      class="<?= (basename($_SERVER['PHP_SELF']) === 'admin.php')      ? 'active' : '' ?>">Admin</a>
  </div>
</nav>
<main class="container">
<?php }

function layout_foot(): void { ?>
</main>
<script src="/generate_word/assets/js/app.js"></script>
</body>
</html>
<?php }
