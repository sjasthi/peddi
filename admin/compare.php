<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

$allDicts = $pdo->query('SELECT id, name, language_code FROM dictionaries ORDER BY name')->fetchAll();

// Index allDicts by id for fast lookup
$dictIndex = [];
foreach ($allDicts as $d) {
    $dictIndex[(int) $d['id']] = $d;
}

$dictAId = (int) ($_GET['dict_a'] ?? 0);
$dictBId = (int) ($_GET['dict_b'] ?? 0);

$comparison = null;

if ($dictAId > 0 && $dictBId > 0 && $dictAId !== $dictBId
        && isset($dictIndex[$dictAId], $dictIndex[$dictBId])) {

    // Load all entries, keyed by lowercase word (last occurrence wins on case collision)
    $loadDict = static function (int $id) use ($pdo): array {
        $stmt = $pdo->prepare(
            'SELECT LOWER(word) AS lw, word, translation
             FROM dictionary_entries WHERE dictionary_id = :d ORDER BY word'
        );
        $stmt->execute([':d' => $id]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['lw']] = $row;
        }
        return $map;
    };

    $entriesA = $loadDict($dictAId);
    $entriesB = $loadDict($dictBId);

    $keysA = array_keys($entriesA);
    $keysB = array_keys($entriesB);

    $sharedKeys  = array_values(array_intersect($keysA, $keysB));
    $uniqueAKeys = array_values(array_diff($keysA, $keysB));
    $uniqueBKeys = array_values(array_diff($keysB, $keysA));

    // Among shared words: same vs different translation
    $sameTrans = [];
    $diffTrans = [];
    foreach ($sharedKeys as $k) {
        if (mb_strtolower(trim($entriesA[$k]['translation']))
                === mb_strtolower(trim($entriesB[$k]['translation']))) {
            $sameTrans[] = $k;
        } else {
            $diffTrans[] = $k;
        }
    }

    $comparison = [
        'dictA'     => $dictIndex[$dictAId],
        'dictB'     => $dictIndex[$dictBId],
        'entriesA'  => $entriesA,
        'entriesB'  => $entriesB,
        'shared'    => $sharedKeys,
        'uniqueA'   => $uniqueAKeys,
        'uniqueB'   => $uniqueBKeys,
        'sameTrans' => $sameTrans,
        'diffTrans' => $diffTrans,
    ];
}

$pageTitle      = 'Compare Dictionaries';
$pageScript     = 'assets/js/admin-compare.js';
$loadDataTables = $comparison !== null;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-arrows-angle-expand text-info me-2"></i>Compare Dictionaries
    </h1>
</div>

<!-- ── Selector form ─────────────────────────────────────────────────────── -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="dict_a" class="form-label fw-semibold">Dictionary A</label>
                <select id="dict_a" name="dict_a" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $dictAId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="dict_b" class="form-label fw-semibold">Dictionary B</label>
                <select id="dict_b" name="dict_b" class="form-select" required>
                    <option value="0">— Select —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $dictBId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-info text-white">
                    <i class="bi bi-arrows-angle-expand me-1"></i>Compare
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($dictAId > 0 && $dictBId > 0 && $dictAId === $dictBId): ?>
<div class="alert alert-warning">Please select two <strong>different</strong> dictionaries.</div>

<?php elseif ($comparison !== null):
    $c = $comparison;
    $nameA = $c['dictA']['name'];
    $nameB = $c['dictB']['name'];
?>

