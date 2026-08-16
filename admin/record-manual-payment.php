<?php
// admin/record-manual-payment.php

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_message('error', 'Invalid request method');
    redirect('admin/student-fees.php');
}

if (!verify_csrf_token($_POST['csrf_token'])) {
    set_message('error', 'Invalid CSRF token');
    redirect('admin/student-fees.php');
}

$db = new Database();
$conn = $db->getConnection();

try {
    $student_fee_id = intval($_POST['student_fee_id']);
    $amount = floatval($_POST['amount']);
    $payment_mode = clean_input($_POST['payment_mode']);
    $reference_no = clean_input($_POST['reference_no']);
    $payment_date = clean_input($_POST['payment_date']);
    $remarks = clean_input($_POST['remarks']);

    // Validate
    if ($student_fee_id <= 0 || $amount <= 0) {
        set_message('error', 'Invalid fee ID or amount');
        redirect('admin/student-fees.php');
    }

    // Get student fee details
    $stmt = $conn->prepare("SELECT sf.*, st.enrollment_no
                           FROM student_fees sf
                           JOIN students st ON sf.student_id = st.id
                           WHERE sf.id = ?");
    $stmt->execute([$student_fee_id]);
    $fee = $stmt->fetch();

    if (!$fee) {
        set_message('error', 'Fee record not found');
        redirect('admin/student-fees.php');
    }

    if ($amount > $fee['pending_amount']) {
        set_message('error', 'Amount exceeds pending fee');
        redirect('admin/student-fees.php');
    }

    // Start transaction
    $conn->beginTransaction();

    // Generate receipt number
    $receipt_no = generate_receipt_number();

    // Insert into payments table
    $stmt = $conn->prepare("INSERT INTO payments
                           (student_id, student_fee_id, enrollment_no, receipt_no, amount,
                            currency, payment_method, payment_status, payment_date, remarks)
                           VALUES (?, ?, ?, ?, ?, 'INR', ?, 'success', ?, ?)");
    $stmt->execute([
        $fee['student_id'],
        $student_fee_id,
        $fee['enrollment_no'],
        $receipt_no,
        $amount,
        $payment_mode,
        $payment_date,
        $remarks
    ]);

    // Insert into admin payment logs
    $stmt = $conn->prepare("INSERT INTO admin_payment_logs
                           (student_id, student_fee_id, amount, payment_mode, reference_no,
                            payment_date, remarks, created_by)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $fee['student_id'],
        $student_fee_id,
        $amount,
        $payment_mode,
        $reference_no,
        $payment_date,
        $remarks,
        $_SESSION['user_id']
    ]);

    // Update student_fees
    $stmt = $conn->prepare("UPDATE student_fees SET paid_amount = paid_amount + ? WHERE id = ?");
    $stmt->execute([$amount, $student_fee_id]);

    // Update status
    $stmt = $conn->prepare("SELECT total_fee, paid_amount FROM student_fees WHERE id = ?");
    $stmt->execute([$student_fee_id]);
    $updated_fee = $stmt->fetch();

    $new_status = 'pending';
    if ($updated_fee['paid_amount'] >= $updated_fee['total_fee']) {
        $new_status = 'paid';
    } elseif ($updated_fee['paid_amount'] > 0) {
        $new_status = 'partial';
    }

    $stmt = $conn->prepare("UPDATE student_fees SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $student_fee_id]);

    // Commit transaction
    $conn->commit();

    set_message('success', 'Manual payment recorded successfully. Receipt No: ' . $receipt_no);
    redirect('admin/student-fees.php');

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Manual payment error: " . $e->getMessage());
    set_message('error', 'Failed to record payment. Please try again.');
    redirect('admin/student-fees.php');
}
?>