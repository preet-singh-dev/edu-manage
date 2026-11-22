<?php
/**
 * includes/sidebar.php
 * Fixed left navigation sidebar with role-based menu visibility.
 */

$current_script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

/**
 * Helper to highlight the active navigation link.
 */
function nav_active(string $needle, string $current_script): string
{
    return (strpos($current_script, $needle) !== false) ? 'active' : '';
}
?>
<aside class="sidebar no-print" id="appSidebar">
    <div class="sidebar-brand">
        <a href="<?= e(url('dashboard.php')) ?>" class="text-decoration-none d-flex align-items-center gap-2">
            <div class="brand-emblem">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <span class="brand-title d-block"><?= e(APP_NAME) ?></span>
                <span class="brand-subtitle d-block">Student Portal</span>
            </div>
        </a>
        <button class="btn btn-sm text-light d-lg-none ms-auto" id="sidebarClose" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <div class="sidebar-heading">Overview</div>
        <a href="<?= e(url('dashboard.php')) ?>" class="sidebar-link <?= nav_active('/dashboard.php', $current_script) ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <?php if (is_admin()): ?>
            <div class="sidebar-heading">Academics &amp; Directory</div>
            <a href="<?= e(url('students/list.php')) ?>" class="sidebar-link <?= nav_active('/students/list.php', $current_script) || nav_active('/students/view.php', $current_script) || nav_active('/students/edit.php', $current_script) ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i>
                <span>Students</span>
            </a>
            <a href="<?= e(url('students/add.php')) ?>" class="sidebar-link <?= nav_active('/students/add.php', $current_script) ?>">
                <i class="bi bi-person-plus-fill"></i>
                <span>Add Student</span>
            </a>
            <a href="<?= e(url('courses/list.php')) ?>" class="sidebar-link <?= nav_active('/courses/', $current_script) ?>">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Courses</span>
            </a>
        <?php endif; ?>

        <div class="sidebar-heading">Classroom</div>
        <a href="<?= e(url('attendance/mark.php')) ?>" class="sidebar-link <?= nav_active('/attendance/mark.php', $current_script) ?>">
            <i class="bi bi-calendar2-check-fill"></i>
            <span>Mark Attendance</span>
        </a>
        <a href="<?= e(url('attendance/report.php')) ?>" class="sidebar-link <?= nav_active('/attendance/report.php', $current_script) ?>">
            <i class="bi bi-clipboard2-data-fill"></i>
            <span>Attendance Report</span>
        </a>
        <a href="<?= e(url('marks/list.php')) ?>" class="sidebar-link <?= nav_active('/marks/list.php', $current_script) || nav_active('/marks/result_card.php', $current_script) ? 'active' : '' ?>">
            <i class="bi bi-award-fill"></i>
            <span>Marks &amp; Results</span>
        </a>
        <a href="<?= e(url('marks/add.php')) ?>" class="sidebar-link <?= nav_active('/marks/add.php', $current_script) ?>">
            <i class="bi bi-plus-square-fill"></i>
            <span>Add Marks</span>
        </a>

        <?php if (is_admin()): ?>
            <div class="sidebar-heading">Finance &amp; Reports</div>
            <a href="<?= e(url('fees/list.php')) ?>" class="sidebar-link <?= nav_active('/fees/list.php', $current_script) || nav_active('/fees/receipt.php', $current_script) ? 'active' : '' ?>">
                <i class="bi bi-cash-coin"></i>
                <span>Fees Management</span>
            </a>
            <a href="<?= e(url('fees/add.php')) ?>" class="sidebar-link <?= nav_active('/fees/add.php', $current_script) ?>">
                <i class="bi bi-receipt-cutoff"></i>
                <span>Record Fee Payment</span>
            </a>
            <a href="<?= e(url('students/export_csv.php')) ?>" class="sidebar-link">
                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                <span>Export Students CSV</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <a href="<?= e(url('logout.php')) ?>" class="sidebar-link text-gold">
            <i class="bi bi-box-arrow-left"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
