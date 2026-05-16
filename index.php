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
<section class="hero-immersive mb-5">
    <div class="row g-0">

        <!-- Left: brand + search ──────────────────────────────── -->
        <div class="col-lg-7 hero-left d-flex flex-column justify-content-center">

            <div class="d-flex align-items-center gap-3 mb-3">
                <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
                     alt="Peddi" height="64" style="width:auto">
                <span class="peddi-logo peddi-logo-xl">Peddi</span>
            </div>

            <p class="hero-tagline mb-1">
                A living archive of Indic words — Telugu, Sanskrit, Hindi, and English,
                compiled by <strong>Peddi Sambasiva Rao</strong>.
            </p>
            <p class="hero-sub mb-4">
                <?= number_format($totalWords) ?> entries across <?= $totalDicts ?> dictionaries.
                Free to search, forever.
            </p>

            <!-- Search -->
            <form id="heroSearchForm" action="<?= APP_BASE ?>/search.php" method="get"
                  class="hero-search mb-4" autocomplete="off">
                <div class="row g-2">
                    <div class="col-12 col-sm">
                        <label for="heroQuery" class="visually-hidden">Search word</label>
                        <input type="text" id="heroQuery" name="q"
                               class="form-control form-control-lg"
                               placeholder="Enter a word…" required>
                    </div>
                    <div class="col-12 col-sm-auto">
                        <label for="heroDictionary" class="visually-hidden">Dictionary</label>
                        <select id="heroDictionary" name="d" class="form-select form-select-lg">
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
                    </div>
                    <div class="col-12 col-sm-auto">
                        <button type="submit" class="btn btn-warning btn-lg w-100 fw-semibold">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                    </div>
                </div>
            </form>

            <!-- Stat pills -->
            <div class="d-flex flex-wrap gap-2">
                <span class="hero-pill">
                    <i class="bi bi-collection text-warning me-1"></i><?= $totalDicts ?> Dictionaries
                </span>
                <span class="hero-pill">
                    <i class="bi bi-alphabet text-info me-1"></i><?= number_format($totalWords) ?> Entries
                </span>
                <span class="hero-pill">
                    <i class="bi bi-translate text-success me-1"></i>4 Search Modes
                </span>
                <a href="<?= APP_BASE ?>/catalog.php" class="hero-pill text-decoration-none">
                    <i class="bi bi-journals text-warning me-1"></i>Browse Catalog
                </a>
            </div>

        </div>

        <!-- Right: welcome image ───────────────────────────────── -->
        <div class="col-lg-5 hero-right d-none d-lg-flex align-items-end justify-content-center">
            <img src="https://www.projectabcd.com/images/dress_images/Slide1.PNG"
                 alt="Namaste — Welcome to Peddi"
                 class="hero-welcome-img"
                 onerror="this.parentElement.style.display='none'">
        </div>

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
