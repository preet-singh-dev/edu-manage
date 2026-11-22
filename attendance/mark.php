<?php
/**
 * attendance/mark.php
 * Mark or update daily Present/Absent attendance for all active students in a course.
 * Accessible by both Admin and Teacher roles.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

$page_title = 'Mark Attendance';

$courses         = $pdo->query('SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC')->fetchAll();
$selected_course = (int) ($_REQUEST['course_id'] ?? ($courses[0]['id'] ?? 0));
$selected_date   = trim($_REQUEST['attendance_date'] ?? date('Y-m-d'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Handle Attendance Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
        redirect('attendance/mark.php?course_id=' . $selected_course . '&attendance_date=' . urlencode($selected_date));
    }

    $statuses = $_POST['attendance'] ?? [];
    if ($selected_course <= 0 || empty($statuses) || !is_array($statuses)) {
        set_flash('warning', 'No attendance records were submitted.');
        redirect('attendance/mark.php?course_id=' . $selected_course . '&attendance_date=' . urlencode($selected_date));
    }

    // Fetch valid active students belonging to this course
    $valid_stmt = $pdo->prepare("SELECT id FROM students WHERE course_id = ? AND status = 'Active'");
    $valid_stmt->execute([$selected_course]);
    $valid_ids = array_flip($valid_stmt->fetchAll(PDO::FETCH_COLUMN));

    $upsert = $pdo->prepare('
        INSERT INTO attendance (student_id, course_id, attendance_date, status, marked_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            course_id = VALUES(course_id),
            marked_by = VALUES(marked_by)
    ');

    $pdo->beginTransaction();
    try {
        foreach ($statuses as $student_id => $status_val) {
            $sid = (int) $student_id;
            if (!isset($valid_ids[$sid])) {
                continue;
            }
            $clean_status = ($status_val === 'Absent') ? 'Absent' : 'Present';
            $upsert->execute([$sid, $selected_course, $selected_date, $clean_status, (int) $_SESSION['user_id']]);
        }
        $pdo->commit();
        set_flash('success', 'Attendance saved for ' . date('d M Y', strtotime($selected_date)) . '.');
    } catch (Throwable $e) {
        $pdo->rollBack();
        set_flash('danger', 'Could not save attendance. Please try again.');
    }

    redirect('attendance/mark.php?course_id=' . $selected_course . '&attendance_date=' . urlencode($selected_date));
}

// Fetch active students in selected course + existing attendance for selected date
$students = [];
if ($selected_course > 0) {
    $st_stmt = $pdo->prepare("
        SELECT s.id, s.roll_no, s.full_name, s.photo, a.status AS existing_status
        FROM students s
        LEFT JOIN attendance a
            ON a.student_id = s.id AND a.attendance_date = ?
        WHERE s.course_id = ? AND s.status = 'Active'
        ORDER BY s.roll_no ASC
    ");
    $st_stmt->execute([$selected_date, $selected_course]);
    $students = $st_stmt->fetchAll();
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Mark Attendance</h4>
        <small class="text-muted">Select a course and date to record daily attendance</small>
    </div>
    <a href="<?= e(url('attendance/report.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-clipboard2-data me-1"></i>View Attendance Report
    </a>
</div>

<!-- Filter Form -->
<div class="royal-card p-3 mb-4">
    <form method="GET" action="<?= e(url('attendance/mark.php')) ?>" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select" required>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $selected_course === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Attendance Date</label>
            <input type="date" name="attendance_date" class="form-control" value="<?= e($selected_date) ?>" required>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-royal w-100">
                <i class="bi bi-funnel me-1"></i>Load Students
            </button>
        </div>
    </form>
</div>

<!-- Attendance Sheet -->
<div class="royal-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-calendar-check text-gold me-2"></i>Attendance Sheet &mdash; <?= e(date('d M Y', strtotime($selected_date))) ?></span>
        <?php if (!empty($students)): ?>
            <div class="d-flex gap-2">
                <button type="button" id="markAllPresent" class="btn btn-sm btn-outline-success">All Present</button>
                <button type="button" id="markAllAbsent" class="btn btn-sm btn-outline-danger">All Absent</button>
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" action="<?= e(url('attendance/mark.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="course_id" value="<?= $selected_course ?>">
        <input type="hidden" name="attendance_date" value="<?= e($selected_date) ?>">
        <input type="hidden" name="save_attendance" value="1">

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-royal table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th class="text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">No active students found in this course.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $st): ?>
                                <?php
                                $sid     = (int) $st['id'];
                                $current = $st['existing_status'] ?? 'Present';
                                $avatar  = !empty($st['photo'])
                                    ? url('uploads/students/' . $st['photo'])
                                    : url('assets/images/default-avatar.svg');
                                ?>
                                <tr>
                                    <td class="fw-semibold text-navy"><?= e($st['roll_no']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= e($avatar) ?>" alt="Photo" class="avatar-thumb">
                                            <span class="fw-medium"><?= e($st['full_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group" aria-label="Attendance choice">
                                            <input type="radio" class="btn-check" name="attendance[<?= $sid ?>]" id="present_<?= $sid ?>" value="Present" <?= $current === 'Present' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-success px-3" for="present_<?= $sid ?>">Present</label>

                                            <input type="radio" class="btn-check" name="attendance[<?= $sid ?>]" id="absent_<?= $sid ?>" value="Absent" <?= $current === 'Absent' ? 'checked' : '' ?>>
                                            <label class="btn btn-sm btn-outline-danger px-3" for="absent_<?= $sid ?>">Absent</label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($students)): ?>
            <div class="card-footer bg-transparent p-3 text-end">
                <button type="submit" class="btn btn-royal px-4">
                    <i class="bi bi-check2-circle me-1"></i>Save Attendance
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
