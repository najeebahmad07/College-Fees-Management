<?php
// student/payment-success.php

$page_title = "Payment Successful";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

if ($payment_id <= 0) {
    set_message('error', 'Invalid payment ID');
    redirect('student/fees.php');
}

// Get payment details
$stmt = $conn->prepare("SELECT p.*, s.semester_name, st.student_name, st.enrollment_no
                       FROM payments p
                       LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
                       LEFT JOIN semesters s ON sf.semester_id = s.id
                       JOIN students st ON p.student_id = st.id
                       WHERE p.id = ? AND p.student_id = ?");
$stmt->execute([$payment_id, $_SESSION['user_id']]);
$payment = $stmt->fetch();

if (!$payment) {
    set_message('error', 'Payment record not found');
    redirect('student/fees.php');
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-success">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 80px;"></i>
                </div>
                <h2 class="text-success mb-3">Payment Successful!</h2>
                <p class="lead mb-4">Your payment has been processed successfully.</p>

                <div class="bg-light p-4 rounded mb-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Receipt Number:</strong><br>
                            <span class="text-primary h5"><?php echo $payment['receipt_no']; ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Payment ID:</strong><br>
                            <code><?php echo $payment['razorpay_payment_id']; ?></code>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Amount Paid:</strong><br>
                            <span class="text-success h4"><?php echo format_currency($payment['amount']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Payment Date:</strong><br>
                            <?php echo format_date($payment['payment_date'], 'd-m-Y H:i:s'); ?>
                        </div>
                        <div class="col-md-12">
                            <strong>Semester:</strong><br>
                            <?php echo $payment['semester_name'] ?? 'N/A'; ?>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-primary btn-lg" target="_blank">
                        <i class="bi bi-file-pdf me-2"></i>Download Receipt
                    </a>
                    <a href="fees.php" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-receipt me-2"></i>View All Fees
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-house me-2"></i>Go to Dashboard
                    </a>
                </div>

                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Please save or print your receipt for future reference.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>