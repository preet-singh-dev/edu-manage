<?php
/**
 * courses/edit.php
 * Update an existing course record.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid course ID.');
    redirect('courses/list.php');
}

$stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$course = $stmt->fetch();

if (!$course) {
    set_flash('danger', 'Course not found.');
    redirect('courses/list.php');
}

$page_title = 'Edit Course';
$errors     = [];
$form = [
    'course_name' => $course['course_name'],
    'course_code' => $course['course_code'],
    'duration'    => $course['duration'],
    'fees'        => (string) $course['fees'],
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
        $dup = $pdo->prepare('SELECT id FROM courses WHERE course_code = ? AND id != ? LIMIT 1');
        $dup->execute([$form['course_code'], $id]);
        if ($dup->fetch()) {
            $errors[] = 'Another course already uses this Course Code.';
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare('UPDATE courses SET course_name = ?, course_code = ?, duration = ?, fees = ? WHERE id = ?');
        $upd->execute([
            $form['course_name'],
            $form['course_code'],
            $form['duration'],
            (float) $form['fees'],
            $id,
        ]);

        set_flash('success', 'Course updated successfully.');
        redirect('courses/list.php');
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Edit Course</h4>
        <small class="text-muted">Update details for <?= e($course['course_code']) ?></small>
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
    <form method="POST" action="<?= e(url('courses/edit.php?id=' . $id)) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Course Name *</label>
                <input type="text" name="course_name" class="form-control" value="<?= e($form['course_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course Code *</label>
                <input type="text" name="course_code" class="form-control" value="<?= e($form['course_code']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Duration *</label>
                <input type="text" name="duration" class="form-control" value="<?= e($form['duration']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Total Course Fees (&#8377;) *</label>
                <input type="number" step="0.01" min="0" name="fees" class="form-control" value="<?= e($form['fees']) ?>" required>
            </div>
        </div>
        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4"><i class="bi bi-save me-1"></i>Update Course</button>
            <a href="<?= e(url('courses/list.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
