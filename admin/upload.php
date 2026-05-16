<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

// ── XLSX parser (ZipArchive + SimpleXML, no external library) ─────────────

/** Convert a cell reference column letter(s) to a 0-based index. A→0, B→1, Z→25, AA→26 */
function xlsxColIdx(string $ref): int
{
    preg_match('/^([A-Z]+)/i', $ref, $m);
    $letters = strtoupper($m[1] ?? 'A');
    $idx     = 0;
    foreach (str_split($letters) as $ch) {
        $idx = $idx * 26 + (ord($ch) - 64);
    }
    return $idx - 1;
}

/** Parse an .xlsx file into an array of rows (each row is an array of strings). */
function parseXlsx(string $path): array|false
{
    if (!class_exists('ZipArchive')) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return false;
    }

    // Strip XML namespaces so SimpleXML works without namespace-aware calls
    $stripNs = static fn(string $xml): string =>
        preg_replace('/(\s)xmlns[^=]*="[^"]*"/', '', $xml) ?? $xml;

    // Build shared-strings table
    $strings = [];
    $ssRaw   = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssRaw !== false) {
        $ss = simplexml_load_string($stripNs($ssRaw));
        if ($ss) {
            foreach ($ss->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } else {
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $strings[] = $text;
                }
            }
        }
    }

    // Parse the first worksheet
    $sheetRaw = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if ($sheetRaw === false) {
        return false;
    }

    $sheet = simplexml_load_string($stripNs($sheetRaw));
    if (!$sheet) {
        return false;
    }

    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $rowData = [];
        foreach ($row->c as $cell) {
            $col   = xlsxColIdx((string) ($cell['r'] ?? 'A1'));
            $type  = (string) ($cell['t'] ?? '');
            $value = (string) ($cell->v ?? '');

            if ($type === 's') {
                $value = $strings[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) ($cell->is->t ?? '');
            }
            // Expand sparse row — keep blank cells as empty strings
            while (count($rowData) <= $col) {
                $rowData[] = '';
            }
            $rowData[$col] = $value;
        }
        if (!empty(array_filter($rowData, fn($v) => $v !== ''))) {
            $rows[] = $rowData;
        }
    }
    return $rows;
}

