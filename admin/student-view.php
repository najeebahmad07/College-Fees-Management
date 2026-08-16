<?php
// admin/student-view.php

$page_title = "Student Details";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    set_message('error', 'Invalid student ID');
    redirect('admin/students.php');
}

// Get student details
$student = get_student($student_id);

if (!$student) {
    set_message('error', 'Student not found');
    redirect('admin/students.php');
}

// Get fee summary
$stmt = $conn->prepare("SELECT
                           COALESCE(SUM(total_fee), 0) as total_fee,
                           COALESCE(SUM(paid_amount), 0) as paid_amount,
                           COALESCE(SUM(pending_amount), 0) as pending_amount
                       FROM student_fees
                       WHERE student_id = ?");
$stmt->execute([$student_id]);
$fee_summary = $stmt->fetch();

// Get semester-wise fees
$stmt = $conn->prepare("SELECT sf.*, s.semester_name
                       FROM student_fees sf
                       LEFT JOIN semesters s ON sf.semester_id = s.id
                       WHERE sf.student_id = ?
                       ORDER BY s.semester_number");
$stmt->execute([$student_id]);
$semester_fees = $stmt->fetchAll();

// Get payment history
$stmt = $conn->prepare("SELECT p.*, s.semester_name
                       FROM payments p
                       LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
                       LEFT JOIN semesters s ON sf.semester_id = s.id
                       WHERE p.student_id = ? AND p.payment_status = 'success'
                       ORDER BY p.payment_date DESC");
$stmt->execute([$student_id]);
$payments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="mb-4">
    <a href="students.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Students
    </a>
    <a href="student-edit.php?id=<?php echo $student_id; ?>" class="btn btn-primary">
        <i class="bi bi-pencil me-2"></i>Edit Student
    </a>
</div>

<div class="row">
    <!-- Student Information Card -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 40px; color: white; font-weight: 600;">
                        <?php echo strtoupper(substr($student['student_name'], 0, 2)); ?>
                    </div>
                </div>
                <h5><?php echo $student['student_name']; ?></h5>
                <p class="text-muted mb-0"><?php echo $student['enrollment_no']; ?></p>
                <p class="text-muted"><?php echo $student['course_name']; ?></p>
                <span class="badge <?php echo $student['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                    <?php echo ucfirst($student['status']); ?>
                </span>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Personal Information</h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Father's Name:</strong><br><?php echo $student['father_name']; ?></p>
                <p class="mb-2"><strong>Mother's Name:</strong><br><?php echo $student['mother_name']; ?></p>
                <p class="mb-2"><strong>Date of Birth:</strong><br><?php echo format_date($student['dob']); ?></p>
                <p class="mb-2"><strong>Gender:</strong><br><?php echo $student['gender']; ?></p>
                <p class="mb-2"><strong>Mobile:</strong><br><?php echo $student['mobile']; ?></p>
                <p class="mb-2"><strong>Email:</strong><br><?php echo $student['email'] ?: 'N/A'; ?></p>
                <p class="mb-0"><strong>Address:</strong><br><?php echo $student['address'] . ', ' . $student['city'] . ', ' . $student['state'] . ' - ' . $student['pincode']; ?></p>
            </div>
        </div>
    </div>

    <!-- Fee Details -->
    <div class="col-md-8">
        <!-- Fee Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Fees</h6>
                        <h4><?php echo format_currency($fee_summary['total_fee']); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Paid Amount</h6>
                        <h4 class="text-success"><?php echo format_currency($fee_summary['paid_amount']); ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending Amount</h6>
                        <h4 class="text-danger"><?php echo format_currency($fee_summary['pending_amount']); ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">Academic Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Department:</strong><br><?php echo $student['department_name']; ?></p>
                        <p class="mb-2"><strong>Course:</strong><br><?php echo $student['course_name']; ?></p>
                        <p class="mb-0"><strong>Course Code:</strong><br><?php echo $student['course_code']; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-2"><strong>Admission Year:</strong><br><?php echo $student['admission_year']; ?></p>
                        <p class="mb-2"><strong>Admission Date:</strong><br><?php echo format_date($student['admission_date']); ?></p>
                        <p class="mb-2"><strong>Current Semester:</strong><br>Semester <?php echo $student['current_semester']; ?></p>
                        <p class="mb-0"><strong>Academic Session:</strong><br><?php echo $student['academic_session']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Semester-wise Fees -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h6 class="mb-0">Semester-wise Fee Status</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Total Fee</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semester_fees as $fee): ?>
                                <tr>
                                    <td><?php echo $fee['semester_name']; ?></td>
                                    <td><?php echo format_currency($fee['total_fee']); ?></td>
                                    <td class="text-success"><?php echo format_currency($fee['paid_amount']); ?></td>
                                    <td class="text-danger"><?php echo format_currency($fee['pending_amount']); ?></td>
                                    <td>
                                        <?php
                                        $statusBadge = '';
                                        switch($fee['status']) {
                                            case 'paid':
                                                $statusBadge = '<span class="badge bg-success">Paid</span>';
                                                break;
                                            case 'partial':
                                                $statusBadge = '<span class="badge bg-info">Partial</span>';
                                                break;
                                            case 'pending':
                                                $statusBadge = '<span class="badge bg-warning">Pending</span>';
                                                break;
                                            case 'overdue':
                                                $statusBadge = '<span class="badge bg-danger">Overdue</span>';
                                                break;
                                        }
                                        echo $statusBadge;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card">
            <div class="card-header bg-white">
                <h6 class="mb-0">Payment History</h6>
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Semester</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Method</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><strong><?php echo $payment['receipt_no']; ?></strong></td>
                                        <td><?php echo $payment['semester_name'] ?? 'N/A'; ?></td>
                                        <td><strong><?php echo format_currency($payment['amount']); ?></strong></td>
                                        <td><?php echo format_date($payment['payment_date'], 'd-m-Y H:i'); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($payment['payment_method']); ?></span></td>
                                        <td>
                                            <a href="view-receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted mb-0">No payment history available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>