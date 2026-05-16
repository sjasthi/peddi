$(function () {
    // ── DataTable (only when the table exists) ─────────────────────────────
    var $table = $('#entriesTable');
    if ($table.length) {
        $table.DataTable({
            responsive: true,
            pageLength: 25,
            order: [[1, 'asc']],          // sort by word
            columnDefs: [
                { orderable: false, targets: -1 }   // Actions column
            ]
        });
    }

    // ── Shared modal instances ─────────────────────────────────────────────
    var formModal   = new bootstrap.Modal(document.getElementById('entryFormModal'));
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteEntryModal'));

    // ── Add entry buttons (header + empty-state) ───────────────────────────
    $('#addEntryBtn, #addEntryBtn2').on('click', function () {
        $('#entryFormTitle').text('Add Entry');
        $('#entryFormAction').val('add');
        $('#entryFormId').val('');
        $('#entryWord').val('');
        $('#entryTranslation').val('');
        formModal.show();
        setTimeout(function () { $('#entryWord').focus(); }, 300);
    });

    // ── Edit button ────────────────────────────────────────────────────────
    $(document).on('click', '.edit-entry-btn', function () {
        var btn = $(this);
        $('#entryFormTitle').text('Edit Entry');
        $('#entryFormAction').val('edit');
        $('#entryFormId').val(btn.data('id'));
        $('#entryWord').val(btn.data('word'));
        $('#entryTranslation').val(btn.data('translation'));
        formModal.show();
        setTimeout(function () { $('#entryWord').focus(); }, 300);
    });

    // ── Delete button ──────────────────────────────────────────────────────
    $(document).on('click', '.delete-entry-btn', function () {
        var btn = $(this);
        $('#deleteEntryId').val(btn.data('id'));
        $('#deleteEntryWord').text(btn.data('word'));
        deleteModal.show();
    });

    // ── Basic client-side validation ───────────────────────────────────────
    $('#entryFormModal form').on('submit', function (e) {
        var word  = $.trim($('#entryWord').val());
        var trans = $.trim($('#entryTranslation').val());
        if (!word || !trans) {
            e.preventDefault();
            $('#entryWord').toggleClass('is-invalid', !word);
            $('#entryTranslation').toggleClass('is-invalid', !trans);
        }
    });

    $('#entryFormModal input').on('input', function () {
        $(this).removeClass('is-invalid');
    });
});
