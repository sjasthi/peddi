<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect already-authenticated admins
if (isLoggedIn() && getCurrentUser()['role'] === 'admin') {
    header('Location: ' . APP_BASE . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are both required.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, password_hash, role FROM users WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && $user['role'] === 'admin' && password_verify($password, $user['password_hash'])) {
            // Rotate session ID to prevent session-fixation attacks
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            header('Location: ' . APP_BASE . '/admin/dashboard.php');
            exit;
        }

        // Generic message — don't reveal which field was wrong
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — Peddi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= APP_BASE ?>/assets/css/main.css">
    <style>
        body { background: #f0f2f5; }
        .login-card { max-width: 400px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">

<div class="login-card w-100 mx-auto px-3">
    <div class="text-center mb-4">
        <img src="<?= APP_BASE ?>/assets/images/peddi-logo.svg"
             alt="Peddi" height="64" style="width:auto">
        <div class="peddi-logo mt-2" style="font-size:2rem; letter-spacing:8px">Peddi</div>
        <h1 class="h6 mt-1 fw-semibold text-body-secondary">Admin Portal</h1>
        <p class="text-body-secondary small mb-0">Sign in to continue</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">

            <?php if ($error !== ''): ?>
            <div class="alert alert-danger d-flex gap-2 py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="post" action="" novalidate>

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text"
                               id="username" name="username"
                               class="form-control<?= $error ? ' is-invalid' : '' ?>"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               autocomplete="username"
                               autofocus required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               id="password" name="password"
                               class="form-control<?= $error ? ' is-invalid' : '' ?>"
                               autocomplete="current-password"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                </button>

            </form>
        </div>
    </div>

    <p class="text-center mt-3 small text-body-secondary">
        <a href="<?= APP_BASE ?>/index.php" class="text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Back to public site
        </a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
