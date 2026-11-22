<?php
/**
 * access_denied.php
 * Displayed when a user attempts to access a page outside their role permissions.
 */

require_once __DIR__ . '/includes/auth_check.php';

http_response_code(403);
$page_title = 'Access Denied';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-5">
    <div class="col-md-7 col-lg-5">
        <div class="royal-card p-4 p-md-5 text-center">
            <div class="mb-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 text-danger" style="width: 76px; height: 76px; font-size: 2.2rem;">
                    <i class="bi bi-shield-lock-fill"></i>
                </span>
            </div>
            <h3 class="fw-bold text-navy">Access Denied (403)</h3>
            <p class="text-muted mb-4">
                Your current role (<strong><?= e(ucfirst($_SESSION['role'] ?? 'user')) ?></strong>) does not have permission to view or modify this resource.
            </p>
            <a href="<?= e(url('dashboard.php')) ?>" class="btn btn-royal px-4">
                <i class="bi bi-arrow-left me-2"></i>Return to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
