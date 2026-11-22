<?php
/**
 * login.php
 * Secure authentication page with CSRF protection, prepared statements,
 * password_verify(), and session ID regeneration.
 */

require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Redirect if already authenticated
if (!empty($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Both username and password are required.';
        } else {
            $stmt = $pdo->prepare('SELECT id, username, full_name, password, role FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');
                redirect('dashboard.php');
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | <?= e(APP_NAME) ?> - Student Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="login-page">

<div class="login-card fade-in-page">
    <div class="p-4 p-sm-5">
        <div class="text-center mb-4">
            <div class="brand-emblem mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.75rem;">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h3 class="fw-bold text-navy mb-1"><?= e(APP_NAME) ?></h3>
            <p class="text-muted small mb-0">Student Management System</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> small py-2 auto-hide-alert" role="alert">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger small py-2" role="alert">
                <i class="bi bi-exclamation-circle-fill me-1"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('login.php')) ?>" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-offwhite"><i class="bi bi-person text-royal"></i></span>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= e($username) ?>" placeholder="Enter username" required autofocus>
                    <div class="invalid-feedback">Please enter your username.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-offwhite"><i class="bi bi-lock text-royal"></i></span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter password" required>
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-royal w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center small text-muted">
            <div><strong class="text-navy">Admin:</strong> admin / admin123</div>
            <div><strong class="text-navy">Teacher:</strong> teacher1 / teacher123</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/script.js')) ?>"></script>
</body>
</html>
