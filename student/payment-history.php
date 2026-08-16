<?php
// student/payment-history.php

$page_title = "Payment History";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get all payments for the student
$stmt = $conn->prepare("SELECT p.*, s.semester_name
                       FROM payments p
                       LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
                       LEFT JOIN semesters s ON sf.semester_id = s.id
                       WHERE p.student_id = ? AND p.payment_status = 'success'
                       ORDER BY p.payment_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$payments = $stmt->fetchAll();

// Calculate totals
$total_paid = 0;
foreach ($payments as $payment) {
    $total_paid += $payment['amount'];
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<h2 class="mb-4">Payment History</h2>

<div class="card mb-4">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h3 class="text-primary"><?php echo count($payments); ?></h3>
                <p class="text-muted mb-0">Total Transactions</p>
            </div>
            <div class="col-md-4">
                <h3 class="text-success"><?php echo format_currency($total_paid); ?></h3>
                <p class="text-muted mb-0">Total Amount Paid</p>
            </div>
            <div class="col-md-4">
                <h3 class="text-info"><?php echo date('Y'); ?></h3>
                <p class="text-muted mb-0">Academic Year</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>All Payments</h5>
    </div>
    <div class="card-body">
        <?php if (count($payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Semester</th>
                            <th>Amount</th>
                            <th>Payment ID</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong><?php echo $payment['receipt_no']; ?></strong></td>
                                <td><?php echo $payment['semester_name'] ?? 'N/A'; ?></td>
                                <td><strong><?php echo format_currency($payment['amount']); ?></strong></td>
                                <td><small><?php echo substr($payment['razorpay_payment_id'], 0, 20) . '...'; ?></small></td>
                                <td><?php echo format_date($payment['payment_date'], 'd-m-Y H:i'); ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst($payment['payment_method']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success badge-status">Success</span>
                                </td>
                                <td>
                                    <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" target="_blank" title="Download Receipt">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                <p class="text-muted mt-3">No payment history found</p>
                <a href="fees.php" class="btn btn-primary">View Fees</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>