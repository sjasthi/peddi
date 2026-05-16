$(function () {
    var dtOptions = {
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']]
    };

    if ($('#dupTable').length) {
        $('#dupTable').DataTable($.extend({}, dtOptions, {
            order: [[1, 'desc']]   // sort by occurrence count descending
        }));
    }

    if ($('#gapTable').length) {
        $('#gapTable').DataTable(dtOptions);
    }
});
