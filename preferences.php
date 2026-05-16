<?php
$pageTitle = 'Preferences';

require_once __DIR__ . '/includes/db.php';

$dicts = $pdo->query('SELECT id, name FROM dictionaries ORDER BY name')->fetchAll();

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maxAge = 365 * 24 * 3600;
    $cookieOpts = ['path' => '/', 'max_age' => $maxAge, 'samesite' => 'Lax'];

    // Theme
    $theme = in_array($_POST['theme'] ?? '', ['light', 'dark'], true) ? $_POST['theme'] : 'light';
    setcookie('theme', $theme, time() + $maxAge, '/', '', false, false);

    // Search mode
    $mode = in_array($_POST['search_mode'] ?? '', ['exact', 'prefix', 'suffix', 'substring'], true)
            ? $_POST['search_mode'] : 'substring';
    setcookie('search_mode', $mode, time() + $maxAge, '/', '', false, false);

    // Results per page
    $perPage = (int)($_POST['results_per_page'] ?? 25);
    if ($perPage < 5 || $perPage > 100) {
        $perPage = 25;
    }
    setcookie('results_per_page', (string) $perPage, time() + $maxAge, '/', '', false, false);

    // Default dictionary
    $defaultDict = (int)($_POST['default_dictionary'] ?? 0);
    setcookie('default_dictionary', (string) $defaultDict, time() + $maxAge, '/', '', false, false);

    // Reload prefs from the just-set cookies so the page reflects them immediately
    $_COOKIE['theme']              = $theme;
    $_COOKIE['search_mode']        = $mode;
    $_COOKIE['results_per_page']   = (string) $perPage;
    $_COOKIE['default_dictionary'] = (string) $defaultDict;
    $prefs = loadPrefs($pdo);

    $saved = true;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-sliders text-warning me-2"></i>Preferences
    </h1>
</div>

<?php if ($saved): ?>
<div class="alert alert-success d-flex gap-2 align-items-center" role="alert">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    Preferences saved. Changes apply immediately across all pages.
</div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:520px">
    <div class="card-header fw-semibold">
        <i class="bi bi-gear me-1"></i>Your Settings
    </div>
    <div class="card-body">
        <form method="post" action="">

            <!-- Theme -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Theme</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="theme" id="theme_light" value="light"
                               <?= $prefs['theme'] === 'light' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_light">
                            <i class="bi bi-sun me-1"></i>Light
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="theme" id="theme_dark" value="dark"
                               <?= $prefs['theme'] === 'dark' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_dark">
                            <i class="bi bi-moon me-1"></i>Dark
                        </label>
                    </div>
                </div>
            </div>

            <!-- Default dictionary -->
            <div class="mb-4">
                <label for="default_dictionary" class="form-label fw-semibold">Default Dictionary</label>
                <select id="default_dictionary" name="default_dictionary" class="form-select">
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
                <div class="form-text">Pre-selected in the search bar on every page.</div>
            </div>

            <!-- Search mode -->
            <div class="mb-4">
                <label for="search_mode" class="form-label fw-semibold">Default Search Mode</label>
                <select id="search_mode" name="search_mode" class="form-select">
                    <?php
                    $modes = [
                        'substring' => 'Substring (contains)',
                        'prefix'    => 'Prefix (starts with)',
                        'suffix'    => 'Suffix (ends with)',
                        'exact'     => 'Exact match',
                    ];
                    foreach ($modes as $val => $label):
                    ?>
                    <option value="<?= $val ?>"
                        <?= $prefs['search_mode'] === $val ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Results per page -->
            <div class="mb-4">
                <label for="results_per_page" class="form-label fw-semibold">Results Per Page</label>
                <select id="results_per_page" name="results_per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $n): ?>
                    <option value="<?= $n ?>"
                        <?= $prefs['results_per_page'] === $n ? 'selected' : '' ?>>
                        <?= $n ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-warning">
                <i class="bi bi-check2 me-1"></i>Save Preferences
            </button>
            <a href="<?= APP_BASE ?>/index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
        </form>
    </div>
    <div class="card-footer text-body-secondary small">
        Preferences are stored as cookies in your browser. Clearing cookies will reset them to defaults.
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
