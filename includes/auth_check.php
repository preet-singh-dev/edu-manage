<?php
/**
 * includes/auth_check.php
 * Enforces session-based authentication and role-based access control (RBAC).
 */

require_once dirname(__DIR__) . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Redirect unauthenticated users to login page
if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    set_flash('warning', 'Please sign in to access EduManage.');
    redirect('login.php');
}

/**
 * Restrict access to specified roles (e.g. ['admin'] or ['admin', 'teacher']).
 */
function require_role(array $allowed_roles): void
{
    $user_role = $_SESSION['role'] ?? '';
    if (!in_array($user_role, $allowed_roles, true)) {
        redirect('access_denied.php');
    }
}

/**
 * Check if current user is an Admin.
 */
function is_admin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Check if current user is a Teacher.
 */
function is_teacher(): bool
{
    return ($_SESSION['role'] ?? '') === 'teacher';
}
