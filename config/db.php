<?php
/**
 * config/db.php
 * Database connection, application URL configuration, and core security helpers.
 * All database credentials are stored exclusively in this file.
 */

// ---------------------------------------------------------------------------
// Database Credentials (Change these to match your MySQL / XAMPP setup)
// ---------------------------------------------------------------------------
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'student_db';
$db_charset = 'utf8mb4';

// ---------------------------------------------------------------------------
// Base URL Auto-Detection (Works in any XAMPP subfolder)
// ---------------------------------------------------------------------------
if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $docRoot     = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
    if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
        $detectedBase = rtrim(substr($projectRoot, strlen($docRoot)), '/');
    } else {
        $detectedBase = '/student-management';
    }
    define('BASE_URL', $detectedBase);
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'EduManage');
}

// ---------------------------------------------------------------------------
// PDO Database Connection (Prepared statements enforced)
// ---------------------------------------------------------------------------
$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";
$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed. Please verify config/db.php and ensure MySQL is running.');
}

// ---------------------------------------------------------------------------
// Core Helper Functions
// ---------------------------------------------------------------------------

/**
 * Escape HTML output to prevent XSS attacks.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build an application URL relative to BASE_URL.
 */
function url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    return ($cleanPath === '') ? (BASE_URL === '' ? '/' : BASE_URL . '/') : BASE_URL . '/' . $cleanPath;
}

/**
 * Redirect safely to an internal route and terminate execution.
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Generate or retrieve the current session's CSRF token.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field for HTML forms.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify a submitted CSRF token using timing-safe comparison.
 */
function verify_csrf_token(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string) $token);
}

/**
 * Store a one-time flash notification message in session.
 */
function set_flash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * Retrieve and clear the current flash message.
 */
function get_flash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Compute grade and Pass/Fail status from percentage.
 */
function calculate_grade(float $percentage): array
{
    if ($percentage >= 90) {
        $grade = 'A+';
    } elseif ($percentage >= 80) {
        $grade = 'A';
    } elseif ($percentage >= 70) {
        $grade = 'B+';
    } elseif ($percentage >= 60) {
        $grade = 'B';
    } elseif ($percentage >= 50) {
        $grade = 'C';
    } elseif ($percentage >= 40) {
        $grade = 'D';
    } else {
        $grade = 'F';
    }

    return [
        'grade'         => $grade,
        'result_status' => ($percentage >= 40) ? 'Pass' : 'Fail',
    ];
}
