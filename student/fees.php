<?php
// student/fees.php

$page_title = "My Fees";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get student details
$student = get_student($_SESSION['user_id']);

// Get all semesters for the student's course
$semesters = get_student_semesters($_SESSION['user_id']);

// Get or create fee records for all semesters
$current_session = $student['academic_session'];
$semester_fees = [];

foreach ($semesters as $semester) {
    $fee = get_or_create_student_fee($_SESSION['user_id'], $semester['id'], $current_session);
    if ($fee) {
        $fee['semester_name'] = $semester['semester_name'];
        $fee['semester_number'] = $semester['semester_number'];
        $semester_fees[] = $fee;
    }
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<h2 class="mb-4">Semester-wise Fee Details</h2>

<?php display_message(); ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Student Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Enrollment No:</strong><br>
                <?php echo $student['enrollment_no']; ?>
            </div>
            <div class="col-md-3">
                <strong>Student Name:</strong><br>
                <?php echo $student['student_name']; ?>
            </div>
            <div class="col-md-3">
                <strong>Course:</strong><br>
                <?php echo $student['course_name']; ?>
            </div>
            <div class="col-md-3">
                <strong>Academic Session:</strong><br>
                <?php echo $student['academic_session']; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Semester Fees</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Semester</th>
                        <th>Total Fee</th>
                        <th>Paid Amount</th>
                        <th>Pending Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($semester_fees) > 0): ?>
                        <?php foreach ($semester_fees as $fee): ?>
                            <tr>
                                <td><strong><?php echo $fee['semester_name']; ?></strong></td>
                                <td><?php echo format_currency($fee['total_fee']); ?></td>
                                <td class="text-success"><strong><?php echo format_currency($fee['paid_amount']); ?></strong></td>
                                <td class="text-danger"><strong><?php echo format_currency($fee['pending_amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $statusBadge = '';
                                    switch($fee['status']) {
                                        case 'paid':
                                            $statusBadge = '<span class="badge bg-success badge-status">Paid</span>';
                                            break;
                                        case 'partial':
                                            $statusBadge = '<span class="badge bg-info badge-status">Partial</span>';
                                            break;
                                        case 'pending':
                                            $statusBadge = '<span class="badge bg-warning badge-status">Pending</span>';
                                            break;
                                        case 'overdue':
                                            $statusBadge = '<span class="badge bg-danger badge-status">Overdue</span>';
                                            break;
                                    }
                                    echo $statusBadge;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($fee['pending_amount'] > 0): ?>
                                        <a href="pay-fee.php?fee_id=<?php echo $fee['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-credit-card me-1"></i>Pay Now
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success"><i class="bi bi-check-circle-fill"></i> Paid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No fee records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>