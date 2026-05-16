$(function () {
    // ── Standard search form ───────────────────────────────────────────────
    var $input    = $('#q');
    var $clearBtn = $('#clearBtn');

    $input.on('input', function () {
        $clearBtn.toggleClass('d-none', $(this).val() === '');
    });

    $clearBtn.on('click', function () {
        $input.val('').focus();
        $(this).addClass('d-none');
    });

    $(document).on('keydown', function (e) {
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT'
                          && document.activeElement.tagName !== 'TEXTAREA'
                          && document.activeElement.tagName !== 'SELECT') {
            e.preventDefault();
            $input.focus().select();
        }
    });

    $('#searchForm').on('submit', function (e) {
        if ($.trim($input.val()) === '') {
            e.preventDefault();
            $input.addClass('is-invalid').focus();
        } else {
            $input.removeClass('is-invalid');
            $input.val($.trim($input.val()));
        }
    });

    $input.on('input', function () { $(this).removeClass('is-invalid'); });

    // ── Telugu Character Search panel ──────────────────────────────────────

    // Rotate chevron icon when panel opens / closes
    $('#teluguPanel').on('show.bs.collapse', function () {
        $('#teluguChevron').css('transform', 'rotate(180deg)');
    }).on('hide.bs.collapse', function () {
        $('#teluguChevron').css('transform', 'rotate(0deg)');
    });

    // Keep position ≤ length
    $('#tLength').on('input', function () {
        var len = parseInt($(this).val(), 10);
        var pos = parseInt($('#tPosition').val(), 10);
        if (pos > len) {
            $('#tPosition').val(len);
        }
        $('#tPosition').attr('max', isNaN(len) ? 20 : len);
    });

    // Form submit
    $('#teluguSearchForm').on('submit', function (e) {
        e.preventDefault();

        var char     = $.trim($('#tChar').val());
        var length   = parseInt($('#tLength').val(), 10);
        var position = parseInt($('#tPosition').val(), 10) || 0;
        var dictId   = parseInt($('#tDictId').val(), 10) || 0;

        // Validate
        var ok = true;
        if (!char) {
            $('#tChar').addClass('is-invalid'); ok = false;
        } else {
            $('#tChar').removeClass('is-invalid');
        }
        if (!length || length < 1 || length > 20) {
            $('#tLength').addClass('is-invalid'); ok = false;
        } else {
            $('#tLength').removeClass('is-invalid');
        }
        if (position > length) {
            $('#tPosition').addClass('is-invalid'); ok = false;
        } else {
            $('#tPosition').removeClass('is-invalid');
        }
        if (!ok) return;

        $('#teluguResults').html(
            '<div class="text-center py-4">' +
            '<div class="spinner-border text-info" role="status" aria-label="Searching"></div>' +
            '<p class="mt-2 text-body-secondary small mb-0">Searching Phase 1… then calling the Telugu character API for each candidate.</p>' +
            '</div>'
        );

        var exact   = $('#tExact').is(':checked') ? 1 : 0;
        var appBase = $('#appConfig').data('app-base') || '';
        var params  = { char: char, length: length, exact: exact };
        if (position > 0) params.position = position;
        if (dictId   > 0) params.dict_id  = dictId;

        $.ajax({
            url:      appBase + '/api/telugu_length_search.php',
            method:   'GET',
            data:     params,
            dataType: 'json'
        })
        .done(function (res) {
            renderTeluguResults(res, char, length, position);
        })
        .fail(function (xhr) {
            var msg = 'Search failed. Is the server running?';
            try {
                var err = JSON.parse(xhr.responseText);
                if (err && err.error) msg = err.error;
            } catch (ex) {}
            $('#teluguResults').html(
                '<div class="alert alert-danger d-flex gap-2">' +
                '<i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>' +
                '<span>' + $('<span>').text(msg).html() + '</span></div>'
            );
        });
    });

    $('#tChar, #tLength, #tPosition').on('input', function () {
        $(this).removeClass('is-invalid');
    });

    // ── Render Telugu search results ───────────────────────────────────────
    function renderTeluguResults(res, char, length, position) {
        var html = '';

        if (!res.success) {
            html = '<div class="alert alert-danger d-flex gap-2">' +
                   '<i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>' +
                   '<span>' + esc(res.error || 'Unknown error') + '</span></div>';
            $('#teluguResults').html(html);
            return;
        }

        // Meta line
        html += '<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">';
        var matchMode = res.exact
            ? '<span class="badge text-bg-warning text-dark ms-1">Exact Match</span>'
            : '<span class="badge text-bg-secondary ms-1">Partial Match</span>';

        html += '<p class="text-body-secondary small mb-0">';
        html += 'Found <strong>' + res.total + '</strong> word' + (res.total !== 1 ? 's' : '');
        html += ' of length <strong>' + length + '</strong> akshar' + (length !== 1 ? 's' : '');
        if (position > 0) {
            html += ' with <span class="font-monospace fw-bold">' + esc(char) + '</span>';
            html += ' at position <strong>' + position + '</strong>';
        } else {
            html += ' containing <span class="font-monospace fw-bold">' + esc(char) + '</span>';
        }
        html += ' ' + matchMode;
        html += ' — checked <strong>' + res.phase1_count + '</strong> candidate' +
                (res.phase1_count !== 1 ? 's' : '') + ' via API';
        if (res.phase2_failed > 0) {
            html += ' <span class="text-warning">(' + res.phase2_failed + ' could not be parsed)</span>';
        }
        html += '</p>';
        html += '</div>';

        if (res.total === 0) {
            html += '<div class="alert alert-info d-flex gap-2">' +
                    '<i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>' +
                    '<span>No matching words found. Try a different character, length, or position.</span></div>';
        } else {
            html += '<div class="table-responsive shadow-sm rounded">';
            html += '<table class="table table-hover table-bordered align-middle mb-0">';
            html += '<thead class="table-dark"><tr>' +
                    '<th>Word</th><th>Logical Characters</th>' +
                    '<th>Translation</th><th>Dictionary</th>' +
                    '</tr></thead><tbody>';

            $.each(res.results, function (i, row) {
                html += '<tr>';

                // Word
                html += '<td class="fw-medium font-monospace fs-5">' + esc(row.word) + '</td>';

                // Logical characters as badges — highlight matching position(s).
                // Exact mode: highlight only when the logical char === input char.
                // Partial mode: highlight when the logical char contains the input char.
                html += '<td>';
                $.each(row.chars, function (j, c) {
                    var charMatches = res.exact ? (c === char) : (c.indexOf(char) !== -1);
                    var isMatch = (position > 0)
                        ? (j === position - 1 && charMatches)
                        : charMatches;
                    html += '<span class="badge me-1 font-monospace ' +
                            (isMatch ? 'text-bg-info' : 'text-bg-secondary bg-opacity-25 text-body') +
                            '" style="font-size:.85rem">' + esc(c) + '</span>';
                });
                html += '</td>';

                // Translation
                html += '<td>' + esc(row.translation) + '</td>';

                // Dictionary
                html += '<td><span class="badge text-bg-secondary me-1">' +
                        esc(row.lang.toUpperCase()) + '</span>' +
                        '<span class="text-body-secondary small">' + esc(row.dict_name) + '</span></td>';

                html += '</tr>';
            });

            html += '</tbody></table></div>';
        }

        $('#teluguResults').html(html);
    }

    // Safe HTML escaper
    function esc(str) {
        return $('<span>').text(String(str)).html();
    }
});
