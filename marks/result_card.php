<?php
/**
 * marks/result_card.php
 * Print-friendly academic Result Card for an individual student.
 * Accessible by both Admin and Teacher roles.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

$student_id = (int) ($_GET['student_id'] ?? 0);
$exam       = trim($_GET['exam'] ?? '');

if ($student_id <= 0) {
    set_flash('danger', 'Please select a valid student.');
    redirect('marks/list.php');
}

$st_stmt = $pdo->prepare('
    SELECT s.*, c.course_name, c.course_code
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
    LIMIT 1
');
$st_stmt->execute([$student_id]);
$student = $st_stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student not found.');
    redirect('marks/list.php');
}

// Fetch exam options for this student
$exam_stmt = $pdo->prepare('SELECT DISTINCT exam_name FROM marks WHERE student_id = ? ORDER BY exam_name ASC');
$exam_stmt->execute([$student_id]);
$exams = $exam_stmt->fetchAll(PDO::FETCH_COLUMN);

$where  = ['student_id = ?'];
$params = [$student_id];

if ($exam !== '') {
    $where[]  = 'exam_name = ?';
    $params[] = $exam;
}

$marks_stmt = $pdo->prepare('SELECT * FROM marks WHERE ' . implode(' AND ', $where) . ' ORDER BY exam_name ASC, subject ASC');
$marks_stmt->execute($params);
$marks = $marks_stmt->fetchAll();

// Aggregate Totals
$total_max      = 0;
$total_obtained = 0.0;
$has_fail       = false;

foreach ($marks as $m) {
    $total_max      += (int) $m['max_marks'];
    $total_obtained += (float) $m['marks_obtained'];
    if ($m['result_status'] === 'Fail') {
        $has_fail = true;
    }
}

$overall_pct = $total_max > 0 ? round(($total_obtained / $total_max) * 100, 2) : 0.0;
$overall     = calculate_grade($overall_pct);
if ($has_fail) {
    $overall['result_status'] = 'Fail';
}

$avatar = !empty($student['photo'])
    ? url('uploads/students/' . $student['photo'])
    : url('assets/images/default-avatar.svg');

$page_title = 'Result Card';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4 no-print">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= e(url('marks/list.php')) ?>" class="btn btn-outline-navy">
            <i class="bi bi-arrow-left me-1"></i>Back to Marks
        </a>
        <?php if (count($exams) > 1): ?>
            <form method="GET" action="<?= e(url('marks/result_card.php')) ?>" class="d-flex gap-2">
                <input type="hidden" name="student_id" value="<?= $student_id ?>">
                <select name="exam" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Exams</option>
                    <?php foreach ($exams as $ex): ?>
                        <option value="<?= e($ex) ?>" <?= $exam === $ex ? 'selected' : '' ?>><?= e($ex) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <button type="button" onclick="window.print()" class="btn btn-gold">
        <i class="bi bi-printer-fill me-1"></i>Print Result Card
    </button>
</div>

<div class="royal-card printable-sheet p-4 p-md-5 mx-auto" style="max-width: 860px;">
    <!-- Institution Header -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4" style="border-bottom: 3px solid var(--gold) !important;">
        <div>
            <h3 class="fw-bold text-navy mb-1">
                <i class="bi bi-mortarboard-fill text-gold me-2"></i><?= e(APP_NAME) ?> Academy
            </h3>
            <p class="text-muted small mb-0">Official Academic Statement of Marks</p>
        </div>
        <div class="text-end">
            <span class="badge bg-navy text-gold px-3 py-2 fs-6">RESULT CARD</span>
            <div class="small text-muted mt-1">Issued: <?= date('d M Y') ?></div>
        </div>
    </div>

    <!-- Student Details -->
    <div class="row align-items-center g-3 mb-4 bg-offwhite p-3 rounded-3 border">
        <div class="col-auto">
            <img src="<?= e($avatar) ?>" alt="Photo" class="avatar-profile" style="width: 78px; height: 78px;">
        </div>
        <div class="col">
            <div class="row g-2 small">
                <div class="col-sm-6">
                    <span class="text-muted">Student Name:</span>
                    <strong class="text-navy d-block"><?= e($student['full_name']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">Roll Number:</span>
                    <strong class="text-navy d-block"><?= e($student['roll_no']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">Father's Name:</span>
                    <strong class="d-block"><?= e($student['father_name']) ?></strong>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted">Course:</span>
                    <strong class="d-block"><?= e($student['course_name']) ?> (<?= e($student['course_code']) ?>)</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Marks Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-royal mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Exam Name</th>
                    <th>Subject</th>
                    <th class="text-center">Max Marks</th>
                    <th class="text-center">Marks Obtained</th>
                    <th class="text-center">Percentage</th>
                    <th class="text-center">Grade</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No exam marks available for this student.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marks as $idx => $m): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= e($m['exam_name']) ?></td>
                            <td class="fw-medium"><?= e($m['subject']) ?></td>
                            <td class="text-center"><?= (int) $m['max_marks'] ?></td>
                            <td class="text-center fw-semibold"><?= e($m['marks_obtained']) ?></td>
                            <td class="text-center"><?= e($m['percentage']) ?>%</td>
                            <td class="text-center fw-bold text-royal"><?= e($m['grade']) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $m['result_status'] === 'Pass' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= e($m['result_status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($marks)): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">Grand Total</td>
                        <td class="text-center"><?= $total_max ?></td>
                        <td class="text-center text-navy"><?= e(number_format($total_obtained, 2)) ?></td>
                        <td class="text-center text-royal"><?= e(number_format($overall_pct, 2)) ?>%</td>
                        <td class="text-center text-navy"><?= e($overall['grade']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $overall['result_status'] === 'Pass' ? 'bg-success' : 'bg-danger' ?>">
                                <?= e($overall['result_status']) ?>
                            </span>
                        </td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 mt-4 border-top small text-muted">
        <div class="col-6">
            <div class="mt-4 pt-2 border-top d-inline-block px-3">Class Teacher Signature</div>
        </div>
        <div class="col-6 text-end">
            <div class="mt-4 pt-2 border-top d-inline-block px-3">Principal / Controller of Examinations</div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
