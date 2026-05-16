</main>

<footer class="bg-body-tertiary border-top mt-5 py-3">
    <div class="container text-center">
        <small class="text-body-secondary">
            &copy; <?= date('Y') ?> Peddi &mdash;
            <em>Words that connect languages, cultures, and people.</em>
            &ensp;&middot;&ensp;
            <a href="<?= APP_BASE ?>/catalog.php" class="link-secondary text-decoration-none">Catalog</a>
            &middot;
            <a href="<?= APP_BASE ?>/search.php"  class="link-secondary text-decoration-none">Search</a>
            &middot;
            <a href="<?= APP_BASE ?>/about.php"   class="link-secondary text-decoration-none">About</a>
        </small>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($loadDataTables)): ?>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<?php endif; ?>
<?php if (!empty($loadChartJs)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>
<?php if (!empty($pageScript)): ?>
<script src="<?= APP_BASE ?>/<?= htmlspecialchars($pageScript) ?>"></script>
<?php endif; ?>
<script>
// Theme toggle: flip data-bs-theme, update icon, persist cookie
(function () {
    var btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var html = document.documentElement;
        var next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        var icon = btn.querySelector('i');
        icon.className = next === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        document.cookie = 'theme=' + next + '; path=/; max-age=' + (365 * 24 * 3600) + '; SameSite=Lax';
    });
}());
</script>
</body>
</html>
