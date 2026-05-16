<?php
/**
 * Telugu character-length search.
 *
 * Phase 1 — SQL LIKE to get candidates that contain the character.
 * Phase 2 — Call ananya.telugupuzzles.com to get logical character
 *            breakdown for each candidate, then filter by length / position.
 *
 * GET params:
 *   char     (required) – one Telugu logical character / akshar
 *   length   (required) – expected number of logical characters in the word
 *   position (optional) – 1-indexed position where the character must appear
 *   dict_id  (optional) – restrict to a single dictionary
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/db.php';

function jsonOut(array $data): never
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// ── Input validation ─────────────────────────────────────────────────────
$char   = trim($_GET['char']      ?? '');
$length = (int)($_GET['length']   ?? 0);
$pos    = (int)($_GET['position'] ?? 0);    // 0 = any position
$dictId = (int)($_GET['dict_id']  ?? 0);
$exact  = !empty($_GET['exact']);           // true = logical char must equal input exactly

if ($char === '') {
    jsonOut(['success' => false, 'error' => 'Character is required.']);
}
if ($length < 1 || $length > 20) {
    jsonOut(['success' => false, 'error' => 'Word length must be between 1 and 20.']);
}
if ($pos < 0 || $pos > 20) {
    jsonOut(['success' => false, 'error' => 'Position must be between 1 and 20 (or 0 for any).']);
}
if ($pos > $length) {
    jsonOut(['success' => false, 'error' => 'Position cannot be greater than the word length.']);
}

// ── Phase 1: SQL substring search ────────────────────────────────────────
$likeChar = '%' . $char . '%';

$sql    = 'SELECT e.word, e.translation, d.name AS dict_name, d.language_code
           FROM dictionary_entries e
           JOIN dictionaries d ON d.id = e.dictionary_id
           WHERE e.word LIKE :pat';
$params = [':pat' => $likeChar];

if ($dictId > 0) {
    $sql .= ' AND e.dictionary_id = :did';
    $params[':did'] = $dictId;
}
$sql .= ' ORDER BY e.word LIMIT 120';   // cap to keep Phase-2 calls manageable

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

if (empty($candidates)) {
    jsonOut(['success' => true, 'results' => [], 'total' => 0,
             'phase1_count' => 0, 'phase2_failed' => 0]);
}

// ── Phase 2: parallel API calls via curl_multi ────────────────────────────
$apiBase = 'https://ananya.telugupuzzles.com/api.php/characters/logical'
         . '?language=Telugu&string=';

// XAMPP (Windows dev) has no CA bundle configured for PHP curl by default.
// Try known bundle locations; if none found, disable peer verification.
$caBundleCandidates = [
    'C:\\xampp\\php\\extras\\ssl\\cacert.pem',
    'C:\\xampp\\phpMyAdmin\\vendor\\composer\\ca-bundle\\res\\cacert.pem',
];
$caBundle  = '';
foreach ($caBundleCandidates as $candidate) {
    if (file_exists($candidate)) { $caBundle = $candidate; break; }
}
$verifySsl = $caBundle !== '';

$mh      = curl_multi_init();
$handles = [];

foreach ($candidates as $idx => $row) {
    $ch = curl_init($apiBase . urlencode($row['word']));
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        CURLOPT_USERAGENT      => 'Peddi/1.0',
        CURLOPT_ENCODING       => 'utf-8',
    ];
    if ($verifySsl) {
        $opts[CURLOPT_CAINFO] = $caBundle;
    }
    curl_setopt_array($ch, $opts);
    curl_multi_add_handle($mh, $ch);
    $handles[$idx] = $ch;
}

// Execute all requests in parallel
$running = null;
do {
    $status = curl_multi_exec($mh, $running);
    if ($running > 0) {
        curl_multi_select($mh);   // blocks until activity or 1 s timeout
    }
} while ($running > 0 && $status === CURLM_OK);

$apiResponses = [];
$phase2Failed = 0;
foreach ($handles as $idx => $ch) {
    $errno  = curl_errno($ch);
    $body   = curl_multi_getcontent($ch);

    if ($errno !== 0) {
        error_log('Peddi telugu_search curl error [' . $errno . ']: ' . curl_error($ch)
                  . ' for word: ' . $candidates[$idx]['word']);
    }

    $parsed = ($errno === 0 && $body !== '') ? parseApiResponse($body) : null;
    if ($parsed === null) {
        $phase2Failed++;
    }
    $apiResponses[$idx] = $parsed;
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

// ── Filter candidates (5-step algorithm) ─────────────────────────────────
$results  = [];
$normChar = nc($char);

foreach ($candidates as $idx => $row) {

    // Step 2 result: skip words whose API call failed
    $logicalChars = $apiResponses[$idx];
    if ($logicalChars === null) {
        continue;
    }

    $wordLen = count($logicalChars);

    // Step 3: check whether the input char appears in the logical characters.
    // Exact mode  → logical char must equal the input char (nc comparison).
    // Default     → logical char only needs to contain the input char (substring).
    $charMatches = static function (string $lc) use ($normChar, $exact): bool {
        $nlc = nc($lc);
        return $exact ? ($nlc === $normChar) : (mb_strpos($nlc, $normChar) !== false);
    };

    $charFound = false;
    foreach ($logicalChars as $lc) {
        if ($charMatches($lc)) { $charFound = true; break; }
    }
    if (!$charFound) {
        continue;
    }

    // Step 4: logical character count must equal the requested length
    if ($wordLen !== $length) {
        continue;
    }

    // Step 5 (optional): if position given, apply the same match rule
    // to the specific logical char at that position
    if ($pos > 0) {
        if ($pos > $wordLen) {
            continue;
        }
        if (!$charMatches($logicalChars[$pos - 1])) {
            continue;
        }
    }

    $results[] = [
        'word'        => $row['word'],
        'translation' => $row['translation'],
        'dict_name'   => $row['dict_name'],
        'lang'        => $row['language_code'],
        'chars'       => $logicalChars,
        'char_count'  => $wordLen,
    ];
}

jsonOut([
    'success'       => true,
    'results'       => $results,
    'total'         => count($results),
    'phase1_count'  => count($candidates),
    'phase2_failed' => $phase2Failed,
    'exact'         => $exact,
]);

// ── Helpers ───────────────────────────────────────────────────────────────

/**
 * Parse the ananya.telugupuzzles.com API response.
 *
 * Expected shape:
 * {
 *   "response_code": 200,
 *   "success": true,
 *   "data":   ["ఆ","స్ట్రే","లి","యా"],
 *   "result": ["ఆ","స్ట్రే","లి","యా"],
 *   "error":  null
 * }
 */
function parseApiResponse(string $json): ?array
{
    $r = json_decode($json, true);
    if (!is_array($r)) {
        return null;
    }

    // Require a successful response
    if (isset($r['success']) && $r['success'] !== true) {
        return null;
    }
    if (isset($r['response_code']) && (int) $r['response_code'] !== 200) {
        return null;
    }

    // Prefer 'data', fall back to 'result'
    foreach (['data', 'result'] as $key) {
        if (isset($r[$key]) && is_array($r[$key]) && count($r[$key]) > 0) {
            return array_values(
                array_filter($r[$key], fn($v) => is_string($v) && $v !== '')
            );
        }
    }

    return null;
}

/** Unicode NFC normalization for consistent character comparison. */
function nc(string $s): string
{
    $s = trim($s);
    if (class_exists('Normalizer')) {
        return \Normalizer::normalize($s, \Normalizer::NFC) ?: $s;
    }
    return $s;
}
