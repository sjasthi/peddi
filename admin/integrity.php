<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

$allDicts = $pdo->query('SELECT id, name FROM dictionaries ORDER BY name')->fetchAll();

$dictIndex = [];
foreach ($allDicts as $d) {
    $dictIndex[(int) $d['id']] = $d;
}

$dictId  = (int) ($_GET['dict_id']  ?? 0);
$refId   = (int) ($_GET['ref_id']   ?? 0);

$results = null;

if ($dictId > 0 && isset($dictIndex[$dictId])) {

    // ── Duplicate words ──────────────────────────────────────────────────────
    $dupStmt = $pdo->prepare(
        'SELECT word, LOWER(word) AS lw, COUNT(*) AS cnt
         FROM dictionary_entries
         WHERE dictionary_id = :d
         GROUP BY LOWER(word)
         HAVING COUNT(*) > 1
         ORDER BY cnt DESC, lw'
    );
    $dupStmt->execute([':d' => $dictId]);
    $duplicates = $dupStmt->fetchAll();

    // ── Gap analysis (words in ref not in target) ────────────────────────────
    $gaps = [];
    if ($refId > 0 && $refId !== $dictId && isset($dictIndex[$refId])) {
        $gapStmt = $pdo->prepare(
            'SELECT r.word, r.translation
             FROM dictionary_entries r
             WHERE r.dictionary_id = :ref
               AND LOWER(r.word) NOT IN (
                   SELECT LOWER(word) FROM dictionary_entries WHERE dictionary_id = :tgt
               )
             ORDER BY r.word'
        );
        $gapStmt->execute([':ref' => $refId, ':tgt' => $dictId]);
        $gaps = $gapStmt->fetchAll();
    }

    $results = [
        'dict'       => $dictIndex[$dictId],
        'ref'        => ($refId > 0 && isset($dictIndex[$refId])) ? $dictIndex[$refId] : null,
        'duplicates' => $duplicates,
        'gaps'       => $gaps,
    ];
}

$pageTitle      = 'Integrity Check';
$pageScript     = 'assets/js/admin-integrity.js';
$loadDataTables = $results !== null;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-shield-check text-success me-2"></i>Integrity Check
    </h1>
</div>

<!-- ── Selector form ─────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="dict_id" class="form-label fw-semibold">
                    Dictionary to Check <span class="text-danger">*</span>
                </label>
                <select id="dict_id" name="dict_id" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $dictId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="ref_id" class="form-label fw-semibold">
                    Reference Dictionary <span class="text-body-secondary fw-normal">(optional — for gap analysis)</span>
                </label>
                <select id="ref_id" name="ref_id" class="form-select">
                    <option value="0">— None —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $refId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-shield-check me-1"></i>Run Check
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($dictId > 0 && !isset($dictIndex[$dictId])): ?>
<div class="alert alert-danger">Invalid dictionary selected.</div>

<?php elseif ($results !== null):
    $r = $results;
    $dictName = htmlspecialchars($r['dict']['name']);
?>

<!-- ── Summary badges ────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-danger border-opacity-50 text-center py-3">
            <div class="fs-2 fw-bold text-danger"><?= count($r['duplicates']) ?></div>
            <div class="text-body-secondary small">Duplicate Word Groups</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-<?= $r['ref'] ? 'warning' : 'secondary' ?> border-opacity-50 text-center py-3">
            <div class="fs-2 fw-bold text-<?= $r['ref'] ? 'warning' : 'secondary' ?>">
                <?= $r['ref'] ? count($r['gaps']) : '—' ?>
            </div>
            <div class="text-body-secondary small">
                <?= $r['ref'] ? 'Missing vs Reference' : 'Select a reference for gap analysis' ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Results ───────────────────────────────────────────────────────────── -->

<!-- Duplicates -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        Duplicate Words in <strong><?= $dictName ?></strong>
        <span class="badge text-bg-danger ms-auto"><?= count($r['duplicates']) ?></span>
    </div>
    <?php if (empty($r['duplicates'])): ?>
    <div class="card-body text-body-secondary">
        <i class="bi bi-check-circle text-success me-1"></i>No duplicate words found.
    </div>
    <?php else: ?>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="dupTable" class="table table-sm table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Word (original casing)</th>
                        <th>Occurrences</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['duplicates'] as $dup): ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($dup['word']) ?></td>
                        <td>
                            <span class="badge text-bg-danger"><?= (int) $dup['cnt'] ?></span>
                        </td>
                        <td>
                            <a href="<?= APP_BASE ?>/admin/entries.php?dict_id=<?= $dictId ?>&search=<?= urlencode($dup['word']) ?>"
                               class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil-square me-1"></i>Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Gap analysis -->
<div class="card shadow-sm mb-4">
    <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-search text-warning"></i>
        <?php if ($r['ref']): ?>
            Words in <strong><?= htmlspecialchars($r['ref']['name']) ?></strong>
            missing from <strong><?= $dictName ?></strong>
            <span class="badge text-bg-warning ms-auto"><?= count($r['gaps']) ?></span>
        <?php else: ?>
            Gap Analysis — select a reference dictionary to enable
        <?php endif; ?>
    </div>
    <?php if (!$r['ref']): ?>
    <div class="card-body text-body-secondary">
        Choose a reference dictionary in the form above to see which of its words are absent here.
    </div>
    <?php elseif (empty($r['gaps'])): ?>
    <div class="card-body text-body-secondary">
        <i class="bi bi-check-circle text-success me-1"></i>
        No gaps found — every word in the reference dictionary exists here too.
    </div>
    <?php else: ?>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="gapTable" class="table table-sm table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Word (from reference)</th>
                        <th>Translation in Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['gaps'] as $gap): ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($gap['word']) ?></td>
                        <td><?= htmlspecialchars($gap['translation']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="text-center py-5 text-body-secondary">
    <i class="bi bi-shield-check display-2 opacity-25"></i>
    <p class="mt-3 fs-5">Select a dictionary above to run an integrity check.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
