<?php
/**
 * students/view.php
 * Comprehensive student profile showing personal details, attendance stats,
 * recent exam marks, and fee summary.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid student ID.');
    redirect('students/list.php');
}

$stmt = $pdo->prepare('
    SELECT s.*, c.course_name, c.course_code, c.duration, c.fees AS course_fees
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student not found.');
    redirect('students/list.php');
}

// Attendance Summary
$att_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_days,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_days
    FROM attendance
    WHERE student_id = ?
");
$att_stmt->execute([$id]);
$att = $att_stmt->fetch();
$att_pct = ($att && (int) $att['total_days'] > 0)
    ? round(((int) $att['present_days'] / (int) $att['total_days']) * 100, 1)
    : 0.0;

// Fee Summary
$fee_stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM fees WHERE student_id = ?');
$fee_stmt->execute([$id]);
$paid_amount    = (float) $fee_stmt->fetchColumn();
$total_fee      = (float) $student['course_fees'];
$pending_amount = max(0, $total_fee - $paid_amount);

// Recent Marks
$marks_stmt = $pdo->prepare('SELECT * FROM marks WHERE student_id = ? ORDER BY id DESC LIMIT 5');
$marks_stmt->execute([$id]);
$marks_list = $marks_stmt->fetchAll();

$avatar = !empty($student['photo'])
    ? url('uploads/students/' . $student['photo'])
    : url('assets/images/default-avatar.svg');

$page_title = 'Student Profile';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0"><?= e($student['full_name']) ?></h4>
        <small class="text-muted">Roll No: <?= e($student['roll_no']) ?> &bull; <?= e($student['course_name']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('marks/result_card.php?student_id=' . $id)) ?>" class="btn btn-gold">
            <i class="bi bi-award me-1"></i>Result Card
        </a>
        <a href="<?= e(url('students/edit.php?id=' . $id)) ?>" class="btn btn-royal">
            <i class="bi bi-pencil-square me-1"></i>Edit
        </a>
        <a href="<?= e(url('students/list.php')) ?>" class="btn btn-outline-navy">Back</a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Profile Card -->
    <div class="col-lg-4">
        <div class="royal-card p-4 text-center h-100">
            <img src="<?= e($avatar) ?>" alt="Student Photo" class="avatar-profile mb-3">
            <h5 class="fw-bold text-navy mb-1"><?= e($student['full_name']) ?></h5>
            <p class="text-muted small mb-2"><?= e($student['roll_no']) ?></p>
            <span class="badge <?= $student['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?> mb-4">
                <?= e($student['status']) ?>
            </span>

            <div class="border-top pt-3 text-start small">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Course</span>
                    <span class="fw-semibold"><?= e($student['course_name']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Attendance</span>
                    <span class="fw-semibold text-royal"><?= e(number_format($att_pct, 1)) ?>% (<?= (int) ($att['present_days'] ?? 0) ?>/<?= (int) ($att['total_days'] ?? 0) ?>)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Total Fee</span>
                    <span class="fw-semibold">&#8377;<?= e(number_format($total_fee, 2)) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Paid Fee</span>
                    <span class="fw-semibold text-success">&#8377;<?= e(number_format($paid_amount, 2)) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Pending Fee</span>
                    <span class="fw-semibold <?= $pending_amount > 0 ? 'text-danger' : 'text-success' ?>">
                        &#8377;<?= e(number_format($pending_amount, 2)) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Detailed Info & Marks -->
    <div class="col-lg-8">
        <div class="royal-card p-4 mb-4">
            <h6 class="fw-bold text-navy border-bottom pb-2 mb-3">
                <i class="bi bi-person-vcard text-gold me-2"></i>Personal &amp; Academic Details
            </h6>
            <div class="row g-3 small">
                <div class="col-sm-6">
                    <span class="text-muted d-block">Father's Name</span>
                    <span class="fw-semibold"><?= e($student['father_name']) ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Email</span>
                    <span class="fw-semibold"><?= e($student['email']) ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Phone</span>
                    <span class="fw-semibold"><?= e($student['phone']) ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Gender / Date of Birth</span>
                    <span class="fw-semibold"><?= e($student['gender']) ?> &bull; <?= e(date('d M Y', strtotime($student['dob']))) ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Admission Date</span>
                    <span class="fw-semibold"><?= e(date('d M Y', strtotime($student['admission_date']))) ?></span>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted d-block">Course Code &amp; Duration</span>
                    <span class="fw-semibold"><?= e($student['course_code']) ?> (<?= e($student['duration']) ?>)</span>
                </div>
                <div class="col-12">
                    <span class="text-muted d-block">Residential Address</span>
                    <span class="fw-semibold"><?= e($student['address']) ?></span>
                </div>
            </div>
        </div>

        <div class="royal-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-journal-check text-royal me-2"></i>Recent Exam Marks</span>
                <a href="<?= e(url('marks/add.php?student_id=' . $id)) ?>" class="btn btn-sm btn-royal">Add Marks</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-royal table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Marks</th>
                                <th>%</th>
                                <th>Grade</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($marks_list)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No marks recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($marks_list as $m): ?>
                                    <tr>
                                        <td><?= e($m['exam_name']) ?></td>
                                        <td><?= e($m['subject']) ?></td>
                                        <td><?= e($m['marks_obtained']) ?> / <?= e($m['max_marks']) ?></td>
                                        <td><?= e($m['percentage']) ?>%</td>
                                        <td><span class="badge badge-role"><?= e($m['grade']) ?></span></td>
                                        <td>
                                            <span class="badge <?= $m['result_status'] === 'Pass' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= e($m['result_status']) ?>
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
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
