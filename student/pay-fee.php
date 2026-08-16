<?php
// student/pay-fee.php

$page_title = "Pay Fee";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/razorpay.php';

$db = new Database();
$conn = $db->getConnection();

// Get fee details
$fee_id = isset($_GET['fee_id']) ? intval($_GET['fee_id']) : 0;

if ($fee_id <= 0) {
    set_message('error', 'Invalid fee ID');
    redirect('student/fees.php');
}

// Verify the fee belongs to current student
$stmt = $conn->prepare("SELECT sf.*, s.semester_name, st.student_name, st.enrollment_no, st.mobile, st.email
                       FROM student_fees sf
                       JOIN semesters s ON sf.semester_id = s.id
                       JOIN students st ON sf.student_id = st.id
                       WHERE sf.id = ? AND sf.student_id = ?");
$stmt->execute([$fee_id, $_SESSION['user_id']]);
$fee = $stmt->fetch();

if (!$fee) {
    set_message('error', 'Fee record not found');
    redirect('student/fees.php');
}

if ($fee['pending_amount'] <= 0) {
    set_message('info', 'This semester fee is already paid');
    redirect('student/fees.php');
}

// Check if Razorpay keys are configured
if (empty(RAZORPAY_KEY_ID) || empty(RAZORPAY_KEY_SECRET)) {
    set_message('error', 'Payment gateway not configured. Please contact administrator.');
    redirect('student/fees.php');
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<div class="mb-4">
    <a href="fees.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Fees
    </a>
</div>

<!-- Payment Status Messages -->
<div id="payment-messages"></div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment Details</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">Fee Information</h6>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Semester:</strong></td>
                        <td><?php echo $fee['semester_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Academic Session:</strong></td>
                        <td><?php echo $fee['academic_session']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Fee:</strong></td>
                        <td><?php echo format_currency($fee['total_fee']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Paid Amount:</strong></td>
                        <td class="text-success"><?php echo format_currency($fee['paid_amount']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Pending Amount:</strong></td>
                        <td class="text-danger"><h5><?php echo format_currency($fee['pending_amount']); ?></h5></td>
                    </tr>
                </table>

                <hr>

                <h6 class="mb-3">Student Information</h6>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Enrollment No:</strong></td>
                        <td><?php echo $fee['enrollment_no']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Student Name:</strong></td>
                        <td><?php echo $fee['student_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Mobile:</strong></td>
                        <td><?php echo $fee['mobile']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo $fee['email']; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Amount to Pay</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" id="payment_amount"
                               value="<?php echo $fee['pending_amount']; ?>"
                               max="<?php echo $fee['pending_amount']; ?>"
                               min="1" step="0.01">
                    </div>
                    <small class="text-muted">Maximum: <?php echo format_currency($fee['pending_amount']); ?></small>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    You can pay partial amount if needed
                </div>

                <button type="button" id="rzp-button" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-lock-fill me-2"></i>Pay Securely
                </button>

                <div class="mt-3 text-center">
                    <img src="https://razorpay.com/assets/razorpay-glyph.svg" alt="Razorpay" style="height: 30px; opacity: 0.6;">
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-shield-check me-2"></i>Secure Payment</h6>
                <p class="small text-muted mb-0">Your payment is secured with Razorpay's industry-standard encryption.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const feeId = <?php echo $fee_id; ?>;
const maxAmount = <?php echo $fee['pending_amount']; ?>;
const razorpayKeyId = '<?php echo RAZORPAY_KEY_ID; ?>';

function showMessage(type, message) {
    const messagesDiv = document.getElementById('payment-messages');
    const alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-info');
    messagesDiv.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}-fill me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    messagesDiv.scrollIntoView({ behavior: 'smooth' });
}

function setButtonState(disabled, text) {
    const button = document.getElementById('rzp-button');
    button.disabled = disabled;
    button.innerHTML = disabled ?
        `<span class="spinner-border spinner-border-sm me-2"></span>${text}` :
        '<i class="bi bi-lock-fill me-2"></i>Pay Securely';
}

document.getElementById('rzp-button').onclick = function(e) {
    e.preventDefault();

    const amount = parseFloat(document.getElementById('payment_amount').value);

    // Validation
    if (isNaN(amount) || amount <= 0) {
        showMessage('error', 'Please enter a valid amount');
        return;
    }

    if (amount > maxAmount) {
        showMessage('error', `Amount cannot exceed ₹${maxAmount.toFixed(2)}`);
        return;
    }

    // Disable button
    setButtonState(true, 'Creating Order...');

    // Create Razorpay order
    fetch('../api/create-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            fee_id: feeId,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Create order response:', data);

        if (data.success) {
            initiateRazorpayPayment(data.order, amount);
        } else {
            showMessage('error', data.message || 'Failed to create order');
            setButtonState(false);
        }
    })
    .catch(error => {
        console.error('Create order error:', error);
        showMessage('error', 'Network error. Please check your connection and try again.');
        setButtonState(false);
    });
};

function initiateRazorpayPayment(order, amount) {
    const options = {
        "key": razorpayKeyId,
        "amount": order.amount,
        "currency": "INR",
        "name": "ASCT",
        "description": "<?php echo $fee['semester_name']; ?> Fee Payment",
        "order_id": order.id,
        "handler": function (response) {
            console.log('Payment response:', response);
            verifyPayment(response, amount);
        },
        "prefill": {
            "name": "<?php echo $fee['student_name']; ?>",
            "email": "<?php echo $fee['email']; ?>",
            "contact": "<?php echo $fee['mobile']; ?>"
        },
        "theme": {
            "color": "#4F46E5"
        },
        "modal": {
            "ondismiss": function() {
                console.log('Payment cancelled by user');
                setButtonState(false);
                showMessage('info', 'Payment cancelled');
            }
        }
    };

    try {
        const rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response){
            console.error('Payment failed:', response.error);
            showMessage('error', 'Payment failed: ' + response.error.description);
            setButtonState(false);
        });
        rzp.open();
    } catch (error) {
        console.error('Razorpay error:', error);
        showMessage('error', 'Failed to open payment gateway');
        setButtonState(false);
    }
}

function verifyPayment(response, amount) {
    setButtonState(true, 'Verifying Payment...');

    console.log('Verifying payment with data:', {
        razorpay_order_id: response.razorpay_order_id,
        razorpay_payment_id: response.razorpay_payment_id,
        fee_id: feeId,
        amount: amount
    });

    fetch('../api/verify-payment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            razorpay_order_id: response.razorpay_order_id,
            razorpay_payment_id: response.razorpay_payment_id,
            razorpay_signature: response.razorpay_signature,
            fee_id: feeId,
            amount: amount
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Verify payment response:', data);

        if (data.success) {
            showMessage('success', 'Payment successful! Redirecting...');
            setTimeout(() => {
                window.location.href = 'payment-success.php?payment_id=' + data.payment_id;
            }, 1500);
        } else {
            showMessage('error', data.message || 'Payment verification failed');
            setButtonState(false);
        }
    })
    .catch(error => {
        console.error('Verify payment error:', error);
        showMessage('error', 'Verification failed. Payment ID: ' + response.razorpay_payment_id + '. Please contact support.');
        setButtonState(false);
    });
}
</script>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>