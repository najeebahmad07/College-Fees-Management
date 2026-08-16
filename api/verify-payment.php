<?php
// api/verify-payment.php

header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    $razorpay_order_id = $input['razorpay_order_id'] ?? '';
    $razorpay_payment_id = $input['razorpay_payment_id'] ?? '';
    $razorpay_signature = $input['razorpay_signature'] ?? '';
    $fee_id = isset($input['fee_id']) ? intval($input['fee_id']) : 0;
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;

    // Validation
    if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature)) {
        echo json_encode(['success' => false, 'message' => 'Missing payment parameters']);
        exit;
    }

    if ($fee_id <= 0 || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid fee or amount']);
        exit;
    }

    // Verify Razorpay signature
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RAZORPAY_KEY_SECRET);

    if (!hash_equals($generated_signature, $razorpay_signature)) {
        error_log("Signature verification failed for order: " . $razorpay_order_id);
        echo json_encode(['success' => false, 'message' => 'Payment verification failed. Invalid signature.']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Start transaction
    $conn->beginTransaction();

    try {
        // Check if payment already processed
        $stmt = $conn->prepare("SELECT id, payment_status FROM payments
                               WHERE razorpay_payment_id = ? AND payment_status = 'success'");
        $stmt->execute([$razorpay_payment_id]);

        if ($stmt->fetch()) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Payment already processed']);
            exit;
        }

        // Generate unique receipt number
        $receipt_no = generate_receipt_number();

        // Update payment record
        $stmt = $conn->prepare("UPDATE payments SET
                               receipt_no = ?,
                               razorpay_payment_id = ?,
                               razorpay_signature = ?,
                               payment_status = 'success',
                               payment_date = NOW()
                               WHERE razorpay_order_id = ? AND student_id = ?");

        $updated = $stmt->execute([
            $receipt_no,
            $razorpay_payment_id,
            $razorpay_signature,
            $razorpay_order_id,
            $_SESSION['user_id']
        ]);

        if (!$updated || $stmt->rowCount() === 0) {
            throw new Exception("Failed to update payment record");
        }

        // Get payment ID
        $stmt = $conn->prepare("SELECT id FROM payments WHERE razorpay_order_id = ? AND student_id = ?");
        $stmt->execute([$razorpay_order_id, $_SESSION['user_id']]);
        $payment_record = $stmt->fetch();

        if (!$payment_record) {
            throw new Exception("Payment record not found after update");
        }

        $payment_id = $payment_record['id'];

        // Update student fee
        $stmt = $conn->prepare("UPDATE student_fees SET
                               paid_amount = paid_amount + ?
                               WHERE id = ? AND student_id = ?");
        $stmt->execute([$amount, $fee_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to update student fee");
        }

        // Update fee status
        $stmt = $conn->prepare("SELECT total_fee, paid_amount FROM student_fees WHERE id = ?");
        $stmt->execute([$fee_id]);
        $fee = $stmt->fetch();

        if ($fee) {
            $new_status = 'pending';
            if ($fee['paid_amount'] >= $fee['total_fee']) {
                $new_status = 'paid';
            } elseif ($fee['paid_amount'] > 0) {
                $new_status = 'partial';
            }

            $stmt = $conn->prepare("UPDATE student_fees SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $fee_id]);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Payment verified successfully',
            'payment_id' => $payment_id,
            'receipt_no' => $receipt_no
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Payment verification transaction error: " . $e->getMessage());
        throw $e;
    }

} catch (PDOException $e) {
    error_log("Payment verification DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred during verification']);
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment verification failed. Please contact support.']);
}
?>