$(function () {
    // ── DataTable ──────────────────────────────────────────────────────────
    $('#dictTable').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 }   // Actions column
        ]
    });

    // ── Shared modal instance helpers ──────────────────────────────────────
    var formModal   = new bootstrap.Modal(document.getElementById('dictFormModal'));
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteDictModal'));

    // ── Add button ─────────────────────────────────────────────────────────
    $('#addDictBtn').on('click', function () {
        $('#dictFormTitle').text('Add Dictionary');
        $('#dictFormAction').val('add');
        $('#dictFormId').val('');
        $('#dictFormName').val('');
        $('#dictFormLang').val('');
        $('#dictFormDesc').val('');
        formModal.show();
        setTimeout(function () { $('#dictFormName').focus(); }, 300);
    });

    // ── Edit button ────────────────────────────────────────────────────────
    $(document).on('click', '.edit-dict-btn', function () {
        var btn = $(this);
        $('#dictFormTitle').text('Edit Dictionary');
        $('#dictFormAction').val('edit');
        $('#dictFormId').val(btn.data('id'));
        $('#dictFormName').val(btn.data('name'));
        $('#dictFormLang').val(btn.data('language-code'));
        $('#dictFormDesc').val(btn.data('description'));
        formModal.show();
        setTimeout(function () { $('#dictFormName').focus(); }, 300);
    });

    // ── Delete button ──────────────────────────────────────────────────────
    $(document).on('click', '.delete-dict-btn', function () {
        var btn = $(this);
        $('#deleteDictId').val(btn.data('id'));
        $('#deleteDictName').text(btn.data('name'));
        deleteModal.show();
    });

    // ── Basic client-side form validation ─────────────────────────────────
    $('#dictFormModal form').on('submit', function (e) {
        var name = $.trim($('#dictFormName').val());
        var lang = $.trim($('#dictFormLang').val());
        if (!name || !lang) {
            e.preventDefault();
            $('#dictFormName').toggleClass('is-invalid', !name);
            $('#dictFormLang').toggleClass('is-invalid', !lang);
        }
    });

    $('#dictFormModal input').on('input', function () {
        $(this).removeClass('is-invalid');
    });
});