// ── POST: process upload ──────────────────────────────────────────────────
$result      = null;
$formDictId  = 0;       // which dict option to pre-select in the form after POST
$showNewForm = false;   // re-open the "create new" panel on error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $isNewDict  = ($_POST['dict_id'] ?? '') === 'new';
    $dictId     = $isNewDict ? 0 : (int) ($_POST['dict_id'] ?? 0);
    $skipHeader = !empty($_POST['skip_header']);
    $skipDups   = !empty($_POST['skip_duplicates']);

    // ── Step 1: create new dictionary if requested ────────────────────────
    if ($isNewDict) {
        $showNewForm = true;
        $newName = trim($_POST['new_dict_name'] ?? '');
        $newLang = trim($_POST['new_dict_lang'] ?? '');
        $newDesc = trim($_POST['new_dict_desc'] ?? '');

        if ($newName === '') {
            $result = ['status' => 'error', 'message' => 'Dictionary name is required when creating a new dictionary.'];
        } elseif ($newLang === '') {
            $result = ['status' => 'error', 'message' => 'Language code is required when creating a new dictionary.'];
        } elseif (mb_strlen($newLang) > 20) {
            $result = ['status' => 'error', 'message' => 'Language code must be 20 characters or fewer (e.g. tel, eng-tel-hin).'];
        } else {
            try {
                $ins = $pdo->prepare(
                    'INSERT INTO dictionaries (name, language_code, description) VALUES (:n, :l, :d)'
                );
                $ins->execute([
                    ':n' => $newName,
                    ':l' => $newLang,
                    ':d' => $newDesc !== '' ? $newDesc : null,
                ]);
                $dictId     = (int) $pdo->lastInsertId();
                $showNewForm = false;
            } catch (PDOException $e) {
                error_log('Peddi create dict error: ' . $e->getMessage());
                $result = ['status' => 'error', 'message' => 'Could not create the dictionary. The name may already be in use.'];
            }
        }
    }

    // ── Step 2: validate file upload ──────────────────────────────────────
    if ($result === null) {
        if ($dictId <= 0) {
            $result = ['status' => 'error', 'message' => 'Please select or create a target dictionary.'];
        } else {
            $formDictId = $dictId;
            $uploadErr  = $_FILES['import_file']['error'] ?? UPLOAD_ERR_NO_FILE;

            if ($uploadErr !== UPLOAD_ERR_OK) {
                $errMap = [
                    UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload_max_filesize limit.',
                    UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
                    UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE    => 'No file was selected.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file to disk.',
                ];
                $result = ['status' => 'error', 'message' => $errMap[$uploadErr] ?? 'Unknown upload error.'];
            } else {
                $file = $_FILES['import_file'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, ['csv', 'xlsx'], true)) {
                    $result = ['status' => 'error', 'message' => 'Unsupported file type. Upload a .csv or .xlsx file.'];
                } else {
                    $rows = [];

                    if ($ext === 'csv') {
                        $handle = fopen($file['tmp_name'], 'r');
                        if ($handle) {
                            // Detect and discard UTF-8 BOM
                            $bom = fread($handle, 3);
                            if ($bom !== "\xEF\xBB\xBF") {
                                rewind($handle);
                            }
                            while (($row = fgetcsv($handle)) !== false) {
                                $rows[] = $row;
                            }
                            fclose($handle);
                        }
                    } else {
                        $parsed = parseXlsx($file['tmp_name']);
                        if ($parsed === false) {
                            $result = ['status' => 'error',
                                       'message' => 'Could not parse the XLSX file. Try saving as CSV and re-uploading.'];
                        } else {
                            $rows = $parsed;
                        }
                    }

                    if ($result === null) {
                        if ($skipHeader && !empty($rows)) {
                            array_shift($rows);
                        }

                        // Preload existing words for duplicate check
                        $existing = [];
                        if ($skipDups) {
                            $stmt = $pdo->prepare(
                                'SELECT LOWER(word) FROM dictionary_entries WHERE dictionary_id = :d'
                            );
                            $stmt->execute([':d' => $dictId]);
                            $existing = array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
                        }

                        $inserted   = 0;
                        $skipped    = [];
                        $insertStmt = $pdo->prepare(
                            'INSERT INTO dictionary_entries (dictionary_id, word, translation) VALUES (:d, :w, :t)'
                        );

                        $pdo->beginTransaction();
                        try {
                            foreach ($rows as $i => $row) {
                                $lineNum = $i + 1 + ($skipHeader ? 1 : 0);
                                $word    = trim($row[0] ?? '');
                                $trans   = trim($row[1] ?? '');

                                if ($word === '') {
                                    $skipped[] = "Row $lineNum: word is empty — skipped.";
                                    continue;
                                }
                                if ($trans === '') {
                                    $skipped[] = "Row $lineNum: translation is empty for \"{$word}\" — skipped.";
                                    continue;
                                }
                                if (mb_strlen($word) > 255) {
                                    $skipped[] = "Row $lineNum: word exceeds 255 characters — skipped.";
                                    continue;
                                }
                                if ($skipDups && isset($existing[mb_strtolower($word)])) {
                                    $skipped[] = "Row $lineNum: \"{$word}\" already exists — skipped.";
                                    continue;
                                }

                                $insertStmt->execute([':d' => $dictId, ':w' => $word, ':t' => $trans]);
                                $inserted++;
                                if ($skipDups) {
                                    $existing[mb_strtolower($word)] = true;
                                }
                            }
                            $pdo->commit();
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            error_log('Peddi upload import error: ' . $e->getMessage());
                            $result = ['status' => 'error', 'message' => 'Database error during import. No rows were saved.'];
                        }

                        if ($result === null) {
                            $result = [
                                'status'     => 'ok',
                                'inserted'   => $inserted,
                                'skipped'    => $skipped,
                                'dict_id'    => $dictId,
                                'dict_new'   => $isNewDict,
                            ];
                        }
                    }
                }
            }
        }
    }
}

