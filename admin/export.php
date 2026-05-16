<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

$allDicts = $pdo->query('SELECT id, name, language_code FROM dictionaries ORDER BY name')->fetchAll();

$dictIndex = [];
foreach ($allDicts as $d) {
    $dictIndex[(int) $d['id']] = $d;
}

$dictId = (int) ($_GET['dict_id'] ?? 0);
$format = $_GET['format'] ?? '';

// ── If a valid export request, stream the file and exit ───────────────────
if ($dictId > 0 && isset($dictIndex[$dictId]) && in_array($format, ['csv', 'json', 'html'], true)) {

    $dict = $dictIndex[$dictId];
    $stmt = $pdo->prepare(
        'SELECT word, translation FROM dictionary_entries
         WHERE dictionary_id = :d ORDER BY word'
    );
    $stmt->execute([':d' => $dictId]);
    $entries = $stmt->fetchAll();

    $safeName = preg_replace('/[^a-z0-9_-]+/i', '_', $dict['name']);
    $filename = $safeName . '.' . $format;

    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM so Excel opens it correctly
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['word', 'translation']);
        foreach ($entries as $e) {
            fputcsv($out, [$e['word'], $e['translation']]);
        }
        fclose($out);
        exit;
    }

    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $payload = [
            'dictionary'   => $dict['name'],
            'language_code'=> $dict['language_code'],
            'exported_at'  => date('c'),
            'count'        => count($entries),
            'entries'      => array_map(fn($e) => ['word' => $e['word'], 'translation' => $e['translation']], $entries),
        ];
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($format === 'html') {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');

        $dictNameSafe = htmlspecialchars($dict['name']);
        $langSafe     = htmlspecialchars($dict['language_code']);
        $dateSafe     = htmlspecialchars(date('Y-m-d H:i'));

        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$dictNameSafe}</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h1 class="mb-1">{$dictNameSafe}</h1>
<p class="text-body-secondary mb-4">Language: <strong>{$langSafe}</strong> &nbsp;|&nbsp; Exported: {$dateSafe} &nbsp;|&nbsp; Entries: <strong>
HTML;
        echo count($entries);
        echo <<<HTML
</strong></p>
<table class="table table-bordered table-striped table-hover table-sm">
<thead class="table-dark"><tr><th>Word</th><th>Translation</th></tr></thead>
<tbody>
HTML;
        foreach ($entries as $e) {
            echo '<tr><td class="font-monospace">' . htmlspecialchars($e['word'])
               . '</td><td>' . htmlspecialchars($e['translation']) . '</td></tr>' . "\n";
        }
        echo <<<HTML
</tbody>
</table>
</body>
</html>
HTML;
        exit;
    }
}

// ── Page: show export form ────────────────────────────────────────────────
$pageTitle = 'Export';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-box-arrow-up text-secondary me-2"></i>Export Dictionary
    </h1>
</div>

<?php if ($dictId > 0 && !isset($dictIndex[$dictId])): ?>
<div class="alert alert-danger">Invalid dictionary selected.</div>
<?php endif; ?>

<div class="card shadow-sm" style="max-width:560px">
    <div class="card-header fw-semibold">
        <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Options
    </div>
    <div class="card-body">
        <form method="get" action="">

            <div class="mb-3">
                <label for="dict_id" class="form-label fw-semibold">
                    Dictionary <span class="text-danger">*</span>
                </label>
                <select id="dict_id" name="dict_id" class="form-select" required>
                    <option value="0">— Select a dictionary —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $dictId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Format <span class="text-danger">*</span></label>
                <div class="d-flex flex-column gap-2">

                    <div class="form-check border rounded px-3 py-2">
                        <input class="form-check-input" type="radio" name="format"
                               id="fmt_csv" value="csv"
                               <?= $format === 'csv' || $format === '' ? 'checked' : '' ?> required>
                        <label class="form-check-label w-100" for="fmt_csv">
                            <strong>CSV</strong>
                            <span class="text-body-secondary small d-block">
                                Comma-separated values with UTF-8 BOM.
                                Opens correctly in Excel and LibreOffice.
                            </span>
                        </label>
                    </div>

                    <div class="form-check border rounded px-3 py-2">
                        <input class="form-check-input" type="radio" name="format"
                               id="fmt_json" value="json"
                               <?= $format === 'json' ? 'checked' : '' ?>>
                        <label class="form-check-label w-100" for="fmt_json">
                            <strong>JSON</strong>
                            <span class="text-body-secondary small d-block">
                                Pretty-printed JSON array with metadata.
                                Suitable for importing into other systems.
                            </span>
                        </label>
                    </div>

                    <div class="form-check border rounded px-3 py-2">
                        <input class="form-check-input" type="radio" name="format"
                               id="fmt_html" value="html"
                               <?= $format === 'html' ? 'checked' : '' ?>>
                        <label class="form-check-label w-100" for="fmt_html">
                            <strong>HTML</strong>
                            <span class="text-body-secondary small d-block">
                                Standalone webpage with a Bootstrap table.
                                Print-ready or share as a file.
                            </span>
                        </label>
                    </div>

                </div>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="bi bi-download me-1"></i>Download
            </button>
        </form>
    </div>
</div>

<!-- ── Preview word counts ────────────────────────────────────────────────── -->
<?php if (!empty($allDicts)): ?>
<div class="card mt-4 shadow-sm" style="max-width:560px">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart me-1"></i>Dictionary Word Counts
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr><th>Dictionary</th><th>Language</th><th class="text-end">Words</th></tr>
            </thead>
            <tbody>
                <?php
                $countStmt = $pdo->query(
                    'SELECT d.id, d.name, d.language_code, COUNT(e.id) AS cnt
                     FROM dictionaries d
                     LEFT JOIN dictionary_entries e ON e.dictionary_id = d.id
                     GROUP BY d.id ORDER BY d.name'
                );
                foreach ($countStmt->fetchAll() as $row):
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><code><?= htmlspecialchars($row['language_code']) ?></code></td>
                    <td class="text-end"><?= (int) $row['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
