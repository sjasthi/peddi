</main>

<!-- ── Rangoli-inspired divider ─────────────────────────────────── -->
<div class="footer-ornament mt-5" aria-hidden="true">
    <svg viewBox="0 0 800 36" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet">
        <!-- Fading lines left and right of centre -->
        <line x1="0"   y1="18" x2="310" y2="18" stroke="#D97706" stroke-width="0.8" opacity="0.25"/>
        <line x1="490" y1="18" x2="800" y2="18" stroke="#D97706" stroke-width="0.8" opacity="0.25"/>
        <!-- Accent dots stepping inward -->
        <circle cx="295" cy="18" r="2.5" fill="#D97706" opacity="0.35"/>
        <circle cx="505" cy="18" r="2.5" fill="#D97706" opacity="0.35"/>
        <circle cx="278" cy="18" r="1.8" fill="#D97706" opacity="0.25"/>
        <circle cx="522" cy="18" r="1.8" fill="#D97706" opacity="0.25"/>
        <circle cx="264" cy="18" r="1.2" fill="#D97706" opacity="0.18"/>
        <circle cx="536" cy="18" r="1.2" fill="#D97706" opacity="0.18"/>
        <!-- Centre lotus / mandala motif -->
        <g transform="translate(400,18)">
            <!-- 8 petals -->
            <path d="M0,-14 C5,-9 5,-4 0,0 C-5,-4 -5,-9 0,-14Z"          fill="#D97706" opacity="0.28"/>
            <path d="M0, 14 C5, 9 5, 4 0,0 C-5, 4 -5, 9 0, 14Z"          fill="#D97706" opacity="0.28"/>
            <path d="M-14,0 C-9,-5 -4,-5 0,0 C-4, 5 -9, 5 -14,0Z"         fill="#D97706" opacity="0.28"/>
            <path d="M 14,0 C  9,-5  4,-5 0,0 C 4, 5  9, 5  14,0Z"         fill="#D97706" opacity="0.28"/>
            <path d="M-10,-10 C-6,-8 -4,-5 0,0 C-5,-4 -8,-6 -10,-10Z"      fill="#D97706" opacity="0.18"/>
            <path d="M 10,-10 C 6,-8  4,-5 0,0 C 5,-4  8,-6  10,-10Z"      fill="#D97706" opacity="0.18"/>
            <path d="M-10, 10 C-6, 8 -4, 5 0,0 C-5, 4 -8, 6 -10, 10Z"     fill="#D97706" opacity="0.18"/>
            <path d="M 10, 10 C 6, 8  4, 5 0,0 C 5, 4  8, 6  10, 10Z"     fill="#D97706" opacity="0.18"/>
            <!-- Outer ring -->
            <circle r="16" fill="none" stroke="#D97706" stroke-width="0.7" opacity="0.22"/>
            <!-- Inner ring -->
            <circle r="7" fill="none" stroke="#D97706" stroke-width="0.7" opacity="0.35"/>
            <!-- Centre dot -->
            <circle r="3" fill="#D97706" opacity="0.55"/>
            <circle r="1.2" fill="#fff" opacity="0.7"/>
        </g>
    </svg>
</div>

<footer class="bg-body-tertiary border-top py-4">
    <div class="container text-center">
        <p class="footer-tagline text-body-secondary mb-2">
            "Words that connect languages, cultures, and people."
        </p>
        <small class="text-body-secondary opacity-75">
            &copy; <?= date('Y') ?> Peddi
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
