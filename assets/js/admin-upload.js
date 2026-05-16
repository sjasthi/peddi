$(function () {

    // ── New-dictionary panel toggle ────────────────────────────────────────
    var $dictSelect   = $('#dict_id');
    var $newDictPanel = $('#newDictPanel');

    function toggleNewDict() {
        var isNew = $dictSelect.val() === 'new';
        $newDictPanel.toggle(isNew);
        // Required attributes follow visibility so the browser won't block submit
        // when the panel is hidden
        $newDictPanel.find('[id^="new_dict_name"], [id^="new_dict_lang"]')
            .prop('required', isNew);
    }

    $dictSelect.on('change', toggleNewDict);

    // Restore state on page load (e.g. after a POST validation error)
    var showNew = $('form[data-show-new]').data('show-new');
    if (showNew === 1 || showNew === '1') {
        $dictSelect.val('new');
    }
    toggleNewDict();

    // ── File input display ─────────────────────────────────────────────────
    $('#import_file').on('change', function () {
        var file = this.files[0];
        if (!file) {
            $('#fileInfo').text('');
            return;
        }
        var kb   = (file.size / 1024).toFixed(1);
        var mb   = (file.size / (1024 * 1024)).toFixed(2);
        var size = file.size >= 1024 * 1024 ? mb + ' MB' : kb + ' KB';
        $('#fileInfo').html(
            '<i class="bi bi-file-earmark me-1"></i>' +
            '<strong>' + $('<span>').text(file.name).html() + '</strong>' +
            ' &mdash; ' + size
        );
    });

    // ── Client-side validation ─────────────────────────────────────────────
    $('form').on('submit', function () {
        var ok = true;

        // Must pick a dict or fill in new dict name+lang
        if ($dictSelect.val() === '0') {
            $dictSelect.addClass('is-invalid');
            ok = false;
        } else {
            $dictSelect.removeClass('is-invalid');
        }

        if ($dictSelect.val() === 'new') {
            var $name = $('#new_dict_name');
            var $lang = $('#new_dict_lang');
            if (!$.trim($name.val())) { $name.addClass('is-invalid'); ok = false; }
            else { $name.removeClass('is-invalid'); }
            if (!$.trim($lang.val())) { $lang.addClass('is-invalid'); ok = false; }
            else { $lang.removeClass('is-invalid'); }
        }

        return ok;
    });

    $dictSelect.on('change', function () { $(this).removeClass('is-invalid'); });
    $('#new_dict_name, #new_dict_lang').on('input', function () {
        $(this).removeClass('is-invalid');
    });

    // ── Skipped rows DataTable ─────────────────────────────────────────────
    if ($('#skippedTable').length) {
        $('#skippedTable').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [{ orderable: false, targets: 1 }]
        });
    }
});
