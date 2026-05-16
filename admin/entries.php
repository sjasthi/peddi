<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

// ── POST handler (PRG pattern) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $dictId  = max(0, (int)($_POST['dict_id'] ?? 0));
    $redirect = APP_BASE . '/admin/entries.php?dict_id=' . $dictId;

    if ($action === 'add') {
        $word  = trim($_POST['word']        ?? '');
        $trans = trim($_POST['translation'] ?? '');
        if ($dictId <= 0 || $word === '' || $trans === '') {
            header('Location: ' . $redirect . '&msg=error-invalid');
            exit;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO dictionary_entries (dictionary_id, word, translation) VALUES (:d, :w, :t)'
        );
        $stmt->execute([':d' => $dictId, ':w' => $word, ':t' => $trans]);
        header('Location: ' . $redirect . '&msg=add-ok');
        exit;
    }

    if ($action === 'edit') {
        $id    = (int)($_POST['id']          ?? 0);
        $word  = trim($_POST['word']        ?? '');
        $trans = trim($_POST['translation'] ?? '');
        if ($id <= 0 || $word === '' || $trans === '') {
            header('Location: ' . $redirect . '&msg=error-invalid');
            exit;
        }
        $stmt = $pdo->prepare(
            'UPDATE dictionary_entries SET word = :w, translation = :t WHERE id = :id'
        );
        $stmt->execute([':w' => $word, ':t' => $trans, ':id' => $id]);
        header('Location: ' . $redirect . '&msg=edit-ok');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare('DELETE FROM dictionary_entries WHERE id = :id')->execute([':id' => $id]);
        }
        header('Location: ' . $redirect . '&msg=delete-ok');
        exit;
    }

    header('Location: ' . $redirect);
    exit;
}

// ── Load dictionaries for selector ───────────────────────────────────────
$allDicts = $pdo->query('SELECT id, name, language_code FROM dictionaries ORDER BY name')->fetchAll();

$selectedDictId   = max(0, (int)($_GET['dict_id'] ?? 0));
$selectedDict     = null;
$entries          = [];

if ($selectedDictId > 0) {
    foreach ($allDicts as $d) {
        if ((int)$d['id'] === $selectedDictId) {
            $selectedDict = $d;
            break;
        }
    }
    if ($selectedDict !== null) {
        $entries = $pdo->prepare(
            'SELECT de.id, de.word, de.translation, de.created_at,
                    d.name AS dict_name
             FROM   dictionary_entries de
             JOIN   dictionaries d ON d.id = de.dictionary_id
             WHERE  de.dictionary_id = :did
             ORDER  BY de.word'
        );
        $entries->execute([':did' => $selectedDictId]);
        $entries = $entries->fetchAll();
    }
}

$flashMessages = [
    'add-ok'        => ['success', 'Entry added successfully.'],
    'edit-ok'       => ['success', 'Entry updated successfully.'],
    'delete-ok'     => ['success', 'Entry deleted successfully.'],
    'error-invalid' => ['danger',  'Please fill in all required fields.'],
    'error-db'      => ['danger',  'A database error occurred.'],
];

$pageTitle      = 'Entries' . ($selectedDict ? ' — ' . $selectedDict['name'] : '');
$pageScript     = 'assets/js/admin-entries.js';
$loadDataTables = true;

require_once __DIR__ . '/../includes/header.php';

$flash = $flashMessages[$_GET['msg'] ?? ''] ?? null;
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="h3 mb-0">
        <i class="bi bi-alphabet text-success me-2"></i>Dictionary Entries
    </h1>
    <?php if ($selectedDict): ?>
    <button id="addEntryBtn" class="btn btn-success">
        <i class="bi bi-plus-lg me-1"></i>Add Entry
    </button>
    <?php endif; ?>
</div>

