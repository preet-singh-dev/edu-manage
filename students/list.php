<?php
/**
 * students/list.php
 * Paginated student directory with search, course/status filters,
 * CSV export link, and Bootstrap delete confirmation modal.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Students Directory';

// Filters & Pagination
$search    = trim($_GET['search'] ?? '');
$course_id = (int) ($_GET['course_id'] ?? 0);
$status    = trim($_GET['status'] ?? '');
$page      = max(1, (int) ($_GET['page'] ?? 1));
$per_page  = 10;

$where  = [];
$params = [];

if ($search !== '') {
    $where[]  = '(s.full_name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($course_id > 0) {
    $where[]  = 's.course_id = ?';
    $params[] = $course_id;
}

if (in_array($status, ['Active', 'Inactive'], true)) {
    $where[]  = 's.status = ?';
    $params[] = $status;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count total matching students
$count_sql  = "SELECT COUNT(*) FROM students s {$where_sql}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows  = (int) $count_stmt->fetchColumn();
$total_pages = max(1, (int) ceil($total_rows / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

// Fetch paginated students
$list_sql = "
    SELECT s.*, c.course_name, c.course_code
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    {$where_sql}
    ORDER BY s.id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$list_stmt = $pdo->prepare($list_sql);
$list_stmt->execute($params);
$students = $list_stmt->fetchAll();

$courses = $pdo->query('SELECT id, course_name, course_code FROM courses ORDER BY course_name ASC')->fetchAll();

// Preserve query string for pagination links
$filter_query = http_build_query([
    'search'    => $search,
    'course_id' => $course_id ?: '',
    'status'    => $status,
]);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Manage Students</h4>
        <small class="text-muted">Showing <?= count($students) ?> of <?= $total_rows ?> students</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(url('students/export_csv.php?' . $filter_query)) ?>" class="btn btn-outline-navy">
            <i class="bi bi-download me-1"></i>Export CSV
        </a>
        <a href="<?= e(url('students/add.php')) ?>" class="btn btn-royal">
            <i class="bi bi-person-plus-fill me-1"></i>Add Student
        </a>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="royal-card p-3 mb-4">
    <form method="GET" action="<?= e(url('students/list.php')) ?>" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Name, Roll No, or Email">
        </div>
        <div class="col-md-3">
            <label class="form-label">Course</label>
            <select name="course_id" class="form-select">
                <option value="">All Courses</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= $course_id === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['course_name']) ?> (<?= e($c['course_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-royal flex-fill"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="<?= e(url('students/list.php')) ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</div>

<!-- Students Table -->
<div class="royal-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Roll No</th>
                        <th>Full Name</th>
                        <th>Course</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No students match the selected filters.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $st): ?>
                            <?php
                            $avatar = !empty($st['photo'])
                                ? url('uploads/students/' . $st['photo'])
                                : url('assets/images/default-avatar.svg');
                            ?>
                            <tr>
                                <td><img src="<?= e($avatar) ?>" alt="Photo" class="avatar-thumb"></td>
                                <td class="fw-semibold text-navy"><?= e($st['roll_no']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= e($st['full_name']) ?></div>
                                    <small class="text-muted"><?= e($st['email']) ?></small>
                                </td>
                                <td><?= e($st['course_name']) ?></td>
                                <td><?= e($st['phone']) ?></td>
                                <td>
                                    <span class="badge <?= $st['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= e($st['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(url('students/view.php?id=' . (int) $st['id'])) ?>" class="btn btn-sm btn-outline-navy" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= e(url('students/edit.php?id=' . (int) $st['id'])) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmModal"
                                            data-id="<?= (int) $st['id'] ?>"
                                            data-name="<?= e($st['full_name'] . ' (' . $st['roll_no'] . ')') ?>"
                                            data-action="<?= e(url('students/delete.php')) ?>">
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

    <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?></small>
            <nav aria-label="Student Pagination">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(url('students/list.php?' . $filter_query . '&page=' . ($page - 1))) ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(url('students/list.php?' . $filter_query . '&page=' . $p)) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= e(url('students/list.php?' . $filter_query . '&page=' . ($page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-navy text-white">
                <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill text-gold me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="deleteConfirmForm" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="deleteRecordId" value="">
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteRecordName"></strong>? This will also remove their attendance, marks, and fee records.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