<!-- ── Summary badges ────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $badges = [
        ['Shared Words',            count($c['shared']),    'primary'],
        ['Unique to A',             count($c['uniqueA']),   'success'],
        ['Unique to B',             count($c['uniqueB']),   'warning'],
        ['Translation Differences', count($c['diffTrans']), 'danger'],
    ];
    foreach ($badges as [$label, $count, $color]): ?>
    <div class="col-6 col-lg-3">
        <div class="card border-<?= $color ?> border-opacity-50 text-center py-3">
            <div class="fs-2 fw-bold text-<?= $color ?>"><?= $count ?></div>
            <div class="text-body-secondary small"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Tabbed results ────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-0" id="compareTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="shared-tab" data-bs-toggle="tab"
                data-bs-target="#tab-shared" type="button" role="tab">
            Shared <span class="badge text-bg-primary ms-1"><?= count($c['shared']) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="uniA-tab" data-bs-toggle="tab"
                data-bs-target="#tab-uniA" type="button" role="tab">
            Unique to A <span class="badge text-bg-success ms-1"><?= count($c['uniqueA']) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="uniB-tab" data-bs-toggle="tab"
                data-bs-target="#tab-uniB" type="button" role="tab">
            Unique to B <span class="badge text-bg-warning ms-1"><?= count($c['uniqueB']) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="diff-tab" data-bs-toggle="tab"
                data-bs-target="#tab-diff" type="button" role="tab">
            Trans. Differences <span class="badge text-bg-danger ms-1"><?= count($c['diffTrans']) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom shadow-sm p-3 bg-body">

    <!-- Shared words -->
    <div class="tab-pane fade show active" id="tab-shared" role="tabpanel">
        <p class="text-body-secondary small mb-2">
            Words that appear in both <strong><?= htmlspecialchars($nameA) ?></strong>
            and <strong><?= htmlspecialchars($nameB) ?></strong>.
        </p>
        <?php if (empty($c['shared'])): ?>
            <p class="text-body-secondary">No shared words found.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="compare-table table table-sm table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Word</th>
                        <th>Translation in A</th>
                        <th>Translation in B</th>
                        <th>Match?</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($c['shared'] as $k):
                        $same = in_array($k, $c['sameTrans'], true);
                    ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($c['entriesA'][$k]['word']) ?></td>
                        <td><?= htmlspecialchars($c['entriesA'][$k]['translation']) ?></td>
                        <td><?= htmlspecialchars($c['entriesB'][$k]['translation']) ?></td>
                        <td>
                            <span class="badge text-bg-<?= $same ? 'success' : 'warning' ?>">
                                <?= $same ? 'Same' : 'Different' ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Unique to A -->
    <div class="tab-pane fade" id="tab-uniA" role="tabpanel">
        <p class="text-body-secondary small mb-2">
            Words in <strong><?= htmlspecialchars($nameA) ?></strong>
            not found in <strong><?= htmlspecialchars($nameB) ?></strong>.
        </p>
        <?php if (empty($c['uniqueA'])): ?>
            <p class="text-body-secondary">Dictionary A has no words missing from B.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="compare-table table table-sm table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Word</th><th>Translation (A)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($c['uniqueA'] as $k): ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($c['entriesA'][$k]['word']) ?></td>
                        <td><?= htmlspecialchars($c['entriesA'][$k]['translation']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Unique to B -->
    <div class="tab-pane fade" id="tab-uniB" role="tabpanel">
        <p class="text-body-secondary small mb-2">
            Words in <strong><?= htmlspecialchars($nameB) ?></strong>
            not found in <strong><?= htmlspecialchars($nameA) ?></strong>.
        </p>
        <?php if (empty($c['uniqueB'])): ?>
            <p class="text-body-secondary">Dictionary B has no words missing from A.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="compare-table table table-sm table-hover align-middle">
                <thead class="table-dark">
                    <tr><th>Word</th><th>Translation (B)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($c['uniqueB'] as $k): ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($c['entriesB'][$k]['word']) ?></td>
                        <td><?= htmlspecialchars($c['entriesB'][$k]['translation']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Translation differences -->
    <div class="tab-pane fade" id="tab-diff" role="tabpanel">
        <p class="text-body-secondary small mb-2">
            Words shared by both dictionaries but with <strong>different translations</strong>.
        </p>
        <?php if (empty($c['diffTrans'])): ?>
            <p class="text-body-secondary">No translation differences found — all shared words have matching translations.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="compare-table table table-sm table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Word</th>
                        <th>Translation in A</th>
                        <th>Translation in B</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($c['diffTrans'] as $k): ?>
                    <tr>
                        <td class="fw-medium font-monospace"><?= htmlspecialchars($c['entriesA'][$k]['word']) ?></td>
                        <td><?= htmlspecialchars($c['entriesA'][$k]['translation']) ?></td>
                        <td><?= htmlspecialchars($c['entriesB'][$k]['translation']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.tab-content -->

<?php else: ?>
<div class="text-center py-5 text-body-secondary">
    <i class="bi bi-arrows-angle-expand display-2 opacity-25"></i>
    <p class="mt-3 fs-5">Select two dictionaries above to compare them.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
