<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);

/** Render a navbar <a> with active state based on current page filename. */
function navLink(string $page, string $label, string $current): string
{
    $isActive = ($page === $current);
    $classes   = 'nav-link' . ($isActive ? ' active' : '');
    $aria      = $isActive ? ' aria-current="page"' : '';
    return sprintf(
        '<a class="%s" href="%s/%s"%s>%s</a>',
        $classes,
        APP_BASE,
        $page,
        $aria,
        htmlspecialchars($label)
    );
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($prefs['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($pageTitle ?? 'Home') . ' — Peddi') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Inter:wght@400;500;600&family=Noto+Serif+Telugu:wght@400;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/main.css">
    <?php if (!empty($loadDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <?php endif; ?>
</head>
<body>

<!-- data-app-base lets JS files read APP_BASE without inline PHP in script tags -->
<div id="appConfig" data-app-base="<?= htmlspecialchars(APP_BASE) ?>" hidden></div>

<nav class="navbar navbar-expand-lg navbar-dark navbar-peddi">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none"
           href="<?= APP_BASE ?>/index.php">
            <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
                 alt="Peddi logo" height="34" style="width:auto">
            <span class="peddi-logo peddi-logo-nav">Peddi</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php $_navUser = getCurrentUser(); ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><?= navLink('index.php',       'Home',        $currentPage) ?></li>
                <li class="nav-item"><?= navLink('catalog.php',     'Catalog',     $currentPage) ?></li>
                <li class="nav-item"><?= navLink('search.php',      'Search',      $currentPage) ?></li>
                <li class="nav-item"><?= navLink('preferences.php', 'Preferences', $currentPage) ?></li>
                <li class="nav-item"><?= navLink('about.php',       'About',       $currentPage) ?></li>
                <?php if ($_navUser['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link fw-semibold admin-nav-link<?= ($currentPage === 'index.php' && str_contains($_SERVER['PHP_SELF'], '/admin/')) ? ' active' : '' ?>"
                       href="<?= APP_BASE ?>/admin/index.php"
                       title="Admin panel">
                        <i class="bi bi-shield-lock-fill me-1"></i>Admin
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($_navUser['role'] !== 'admin'): ?>
                <a href="<?= APP_BASE ?>/admin/login.php"
                   class="btn btn-sm btn-outline-secondary"
                   title="Admin login">
                    <i class="bi bi-person-lock me-1"></i>Admin Login
                </a>
                <?php endif; ?>
                <button id="themeToggle"
                        class="btn btn-outline-secondary btn-sm"
                        title="Toggle light / dark theme"
                        aria-label="Toggle theme">
                    <i class="bi <?= $prefs['theme'] === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
