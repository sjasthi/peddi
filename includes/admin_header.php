<?php
// db.php provides $pdo and $prefs; auth.php + requireAdmin() called by the page already.
require_once __DIR__ . '/db.php';

$_adminUser  = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

function adminNavLink(string $file, string $label, string $current): string
{
    $active  = ($file === $current);
    $classes = 'nav-link text-white' . ($active ? ' active fw-semibold' : ' opacity-75');
    $aria    = $active ? ' aria-current="page"' : '';
    return sprintf(
        '<a class="%s" href="%s/admin/%s"%s>%s</a>',
        $classes, APP_BASE, $file, $aria, htmlspecialchars($label)
    );
}

function adminDropdownLink(string $file, string $label, string $current): string
{
    $active  = ($file === $current);
    $classes = 'dropdown-item' . ($active ? ' active' : '');
    $aria    = $active ? ' aria-current="page"' : '';
    return sprintf(
        '<a class="%s" href="%s/admin/%s"%s>%s</a>',
        $classes, APP_BASE, $file, $aria, htmlspecialchars($label)
    );
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?= htmlspecialchars($prefs['theme']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(($pageTitle ?? 'Admin') . ' — Peddi Admin') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/main.css">
</head>
<body>

<div id="appConfig" data-app-base="<?= htmlspecialchars(APP_BASE) ?>" hidden></div>

<nav class="navbar navbar-expand-lg border-bottom shadow-sm" style="background:#1a1d23">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none"
           href="<?= APP_BASE ?>/admin/index.php">
            <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
                 alt="Peddi logo" height="34" style="width:auto">
            <span class="peddi-logo peddi-logo-nav">Peddi</span>
        </a>
        <span class="text-warning opacity-50 small fw-light ms-1 d-none d-lg-inline"
              style="letter-spacing:2px; margin-top:2px">ADMIN</span>

        <button class="navbar-toggler border-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#adminNav"
                aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><?= adminNavLink('dashboard.php',    'Dashboard',    $currentPage) ?></li>
                <li class="nav-item"><?= adminNavLink('dictionaries.php', 'Dictionaries', $currentPage) ?></li>
                <li class="nav-item"><?= adminNavLink('entries.php',      'Entries',      $currentPage) ?></li>
                <?php
                $toolPages   = ['upload.php', 'compare.php', 'integrity.php', 'export.php'];
                $toolsActive = in_array($currentPage, $toolPages, true);
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-white <?= $toolsActive ? 'fw-semibold' : 'opacity-75' ?>"
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-tools me-1"></i>Tools
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><?= adminDropdownLink('upload.php',    'Upload / Import', $currentPage) ?></li>
                        <li><?= adminDropdownLink('compare.php',   'Compare',         $currentPage) ?></li>
                        <li><?= adminDropdownLink('integrity.php', 'Integrity',       $currentPage) ?></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><?= adminDropdownLink('export.php',    'Export',          $currentPage) ?></li>
                    </ul>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <span class="text-white opacity-50 small d-none d-lg-inline">
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_adminUser['username']) ?>
                </span>
                <a href="<?= APP_BASE ?>/index.php"
                   class="btn btn-sm btn-outline-secondary text-white border-secondary"
                   title="View public site" target="_blank">
                    <i class="bi bi-house"></i>
                </a>
                <a href="<?= APP_BASE ?>/admin/logout.php"
                   class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
                <button id="themeToggle"
                        class="btn btn-sm btn-outline-secondary text-white border-secondary"
                        title="Toggle light / dark theme" aria-label="Toggle theme">
                    <i class="bi <?= $prefs['theme'] === 'dark' ? 'bi-sun-fill' : 'bi-moon-fill' ?>"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<main class="container-fluid px-4 py-4">
