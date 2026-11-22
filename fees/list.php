<?php
/**
 * fees/list.php
 * Shows total, paid, and pending fees per student alongside payment transactions.
 * Restricted to Admin role.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Fees Management';

// Student Fee Balances
$balances = $pdo->query('
    SELECT
        s.id, s.roll_no, s.full_name, c.course_name, c.fees AS total_fees,
        COALESCE(SUM(f.amount), 0) AS paid_amount
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    LEFT JOIN fees f ON f.student_id = s.id
    GROUP BY s.id, s.roll_no, s.full_name, c.course_name, c.fees
    ORDER BY s.roll_no ASC
')->fetchAll();

// Recent Payment Transactions
$payments = $pdo->query('
    SELECT f.*, s.roll_no, s.full_name, c.course_name
    FROM fees f
    INNER JOIN students s ON f.student_id = s.id
    INNER JOIN courses c ON s.course_id = c.id
    ORDER BY f.payment_date DESC, f.id DESC
')->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Fees Management</h4>
        <small class="text-muted">Track student fee balances and print payment receipts</small>
    </div>
    <a href="<?= e(url('fees/add.php')) ?>" class="btn btn-royal">
        <i class="bi bi-plus-circle me-1"></i>Record Payment
    </a>
</div>

<!-- Student Fee Summary Table -->
<div class="royal-card mb-4">
    <div class="card-header">
        <i class="bi bi-wallet2 text-gold me-2"></i>Student Fee Balance Summary
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Total Fees</th>
                        <th>Paid Amount</th>
                        <th>Pending Amount</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($balances)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No students found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($balances as $b): ?>
                            <?php
                            $total   = (float) $b['total_fees'];
                            $paid    = (float) $b['paid_amount'];
                            $pending = max(0, $total - $paid);
                            ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= e($b['roll_no']) ?></td>
                                <td class="fw-medium"><?= e($b['full_name']) ?></td>
                                <td><?= e($b['course_name']) ?></td>
                                <td>&#8377;<?= e(number_format($total, 2)) ?></td>
                                <td class="text-success fw-semibold">&#8377;<?= e(number_format($paid, 2)) ?></td>
                                <td class="<?= $pending > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' ?>">
                                    &#8377;<?= e(number_format($pending, 2)) ?>
                                </td>
                                <td>
                                    <?php if ($pending <= 0): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php elseif ($paid > 0): ?>
                                        <span class="badge bg-warning text-dark">Partial</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(url('fees/add.php?student_id=' . (int) $b['id'])) ?>" class="btn btn-sm btn-outline-navy">
                                        <i class="bi bi-cash me-1"></i>Pay
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Payment Transactions -->
<div class="royal-card">
    <div class="card-header">
        <i class="bi bi-receipt-cutoff text-royal me-2"></i>Payment Transactions
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-royal table-hover mb-0">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Mode</th>
                        <th>Amount</th>
                        <th>Remarks</th>
                        <th class="text-end">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No fee payments recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="fw-semibold text-navy"><?= e($p['receipt_no']) ?></td>
                                <td><?= e(date('d M Y', strtotime($p['payment_date']))) ?></td>
                                <td>
                                    <div class="fw-medium"><?= e($p['full_name']) ?></div>
                                    <small class="text-muted"><?= e($p['roll_no']) ?> &bull; <?= e($p['course_name']) ?></small>
                                </td>
                                <td><span class="badge badge-role"><?= e($p['payment_mode']) ?></span></td>
                                <td class="fw-semibold text-success">&#8377;<?= e(number_format((float) $p['amount'], 2)) ?></td>
                                <td class="text-muted small"><?= e($p['remarks'] ?? '-') ?></td>
                                <td class="text-end">
                                    <a href="<?= e(url('fees/receipt.php?id=' . (int) $p['id'])) ?>" class="btn btn-sm btn-gold">
                                        <i class="bi bi-printer me-1"></i>Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
