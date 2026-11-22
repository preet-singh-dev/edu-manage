<?php
/**
 * dashboard.php
 * Main overview with KPI summary cards, Chart.js students-per-course chart,
 * and recent 5 students table.
 */

require_once __DIR__ . '/includes/auth_check.php';
require_role(['admin', 'teacher']);

$page_title       = 'Dashboard';
$include_chart_js = true;

// 1. Summary Metrics
$total_students = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$total_courses  = (int) $pdo->query('SELECT COUNT(*) FROM courses')->fetchColumn();
$total_teachers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();

$att_stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_marked,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count
    FROM attendance
    WHERE attendance_date = CURDATE()
");
$att_stmt->execute();
$att_row = $att_stmt->fetch();
$today_attendance_pct = ($att_row && (int) $att_row['total_marked'] > 0)
    ? round(((int) $att_row['present_count'] / (int) $att_row['total_marked']) * 100, 1)
    : 0.0;

// 2. Students per Course for Chart.js
$chart_stmt = $pdo->query('
    SELECT c.course_name, COUNT(s.id) AS student_count
    FROM courses c
    LEFT JOIN students s ON s.course_id = c.id
    GROUP BY c.id, c.course_name
    ORDER BY student_count DESC, c.course_name ASC
');
$course_stats = $chart_stmt->fetchAll();

$chart_labels = [];
$chart_counts = [];
foreach ($course_stats as $row) {
    $chart_labels[] = $row['course_name'];
    $chart_counts[] = (int) $row['student_count'];
}

// 3. Recent 5 Students
$recent_students = $pdo->query('
    SELECT s.id, s.roll_no, s.full_name, s.photo, s.status, s.admission_date, c.course_name
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    ORDER BY s.id DESC
    LIMIT 5
')->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Summary Cards -->
<div class="row g-3 g-md-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted small fw-medium mb-1">Total Students</p>
                    <h2 class="fw-bold text-navy mb-0"><?= $total_students ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-mortarboard text-gold me-1"></i>Enrolled across all courses
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-gold h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted small fw-medium mb-1">Total Courses</p>
                    <h2 class="fw-bold text-navy mb-0"><?= $total_courses ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-check2-circle text-royal me-1"></i>Active academic programs
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-navy h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted small fw-medium mb-1">Total Teachers</p>
                    <h2 class="fw-bold text-navy mb-0"><?= $total_teachers ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-person-workspace"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-award text-gold me-1"></i>Faculty accounts active
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card accent-gold h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="text-muted small fw-medium mb-1">Today's Attendance</p>
                    <h2 class="fw-bold text-navy mb-0"><?= e(number_format($today_attendance_pct, 1)) ?>%</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                <i class="bi bi-clock-history text-royal me-1"></i><?= date('d M Y') ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart & Recent Students -->
<div class="row g-4">
    <div class="col-lg-5">
        <div class="royal-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart-fill text-gold me-2"></i>Students per Course</span>
                <span class="badge badge-role">Live</span>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 290px;">
                    <canvas id="studentsPerCourseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="royal-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-lines-fill text-royal me-2"></i>Recent 5 Students</span>
                <?php if (is_admin()): ?>
                    <a href="<?= e(url('students/list.php')) ?>" class="btn btn-sm btn-royal">View All</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-royal table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Roll No</th>
                                <th>Course</th>
                                <th>Status</th>
                                <?php if (is_admin()): ?>
                                    <th class="text-end">Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_students)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No student records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_students as $st): ?>
                                    <?php
                                    $avatar = !empty($st['photo'])
                                        ? url('uploads/students/' . $st['photo'])
                                        : url('assets/images/default-avatar.svg');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= e($avatar) ?>" alt="Photo" class="avatar-thumb">
                                                <div>
                                                    <div class="fw-semibold text-navy"><?= e($st['full_name']) ?></div>
                                                    <small class="text-muted"><?= e(date('d M Y', strtotime($st['admission_date']))) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="fw-medium"><?= e($st['roll_no']) ?></span></td>
                                        <td><?= e($st['course_name']) ?></td>
                                        <td>
                                            <span class="badge <?= $st['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= e($st['status']) ?>
                                            </span>
                                        </td>
                                        <?php if (is_admin()): ?>
                                            <td class="text-end">
                                                <a href="<?= e(url('students/view.php?id=' . (int) $st['id'])) ?>" class="btn btn-sm btn-outline-navy" title="View Profile">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('studentsPerCourseChart');
    if (!ctx || typeof Chart === 'undefined') return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Enrolled Students',
                data: <?= json_encode($chart_counts) ?>,
                backgroundColor: ['#1c2f6b', '#c9a227', '#0a1128', '#3352b0', '#dbb94d'],
                borderRadius: 8,
                maxBarThickness: 48
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
