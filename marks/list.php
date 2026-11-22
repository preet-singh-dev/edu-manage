<?php
/**
 * marks/list.php
 * View and filter student exam marks, delete entries via POST+CSRF,
 * and open printable Result Cards.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

// Handle POST delete mark action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_mark_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token.');
    } else {
        $del_id = (int) $_POST['delete_mark_id'];
        $del    = $pdo->prepare('DELETE FROM marks WHERE id = ?');
        $del->execute([$del_id]);
        set_flash('success', 'Mark entry deleted.');
    }
    redirect('marks/list.php');
}

$page_title = 'Marks & Results';

$course_id = (int) ($_GET['course_id'] ?? 0);
$exam_name = trim($_GET['exam_name'] ?? '');
$search    = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($course_id > 0) {
    $where[]  = 's.course_id = ?';
    $params[] = $course_id;
}
if ($exam_name !== '') {
    $where[]  = 'm.exam_name = ?';
    $params[] = $exam_name;
}
if ($search !== '') {
    $where[]  = '(s.full_name LIKE ? OR s.roll_no LIKE ? OR m.subject LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare("
    SELECT m.*, s.roll_no, s.full_name, c.course_name
    FROM marks m
    INNER JOIN students s ON m.student_id = s.id
    INNER JOIN courses c ON s.course_id = c.id
    {$where_sql}
    ORDER BY m.id DESC
");
$stmt->execute($params);
$marks = $stmt->fetchAll();

$courses = $pdo->query('SELECT id, course_name FROM courses ORDER BY course_name ASC')->fetchAll();
$exams   = $pdo->query('SELECT DISTINCT exam_name FROM marks ORDER BY exam_name ASC')->fetchAll(PDO::FETCH_COLUMN);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Marks &amp; Exam Results</h4>
        <small class="text-muted">Manage subject marks and generate printable result cards</small>
    </div>
    <a href="<?= e(url('marks/add.php')) ?>" class="btn btn-royal">
        <i class="bi bi-plus-circle me-1"></i>Add Marks
    </a>
</div>

<!-- Filter Bar -->
<div class="royal-card p-3 mb-4">
    <form method="GET" action="<?= e(url('marks/list.php')) ?>" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search Student / Subject</label>
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Roll No, Name, or Subject">
        </div>
        <div class="col-md-3">
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
            <label class="form-label">Exam</label>
            <select name="exam_name" class="form-select">
                <option value="">All Exams</option>
                <?php foreach ($exams as $ex): ?>
                    <option value="<?= e($ex) ?>" <?= $exam_name === $ex ? 'selected' : '' ?>>
                        <?= e($ex) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-royal flex-fill">Filter</button>
            <a href="<?= e(url('marks/list.php')) ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<div class="royal-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Subject</th>
                        <th>Score</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Result</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($marks)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">No marks records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($marks as $m): ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= e($m['roll_no']) ?></td>
                                <td>
                                    <div class="fw-medium"><?= e($m['full_name']) ?></div>
                                    <small class="text-muted"><?= e($m['course_name']) ?></small>
                                </td>
                                <td><?= e($m['exam_name']) ?></td>
                                <td><?= e($m['subject']) ?></td>
                                <td><?= e($m['marks_obtained']) ?> / <?= (int) $m['max_marks'] ?></td>
                                <td><?= e($m['percentage']) ?>%</td>
                                <td><span class="badge badge-role"><?= e($m['grade']) ?></span></td>
                                <td>
                                    <span class="badge <?= $m['result_status'] === 'Pass' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= e($m['result_status']) ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(url('marks/result_card.php?student_id=' . (int) $m['student_id'] . '&exam=' . urlencode($m['exam_name']))) ?>"
                                       class="btn btn-sm btn-gold" title="Printable Result Card">
                                        <i class="bi bi-printer me-1"></i>Result Card
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmModal"
                                            data-id="<?= (int) $m['id'] ?>"
                                            data-name="<?= e($m['full_name'] . ' - ' . $m['subject'] . ' (' . $m['exam_name'] . ')') ?>"
                                            data-action="<?= e(url('marks/list.php')) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-gold me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="deleteConfirmForm" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_mark_id" id="deleteRecordId" value="">
                <div class="modal-body">
                    Are you sure you want to delete marks for <strong id="deleteRecordName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete Marks</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
