<?php
/**
 * fees/add.php
 * Record a student fee payment and redirect to printable receipt.
 * Restricted to Admin role.
 */

require_once dirname(__DIR__) . '/includes/auth_check.php';
require_role(['admin']);

$page_title = 'Record Fee Payment';

$students = $pdo->query('
    SELECT
        s.id, s.roll_no, s.full_name, c.course_name, c.fees AS total_fees,
        COALESCE(SUM(f.amount), 0) AS paid_fees
    FROM students s
    INNER JOIN courses c ON s.course_id = c.id
    LEFT JOIN fees f ON f.student_id = s.id
    GROUP BY s.id, s.roll_no, s.full_name, c.course_name, c.fees
    ORDER BY s.roll_no ASC
')->fetchAll();

$student_fee_map = [];
foreach ($students as $st) {
    $total   = (float) $st['total_fees'];
    $paid    = (float) $st['paid_fees'];
    $pending = max(0, $total - $paid);
    $student_fee_map[(int) $st['id']] = [
        'total'   => $total,
        'paid'    => $paid,
        'pending' => $pending,
    ];
}

$errors = [];
$form = [
    'student_id'   => (string) (int) ($_GET['student_id'] ?? 0),
    'amount'       => '',
    'payment_date' => date('Y-m-d'),
    'payment_mode' => 'Cash',
    'remarks'      => '',
];

$allowed_modes = ['Cash', 'Card', 'Bank Transfer', 'UPI', 'Cheque'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }

    foreach ($form as $k => $v) {
        $form[$k] = trim((string) ($_POST[$k] ?? $v));
    }

    $sid    = (int) $form['student_id'];
    $amount = is_numeric($form['amount']) ? (float) $form['amount'] : 0.0;

    if ($sid <= 0 || !isset($student_fee_map[$sid])) {
        $errors[] = 'Please select a valid student.';
    }
    if ($amount <= 0) {
        $errors[] = 'Payment amount must be greater than 0.';
    }
    if ($form['payment_date'] === '' || !strtotime($form['payment_date'])) {
        $errors[] = 'Valid payment date is required.';
    }
    if (!in_array($form['payment_mode'], $allowed_modes, true)) {
        $errors[] = 'Invalid payment mode selected.';
    }

    if (empty($errors)) {
        $receipt_no = 'REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $stmt = $pdo->prepare('
            INSERT INTO fees (receipt_no, student_id, amount, payment_date, payment_mode, remarks)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $receipt_no,
            $sid,
            $amount,
            $form['payment_date'],
            $form['payment_mode'],
            $form['remarks'] !== '' ? $form['remarks'] : null,
        ]);

        $new_fee_id = (int) $pdo->lastInsertId();
        set_flash('success', 'Fee payment recorded (Receipt: ' . $receipt_no . ').');
        redirect('fees/receipt.php?id=' . $new_fee_id);
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-navy mb-0">Record Fee Payment</h4>
        <small class="text-muted">Collect tuition payments and generate official receipts</small>
    </div>
    <a href="<?= e(url('fees/list.php')) ?>" class="btn btn-outline-navy">
        <i class="bi bi-arrow-left me-1"></i>Fees Overview
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

<div class="royal-card p-4" style="max-width: 760px;">
    <form method="POST" action="<?= e(url('fees/add.php')) ?>" class="needs-validation" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Student *</label>
                <select name="student_id" id="feeStudentSelect" class="form-select" required>
                    <option value="">-- Select Student --</option>
                    <?php foreach ($students as $st): ?>
                        <option value="<?= (int) $st['id'] ?>" <?= (int) $form['student_id'] === (int) $st['id'] ? 'selected' : '' ?>>
                            <?= e($st['roll_no'] . ' - ' . $st['full_name'] . ' (' . $st['course_name'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Live Fee Summary Box -->
            <div class="col-12">
                <div class="p-3 rounded-3 bg-offwhite border row g-2 text-center mx-0">
                    <div class="col-4">
                        <small class="text-muted d-block">Total Fees</small>
                        <strong class="text-navy" id="feeTotalDisplay">&#8377;0.00</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Paid Amount</small>
                        <strong class="text-success" id="feePaidDisplay">&#8377;0.00</strong>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block">Pending Balance</small>
                        <strong class="text-danger" id="feePendingDisplay">&#8377;0.00</strong>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Amount (&#8377;) *</label>
                <input type="number" step="0.01" min="1" name="amount" class="form-control" value="<?= e($form['amount']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Payment Date *</label>
                <input type="date" name="payment_date" class="form-control" value="<?= e($form['payment_date']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Payment Mode *</label>
                <select name="payment_mode" class="form-select" required>
                    <?php foreach ($allowed_modes as $mode): ?>
                        <option value="<?= e($mode) ?>" <?= $form['payment_mode'] === $mode ? 'selected' : '' ?>>
                            <?= e($mode) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">Remarks (Optional)</label>
                <input type="text" name="remarks" class="form-control" value="<?= e($form['remarks']) ?>" placeholder="e.g., Semester 2 installment">
            </div>
        </div>

        <div class="mt-4 pt-3 border-top d-flex gap-2">
            <button type="submit" class="btn btn-royal px-4">
                <i class="bi bi-receipt me-1"></i>Save &amp; Generate Receipt
            </button>
            <a href="<?= e(url('fees/list.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const feeMap = <?= json_encode($student_fee_map) ?>;
    const selectEl = document.getElementById('feeStudentSelect');
    const totalEl = document.getElementById('feeTotalDisplay');
    const paidEl = document.getElementById('feePaidDisplay');
    const pendingEl = document.getElementById('feePendingDisplay');

    function formatCurrency(val) {
        return '\u20B9' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function refreshFeeSummary() {
        const sid = selectEl ? selectEl.value : '';
        const info = feeMap[sid] || { total: 0, paid: 0, pending: 0 };
        if (totalEl) totalEl.textContent = formatCurrency(info.total);
        if (paidEl) paidEl.textContent = formatCurrency(info.paid);
        if (pendingEl) pendingEl.textContent = formatCurrency(info.pending);
    }

    if (selectEl) {
        selectEl.addEventListener('change', refreshFeeSummary);
        refreshFeeSummary();
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
