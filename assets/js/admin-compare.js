$(function () {
    var dtOptions = {
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']]
    };

    // Init DataTables on all compare tables inside the active tab immediately
    $('#tab-shared .compare-table').DataTable(dtOptions);

    // Init remaining tabs lazily when they first become visible
    // (DataTables needs visible elements for correct column widths)
    var initialized = { 'tab-shared': true };

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var targetId = $(e.target).data('bs-target').replace('#', '');
        if (initialized[targetId]) {
            return;
        }
        initialized[targetId] = true;
        $('#' + targetId + ' .compare-table').DataTable(dtOptions);
    });
});
