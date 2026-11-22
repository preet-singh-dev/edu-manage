<?php
/**
 * courses/add.php
 * Create a new course with server-side validation and CSRF protection.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Add Course';
$errors = [];
$form = [
    'course_name' => '',
    'course_code' => '',
    'duration'    => '',
    'fees'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }

    $form['course_name'] = trim($_POST['course_name'] ?? '');
    $form['course_code'] = strtoupper(trim($_POST['course_code'] ?? ''));
    $form['duration']    = trim($_POST['duration'] ?? '');
    $form['fees']        = trim($_POST['fees'] ?? '');

    if ($form['course_name'] === '') $errors[] = 'Course Name is required.';
    if ($form['course_code'] === '') $errors[] = 'Course Code is required.';
    if ($form['duration'] === '')    $errors[] = 'Duration is required.';
    if (!is_numeric($form['fees']) || (float) $form['fees'] < 0) {
        $errors[] = 'Please enter a valid non-negative course fee.';
    }

    if (empty($errors)) {
        $dup = $pdo->prepare('SELECT id FROM courses WHERE course_code = ? LIMIT 1');
        $dup->execute([$form['course_code']]);
        if ($dup->fetch()) {
            $errors[] = 'Course Code already exists.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO courses (course_name, course_code, duration, fees) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $form['course_name'],
            $form['course_code'],
            $form['duration'],
            (float) $form['fees'],
        ]);

        set_flash('success', 'Course "' . $form['course_name'] . '" added successfully.');
        redirect('courses/list.php');
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Add Course</h4>
        <small class="text-muted">Define a new academic program</small>
    </div>
    <a href="<?= e(url('courses/list.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-arrow-left me-1"></i>Back
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

<div class="royal-card p-4" style="max-width: 720px;">
    <form method="POST" action="<?= e(url('courses/add.php')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Course Name *</label>
                <input type="text" name="course_name" class="form-control" value="<?= e($form['course_name']) ?>" placeholder="e.g., B.Tech Computer Science" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course Code *</label>
                <input type="text" name="course_code" class="form-control" value="<?= e($form['course_code']) ?>" placeholder="e.g., CS202" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Duration *</label>
                <input type="text" name="duration" class="form-control" value="<?= e($form['duration']) ?>" placeholder="e.g., 3 Years" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Total Course Fees (&#8377;) *</label>
                <input type="number" step="0.01" min="0" name="fees" class="form-control" value="<?= e($form['fees']) ?>" required>
            </div>
        </div>
        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4"><i class="bi bi-check-lg me-1"></i>Save Course</button>
            <a href="<?= e(url('courses/list.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