<!-- ── Dictionary selector ───────────────────────────────────────────────── -->
<div class="card mb-4 shadow-sm">
    <div class="card-body py-2">
        <form method="get" action="" class="row g-2 align-items-center">
            <div class="col-auto">
                <label for="dict_id" class="col-form-label fw-semibold">Dictionary:</label>
            </div>
            <div class="col-sm-4 col-md-3">
                <select id="dict_id" name="dict_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">— Select a dictionary —</option>
                    <?php foreach ($allDicts as $d): ?>
                    <option value="<?= (int)$d['id'] ?>"
                        <?= $selectedDictId === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                        (<?= htmlspecialchars(strtoupper($d['language_code'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedDict): ?>
            <div class="col-auto text-body-secondary small">
                <?= count($entries) ?> entr<?= count($entries) !== 1 ? 'ies' : 'y' ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show d-flex gap-2" role="alert">
    <i class="bi bi-<?= $flash[0] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> flex-shrink-0 mt-1"></i>
    <span><?= htmlspecialchars($flash[1]) ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- ── Content area ──────────────────────────────────────────────────────── -->
<?php if (!$selectedDictId): ?>
    <div class="text-center py-5 text-body-secondary">
        <i class="bi bi-arrow-up-circle display-3 opacity-25"></i>
        <p class="mt-3 fs-5">Select a dictionary above to view and manage its entries.</p>
    </div>

<?php elseif ($selectedDict === null): ?>
    <div class="alert alert-warning">Dictionary not found.</div>

<?php else: ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($entries)): ?>
            <p class="text-body-secondary mb-0">
                No entries yet.
                <button id="addEntryBtn2" class="btn btn-sm btn-success ms-2">
                    <i class="bi bi-plus-lg me-1"></i>Add the first entry
                </button>
            </p>
            <?php else: ?>
            <div class="table-responsive">
                <table id="entriesTable" class="table table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Word</th>
                            <th>Translation</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><?= (int)$e['id'] ?></td>
                            <td class="fw-medium font-monospace">
                                <?= htmlspecialchars($e['word']) ?>
                            </td>
                            <td><?= htmlspecialchars($e['translation']) ?></td>
                            <td class="text-body-secondary small">
                                <?= date('Y-m-d', strtotime($e['created_at'])) ?>
                            </td>
                            <td class="text-center text-nowrap">
                                <button class="btn btn-sm btn-outline-primary edit-entry-btn"
                                        title="Edit"
                                        data-id="<?= (int)$e['id'] ?>"
                                        data-word="<?= htmlspecialchars($e['word'], ENT_QUOTES) ?>"
                                        data-translation="<?= htmlspecialchars($e['translation'], ENT_QUOTES) ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-entry-btn"
                                        title="Delete"
                                        data-id="<?= (int)$e['id'] ?>"
                                        data-word="<?= htmlspecialchars($e['word'], ENT_QUOTES) ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<!-- ── Add / Edit modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="entryFormModal" tabindex="-1" aria-labelledby="entryFormTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="entryFormTitle">Add Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action"    id="entryFormAction" value="add">
                    <input type="hidden" name="id"        id="entryFormId"     value="">
                    <input type="hidden" name="dict_id"   value="<?= $selectedDictId ?>">

                    <div class="mb-3">
                        <label for="entryWord" class="form-label fw-semibold">
                            Word <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="entryWord" name="word"
                               class="form-control font-monospace" maxlength="255" required>
                        <div class="form-text">Enter the word in its source language</div>
                    </div>

                    <div class="mb-3">
                        <label for="entryTranslation" class="form-label fw-semibold">
                            Translation <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="entryTranslation" name="translation"
                               class="form-control" maxlength="500" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ── Delete confirm modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="deleteEntryModal" tabindex="-1" aria-labelledby="deleteEntryTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteEntryTitle">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Delete Entry
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action"  value="delete">
                    <input type="hidden" name="id"      id="deleteEntryId" value="">
                    <input type="hidden" name="dict_id" value="<?= $selectedDictId ?>">
                    <p class="mb-0">Delete <strong id="deleteEntryWord"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
