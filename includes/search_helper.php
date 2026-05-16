<?php
/**
 * Shared search logic used by search.php and search_results.php.
 * Requires $pdo and $prefs to already be in scope (loaded via db.php).
 */

/**
 * Execute a dictionary search and return results + pagination metadata.
 *
 * @return array{
 *   q: string, dictId: int, mode: string,
 *   page: int, perPage: int,
 *   totalRows: int, totalPages: int,
 *   rangeStart: int, rangeEnd: int,
 *   results: list<array>
 * }
 */
function executeSearch(PDO $pdo, array $prefs): array
{
    $q       = trim($_GET['q'] ?? '');
    $dictId  = max(0, (int)($_GET['d']    ?? 0));
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = $prefs['results_per_page'];

    $mode = $_GET['mode'] ?? $prefs['search_mode'];
    if (!in_array($mode, ['exact', 'prefix', 'suffix', 'substring'], true)) {
        $mode = 'substring';
    }

    if ($q === '') {
        return [
            'q' => '', 'dictId' => $dictId, 'mode' => $mode,
            'page' => 1, 'perPage' => $perPage,
            'totalRows' => 0, 'totalPages' => 0,
            'rangeStart' => 0, 'rangeEnd' => 0,
            'results' => [],
        ];
    }

    // Build WHERE clause -----------------------------------------------
    $conditions = [];
    $params     = [];

    if ($mode === 'exact') {
        $conditions[] = 'de.word = :term';
        $params[':term'] = $q;
    } else {
        // Escape LIKE special characters before adding wildcards
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

    $where   = implode(' AND ', $conditions);
    $fromSql = "FROM dictionary_entries de
                JOIN dictionaries d ON d.id = de.dictionary_id
                WHERE $where";

    // COUNT query for pagination ----------------------------------------
    $countStmt = $pdo->prepare("SELECT COUNT(*) $fromSql");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();

    $totalPages = $perPage > 0 ? (int) ceil($totalRows / $perPage) : 1;
    if ($totalPages < 1) {
        $totalPages = 1;
    }
    $page   = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    // Data query --------------------------------------------------------
    $dataStmt = $pdo->prepare(
        "SELECT de.word, de.translation, d.name AS dict_name, d.language_code
         $fromSql
         ORDER BY de.word, d.name
         LIMIT :lim OFFSET :off"
    );
    foreach ($params as $key => $val) {
        $dataStmt->bindValue($key, $val);
    }
    $dataStmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':off', $offset,  PDO::PARAM_INT);
    $dataStmt->execute();
    $results = $dataStmt->fetchAll();

    $rangeStart = $totalRows > 0 ? $offset + 1 : 0;
    $rangeEnd   = min($offset + $perPage, $totalRows);

    return compact(
        'q', 'dictId', 'mode', 'page', 'perPage',
        'totalRows', 'totalPages', 'rangeStart', 'rangeEnd', 'results'
    );
}

/**
 * Wrap occurrences of $term inside $text with <mark>, HTML-safe output.
 */
function highlightTerm(string $text, string $term): string
{
    if ($term === '') {
        return htmlspecialchars($text);
    }
    $safeText = htmlspecialchars($text);
    $safeTerm = preg_quote(htmlspecialchars($term), '/');
    return preg_replace('/(' . $safeTerm . ')/ui', '<mark>$1</mark>', $safeText);
}

/**
 * Render Bootstrap 5 pagination nav.
 * Preserves all current GET params except 'page'.
 */
function paginationHtml(int $current, int $total, array $get): string
{
    if ($total <= 1) {
        return '';
    }

    $base = array_diff_key($get, ['page' => '']);
    $url  = function (int $p) use ($base): string {
        return '?' . http_build_query(array_merge($base, ['page' => $p]));
    };

    $out  = '<nav aria-label="Results pages" class="mt-4">';
    $out .= '<ul class="pagination justify-content-center flex-wrap mb-0">';

    // Previous
    if ($current <= 1) {
        $out .= '<li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>';
    } else {
        $out .= sprintf('<li class="page-item"><a class="page-link" href="%s">&laquo; Prev</a></li>', $url($current - 1));
    }

    // Window of pages
    $start = max(1, $current - 2);
    $end   = min($total, $current + 2);

    if ($start > 1) {
        $out .= sprintf('<li class="page-item"><a class="page-link" href="%s">1</a></li>', $url(1));
        if ($start > 2) {
            $out .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $current) {
            $out .= sprintf('<li class="page-item active" aria-current="page"><span class="page-link">%d</span></li>', $i);
        } else {
            $out .= sprintf('<li class="page-item"><a class="page-link" href="%s">%d</a></li>', $url($i), $i);
        }
    }

    if ($end < $total) {
        if ($end < $total - 1) {
            $out .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        $out .= sprintf('<li class="page-item"><a class="page-link" href="%s">%d</a></li>', $url($total), $total);
    }

    // Next
    if ($current >= $total) {
        $out .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
    } else {
        $out .= sprintf('<li class="page-item"><a class="page-link" href="%s">Next &raquo;</a></li>', $url($current + 1));
    }

    $out .= '</ul></nav>';
    return $out;
}
