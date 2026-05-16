<?php
/**
 * GET /api/search.php
 *
 * Params:
 *   q      (required) — search term
 *   mode   (optional) — exact | prefix | suffix | substring  [default: exact]
 *   dict   (optional) — dictionary name-slug or language_code  e.g. "telugu-english", "tel"
 *   d      (optional) — dictionary numeric ID  (used by autocomplete)
 *   limit  (optional) — max results returned, 1–200  [default: 100]
 *
 * Responses: 200 ok | 400 bad request | 404 dictionary not found | 500 server error
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

// ---------------------------------------------------------------------------
// Helper — send a JSON response and halt
// ---------------------------------------------------------------------------
function jsonOut(int $httpCode, array $payload): void
{
    http_response_code($httpCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function errorOut(int $code, string $message, ?string $q, ?string $dict, ?string $mode): void
{
    jsonOut($code, [
        'status'     => 'error',
        'message'    => $message,
        'query'      => $q,
        'dictionary' => $dict,
        'mode'       => $mode,
        'count'      => 0,
        'results'    => [],
    ]);
}

// ---------------------------------------------------------------------------
// Validate q
// ---------------------------------------------------------------------------
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    errorOut(400, 'Parameter "q" is required.', null, null, null);
}
if (mb_strlen($q) > 200) {
    errorOut(400, 'Parameter "q" must be 200 characters or fewer.', null, null, null);
}

// ---------------------------------------------------------------------------
// Validate mode  (API default is "exact", different from the web UI default)
// ---------------------------------------------------------------------------
$mode = strtolower(trim($_GET['mode'] ?? 'exact'));
$validModes = ['exact', 'prefix', 'suffix', 'substring'];

if (!in_array($mode, $validModes, true)) {
    errorOut(400,
        'Invalid mode "' . $mode . '". Allowed: exact, prefix, suffix, substring.',
        $q, null, null);
}

// ---------------------------------------------------------------------------
// Clamp limit
// ---------------------------------------------------------------------------
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 100;
$limit = max(1, min(200, $limit));

// ---------------------------------------------------------------------------
// Resolve optional dictionary  (d=<id>  OR  dict=<slug / language_code>)
// ---------------------------------------------------------------------------
$dictId   = 0;       // 0 = all dictionaries
$dictName = 'all';

$dParam    = $_GET['d']    ?? null;   // numeric ID from autocomplete
$dictParam = $_GET['dict'] ?? null;   // human-readable slug

/**
 * Convert a free-form string to a URL-style slug for fuzzy matching.
 * "Telugu-English Dictionary" → "telugu-english-dictionary"
 */
function toSlug(string $s): string
{
    return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
}

try {
    if ($dParam !== null && $dParam !== '') {
        // ── Lookup by numeric ID ────────────────────────────────────────
        if (!ctype_digit($dParam)) {
            errorOut(400, 'Parameter "d" must be a positive integer.', $q, null, $mode);
        }
        $stmt = $pdo->prepare('SELECT id, name FROM dictionaries WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int) $dParam]);
        $row = $stmt->fetch();
        if (!$row) {
            errorOut(404, "No dictionary found with id=$dParam.", $q, $dParam, $mode);
        }
        $dictId   = (int) $row['id'];
        $dictName = $row['name'];

    } elseif ($dictParam !== null && $dictParam !== '') {
        // ── Lookup by slug or language_code ─────────────────────────────
        // A slug like "telugu-english" is matched against the slug derived
        // from the name ("telugu-english-dictionary") via substring search,
        // and also against the language_code column ("tel").
        $all = $pdo->query('SELECT id, name, language_code FROM dictionaries')->fetchAll();

        $querySlug = toSlug($dictParam);
        $found     = null;

        foreach ($all as $row) {
            $dbSlug = toSlug($row['name']);
            $lcCode = strtolower($row['language_code']);

            if (strpos($dbSlug, $querySlug) !== false
                    || $lcCode === strtolower($dictParam)) {
                $found = $row;
                break;
            }
        }

        if ($found === null) {
            errorOut(404,
                "No dictionary found matching \"$dictParam\". "
                    . "Use /api/dictionaries to list available dictionaries.",
                $q, $dictParam, $mode);
        }

        $dictId   = (int) $found['id'];
        $dictName = $found['name'];
    }

    // -------------------------------------------------------------------------
    // Build WHERE clause
    // -------------------------------------------------------------------------
    $conditions = [];
    $params     = [];

    if ($mode === 'exact') {
        $conditions[] = 'de.word = :term';
        $params[':term'] = $q;
    } else {
        $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        if ($mode === 'prefix') {
            $pattern = $esc . '%';
        } elseif ($mode === 'suffix') {
            $pattern = '%' . $esc;
        } else {
            $pattern = '%' . $esc . '%';
        }
        $conditions[] = 'de.word LIKE :pattern';
        $params[':pattern'] = $pattern;
    }

    if ($dictId > 0) {
        $conditions[] = 'de.dictionary_id = :dict_id';
        $params[':dict_id'] = $dictId;
    }

    $where = implode(' AND ', $conditions);

    // -------------------------------------------------------------------------
    // Execute query
    // -------------------------------------------------------------------------
    $stmt = $pdo->prepare(
        "SELECT de.word,
                de.translation,
                d.id          AS dictionary_id,
                d.name        AS dictionary_name,
                d.language_code
         FROM   dictionary_entries de
         JOIN   dictionaries       d  ON d.id = de.dictionary_id
         WHERE  $where
         ORDER  BY de.word
         LIMIT  :lim"
    );

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    // -------------------------------------------------------------------------
    // Success response
    // -------------------------------------------------------------------------
    $results = array_map(static function (array $r): array {
        return [
            'word'            => $r['word'],
            'translation'     => $r['translation'],
            'dictionary_id'   => (int) $r['dictionary_id'],
            'dictionary_name' => $r['dictionary_name'],
            'language_code'   => $r['language_code'],
        ];
    }, $rows);

    jsonOut(200, [
        'status'     => 'ok',
        'query'      => $q,
        'dictionary' => $dictName,
        'mode'       => $mode,
        'count'      => count($results),
        'results'    => $results,
    ]);

} catch (PDOException $e) {
    error_log('Peddi API /api/search error: ' . $e->getMessage());
    errorOut(500, 'A server error occurred. Please try again later.', $q, $dictName, $mode);
}
