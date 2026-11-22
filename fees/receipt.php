<?php
/**
 * fees/receipt.php
 * Print-friendly official Fee Payment Receipt.
 * Restricted to Admin role.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('danger', 'Invalid receipt ID.');
    redirect('fees/list.php');
}

$stmt = $pdo->prepare('
    SELECT f.*, s.roll_no, s.full_name, s.father_name, s.phone,
           c.course_name, c.course_code, c.fees AS total_fees
    FROM fees f
    INNER JOIN students s ON f.student_id = s.id
    INNER JOIN courses c ON s.course_id = c.id
    WHERE f.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    set_flash('danger', 'Fee receipt not found.');
    redirect('fees/list.php');
}

// Compute total paid & remaining balance for this student
$paid_stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM fees WHERE student_id = ?');
$paid_stmt->execute([(int) $receipt['student_id']]);
$total_paid = (float) $paid_stmt->fetchColumn();
$total_fees = (float) $receipt['total_fees'];
$pending    = max(0, $total_fees - $total_paid);

$page_title = 'Fee Receipt';
include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="<?= e(url('fees/list.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-arrow-left me-1"></i>Back to Fees
    </a>
    <button type="button" onclick="window.print()" class="btn btn-gold">
        <i class="bi bi-printer-fill me-1"></i>Print Receipt
    </button>
</div>

<div class="royal-card printable-sheet p-4 p-md-5 mx-auto" style="max-width: 780px;">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4" style="border-bottom: 3px solid var(--gold) !important;">
        <div>
            <h3 class="fw-bold text-navy mb-1">
                <i class="bi bi-mortarboard-fill text-gold me-2"></i><?= e(APP_NAME) ?> Academy
            </h3>
            <p class="text-muted small mb-0">Official Tuition &amp; Fee Payment Receipt</p>
        </div>
        <div class="text-end">
            <span class="badge bg-navy text-gold px-3 py-2 fs-6">FEE RECEIPT</span>
            <div class="small fw-semibold text-navy mt-1"><?= e($receipt['receipt_no']) ?></div>
        </div>
    </div>

    <div class="row g-3 small mb-4">
        <div class="col-sm-6">
            <span class="text-muted d-block">Received From</span>
            <strong class="fs-6 text-navy"><?= e($receipt['full_name']) ?></strong>
            <div class="text-muted">S/D of <?= e($receipt['father_name']) ?></div>
            <div class="text-muted">Roll No: <?= e($receipt['roll_no']) ?></div>
        </div>
        <div class="col-sm-6 text-sm-end">
            <span class="text-muted d-block">Payment Date</span>
            <strong class="text-navy"><?= e(date('d M Y', strtotime($receipt['payment_date']))) ?></strong>
            <div class="text-muted">Course: <?= e($receipt['course_name']) ?> (<?= e($receipt['course_code']) ?>)</div>
            <div class="text-muted">Mode: <?= e($receipt['payment_mode']) ?></div>
        </div>
    </div>

    <div class="table-responsive mb-4">
        <table class="table table-bordered table-royal mb-0">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount (&#8377;)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Tuition / Course Fee Payment (<?= e($receipt['course_name']) ?>)
                        <?php if (!empty($receipt['remarks'])): ?>
                            <div class="small text-muted">Note: <?= e($receipt['remarks']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold text-navy fs-5">&#8377;<?= e(number_format((float) $receipt['amount'], 2)) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row g-2 bg-offwhite p-3 rounded-3 border small mb-4">
        <div class="col-4">
            <span class="text-muted d-block">Total Course Fee</span>
            <strong class="text-navy">&#8377;<?= e(number_format($total_fees, 2)) ?></strong>
        </div>
        <div class="col-4 text-center">
            <span class="text-muted d-block">Total Paid To Date</span>
            <strong class="text-success">&#8377;<?= e(number_format($total_paid, 2)) ?></strong>
        </div>
        <div class="col-4 text-end">
            <span class="text-muted d-block">Remaining Balance</span>
            <strong class="<?= $pending > 0 ? 'text-danger' : 'text-success' ?>">&#8377;<?= e(number_format($pending, 2)) ?></strong>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end pt-4 mt-4 border-top small text-muted">
        <span>This is a computer-generated receipt.</span>
        <div class="text-end">
            <div class="mt-3 pt-2 border-top px-3">Authorized Signatory</div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
