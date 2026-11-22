<?php
/**
 * students/delete.php
 * POST-only student deletion handler with CSRF check and safe photo cleanup.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('students/list.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token.');
    redirect('students/list.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid student ID.');
    redirect('students/list.php');
}

$stmt = $pdo->prepare('SELECT full_name, photo FROM students WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student record not found.');
    redirect('students/list.php');
}

// Safely remove uploaded profile photo if it exists inside uploads/students
if (!empty($student['photo'])) {
    $upload_dir = realpath(dirname(__DIR__) . '/uploads/students');
    $file_path  = $upload_dir ? realpath($upload_dir . '/' . basename($student['photo'])) : false;
    if ($upload_dir && $file_path && strpos($file_path, $upload_dir . DIRECTORY_SEPARATOR) === 0 && is_file($file_path)) {
        unlink($file_path);
    }
}

$del = $pdo->prepare('DELETE FROM students WHERE id = ?');
$del->execute([$id]);

set_flash('success', 'Student "' . $student['full_name'] . '" deleted successfully.');
redirect('students/list.php');