// Load dict list AFTER potential new-dict creation so it appears in the dropdown
$allDicts = $pdo->query('SELECT id, name FROM dictionaries ORDER BY name')->fetchAll();

// After a successful import, pre-select the used dictionary in the form
if ($result !== null && $result['status'] === 'ok') {
    $formDictId = $result['dict_id'];
}

$pageTitle      = 'Upload / Import';
$pageScript     = 'assets/js/admin-upload.js';
$loadDataTables = !empty($result['skipped']);

require_once __DIR__ . '/../includes/header.php';

$maxUpload = ini_get('upload_max_filesize');
$maxPost   = ini_get('post_max_size');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-cloud-upload text-primary me-2"></i>Upload / Bulk Import
    </h1>
</div>

<div class="alert alert-info d-flex gap-2 py-2 mb-4" role="alert">
    <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
    <div>
        Upload a <strong>.csv</strong> or <strong>.xlsx</strong> file with columns:
        <code>word</code> (col A) and <code>translation</code> (col B).
        Server limits: upload_max_filesize = <strong><?= htmlspecialchars($maxUpload) ?></strong>,
        post_max_size = <strong><?= htmlspecialchars($maxPost) ?></strong>.
    </div>
</div>

<!-- ── Results ──────────────────────────────────────────────────────────── -->
<?php if ($result !== null): ?>
    <?php if ($result['status'] === 'error'): ?>
    <div class="alert alert-danger d-flex gap-2 mb-4" role="alert">
        <i class="bi bi-x-circle-fill flex-shrink-0 mt-1"></i>
        <span><?= htmlspecialchars($result['message']) ?></span>
    </div>
    <?php else: ?>
    <div class="alert alert-<?= $result['inserted'] > 0 ? 'success' : 'warning' ?> d-flex gap-2 mb-4" role="alert">
        <i class="bi bi-<?= $result['inserted'] > 0 ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> flex-shrink-0 mt-1"></i>
        <div>
            <?php if (!empty($result['dict_new'])): ?>
            <strong>New dictionary created.</strong>
            <?php endif; ?>
            <strong><?= $result['inserted'] ?></strong> entr<?= $result['inserted'] !== 1 ? 'ies' : 'y' ?> imported,
            <strong><?= count($result['skipped']) ?></strong> row<?= count($result['skipped']) !== 1 ? 's' : '' ?> skipped.
            <?php if ($result['inserted'] > 0): ?>
            <a href="<?= APP_BASE ?>/admin/entries.php?dict_id=<?= $result['dict_id'] ?>"
               class="alert-link ms-1">View entries →</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($result['skipped'])): ?>
    <div class="card mb-4">
        <div class="card-header fw-semibold text-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>Skipped Rows
        </div>
        <div class="card-body p-0">
            <table id="skippedTable" class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Reason</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($result['skipped'] as $i => $msg): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($msg) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<!-- ── Upload form ──────────────────────────────────────────────────────── -->
