<?php
/**
 * includes/header.php
 * Shared HTML head, top navigation bar, and auto-hiding flash alerts.
 * Expects $page_title (optional) and active session from auth_check.php.
 */

$page_title   = $page_title ?? 'Dashboard';
$current_user = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$current_role = ucfirst($_SESSION['role'] ?? 'user');
$flash        = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> | <?= e(APP_NAME) ?> - Student Management System</title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Custom Theme Styles -->
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>

<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop"></div>

    <div class="main-content-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar no-print">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-navy d-lg-none" type="button" id="sidebarToggle" aria-label="Toggle Sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="mb-0 fw-semibold text-navy"><?= e($page_title) ?></h5>
                    <small class="text-muted d-none d-sm-inline">EduManage Academic Administration</small>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2 user-chip">
                    <div class="user-avatar-sm">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="d-none d-md-block lh-sm">
                        <div class="fw-semibold small text-navy"><?= e($current_user) ?></div>
                        <span class="badge badge-role"><?= e($current_role) ?></span>
                    </div>
                </div>
                <a href="<?= e(url('logout.php')) ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </header>

        <!-- Main Page Container -->
        <main class="page-content fade-in-page">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show shadow-sm auto-hide-alert no-print" role="alert">
                    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill') ?> me-2"></i>
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
