<?php
// api/create-order.php

header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Check if user is logged in
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
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $fee_id = isset($input['fee_id']) ? intval($input['fee_id']) : 0;
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;

    // Validation
    if ($fee_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid fee ID']);
        exit;
    }

    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        exit;
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify fee belongs to student
    $stmt = $conn->prepare("SELECT sf.*, st.enrollment_no, st.student_name
                           FROM student_fees sf
                           JOIN students st ON sf.student_id = st.id
                           WHERE sf.id = ? AND sf.student_id = ?");
    $stmt->execute([$fee_id, $_SESSION['user_id']]);
    $fee = $stmt->fetch();

    if (!$fee) {
        echo json_encode(['success' => false, 'message' => 'Fee record not found']);
        exit;
    }

    if ($amount > $fee['pending_amount']) {
        echo json_encode(['success' => false, 'message' => 'Amount exceeds pending fee']);
        exit;
    }

    // Check Razorpay configuration
    if (empty(RAZORPAY_KEY_ID) || empty(RAZORPAY_KEY_SECRET)) {
        echo json_encode(['success' => false, 'message' => 'Payment gateway not configured']);
        exit;
    }

    // Generate temporary receipt number
    $temp_receipt = 'TEMP_' . time() . '_' . $_SESSION['user_id'];

    // Create Razorpay order
    $razorpay_order = create_razorpay_order($amount, $temp_receipt, [
        'student_id' => $_SESSION['user_id'],
        'enrollment_no' => $fee['enrollment_no'],
        'fee_id' => $fee_id
    ]);

    if (!$razorpay_order) {
        echo json_encode(['success' => false, 'message' => 'Failed to create payment order. Please contact administrator.']);
        exit;
    }

    // Save payment record with 'created' status
    $stmt = $conn->prepare("INSERT INTO payments
                           (student_id, student_fee_id, enrollment_no, receipt_no, razorpay_order_id,
                            amount, currency, payment_method, payment_status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'razorpay', 'created')");
    $stmt->execute([
        $_SESSION['user_id'],
        $fee_id,
        $fee['enrollment_no'],
        $temp_receipt,
        $razorpay_order['id'],
        $amount,
        RAZORPAY_CURRENCY
    ]);

    echo json_encode([
        'success' => true,
        'order' => $razorpay_order,
        'message' => 'Order created successfully'
    ]);

} catch (PDOException $e) {
    error_log("Create order DB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Create order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>