<div class="card shadow-sm" style="max-width:640px">
    <div class="card-header fw-semibold">
        <i class="bi bi-file-earmark-arrow-up me-1"></i>Import File
    </div>
    <div class="card-body">
        <form method="post" action="" enctype="multipart/form-data" novalidate
              data-show-new="<?= $showNewForm ? '1' : '0' ?>">

            <!-- ── Dictionary selector ──────────────────────────────────── -->
            <div class="mb-3">
                <label for="dict_id" class="form-label fw-semibold">
                    Target Dictionary <span class="text-danger">*</span>
                </label>
                <select id="dict_id" name="dict_id" class="form-select" required>
                    <option value="0">— Select a dictionary —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"
                        <?= $formDictId === (int) $d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                    <option value="new" <?= $showNewForm ? 'selected' : '' ?>>
                        ✚ Create a new dictionary…
                    </option>
                </select>
            </div>

            <!-- ── New dictionary fields (hidden until "new" is chosen) ─── -->
            <div id="newDictPanel" class="card bg-body-secondary border-primary border-opacity-25 mb-3 p-3"
                 style="display:none">
                <p class="fw-semibold small text-primary mb-3">
                    <i class="bi bi-plus-circle me-1"></i>New Dictionary Details
                </p>

                <div class="mb-2">
                    <label for="new_dict_name" class="form-label form-label-sm fw-semibold">
                        Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="new_dict_name" name="new_dict_name"
                           class="form-control form-control-sm"
                           placeholder="e.g. Hindi-English Dictionary"
                           value="<?= htmlspecialchars($_POST['new_dict_name'] ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label for="new_dict_lang" class="form-label form-label-sm fw-semibold">
                        Language Code <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="new_dict_lang" name="new_dict_lang"
                           class="form-control form-control-sm"
                           placeholder="e.g. hin, ben, tam"
                           maxlength="20"
                           list="langCodeList"
                           autocomplete="off"
                           value="<?= htmlspecialchars($_POST['new_dict_lang'] ?? '') ?>">
                    <datalist id="langCodeList">
                        <option value="tel">tel — Telugu</option>
                        <option value="san">san — Sanskrit</option>
                        <option value="hin">hin — Hindi</option>
                        <option value="ben">ben — Bengali</option>
                        <option value="tam">tam — Tamil</option>
                        <option value="kan">kan — Kannada</option>
                        <option value="mal">mal — Malayalam</option>
                        <option value="mar">mar — Marathi</option>
                        <option value="guj">guj — Gujarati</option>
                        <option value="pan">pan — Punjabi</option>
                        <option value="urd">urd — Urdu</option>
                        <option value="ori">ori — Odia</option>
                        <option value="asm">asm — Assamese</option>
                        <option value="kas">kas — Kashmiri</option>
                        <option value="nep">nep — Nepali</option>
                        <option value="sin">sin — Sinhala</option>
                    </datalist>
                    <div class="form-text">
                        ISO 639 code (up to 20 chars). Type to see suggestions, or use a combined code like <code>eng-tel-hin</code> for multilingual dictionaries.
                    </div>
                </div>

                <div class="mb-0">
                    <label for="new_dict_desc" class="form-label form-label-sm fw-semibold">
                        Description <span class="text-body-secondary fw-normal">(optional)</span>
                    </label>
                    <textarea id="new_dict_desc" name="new_dict_desc"
                              class="form-control form-control-sm" rows="2"
                              placeholder="A short description of this dictionary…"><?= htmlspecialchars($_POST['new_dict_desc'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- ── File input ────────────────────────────────────────────── -->
            <div class="mb-3">
                <label for="import_file" class="form-label fw-semibold">
                    File (.csv or .xlsx) <span class="text-danger">*</span>
                </label>
                <input type="file" id="import_file" name="import_file"
                       class="form-control" accept=".csv,.xlsx" required>
                <div id="fileInfo" class="form-text"></div>
            </div>

            <!-- ── Options ───────────────────────────────────────────────── -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           id="skip_header" name="skip_header" value="1"
                           <?= !empty($_POST['skip_header']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="skip_header">
                        First row is a header — skip it
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                           id="skip_duplicates" name="skip_duplicates" value="1"
                           checked <?= !empty($_POST['skip_duplicates']) ? '' : '' ?>>
                    <label class="form-check-label" for="skip_duplicates">
                        Skip rows where the word already exists in the dictionary
                    </label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i>Upload &amp; Import
            </button>
        </form>
    </div>
</div>

<!-- ── CSV format guide ──────────────────────────────────────────────────── -->
<div class="card mt-4 shadow-sm" style="max-width:640px">
    <div class="card-header fw-semibold">
        <i class="bi bi-file-text me-1"></i>Expected File Format
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0">
            <thead class="table-dark">
                <tr><th>Column A — word</th><th>Column B — translation</th></tr>
            </thead>
            <tbody>
                <tr><td class="text-body-secondary">word</td><td class="text-body-secondary">translation</td></tr>
                <tr><td>నీరు</td><td>water</td></tr>
                <tr><td>జల</td><td>water (formal)</td></tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-body-secondary small">
        Row 1 is treated as data unless "Skip header row" is checked.
        Extra columns beyond B are ignored.
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
