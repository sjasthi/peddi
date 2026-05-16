<?php
/**
 * Standalone search results page — bookmarkable / shareable URL.
 * Accepts the same GET params as search.php: q, d, mode, page.
 * If accessed without a query, redirects to search.php.
 */
$pageTitle  = 'Search Results';
$pageScript = 'assets/js/search.js';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/search_helper.php';

// Redirect to search.php when there is no query
if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    header('Location: ' . APP_BASE . '/search.php');
    exit;
}

$dicts  = $pdo->query('SELECT id, name FROM dictionaries ORDER BY name')->fetchAll();
$search = executeSearch($pdo, $prefs);

$modeLabels = ['substring' => 'Contains', 'prefix' => 'Starts with',
               'suffix'    => 'Ends with', 'exact'  => 'Exact match'];

// Compact filter form values
$fQ    = htmlspecialchars($search['q'], ENT_QUOTES);
$fDict = $search['dictId'];
$fMode = $search['mode'];

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Breadcrumb ───────────────────────────────────────────── -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/search.php">Search</a></li>
        <li class="breadcrumb-item active" aria-current="page">
            Results for &ldquo;<?= htmlspecialchars($search['q']) ?>&rdquo;
        </li>
    </ol>
</nav>

<!-- ── Compact refine-search form ───────────────────────────── -->
<div class="card mb-4 shadow-sm">
    <div class="card-body py-2">
        <form id="searchForm" method="get" action="<?= APP_BASE ?>/search_results.php"
              class="row g-2 align-items-center" novalidate>

            <div class="col-12 col-sm-auto flex-grow-1">
                <label for="q" class="visually-hidden">Search term</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="q" name="q"
                           class="form-control form-control-sm"
                           value="<?= $fQ ?>"
                           placeholder="Refine search…"
                           autocomplete="off" required>
                    <button type="button" id="clearBtn"
                            class="btn btn-sm btn-outline-secondary<?= $fQ === '' ? ' d-none' : '' ?>"
                            aria-label="Clear">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>

            <div class="col-12 col-sm-auto">
                <label for="d" class="visually-hidden">Dictionary</label>
                <select id="d" name="d" class="form-select form-select-sm">
                    <option value="0" <?= $fDict === 0 ? 'selected' : '' ?>>All Dictionaries</option>
                    <?php foreach ($dicts as $dict): ?>
                    <option value="<?= (int) $dict['id'] ?>"
                        <?= $fDict === (int) $dict['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dict['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-auto">
                <label for="mode" class="visually-hidden">Search mode</label>
                <select id="mode" name="mode" class="form-select form-select-sm">
                    <?php foreach ($modeLabels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $fMode === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search"></i> Go
                </button>
                <a href="<?= APP_BASE ?>/search.php?<?= htmlspecialchars(http_build_query($_GET)) ?>"
                   class="btn btn-outline-secondary btn-sm ms-1"
                   title="Open in full search page">
                    <i class="bi bi-sliders"></i> Full Search
                </a>
            </div>

        </form>
    </div>
</div>

<!-- ── Results ──────────────────────────────────────────────── -->
<?php if ($search['totalRows'] === 0): ?>

    <div class="alert alert-info d-flex gap-2" role="alert">
        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
        <div>
            No results for <strong>&ldquo;<?= htmlspecialchars($search['q']) ?>&rdquo;</strong>
            (<?= htmlspecialchars($modeLabels[$search['mode']]) ?> mode).
            Try a different word or mode.
        </div>
    </div>

<?php else: ?>

    <!-- Result meta bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <p class="text-body-secondary mb-0 small">
            Showing
            <strong><?= number_format($search['rangeStart']) ?>–<?= number_format($search['rangeEnd']) ?></strong>
            of <strong><?= number_format($search['totalRows']) ?></strong>
            result<?= $search['totalRows'] !== 1 ? 's' : '' ?> &mdash;
            <strong><?= htmlspecialchars($modeLabels[$search['mode']]) ?></strong> &middot;
            <?php if ($search['dictId'] > 0):
                $matchedDict = array_filter($dicts, fn($d) => (int)$d['id'] === $search['dictId']);
                $matchedDict = reset($matchedDict);
            ?>
                <em><?= htmlspecialchars($matchedDict['name'] ?? '') ?></em>
            <?php else: ?>
                all dictionaries
            <?php endif; ?>
        </p>
        <small class="text-body-secondary">
            Page <?= $search['page'] ?> of <?= $search['totalPages'] ?>
        </small>
    </div>

    <!-- Results table -->
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-hover table-bordered align-middle mb-0" id="resultsTable">
            <thead class="table-dark">
                <tr>
                    <th scope="col" style="width:30%">Word</th>
                    <th scope="col">Translation</th>
                    <th scope="col" style="width:25%">Dictionary</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($search['results'] as $row): ?>
                <tr>
                    <td class="fw-medium font-monospace">
                        <?= highlightTerm($row['word'], $search['q']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['translation']) ?></td>
                    <td>
                        <span class="badge text-bg-secondary me-1">
                            <?= htmlspecialchars(strtoupper($row['language_code'])) ?>
                        </span>
                        <span class="text-body-secondary small">
                            <?= htmlspecialchars($row['dict_name']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?= paginationHtml($search['page'], $search['totalPages'], $_GET) ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
