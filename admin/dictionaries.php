<?php
require_once __DIR__ . '/../includes/auth.php';
auth_check();

require_once __DIR__ . '/../includes/db.php';

// ── POST handler (PRG pattern) ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $redirect = APP_BASE . '/admin/dictionaries.php';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $lang = trim($_POST['language_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($name === '' || $lang === '') {
            header('Location: ' . $redirect . '?msg=error-invalid');
            exit;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO dictionaries (name, language_code, description) VALUES (:n, :l, :d)'
        );
        $stmt->execute([':n' => $name, ':l' => $lang, ':d' => $desc]);
        header('Location: ' . $redirect . '?msg=add-ok');
        exit;
    }

    if ($action === 'edit') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $lang = trim($_POST['language_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($id <= 0 || $name === '' || $lang === '') {
            header('Location: ' . $redirect . '?msg=error-invalid');
            exit;
        }
        $stmt = $pdo->prepare(
            'UPDATE dictionaries SET name = :n, language_code = :l, description = :d WHERE id = :id'
        );
        $stmt->execute([':n' => $name, ':l' => $lang, ':d' => $desc, ':id' => $id]);
        header('Location: ' . $redirect . '?msg=edit-ok');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // ON DELETE CASCADE handles entries automatically
            $pdo->prepare('DELETE FROM dictionaries WHERE id = :id')->execute([':id' => $id]);
        }
        header('Location: ' . $redirect . '?msg=delete-ok');
        exit;
    }

    header('Location: ' . $redirect);
    exit;
}

// ── Load data ─────────────────────────────────────────────────────────────
$dictionaries = $pdo->query(
    'SELECT d.id, d.name, d.language_code, d.description, d.created_at,
            COUNT(de.id) AS word_count
     FROM   dictionaries d
     LEFT JOIN dictionary_entries de ON de.dictionary_id = d.id
     GROUP  BY d.id
     ORDER  BY d.name'
)->fetchAll();

// Flash message map
$flashMessages = [
    'add-ok'        => ['success', 'Dictionary added successfully.'],
    'edit-ok'       => ['success', 'Dictionary updated successfully.'],
    'delete-ok'     => ['success', 'Dictionary deleted successfully.'],
    'error-invalid' => ['danger',  'Please fill in all required fields.'],
    'error-db'      => ['danger',  'A database error occurred.'],
];

$pageTitle       = 'Dictionaries';
$pageScript      = 'assets/js/admin-dictionaries.js';
$loadDataTables  = true;

require_once __DIR__ . '/../includes/header.php';

$flash = $flashMessages[$_GET['msg'] ?? ''] ?? null;
?>

<!-- ── Page header ──────────────────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
        <i class="bi bi-collection text-primary me-2"></i>Dictionaries
    </h1>
    <button id="addDictBtn" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Add Dictionary
    </button>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash[0] ?> alert-dismissible fade show d-flex gap-2" role="alert">
    <i class="bi bi-<?= $flash[0] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> flex-shrink-0 mt-1"></i>
    <span><?= htmlspecialchars($flash[1]) ?></span>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- ── DataTable ─────────────────────────────────────────────────────────── -->
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="dictTable" class="table table-hover align-middle w-100">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Lang</th>
                        <th>Description</th>
                        <th class="text-end">Words</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dictionaries as $d): ?>
                    <tr>
                        <td><?= (int)$d['id'] ?></td>
                        <td class="fw-medium"><?= htmlspecialchars($d['name']) ?></td>
                        <td>
                            <span class="badge text-bg-secondary">
                                <?= htmlspecialchars(strtoupper($d['language_code'])) ?>
                            </span>
                        </td>
                        <td class="text-truncate text-body-secondary small" style="max-width:220px"
                            title="<?= htmlspecialchars($d['description']) ?>">
                            <?= htmlspecialchars($d['description'] ?: '—') ?>
                        </td>
                        <td class="text-end"><?= number_format((int)$d['word_count']) ?></td>
                        <td class="text-body-secondary small">
                            <?= date('Y-m-d', strtotime($d['created_at'])) ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= APP_BASE ?>/admin/entries.php?dict_id=<?= (int)$d['id'] ?>"
                               class="btn btn-sm btn-outline-secondary" title="View entries">
                                <i class="bi bi-list-ul"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary edit-dict-btn"
                                    title="Edit"
                                    data-id="<?= (int)$d['id'] ?>"
                                    data-name="<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>"
                                    data-language-code="<?= htmlspecialchars($d['language_code'], ENT_QUOTES) ?>"
                                    data-description="<?= htmlspecialchars($d['description'] ?? '', ENT_QUOTES) ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-dict-btn"
                                    title="Delete"
                                    data-id="<?= (int)$d['id'] ?>"
                                    data-name="<?= htmlspecialchars($d['name'], ENT_QUOTES) ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── Add / Edit modal ─────────────────────────────────────────────────── -->
<div class="modal fade" id="dictFormModal" tabindex="-1" aria-labelledby="dictFormTitle" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dictFormTitle">Add Dictionary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="dictFormAction" value="add">
                    <input type="hidden" name="id"     id="dictFormId"     value="">

                    <div class="mb-3">
                        <label for="dictFormName" class="form-label fw-semibold">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="dictFormName" name="name"
                               class="form-control" maxlength="100" required>
                        <div class="form-text">e.g. Telugu-English Dictionary</div>
                    </div>

                    <div class="mb-3">
                        <label for="dictFormLang" class="form-label fw-semibold">
                            Language Code <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="dictFormLang" name="language_code"
                               class="form-control" maxlength="10" required>
                        <div class="form-text">ISO 639 code — e.g. <code>tel</code>, <code>san</code>, <code>hin</code></div>
                    </div>

                    <div class="mb-3">
                        <label for="dictFormDesc" class="form-label fw-semibold">Description</label>
                        <textarea id="dictFormDesc" name="description"
                                  class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ── Delete confirm modal ──────────────────────────────────────────────── -->
<div class="modal fade" id="deleteDictModal" tabindex="-1" aria-labelledby="deleteDictTitle" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form method="post" action="">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteDictTitle">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Delete Dictionary
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id"     id="deleteDictId" value="">
                    <p class="mb-1">Delete <strong id="deleteDictName"></strong>?</p>
                    <p class="text-danger small mb-0">
                        <i class="bi bi-exclamation-circle me-1"></i>All entries will also be deleted.
                    </p>
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
