<?php
/**
 * courses/delete.php
 * POST-only course deletion handler; blocks deletion if students are enrolled.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses/list.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token.');
    redirect('courses/list.php');
}

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid course ID.');
    redirect('courses/list.php');
}

$check = $pdo->prepare('SELECT COUNT(*) FROM students WHERE course_id = ?');
$check->execute([$id]);
if ((int) $check->fetchColumn() > 0) {
    set_flash('warning', 'Cannot delete this course because students are currently enrolled in it.');
    redirect('courses/list.php');
}

$del = $pdo->prepare('DELETE FROM courses WHERE id = ?');
$del->execute([$id]);

set_flash('success', 'Course deleted successfully.');
redirect('courses/list.php');
