<?php
$pageTitle = 'Dictionary Catalog';

require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->query(
    'SELECT d.id, d.name, d.language_code, d.description,
            COUNT(e.id) AS word_count, d.created_at
     FROM dictionaries d
     LEFT JOIN dictionary_entries e ON e.dictionary_id = d.id
     GROUP BY d.id
     ORDER BY d.name'
);
$dicts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-journals text-success me-2"></i>Dictionary Catalog
    </h1>
    <span class="badge text-bg-secondary fs-6"><?= count($dicts) ?> dictionar<?= count($dicts) !== 1 ? 'ies' : 'y' ?></span>
</div>

<?php if (empty($dicts)): ?>
<div class="text-center py-5 text-body-secondary">
    <i class="bi bi-journals display-2 opacity-25"></i>
    <p class="mt-3 fs-5">No dictionaries available yet.</p>
</div>
<?php else: ?>

<div class="row g-4">
    <?php foreach ($dicts as $d): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <i class="bi bi-book text-success fs-4 flex-shrink-0 mt-1"></i>
                    <div>
                        <h5 class="card-title mb-0"><?= htmlspecialchars($d['name']) ?></h5>
                        <code class="small text-body-secondary"><?= htmlspecialchars($d['language_code']) ?></code>
                    </div>
                </div>

                <?php if (!empty($d['description'])): ?>
                <p class="card-text text-body-secondary small mb-3">
                    <?= htmlspecialchars($d['description']) ?>
                </p>
                <?php endif; ?>

                <div class="mt-auto">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-body-secondary small">
                            <i class="bi bi-alphabet me-1"></i>
                            <strong><?= number_format((int) $d['word_count']) ?></strong>
                            entr<?= (int) $d['word_count'] !== 1 ? 'ies' : 'y' ?>
                        </span>
                        <span class="text-body-secondary small">
                            <i class="bi bi-calendar3 me-1"></i>
                            <?= date('M j, Y', strtotime($d['created_at'])) ?>
                        </span>
                    </div>
                    <a href="<?= APP_BASE ?>/search.php?d=<?= (int) $d['id'] ?>&mode=substring"
                       class="btn btn-outline-success btn-sm w-100">
                        <i class="bi bi-search me-1"></i>Search this dictionary
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
