<?php
/**
 * students/export_csv.php
 * Exports filtered or complete student directory to CSV with formula-injection protection.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$search    = trim($_GET['search'] ?? '');
$course_id = (int) ($_GET['course_id'] ?? 0);
$status    = trim($_GET['status'] ?? '');

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(s.full_name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($course_id > 0) {
    $where[]  = 's.course_id = ?';
    $params[] = $course_id;
}
if (in_array($status, ['Active', 'Inactive'], true)) {
    $where[]  = 's.status = ?';
    $params[] = $status;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("
    SELECT s.roll_no, s.full_name, s.father_name, s.email, s.phone,
           s.gender, s.dob, c.course_name, c.course_code,
           s.admission_date, s.status, s.address
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    {$where_sql}
    ORDER BY s.roll_no ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

/**
 * Prevent spreadsheet formula injection (=, +, -, @).
 */
function csv_safe(?string $value): string
{
    $str = (string) $value;
    if ($str !== '' && in_array($str[0], ['=', '+', '-', '@'], true)) {
        return "'" . $str;
    }
    return $str;
}

$filename = 'students_report_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// UTF-8 BOM for clean Excel import
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    'Roll No',
    'Full Name',
    "Father's Name",
    'Email',
    'Phone',
    'Gender',
    'Date of Birth',
    'Course',
    'Course Code',
    'Admission Date',
    'Status',
    'Address',
]);

foreach ($rows as $r) {
    fputcsv($output, [
        csv_safe($r['roll_no']),
        csv_safe($r['full_name']),
        csv_safe($r['father_name']),
        csv_safe($r['email']),
        csv_safe($r['phone']),
        csv_safe($r['gender']),
        csv_safe($r['dob']),
        csv_safe($r['course_name']),
        csv_safe($r['course_code']),
        csv_safe($r['admission_date']),
        csv_safe($r['status']),
        csv_safe($r['address']),
    ]);
}

fclose($output);
exit;
