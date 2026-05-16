<?php
$pageTitle = 'About';
require_once __DIR__ . '/includes/db.php';

$totalDicts = (int) $pdo->query('SELECT COUNT(*) FROM dictionaries')->fetchColumn();
$totalWords = (int) $pdo->query('SELECT COUNT(*) FROM dictionary_entries')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── Hero banner ───────────────────────────────────────────────────────── -->
<div class="rounded-3 p-4 p-md-5 mb-5 text-center about-hero-banner">
    <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
         alt="Peddi" height="80" style="width:auto" class="mb-3">
    <div class="peddi-logo peddi-logo-xl mb-1">Peddi</div>
    <p class="lead mb-0 opacity-75">A multilingual Indic dictionary platform</p>
</div>

<div class="row g-5">

    <!-- ── Lexicographer ────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <img src="https://peddisambasivarao.in/wp-content/uploads/2025/08/g10.jpg"
                         alt="Peddi Sambasiva Rao"
                         class="rounded-circle object-fit-cover border border-3 border-warning shadow-sm"
                         style="width:120px;height:120px;"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="rounded-circle d-none align-items-center justify-content-center mx-auto border border-3 border-warning"
                         style="width:120px;height:120px;background:#fff3cd">
                        <i class="bi bi-person-fill fs-1 text-warning"></i>
                    </div>
                    <h2 class="h5 mb-0 mt-3">Peddi Sambasiva Rao</h2>
                    <span class="text-body-secondary small">Lexicographer &middot; Andhra Pradesh</span>
                </div>

                <p class="text-body-secondary">
                    The dictionaries in Peddi have been compiled and curated by
                    <strong>Peddi Sambasiva Rao</strong>, a renowned lexicographer from
                    <strong>Andhra Pradesh</strong>. His lifelong dedication to documenting
                    and preserving Indic vocabulary has produced a rich multilingual reference
                    that bridges Telugu, Sanskrit, Hindi, and English for scholars, students,
                    and language enthusiasts alike.
                </p>

                <div class="row g-3 text-center mb-4">
                    <div class="col-6">
                        <div class="border rounded py-3">
                            <div class="fs-3 fw-bold text-warning"><?= number_format($totalDicts) ?></div>
                            <div class="small text-body-secondary">Dictionar<?= $totalDicts !== 1 ? 'ies' : 'y' ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border rounded py-3">
                            <div class="fs-3 fw-bold text-warning"><?= number_format($totalWords) ?></div>
                            <div class="small text-body-secondary">Entries</div>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="https://peddisambasivarao.in/" target="_blank" rel="noopener noreferrer"
                       class="btn btn-outline-warning btn-sm">
                        <i class="bi bi-globe me-1"></i>peddisambasivarao.in
                    </a>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Developer ────────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body p-4">

                <div class="text-center mb-4">
                    <img src="https://media.licdn.com/dms/image/v2/D5603AQE8Be-23iy-sg/profile-displayphoto-crop_800_800/B56Z0hK8bMJwAI-/0/1774378014173?e=1780531200&v=beta&t=f9mbouE27DkjEbG6XiaQ8o0UiHgqEdXphTKd_flf65E"
                         alt="Siva Jasthi"
                         class="rounded-circle object-fit-cover border border-3 border-primary shadow-sm"
                         style="width:120px;height:120px;"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="rounded-circle d-none align-items-center justify-content-center mx-auto border border-3 border-primary"
                         style="width:120px;height:120px;background:#cfe2ff">
                        <i class="bi bi-code-slash fs-1 text-primary"></i>
                    </div>
                    <h2 class="h5 mb-0 mt-3">Siva Jasthi</h2>
                    <span class="text-body-secondary small">Founder, Presdient &amp; Chief Instructor &commat; Learn and Help</span>
                </div>

                <p class="text-body-secondary">
                    Peddi was designed and developed by <strong>Siva Jasthi</strong> as part
                    of an ongoing effort to make Peddi Sambasiva Rao's lexicographic work
                    freely accessible online.
                </p>

                <ul class="list-unstyled text-body-secondary small mb-4 mt-3">
                    <li class="mb-2">
                        <i class="bi bi-envelope-fill text-primary me-2"></i>
                        <a href="mailto:siva.jasthi@gmail.com" class="text-decoration-none">
                            siva.jasthi@gmail.com
                        </a>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-globe text-primary me-2"></i>
                        <a href="https://www.jasthi.com" target="_blank" rel="noopener noreferrer"
                           class="text-decoration-none">
                            www.jasthi.com
                        </a>
                    </li>
                    <li>
                        <i class="bi bi-building text-primary me-2"></i>
                        <a href="http://www.learnandhelp.com" target="_blank" rel="noopener noreferrer"
                           class="text-decoration-none">
                            www.learnandhelp.com
                        </a>
                        <span class="ms-1 text-body-secondary">— Learn and Help</span>
                    </li>
                </ul>

                <div class="text-center">
                    <a href="https://www.jasthi.com" target="_blank" rel="noopener noreferrer"
                       class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-globe me-1"></i>www.jasthi.com
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- ── Platform note ─────────────────────────────────────────────────────── -->
<div class="card mt-5 border-0 bg-body-tertiary">
    <div class="card-body px-4 py-3 d-flex gap-3 align-items-start">
        <i class="bi bi-info-circle-fill text-secondary flex-shrink-0 mt-1"></i>
        <p class="mb-0 text-body-secondary small">
            Peddi supports exact, prefix, suffix, and substring search across all loaded
            dictionaries. Browse the <a href="<?= APP_BASE ?>/catalog.php">catalog</a> to
            explore available dictionaries, or go straight to
            <a href="<?= APP_BASE ?>/search.php">search</a>.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
