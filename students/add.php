<?php
/**
 * students/add.php
 * Create a new student record with server-side validation, CSRF protection,
 * and secure JPG/PNG profile photo upload (max 2MB).
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Add New Student';
$courses    = $pdo->query('SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC')->fetchAll();

$errors = [];
$form = [
    'roll_no'        => '',
    'full_name'      => '',
    'father_name'    => '',
    'email'          => '',
    'phone'          => '',
    'gender'         => 'Male',
    'dob'            => '',
    'address'        => '',
    'course_id'      => '',
    'admission_date' => date('Y-m-d'),
    'status'         => 'Active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    }

    foreach ($form as $key => $default) {
        $form[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    // Server-side validation
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

    // Check unique Roll No & Email
    if (empty($errors)) {
        $dup = $pdo->prepare('SELECT id FROM students WHERE roll_no = ? OR email = ? LIMIT 1');
        $dup->execute([$form['roll_no'], $form['email']]);
        if ($dup->fetch()) {
            $errors[] = 'A student with this Roll No or Email already exists.';
        }
    }

    // Validate & handle optional Profile Photo upload
    $uploaded_photo = null;
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
            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
            ];
            if (!isset($allowed_mimes[$mime])) {
                $errors[] = 'Only JPG and PNG images are allowed for profile photo.';
            } else {
                $upload_dir = dirname(__DIR__) . '/uploads/students';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_filename = 'student_' . bin2hex(random_bytes(12)) . '.' . $allowed_mimes[$mime];
                $dest_path    = $upload_dir . '/' . $new_filename;
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $dest_path)) {
                    $errors[] = 'Failed to save uploaded photo.';
                } else {
                    $uploaded_photo = $new_filename;
                }
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('
            INSERT INTO students
                (roll_no, full_name, father_name, email, phone, gender, dob, address, course_id, admission_date, photo, status)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
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
            $uploaded_photo,
            $form['status'],
        ]);

        set_flash('success', 'Student "' . $form['full_name'] . '" added successfully.');
        redirect('students/list.php');
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Add New Student</h4>
        <small class="text-muted">Register a student and assign a course</small>
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
    <form method="POST" action="<?= e(url('students/add.php')) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Roll No *</label>
                <input type="text" name="roll_no" class="form-control" value="<?= e($form['roll_no']) ?>" placeholder="e.g., EDU-2026-011" required>
                <div class="invalid-feedback">Roll No is required.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($form['full_name']) ?>" required>
                <div class="invalid-feedback">Full Name is required.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Father's Name *</label>
                <input type="text" name="father_name" class="form-control" value="<?= e($form['father_name']) ?>" required>
                <div class="invalid-feedback">Father's Name is required.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" value="<?= e($form['email']) ?>" required>
                <div class="invalid-feedback">Valid email is required.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone" class="form-control" value="<?= e($form['phone']) ?>" required>
                <div class="invalid-feedback">Valid phone number is required.</div>
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
                <div class="invalid-feedback">Date of birth is required.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Course *</label>
                <select name="course_id" class="form-select" required>
                    <option value="">-- Select Course --</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $form['course_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Please select a course.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Admission Date *</label>
                <input type="date" name="admission_date" class="form-control" value="<?= e($form['admission_date']) ?>" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Address *</label>
                <textarea name="address" class="form-control" rows="3" required><?= e($form['address']) ?></textarea>
                <div class="invalid-feedback">Address is required.</div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status *</label>
                <select name="status" class="form-select" required>
                    <option value="Active" <?= $form['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $form['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Profile Photo (JPG/PNG, Max 2MB)</label>
                <input type="file" name="photo" id="photoInput" class="form-control" accept="image/jpeg,image/png">
                <div class="text-danger small mt-1" id="photoError"></div>
            </div>
            <div class="col-md-6 d-flex align-items-center">
                <img src="<?= e(url('assets/images/default-avatar.svg')) ?>" id="photoPreview" alt="Preview" class="avatar-profile" style="width: 84px; height: 84px;">
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4">
                <i class="bi bi-check-lg me-1"></i>Save Student
            </button>
            <a href="<?= e(url('students/list.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
