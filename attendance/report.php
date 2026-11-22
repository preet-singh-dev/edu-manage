<?php
/**
 * attendance/report.php
 * Monthly attendance report filterable by month, course, and individual student.
 * Accessible by both Admin and Teacher roles.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

$page_title = 'Attendance Report';

$month      = trim($_GET['month'] ?? date('Y-m'));
$course_id  = (int) ($_GET['course_id'] ?? 0);
$student_id = (int) ($_GET['student_id'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$courses  = $pdo->query('SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC')->fetchAll();
$students = $pdo->query('SELECT id, roll_no, full_name FROM students ORDER BY roll_no ASC')->fetchAll();

$where  = ["DATE_FORMAT(a.attendance_date, '%Y-%m') = ?"];
$params = [$month];

if ($course_id > 0) {
    $where[]  = 's.course_id = ?';
    $params[] = $course_id;
}
if ($student_id > 0) {
    $where[]  = 's.id = ?';
    $params[] = $student_id;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// Monthly Summary per Student
$summary_stmt = $pdo->prepare("
    SELECT
        s.id, s.roll_no, s.full_name, c.course_name,
        COUNT(a.id) AS total_days,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_days,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_days
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    INNER JOIN attendance a ON a.student_id = s.id
    {$where_sql}
    GROUP BY s.id, s.roll_no, s.full_name, c.course_name
    ORDER BY s.roll_no ASC
");
$summary_stmt->execute($params);
$summary_rows = $summary_stmt->fetchAll();

// Detailed Date Log when a specific student is filtered
$daily_logs = [];
if ($student_id > 0) {
    $log_stmt = $pdo->prepare("
        SELECT a.attendance_date, a.status, c.course_name
        FROM attendance a
        INNER JOIN courses c ON a.course_id = c.id
        WHERE a.student_id = ? AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?
        ORDER BY a.attendance_date DESC
    ");
    $log_stmt->execute([$student_id, $month]);
    $daily_logs = $log_stmt->fetchAll();
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Attendance Report</h4>
        <small class="text-muted">Monthly summary by student and course</small>
    </div>
    <a href="<?= e(url('attendance/mark.php')) ?>" class="btn btn-royal">
        <i class="bi bi-calendar2-plus me-1"></i>Mark Attendance
    </a>
</div>

<!-- Filters -->
<div class="royal-card p-3 mb-4">
    <form method="GET" action="<?= e(url('attendance/report.php')) ?>" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Month</label>
            <input type="month" name="month" class="form-control" value="<?= e($month) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $course_id === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['course_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Student</label>
            <select name="student_id" class="form-select">
                <option value="">All Students</option>
                <?php foreach ($students as $st): ?>
                    <option value="<?= (int) $st['id'] ?>" <?= $student_id === (int) $st['id'] ? 'selected' : '' ?>>
                        <?= e($st['roll_no'] . ' - ' . $st['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-royal w-100"><i class="bi bi-search me-1"></i>Generate</button>
        </div>
    </form>
</div>

<!-- Summary Table -->
<div class="royal-card mb-4">
    <div class="card-header">
        <i class="bi bi-table text-gold me-2"></i>Monthly Summary (<?= e(date('F Y', strtotime($month . '-01'))) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summary_rows)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No attendance records found for the selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($summary_rows as $row): ?>
                            <?php
                            $total = (int) $row['total_days'];
                            $pres  = (int) $row['present_days'];
                            $pct   = $total > 0 ? round(($pres / $total) * 100, 1) : 0.0;
                            ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= e($row['roll_no']) ?></td>
                                <td class="fw-medium"><?= e($row['full_name']) ?></td>
                                <td><?= e($row['course_name']) ?></td>
                                <td><?= $total ?></td>
                                <td class="text-success fw-semibold"><?= $pres ?></td>
                                <td class="text-danger fw-semibold"><?= (int) $row['absent_days'] ?></td>
                                <td>
                                    <span class="badge <?= $pct >= 75 ? 'bg-success' : ($pct >= 50 ? 'bg-warning text-dark' : 'bg-danger') ?>">
                                        <?= e(number_format($pct, 1)) ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($student_id > 0): ?>
<div class="royal-card">
    <div class="card-header">
        <i class="bi bi-calendar3 text-royal me-2"></i>Daily Attendance Log
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Course</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_logs)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No daily entries for this month.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daily_logs as $log): ?>
                            <tr>
                                <td><?= e(date('d M Y (D)', strtotime($log['attendance_date']))) ?></td>
                                <td><?= e($log['course_name']) ?></td>
                                <td>
                                    <span class="badge <?= $log['status'] === 'Present' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= e($log['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
