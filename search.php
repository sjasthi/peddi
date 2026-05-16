<?php
$pageTitle  = 'Search';
$pageScript = 'assets/js/search.js';

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/search_helper.php';

$dicts    = $pdo->query('SELECT id, name FROM dictionaries ORDER BY name')->fetchAll();
$hasQuery = isset($_GET['q']) && trim($_GET['q']) !== '';
$search   = $hasQuery ? executeSearch($pdo, $prefs) : null;

// Labels for the mode selector
$modeLabels = ['substring' => 'Contains', 'prefix' => 'Starts with',
               'suffix'    => 'Ends with', 'exact'  => 'Exact match'];

// Current form values (GET → cookie pref fallback)
$fQ    = htmlspecialchars(trim($_GET['q']    ?? ''), ENT_QUOTES);
$fDict = (int)($_GET['d']    ?? $prefs['default_dictionary_id']);
$fMode = $_GET['mode'] ?? $prefs['search_mode'];
if (!array_key_exists($fMode, $modeLabels)) {
    $fMode = 'substring';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
<div class="col-xl-9 col-lg-10">

    <h1 class="h3 mb-4">
        <i class="bi bi-search text-primary me-2"></i>Search Dictionaries
    </h1>

    <!-- ── Search form ──────────────────────────────────────── -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form id="searchForm" method="get" action="<?= APP_BASE ?>/search.php" novalidate>
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-5">
                        <label for="q" class="form-label fw-semibold">Search Term</label>
                        <div class="input-group">
                            <input type="text"
                                   id="q" name="q"
                                   class="form-control"
                                   value="<?= $fQ ?>"
                                   placeholder="Enter a word…"
                                   autocomplete="off"
                                   autofocus
                                   required>
                            <button type="button" id="clearBtn"
                                    class="btn btn-outline-secondary<?= $fQ === '' ? ' d-none' : '' ?>"
                                    aria-label="Clear search term">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label for="d" class="form-label fw-semibold">Dictionary</label>
                        <select id="d" name="d" class="form-select">
                            <option value="0" <?= $fDict === 0 ? 'selected' : '' ?>>
                                All Dictionaries
                            </option>
                            <?php foreach ($dicts as $dict): ?>
                            <option value="<?= (int) $dict['id'] ?>"
                                <?= $fDict === (int) $dict['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dict['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label for="mode" class="form-label fw-semibold">Mode</label>
                        <select id="mode" name="mode" class="form-select">
                            <?php foreach ($modeLabels as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $fMode === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- ── Telugu Character Search ──────────────────────────── -->
    <div class="card mb-4 shadow-sm border-info border-opacity-50" id="teluguSearchCard">
        <div class="card-header bg-transparent border-info border-opacity-25 d-flex justify-content-between
                    align-items-center"
             style="cursor:pointer"
             data-bs-toggle="collapse" data-bs-target="#teluguPanel"
             aria-expanded="false" aria-controls="teluguPanel">
            <span class="fw-semibold text-info">
                <i class="bi bi-alphabet-uppercase me-2"></i>Telugu Character Search
                <span class="badge text-bg-info ms-2 fw-normal" style="font-size:.7rem">Beta</span>
            </span>
            <i class="bi bi-chevron-down text-info" id="teluguChevron"></i>
        </div>

        <div class="collapse" id="teluguPanel">
            <div class="card-body">
                <p class="text-body-secondary small mb-3">
                    Find Telugu words by <strong>logical character (akshar) count</strong>.
                    Enter a Telugu akshar, specify the word length (number of logical characters),
                    and optionally the position where the akshar must appear.
                    Results are verified via the Telugu character-parsing API.
                </p>

                <form id="teluguSearchForm" novalidate>
                    <div class="row g-3 align-items-end">

                        <div class="col-6 col-md-2">
                            <label for="tChar" class="form-label fw-semibold">Akshar</label>
                            <input type="text" id="tChar" class="form-control text-center fw-bold"
                                   style="font-size:1.4rem; line-height:1.2" maxlength="6"
                                   placeholder="క" required>
                            <div class="form-text">One logical character</div>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="tLength" class="form-label fw-semibold">Length</label>
                            <input type="number" id="tLength" class="form-control"
                                   min="1" max="20" placeholder="3" required>
                            <div class="form-text">No. of akshars</div>
                        </div>

                        <div class="col-6 col-md-2">
                            <label for="tPosition" class="form-label fw-semibold">
                                Position
                                <span class="text-body-secondary fw-normal small">(opt.)</span>
                            </label>
                            <input type="number" id="tPosition" class="form-control"
                                   min="1" max="20" placeholder="—">
                            <div class="form-text">1 = first akshar</div>
                        </div>

                        <div class="col-6 col-md-3">
                            <label for="tDictId" class="form-label fw-semibold">Dictionary</label>
                            <select id="tDictId" class="form-select">
                                <option value="0">All Dictionaries</option>
                                <?php foreach ($dicts as $dict): ?>
                                <option value="<?= (int) $dict['id'] ?>"
                                    <?= $fDict === (int) $dict['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dict['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <button type="submit" class="btn btn-info text-white w-100">
                                <i class="bi bi-search me-1"></i>Search by Character
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                       role="switch" id="tExact">
                                <label class="form-check-label fw-semibold" for="tExact">
                                    Exact Match
                                </label>
                                <span class="text-body-secondary small ms-2">
                                    — when on, the akshar must match the logical character
                                    exactly (e.g. <code>కా</code> only matches <code>కా</code>,
                                    not <code>క</code>); when off, a partial match is accepted.
                                </span>
                            </div>
                        </div>

                    </div>
                </form>

                <div id="teluguResults" class="mt-4"></div>
            </div>
        </div>
    </div>

    <!-- ── Results ──────────────────────────────────────────── -->
    <?php if ($search !== null): ?>

        <?php if ($search['totalRows'] === 0): ?>
            <div class="alert alert-info d-flex gap-2" role="alert">
                <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                <div>
                    No results found for <strong>&ldquo;<?= htmlspecialchars($search['q']) ?>&rdquo;</strong>
                    (<?= htmlspecialchars($modeLabels[$search['mode']]) ?> mode).
                    Try a broader mode or check your spelling.
                </div>
            </div>

        <?php else: ?>

            <!-- Result meta bar -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <p class="text-body-secondary mb-0 small">
                    Showing
                    <strong><?= number_format($search['rangeStart']) ?>–<?= number_format($search['rangeEnd']) ?></strong>
                    of <strong><?= number_format($search['totalRows']) ?></strong>
                    result<?= $search['totalRows'] !== 1 ? 's' : '' ?> for
                    <strong>&ldquo;<?= htmlspecialchars($search['q']) ?>&rdquo;</strong>
                    &mdash; <?= htmlspecialchars($modeLabels[$search['mode']]) ?>
                    <?php if ($search['dictId'] > 0):
                        $matchedDict = array_filter($dicts, fn($d) => (int)$d['id'] === $search['dictId']);
                        $matchedDict = reset($matchedDict);
                    ?>
                        in <em><?= htmlspecialchars($matchedDict['name'] ?? '') ?></em>
                    <?php else: ?>
                        across all dictionaries
                    <?php endif; ?>
                </p>
                <a href="<?= APP_BASE ?>/search_results.php?<?= htmlspecialchars(http_build_query($_GET)) ?>"
                   class="btn btn-sm btn-outline-secondary"
                   title="Open as standalone results page (shareable URL)">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Full Results View
                </a>
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

    <?php else: ?>
        <!-- Landing state — no query yet -->
        <div class="text-center py-5 text-body-secondary">
            <i class="bi bi-search display-1 opacity-25"></i>
            <p class="mt-3 mb-1 fs-5">Enter a word above to begin searching.</p>
            <p class="small">
                Tip: press <kbd>/</kbd> anywhere on the page to jump to the search box.
            </p>
        </div>
    <?php endif; ?>

</div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
