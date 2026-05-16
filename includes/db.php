<?php
require_once __DIR__ . '/config.php';

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('Peddi DB error: ' . $e->getMessage());
    http_response_code(503);
    $isApi = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    if ($isApi) {
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'error' => 'Database unavailable']));
    }
    exit('<!DOCTYPE html><html lang="en"><body style="font-family:sans-serif;padding:2rem">'
        . '<h2>Service Unavailable</h2><p>Could not connect to the database. Please try again later.</p>'
        . '</body></html>');
}

/**
 * Load user preferences: cookies take priority over DB defaults, which fall back
 * to hardcoded safe values if the preferences table is not yet populated.
 */
function loadPrefs(PDO $pdo): array
{
    $db = [];
    try {
        $rows = $pdo->query('SELECT pref_key, pref_value FROM preferences')->fetchAll();
        foreach ($rows as $row) {
            $db[$row['pref_key']] = $row['pref_value'];
        }
    } catch (PDOException $e) {
        // Table missing on first-time setup — hardcoded fallbacks apply below
    }

    $theme = $_COOKIE['theme'] ?? $db['default_theme'] ?? 'light';
    if (!in_array($theme, ['light', 'dark'], true)) {
        $theme = 'light';
    }

    $searchMode = $_COOKIE['search_mode'] ?? $db['default_search_mode'] ?? 'substring';
    if (!in_array($searchMode, ['exact', 'prefix', 'suffix', 'substring'], true)) {
        $searchMode = 'substring';
    }

    $perPage = (int)($_COOKIE['results_per_page'] ?? $db['default_results_per_page'] ?? 25);
    if ($perPage < 5 || $perPage > 100) {
        $perPage = 25;
    }

    $defaultDict = (int)($_COOKIE['default_dictionary'] ?? $db['default_dictionary_id'] ?? 0);

    return [
        'theme'                 => $theme,
        'search_mode'           => $searchMode,
        'results_per_page'      => $perPage,
        'default_dictionary_id' => $defaultDict,
    ];
}

$prefs = loadPrefs($pdo);
