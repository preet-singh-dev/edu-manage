<?php
/**
 * marks/add.php
 * Record student exam marks with auto-calculated percentage, grade, and Pass/Fail status.
 * Accessible by both Admin and Teacher roles.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

$page_title = 'Add Exam Marks';

$students = $pdo->query('
    SELECT s.id, s.roll_no, s.full_name, c.course_name
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    ORDER BY s.roll_no ASC
')->fetchAll();

$errors = [];
$form = [
    'student_id'     => (string) (int) ($_GET['student_id'] ?? 0),
    'subject'        => '',
    'exam_name'      => 'Mid-Term 2026',
    'max_marks'      => '100',
    'marks_obtained' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }

    foreach ($form as $k => $v) {
        $form[$k] = trim((string) ($_POST[$k] ?? $v));
    }

    $sid       = (int) $form['student_id'];
    $max_marks = (int) $form['max_marks'];
    $obtained  = is_numeric($form['marks_obtained']) ? (float) $form['marks_obtained'] : -1;

    if ($sid <= 0) $errors[] = 'Please select a student.';
    if ($form['subject'] === '') $errors[] = 'Subject name is required.';
    if ($form['exam_name'] === '') $errors[] = 'Exam name is required.';
    if ($max_marks <= 0) $errors[] = 'Maximum marks must be greater than 0.';
    if ($obtained < 0 || $obtained > $max_marks) {
        $errors[] = 'Marks obtained must be between 0 and Maximum marks.';
    }

    if (empty($errors)) {
        $percentage = round(($obtained / $max_marks) * 100, 2);
        $calc       = calculate_grade($percentage);

        $stmt = $pdo->prepare('
            INSERT INTO marks
                (student_id, subject, exam_name, max_marks, marks_obtained, percentage, grade, result_status, added_by)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $sid,
            $form['subject'],
            $form['exam_name'],
            $max_marks,
            $obtained,
            $percentage,
            $calc['grade'],
            $calc['result_status'],
            (int) $_SESSION['user_id'],
        ]);

        set_flash('success', 'Marks recorded successfully (' . $percentage . '% - Grade ' . $calc['grade'] . ').');
        redirect('marks/list.php');
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Add Student Marks</h4>
        <small class="text-muted">Percentage, grade, and Pass/Fail are computed automatically</small>
    </div>
    <a href="<?= e(url('marks/list.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-arrow-left me-1"></i>View All Marks
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="royal-card p-4" style="max-width: 780px;">
    <form method="POST" action="<?= e(url('marks/add.php')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label">Student *</label>
                <select name="student_id" class="form-select" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?= (int) $st['id'] ?>" <?= (int) $form['student_id'] === (int) $st['id'] ? 'selected' : '' ?>>
                            <?= e($st['roll_no'] . ' - ' . $st['full_name'] . ' (' . $st['course_name'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Subject *</label>
                <input type="text" name="subject" class="form-control" value="<?= e($form['subject']) ?>" placeholder="e.g., Database Systems" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Exam Name *</label>
                <input type="text" name="exam_name" class="form-control" value="<?= e($form['exam_name']) ?>" placeholder="e.g., Mid-Term 2026" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Maximum Marks *</label>
                <input type="number" name="max_marks" id="maxMarksInput" class="form-control" min="1" value="<?= e($form['max_marks']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Marks Obtained *</label>
                <input type="number" step="0.01" name="marks_obtained" id="obtainedMarksInput" class="form-control" min="0" value="<?= e($form['marks_obtained']) ?>" required>
            </div>

            <!-- Live Calculation Preview -->
            <div class="col-12">
                <div class="p-3 rounded-3 bg-offwhite border d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <small class="text-muted d-block">Calculated Percentage</small>
                        <strong class="fs-5 text-navy" id="previewPercentage">--%</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Grade</small>
                        <strong class="fs-5 text-royal" id="previewGrade">--</strong>
                    </div>
                    <div>
                        <small class="text-muted d-block">Result Status (Pass &ge; 40%)</small>
                        <span class="badge bg-secondary" id="previewStatus">--</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4"><i class="bi bi-check-lg me-1"></i>Save Marks</button>
            <a href="<?= e(url('marks/list.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
