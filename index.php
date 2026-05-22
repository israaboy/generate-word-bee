<?php require_once __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8">
    <title>Gerador de Formulários - Bee</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/generate_word/assets/css/app.css">
    <link rel="shortcut icon" href="assets\images\plug-icon.ico" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  </head>
  <body>
    <nav class="navbar">
      <a class="navbar-brand ps-3" href="/generate_word/">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Formulários
      </a>
      <div class="navbar-links pe-3">
        <a href="/generate_word/#dashboard"  class="nav-link">Dashboard</a>
        <a href="/generate_word/#formulario" class="nav-link">Preencher</a>
        <a href="/generate_word/#upload"     class="nav-link">Templates</a>
        <a href="/generate_word/#admin"      class="nav-link">Admin</a>
      </div>
    </nav>
    <main id="app" class="p-20">
        <div class="loader">Carregando...</div>
    </main>
    <script src="/generate_word/assets/js/app.js"></script>
    <script src="/generate_word/assets/js/router.js"></script>
  </body>
</html>
