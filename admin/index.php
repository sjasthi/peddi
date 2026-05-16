<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';

$user = getCurrentUser();

$tiles = [
    [
        'href'  => 'dashboard.php',
        'icon'  => 'bi-speedometer2',
        'color' => 'text-warning',
        'title' => 'Dashboard',
        'desc'  => 'Stats, charts & activity overview',
    ],
    [
        'href'  => 'dictionaries.php',
        'icon'  => 'bi-book-fill',
        'color' => 'text-primary',
        'title' => 'Dictionaries',
        'desc'  => 'Create, edit & delete dictionaries',
    ],
    [
        'href'  => 'entries.php',
        'icon'  => 'bi-collection-fill',
        'color' => 'text-success',
        'title' => 'Entries',
        'desc'  => 'Browse & manage word entries',
    ],
    [
        'href'  => 'upload.php',
        'icon'  => 'bi-cloud-upload-fill',
        'color' => 'text-info',
        'title' => 'Upload / Import',
        'desc'  => 'Import dictionaries from CSV or JSON',
    ],
    [
        'href'  => 'compare.php',
        'icon'  => 'bi-arrows-left-right',
        'color' => 'text-purple',
        'title' => 'Compare',
        'desc'  => 'Side-by-side dictionary analysis',
    ],
    [
        'href'  => 'integrity.php',
        'icon'  => 'bi-shield-check',
        'color' => 'text-danger',
        'title' => 'Integrity',
        'desc'  => 'Detect duplicates & orphaned entries',
    ],
    [
        'href'  => 'export.php',
        'icon'  => 'bi-download',
        'color' => 'text-teal',
        'title' => 'Export',
        'desc'  => 'Export dictionaries to CSV or JSON',
    ],
];
?>

<div class="row justify-content-center">
<div class="col-xl-10 col-lg-11">

    <!-- Header bar -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-shield-lock-fill text-warning me-2"></i>Admin Panel
            </h1>
            <p class="text-body-secondary mb-0 small">
                Welcome back, <strong><?= htmlspecialchars($user['username']) ?></strong>
                &mdash; choose a section to get started.
            </p>
        </div>
        <a href="<?= APP_BASE ?>/admin/logout.php"
           class="btn btn-sm btn-outline-danger">
            <i class="bi bi-box-arrow-right me-1"></i>Logout
        </a>
    </div>

    <!-- iPad-style tile grid -->
    <div class="row g-3 g-md-4">
        <?php foreach ($tiles as $tile): ?>
        <div class="col-6 col-md-4 col-xl-3">
            <a href="<?= APP_BASE ?>/admin/<?= $tile['href'] ?>"
               class="admin-tile text-decoration-none d-block h-100">
                <div class="card h-100 border shadow-sm admin-tile-card text-center py-4 px-3">
                    <div class="mb-3">
                        <i class="bi <?= $tile['icon'] ?> <?= $tile['color'] ?>"
                           style="font-size:2.75rem; line-height:1"></i>
                    </div>
                    <h2 class="h6 fw-bold mb-1 text-body">
                        <?= htmlspecialchars($tile['title']) ?>
                    </h2>
                    <p class="text-body-secondary small mb-0" style="font-size:.8rem">
                        <?= htmlspecialchars($tile['desc']) ?>
                    </p>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
