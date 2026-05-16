$(function () {
    var APP_BASE = $('#appConfig').data('app-base') || '';

    var $form  = $('#heroSearchForm');
    var $input = $('#heroQuery');
    var $dict  = $('#heroDictionary');

    // Attach autocomplete list right after the input wrapper
    var $list = $('<ul class="autocomplete-list"></ul>').appendTo($input.parent());
    var timer;

    $input.on('input', function () {
        clearTimeout(timer);
        var q = $.trim($(this).val());
        if (q.length < 2) { $list.empty().hide(); return; }

        timer = setTimeout(function () {
            $.ajax({
                url:      APP_BASE + '/api/search.php',
                method:   'GET',
                dataType: 'json',
                data: { q: q, mode: 'prefix', d: $dict.val(), limit: 8 },
                success: function (res) {
                    $list.empty();
                    if (res.status !== 'ok' || !res.results || !res.results.length) {
                        $list.hide();
                        return;
                    }
                    $.each(res.results, function (_, item) {
                        $('<li>')
                            .text(item.word + ' — ' + item.translation)
                            .on('mousedown', function (e) {
                                // mousedown fires before blur so we can intercept
                                e.preventDefault();
                                $input.val(item.word);
                                $list.empty().hide();
                                $form.trigger('submit');
                            })
                            .appendTo($list);
                    });
                    $list.show();
                },
                error: function () { $list.empty().hide(); }
            });
        }, 250);
    });

    $input.on('keydown', function (e) {
        var $items = $list.find('li');
        if (!$items.length) return;

        var $active = $items.filter('.active');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            var $next = $active.length ? $active.removeClass('active').next() : $items.first();
            $next.addClass('active');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var $prev = $active.length ? $active.removeClass('active').prev() : $items.last();
            $prev.addClass('active');
        } else if (e.key === 'Enter' && $active.length) {
            e.preventDefault();
            $input.val($active.text().split(' — ')[0]);
            $list.empty().hide();
            $form.trigger('submit');
        } else if (e.key === 'Escape') {
            $list.empty().hide();
        }
    });

    // Close on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#heroQuery, .autocomplete-list').length) {
            $list.empty().hide();
        }
    });
});
