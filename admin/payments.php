<?php
// admin/payments.php

$page_title = "Payments";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Filters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'success';

$sql = "SELECT p.*, s.student_name, s.enrollment_no, sem.semester_name
        FROM payments p
        JOIN students s ON p.student_id = s.id
        LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
        LEFT JOIN semesters sem ON sf.semester_id = sem.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (s.enrollment_no LIKE ? OR s.student_name LIKE ? OR p.receipt_no LIKE ? OR p.razorpay_payment_id LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status_filter)) {
    $sql .= " AND p.payment_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY p.id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<h2 class="mb-4">Payment Management</h2>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Search by Enrollment, Name, Receipt No, Payment ID" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="success" <?php echo $status_filter === 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Enrollment No</th>
                        <th>Student Name</th>
                        <th>Semester</th>
                        <th>Amount</th>
                        <th>Payment Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><strong><?php echo $payment['receipt_no']; ?></strong></td>
                                <td><?php echo $payment['enrollment_no']; ?></td>
                                <td><?php echo $payment['student_name']; ?></td>
                                <td><?php echo $payment['semester_name'] ?? 'N/A'; ?></td>
                                <td><strong><?php echo format_currency($payment['amount']); ?></strong></td>
                                <td><?php echo format_date($payment['payment_date'], 'd-m-Y H:i'); ?></td>
                                <td><span class="badge bg-info"><?php echo ucfirst($payment['payment_method']); ?></span></td>
                                <td>
                                    <?php
                                    $statusBadge = '';
                                    switch($payment['payment_status']) {
                                        case 'success':
                                            $statusBadge = '<span class="badge bg-success">Success</span>';
                                            break;
                                        case 'pending':
                                            $statusBadge = '<span class="badge bg-warning">Pending</span>';
                                            break;
                                        case 'failed':
                                            $statusBadge = '<span class="badge bg-danger">Failed</span>';
                                            break;
                                    }
                                    echo $statusBadge;
                                    ?>
                                </td>
                                <td>
                                    <?php if ($payment['payment_status'] === 'success'): ?>
                                        <a href="view-receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No payments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>