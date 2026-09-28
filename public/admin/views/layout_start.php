<?php

$pageTitle = $pageTitle ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
$navigation = [
    'dashboard' => ['Painel', '/admin/dashboard.php'],
    'products' => ['Produtos', '/admin/products.php'],
    'categories' => ['Categorias', '/admin/categories.php'],
    'prices' => ['Preços', '/admin/prices.php'],
    'sources' => ['Fontes', '/admin/sources.php'],
    'users' => ['Usuários', '/admin/users.php'],
    'settings' => ['Configurações', '/admin/settings.php'],
];
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Economy API</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body class="admin-page">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="brand-lockup admin-brand" href="/admin/dashboard.php" aria-label="Economy API, painel">
                <span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>
                <span>economy<span class="brand-period">.</span></span>
            </a>
            <div class="nav-section-label">OPERAÇÃO</div>
            <nav class="admin-navigation" aria-label="Navegação principal">
                <?php foreach ($navigation as $key => [$label, $href]): ?>
                    <a class="admin-nav-link<?= $activePage === $key ? ' is-active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === $key ? ' aria-current="page"' : '' ?>>
                        <span class="nav-index"><?= str_pad((string) (array_search($key, array_keys($navigation), true) + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-bottom">
                <span class="sidebar-status-dot"></span>
                <span>API <strong>ONLINE</strong></span>
                <span class="sidebar-version">V 1.0</span>
            </div>
        </aside>

        <div class="admin-workspace">
            <header class="admin-topbar">
                <div class="topbar-crumb">ECONOMY API <span>/</span> ADMINISTRAÇÃO</div>
                <div class="topbar-user">
                    <span class="topbar-avatar">A</span>
                    <span><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Administrador', ENT_QUOTES, 'UTF-8') ?></span>
                    <form class="logout-form" action="/admin/logout.php" method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(adminCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button class="logout-button" type="submit" aria-label="Sair da conta" title="Sair">Sair</button>
                    </form>
                </div>
            </header>
            <main class="admin-content">
                <div class="admin-page-heading">
                    <div>
                        <p class="eyebrow">WORKSPACE <span>/ 01</span></p>
                        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>
                    <span class="heading-date"><?= date('d/m/Y') ?></span>
                </div>