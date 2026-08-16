<?php
// admin/reports.php

$page_title = "Reports";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Date filters
$from_date = isset($_GET['from_date']) ? clean_input($_GET['from_date']) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? clean_input($_GET['to_date']) : date('Y-m-d');
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;

// Collection Report
$stmt = $conn->prepare("SELECT
                           DATE(payment_date) as payment_date,
                           COUNT(*) as transaction_count,
                           SUM(amount) as total_amount
                       FROM payments
                       WHERE payment_status = 'success'
                       AND DATE(payment_date) BETWEEN ? AND ?
                       GROUP BY DATE(payment_date)
                       ORDER BY payment_date DESC");
$stmt->execute([$from_date, $to_date]);
$daily_collections = $stmt->fetchAll();

// Course-wise collection
$stmt = $conn->prepare("SELECT
                           c.course_name,
                           COUNT(DISTINCT p.student_id) as student_count,
                           COUNT(p.id) as transaction_count,
                           SUM(p.amount) as total_collected
                       FROM payments p
                       JOIN students s ON p.student_id = s.id
                       JOIN courses c ON s.course_id = c.id
                       WHERE p.payment_status = 'success'
                       AND DATE(p.payment_date) BETWEEN ? AND ?
                       GROUP BY c.id
                       ORDER BY total_collected DESC");
$stmt->execute([$from_date, $to_date]);
$course_collections = $stmt->fetchAll();

// Pending fees by course
$stmt = $conn->query("SELECT
                         c.course_name,
                         COUNT(DISTINCT sf.student_id) as student_count,
                         SUM(sf.total_fee) as total_fee,
                         SUM(sf.paid_amount) as paid_amount,
                         SUM(sf.pending_amount) as pending_amount
                     FROM student_fees sf
                     JOIN students s ON sf.student_id = s.id
                     JOIN courses c ON s.course_id = c.id
                     WHERE sf.pending_amount > 0
                     GROUP BY c.id
                     ORDER BY pending_amount DESC");
$pending_by_course = $stmt->fetchAll();

// Get courses for filter
$courses = $conn->query("SELECT * FROM courses WHERE status = 'active'")->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<h2 class="mb-4">Reports & Analytics</h2>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" class="form-control" name="from_date" value="<?php echo $from_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" class="form-control" name="to_date" value="<?php echo $to_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Course</label>
                <select class="form-select" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-2"></i>Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Daily Collection Report -->
<div class="card mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Daily Collection Report</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_transactions = 0;
                    $total_amount = 0;
                    foreach ($daily_collections as $collection):
                        $total_transactions += $collection['transaction_count'];
                        $total_amount += $collection['total_amount'];
                    ?>
                        <tr>
                            <td><?php echo format_date($collection['payment_date']); ?></td>
                            <td><?php echo $collection['transaction_count']; ?></td>
                            <td><strong><?php echo format_currency($collection['total_amount']); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="table-primary">
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo $total_transactions; ?></strong></td>
                        <td><strong><?php echo format_currency($total_amount); ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Course-wise Collection -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Course-wise Collection</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Students</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_collections as $collection): ?>
                                <tr>
                                    <td><?php echo $collection['course_name']; ?></td>
                                    <td><?php echo $collection['student_count']; ?></td>
                                    <td><strong><?php echo format_currency($collection['total_collected']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Pending Fees by Course</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Students</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_by_course as $pending): ?>
                                <tr>
                                    <td><?php echo $pending['course_name']; ?></td>
                                    <td><?php echo $pending['student_count']; ?></td>
                                    <td><strong class="text-danger"><?php echo format_currency($pending['pending_amount']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>