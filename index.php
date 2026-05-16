<?php
$pageTitle  = 'Home';
$pageScript = 'assets/js/index.js';

require_once __DIR__ . '/includes/db.php';

// Fetch dictionaries for the search dropdown
$dicts = $pdo->query('SELECT id, name, language_code FROM dictionaries ORDER BY name')->fetchAll();

// Quick stats
$totalDicts = (int) $pdo->query('SELECT COUNT(*) FROM dictionaries')->fetchColumn();
$totalWords = (int) $pdo->query('SELECT COUNT(*) FROM dictionary_entries')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero ─────────────────────────────────────────────────── -->
<section class="hero-manuscript mb-5 px-3 px-md-5 py-4 py-md-5 text-center">

    <!-- Logo mark -->
    <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
         alt="Peddi" height="72" style="width:auto" class="mb-2">

    <!-- Wordmark -->
    <h1 class="peddi-logo peddi-logo-xl mb-3">Peddi</h1>

    <!-- Ornamental rule -->
    <div class="hero-rule mb-3">
        <span class="hero-rule-dot"></span>
        <span class="hero-rule-dot"></span>
        <span class="hero-rule-dot"></span>
    </div>

    <!-- Bilingual tagline -->
    <div class="hero-bilingual mb-1">
        <span class="hero-en">A living archive of Indic words</span>
        <span class="hero-sep" aria-hidden="true">✦</span>
        <span class="hero-tel font-telugu">పదాల సజీవ సంపద</span>
    </div>
    <p class="hero-sub-line mb-4">
        <?= number_format($totalWords) ?> entries across <?= $totalDicts ?> dictionaries
        &mdash; compiled by <em>Peddi Sambasiva Rao</em>. Free to search, forever.
    </p>

    <!-- Search bar -->
    <div class="hero-search-wrap mx-auto mb-4">
        <form id="heroSearchForm" action="<?= APP_BASE ?>/search.php" method="get"
              autocomplete="off">
            <div class="d-flex flex-column flex-sm-row gap-2 hero-search-bar">
                <label for="heroQuery" class="visually-hidden">Search word</label>
                <input type="text" id="heroQuery" name="q"
                       class="form-control form-control-lg flex-grow-1"
                       placeholder="Search a word — e.g. ధర్మం, karma, love…"
                       required>
                <label for="heroDictionary" class="visually-hidden">Dictionary</label>
                <select id="heroDictionary" name="d"
                        class="form-select form-select-lg" style="width:auto;min-width:155px">
                    <option value="0" <?= $prefs['default_dictionary_id'] === 0 ? 'selected' : '' ?>>
                        All Dictionaries
                    </option>
                    <?php foreach ($dicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $prefs['default_dictionary_id'] === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-saffron btn-lg px-4">
                    <i class="bi bi-search me-1"></i>Search
                </button>
            </div>
        </form>
    </div>

    <!-- Stat pills -->
    <div class="d-flex justify-content-center flex-wrap gap-2">
        <span class="hero-pill">
            <i class="bi bi-collection me-1"></i><?= $totalDicts ?> Dictionaries
        </span>
        <span class="hero-pill">
            <i class="bi bi-alphabet me-1"></i><?= number_format($totalWords) ?> Entries
        </span>
        <span class="hero-pill">
            <i class="bi bi-translate me-1"></i>4 Search Modes
        </span>
        <a href="<?= APP_BASE ?>/catalog.php" class="hero-pill text-decoration-none">
            <i class="bi bi-journals me-1"></i>Browse Catalog
        </a>
    </div>

</section>

<!-- ── Quick-access strips ────────────────────────────────────── -->
<div class="row g-3 mb-5">
    <div class="col-6 col-lg-3">
        <a href="<?= APP_BASE ?>/search.php" class="card stat-card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="bi bi-search fs-1 text-primary mb-2 d-block"></i>
                <h3 class="fw-bold mb-0"><?= $totalDicts ?></h3>
                <p class="text-body-secondary small mb-0">Dictionar<?= $totalDicts !== 1 ? 'ies' : 'y' ?></p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= APP_BASE ?>/search.php" class="card stat-card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="bi bi-alphabet fs-1 text-success mb-2 d-block"></i>
                <h3 class="fw-bold mb-0"><?= number_format($totalWords) ?></h3>
                <p class="text-body-secondary small mb-0">Entries</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= APP_BASE ?>/search.php#teluguSearchCard"
           class="card stat-card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="bi bi-alphabet-uppercase fs-1 text-warning mb-2 d-block"></i>
                <h3 class="fw-bold mb-0" style="font-size:1.4rem">అ ఆ ఇ</h3>
                <p class="text-body-secondary small mb-0">Telugu Search</p>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= APP_BASE ?>/catalog.php" class="card stat-card text-center h-100 text-decoration-none">
            <div class="card-body">
                <i class="bi bi-journals fs-1 text-info mb-2 d-block"></i>
                <h3 class="fw-bold mb-0">Catalog</h3>
                <p class="text-body-secondary small mb-0">Browse All</p>
            </div>
        </a>
    </div>
</div>

<!-- ── Feature cards ─────────────────────────────────────────── -->
<h2 class="h5 text-body-secondary text-uppercase fw-semibold mb-3 letter-spacing-1">Features</h2>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <i class="bi bi-search text-primary fs-3 mb-3 d-block"></i>
                <h5 class="card-title">Advanced Search</h5>
                <p class="card-text text-body-secondary">
                    Search across Indic language dictionaries using exact, prefix,
                    suffix, or substring matching.
                </p>
                <a href="<?= APP_BASE ?>/search.php" class="btn btn-outline-primary btn-sm">
                    Start Searching
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <i class="bi bi-journals text-success fs-3 mb-3 d-block"></i>
                <h5 class="card-title">Dictionary Catalog</h5>
                <p class="card-text text-body-secondary">
                    Browse all available dictionaries, view metadata, and explore
                    word lists by language.
                </p>
                <a href="<?= APP_BASE ?>/catalog.php" class="btn btn-outline-success btn-sm">
                    Browse Catalog
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <i class="bi bi-sliders text-warning fs-3 mb-3 d-block"></i>
                <h5 class="card-title">Your Preferences</h5>
                <p class="card-text text-body-secondary">
                    Set your default dictionary, results per page, theme, and
                    search mode — stored in your browser.
                </p>
                <a href="<?= APP_BASE ?>/preferences.php" class="btn btn-outline-warning btn-sm">
                    Set Preferences
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
