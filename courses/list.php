<?php
/**
 * courses/list.php
 * Lists all academic courses with student enrollment counts and delete modal.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Courses Management';

$courses = $pdo->query('
    SELECT c.*, COUNT(s.id) AS student_count
    FROM courses c
    LEFT JOIN students s ON s.course_id = c.id
    GROUP BY c.id
    ORDER BY c.course_name ASC
')->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Courses</h4>
        <small class="text-muted">Manage academic programs, codes, durations, and tuition fees</small>
    </div>
    <a href="<?= e(url('courses/add.php')) ?>" class="btn btn-royal">
        <i class="bi bi-plus-circle me-1"></i>Add Course
    </a>
</div>

<div class="royal-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Course Name</th>
                        <th>Duration</th>
                        <th>Fees</th>
                        <th>Students</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No courses found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td><span class="badge badge-role"><?= e($c['course_code']) ?></span></td>
                                <td class="fw-semibold text-navy"><?= e($c['course_name']) ?></td>
                                <td><?= e($c['duration']) ?></td>
                                <td class="fw-semibold">&#8377;<?= e(number_format((float) $c['fees'], 2)) ?></td>
                                <td><span class="badge bg-light text-navy border"><?= (int) $c['student_count'] ?></span></td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(url('courses/edit.php?id=' . (int) $c['id'])) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteConfirmModal"
                                            data-id="<?= (int) $c['id'] ?>"
                                            data-name="<?= e($c['course_name'] . ' (' . $c['course_code'] . ')') ?>"
                                            data-action="<?= e(url('courses/delete.php')) ?>">
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
                <input type="hidden" name="id" id="deleteRecordId" value="">
                <div class="modal-body">
                    Are you sure you want to delete course <strong id="deleteRecordName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
