<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

$pageTitle   = 'Dashboard';
$pageScript  = 'assets/js/admin-dashboard.js';
$loadChartJs = true;

require_once __DIR__ . '/../includes/db.php';

// ── Stats ────────────────────────────────────────────────────────────────────
$totalDicts = (int) $pdo->query('SELECT COUNT(*) FROM dictionaries')->fetchColumn();
$totalWords = (int) $pdo->query('SELECT COUNT(*) FROM dictionary_entries')->fetchColumn();
$totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

$perDict = $pdo->query(
    'SELECT d.id, d.name, d.language_code,
            COUNT(de.id) AS word_count,
            d.created_at
     FROM dictionaries d
     LEFT JOIN dictionary_entries de ON de.dictionary_id = d.id
     GROUP BY d.id
     ORDER BY word_count DESC'
)->fetchAll();

// Data for Chart.js — passed via data-* to avoid inline PHP in <script>
$chartLabels = array_map(fn($r) => $r['name'],       $perDict);
$chartValues = array_map(fn($r) => (int)$r['word_count'], $perDict);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-speedometer2 text-warning me-2"></i>Dashboard</h1>
    <span class="text-body-secondary small">
        <i class="bi bi-clock me-1"></i><?= date('D, d M Y H:i') ?>
    </span>
</div>

<!-- ── Stat cards ────────────────────────────────────────────────────────── -->
<div class="row g-4 mb-5">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-primary border-opacity-50 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-collection fs-3 text-primary"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold"><?= $totalDicts ?></div>
                    <div class="text-body-secondary small">Dictionaries</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="<?= APP_BASE ?>/admin/dictionaries.php" class="btn btn-sm btn-outline-primary">
                    Manage <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-success border-opacity-50 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3">
                    <i class="bi bi-alphabet fs-3 text-success"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold"><?= number_format($totalWords) ?></div>
                    <div class="text-body-secondary small">Total Entries</div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="<?= APP_BASE ?>/admin/entries.php" class="btn btn-sm btn-outline-success">
                    Browse <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-warning border-opacity-50 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-translate fs-3 text-warning"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold">
                        <?= count(array_unique(array_column($perDict, 'language_code'))) ?>
                    </div>
                    <div class="text-body-secondary small">Languages</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-info border-opacity-50 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-3">
                    <i class="bi bi-people fs-3 text-info"></i>
                </div>
                <div>
                    <div class="fs-2 fw-bold"><?= $totalUsers ?></div>
                    <div class="text-body-secondary small">Users</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Chart + breakdown table ───────────────────────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-bar-chart-fill text-primary me-1"></i> Word Count per Dictionary
            </div>
            <div class="card-body">
                <canvas id="wordCountChart"
                        data-labels="<?= htmlspecialchars(json_encode($chartLabels), ENT_QUOTES) ?>"
                        data-values="<?= htmlspecialchars(json_encode($chartValues), ENT_QUOTES) ?>">
                </canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-table text-success me-1"></i> Breakdown
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Dictionary</th>
                            <th class="text-center">Lang</th>
                            <th class="text-end">Words</th>
                            <th class="text-end">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perDict as $row):
                            $pct = $totalWords > 0
                                ? round($row['word_count'] / $totalWords * 100, 1)
                                : 0;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= APP_BASE ?>/admin/entries.php?dict_id=<?= $row['id'] ?>"
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($row['name']) ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge text-bg-secondary">
                                    <?= htmlspecialchars(strtoupper($row['language_code'])) ?>
                                </span>
                            </td>
                            <td class="text-end fw-medium"><?= number_format($row['word_count']) ?></td>
                            <td class="text-end text-body-secondary small"><?= $pct ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="fw-semibold">Total</td>
                            <td class="text-end fw-semibold"><?= number_format($totalWords) ?></td>
                            <td class="text-end">100%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
