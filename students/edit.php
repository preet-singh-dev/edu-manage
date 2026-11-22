<?php
/**
 * students/edit.php
 * Edit existing student record with optional photo replacement.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid student ID.');
    redirect('students/list.php');
}

$stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('danger', 'Student record not found.');
    redirect('students/list.php');
}

$page_title = 'Edit Student';
$courses    = $pdo->query('SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC')->fetchAll();
$errors     = [];

$form = [
    'roll_no'        => $student['roll_no'],
    'full_name'      => $student['full_name'],
    'father_name'    => $student['father_name'],
    'email'          => $student['email'],
    'phone'          => $student['phone'],
    'gender'         => $student['gender'],
    'dob'            => $student['dob'],
    'address'        => $student['address'],
    'course_id'      => (string) $student['course_id'],
    'admission_date' => $student['admission_date'],
    'status'         => $student['status'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    foreach ($form as $key => $default) {
        $form[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    if ($form['roll_no'] === '')        $errors[] = 'Roll No is required.';
    if ($form['full_name'] === '')      $errors[] = 'Full Name is required.';
    if ($form['father_name'] === '')    $errors[] = "Father's Name is required.";
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\-\s]{7,20}$/', $form['phone'])) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!in_array($form['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors[] = 'Please select a valid gender.';
    }
    if ($form['dob'] === '' || !strtotime($form['dob'])) {
        $errors[] = 'Valid Date of Birth is required.';
    }
    if ($form['address'] === '') {
        $errors[] = 'Address is required.';
    }
    if ((int) $form['course_id'] <= 0) {
        $errors[] = 'Please select a course.';
    }
    if ($form['admission_date'] === '' || !strtotime($form['admission_date'])) {
        $errors[] = 'Valid Admission Date is required.';
    }
    if (!in_array($form['status'], ['Active', 'Inactive'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        $dup = $pdo->prepare('SELECT id FROM students WHERE (roll_no = ? OR email = ?) AND id != ? LIMIT 1');
        $dup->execute([$form['roll_no'], $form['email'], $id]);
        if ($dup->fetch()) {
            $errors[] = 'Another student already uses this Roll No or Email.';
        }
    }

    $photo_filename = $student['photo'];
    if (empty($errors) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading profile photo.';
        } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Profile photo must not exceed 2 MB.';
        } else {
            $mime = '';
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = (string) $finfo->file($_FILES['photo']['tmp_name']);
            } elseif (function_exists('getimagesize')) {
                $img_info = @getimagesize($_FILES['photo']['tmp_name']);
                $mime     = (string) ($img_info['mime'] ?? '');
            }
            $allowed_mimes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

            if (!isset($allowed_mimes[$mime])) {
                $errors[] = 'Only JPG and PNG images are allowed.';
            } else {
                $upload_dir = dirname(__DIR__) . '/uploads/students';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_filename = 'student_' . bin2hex(random_bytes(12)) . '.' . $allowed_mimes[$mime];
                $dest_path    = $upload_dir . '/' . $new_filename;

                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest_path)) {
                    $errors[] = 'Failed to store uploaded photo.';
                } else {
                    // Safely remove previous photo if present
                    if (!empty($student['photo'])) {
                        $base_real = realpath($upload_dir);
                        $old_real  = realpath($upload_dir . '/' . basename($student['photo']));
                        if ($base_real && $old_real && strpos($old_real, $base_real . DIRECTORY_SEPARATOR) === 0 && is_file($old_real)) {
                            unlink($old_real);
                        }
                    }
                    $photo_filename = $new_filename;
                }
            }
        }
    }

    if (empty($errors)) {
        $upd = $pdo->prepare('
            UPDATE students
            SET roll_no = ?, full_name = ?, father_name = ?, email = ?, phone = ?,
                gender = ?, dob = ?, address = ?, course_id = ?, admission_date = ?,
                photo = ?, status = ?
            WHERE id = ?
        ');
        $upd->execute([
            $form['roll_no'],
            $form['full_name'],
            $form['father_name'],
            $form['email'],
            $form['phone'],
            $form['gender'],
            $form['dob'],
            $form['address'],
            (int) $form['course_id'],
            $form['admission_date'],
            $photo_filename,
            $form['status'],
            $id,
        ]);

        set_flash('success', 'Student "' . $form['full_name'] . '" updated successfully.');
        redirect('students/view.php?id=' . $id);
    }
}

$current_avatar = !empty($student['photo'])
    ? url('uploads/students/' . $student['photo'])
    : url('assets/images/default-avatar.svg');

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Edit Student</h4>
        <small class="text-muted">Roll No: <?= e($student['roll_no']) ?></small>
    </div>
    <a href="<?= e(url('students/list.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-arrow-left me-1"></i>Back to List
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

<div class="royal-card p-4">
    <form method="POST" action="<?= e(url('students/edit.php?id=' . $id)) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Roll No *</label>
                <input type="text" name="roll_no" class="form-control" value="<?= e($form['roll_no']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($form['full_name']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Father's Name *</label>
                <input type="text" name="father_name" class="form-control" value="<?= e($form['father_name']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone" class="form-control" value="<?= e($form['phone']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Gender *</label>
                <select name="gender" class="form-select" required>
                    <option value="Male" <?= $form['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $form['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $form['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Date of Birth *</label>
                <input type="date" name="dob" class="form-control" value="<?= e($form['dob']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course *</label>
                <select name="course_id" class="form-select" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $form['course_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Admission Date *</label>
                <input type="date" name="admission_date" class="form-control" value="<?= e($form['admission_date']) ?>" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Address *</label>
                <textarea name="address" class="form-control" rows="3" required><?= e($form['address']) ?></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="Active" <?= $form['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $form['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Replace Profile Photo (JPG/PNG, Max 2MB)</label>
                <input type="file" name="photo" id="photoInput" class="form-control" accept="image/jpeg,image/png">
                <div class="text-danger small mt-1" id="photoError"></div>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <img src="<?= e($current_avatar) ?>" id="photoPreview" alt="Current Photo" class="avatar-profile" style="width: 84px; height: 84px;">
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4">
                <i class="bi bi-save me-1"></i>Update Student
            </button>
            <a href="<?= e(url('students/view.php?id=' . $id)) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
