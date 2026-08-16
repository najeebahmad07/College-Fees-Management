
<?php
// admin/dashboard.php

$page_title = "Dashboard";

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

// Total Students
$stmt = $conn->query("SELECT COUNT(*) AS total FROM students");
$total_students = $stmt->fetch()['total'] ?? 0;

// Active Students
$stmt = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'active'");
$active_students = $stmt->fetch()['total'] ?? 0;

// Total Departments
$stmt = $conn->query("SELECT COUNT(*) AS total FROM departments");
$total_departments = $stmt->fetch()['total'] ?? 0;

// Total Courses
$stmt = $conn->query("SELECT COUNT(*) AS total FROM courses");
$total_courses = $stmt->fetch()['total'] ?? 0;

// Total Fee Collected
$stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_status = 'success'");
$total_collected = $stmt->fetch()['total'] ?? 0;

// Total Pending Fees
$stmt = $conn->query("SELECT COALESCE(SUM(pending_amount), 0) AS total FROM student_fees");
$total_pending = $stmt->fetch()['total'] ?? 0;

// Today's Collection
$stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE payment_status = 'success' AND DATE(payment_date) = CURDATE()");
$today_collection = $stmt->fetch()['total'] ?? 0;

// Current Month Collection
$stmt = $conn->query("
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM payments
    WHERE payment_status = 'success'
    AND MONTH(payment_date) = MONTH(CURDATE())
    AND YEAR(payment_date) = YEAR(CURDATE())
");
$month_collection = $stmt->fetch()['total'] ?? 0;

// Total Transactions
$stmt = $conn->query("SELECT COUNT(*) AS total FROM payments");
$total_transactions = $stmt->fetch()['total'] ?? 0;

/*
|--------------------------------------------------------------------------
| Recent Payments
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        p.id,
        p.receipt_no,
        p.enrollment_no,
        p.amount,
        p.payment_status,
        p.payment_method,
        p.payment_date,
        st.student_name,
        sem.semester_name
    FROM payments p
    JOIN students st ON p.student_id = st.id
    LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
    LEFT JOIN semesters sem ON sf.semester_id = sem.id
    ORDER BY p.id DESC
    LIMIT 10
");
$stmt->execute();
$recent_payments = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Pending Fees
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        st.id AS student_id,
        st.enrollment_no,
        st.student_name,
        c.course_name,
        sem.semester_name,
        sf.total_fee,
        sf.paid_amount,
        sf.pending_amount,
        sf.status
    FROM student_fees sf
    JOIN students st ON sf.student_id = st.id
    JOIN courses c ON st.course_id = c.id
    LEFT JOIN semesters sem ON sf.semester_id = sem.id
    WHERE sf.pending_amount > 0
    ORDER BY sf.pending_amount DESC
    LIMIT 10
");
$stmt->execute();
$pending_fees = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Chart 1: Last 7 Days Collection
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        DATE(payment_date) AS payment_day,
        COALESCE(SUM(amount), 0) AS total_amount
    FROM payments
    WHERE payment_status = 'success'
    AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(payment_date)
    ORDER BY payment_day ASC
");
$stmt->execute();
$daily_collection_raw = $stmt->fetchAll();

$last_7_days = [];
$daily_collection_map = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $last_7_days[$date] = 0;
}

foreach ($daily_collection_raw as $row) {
    $daily_collection_map[$row['payment_day']] = (float)$row['total_amount'];
}

foreach ($last_7_days as $date => $amount) {
    if (isset($daily_collection_map[$date])) {
        $last_7_days[$date] = $daily_collection_map[$date];
    }
}

$daily_labels = array_map(function ($date) {
    return date('d M', strtotime($date));
}, array_keys($last_7_days));

$daily_values = array_values($last_7_days);

/*
|--------------------------------------------------------------------------
| Chart 2: Paid vs Pending
|--------------------------------------------------------------------------
*/

$paid_pending_labels = ['Collected', 'Pending'];
$paid_pending_values = [
    (float)$total_collected,
    (float)$total_pending
];

/*
|--------------------------------------------------------------------------
| Chart 3: Department-wise Collection
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        d.department_name,
        COALESCE(SUM(p.amount), 0) AS collected_amount
    FROM departments d
    LEFT JOIN students st ON d.id = st.department_id
    LEFT JOIN payments p ON st.id = p.student_id AND p.payment_status = 'success'
    GROUP BY d.id
    ORDER BY collected_amount DESC
");
$stmt->execute();
$department_collection = $stmt->fetchAll();

$department_labels = [];
$department_values = [];

foreach ($department_collection as $row) {
    $department_labels[] = $row['department_name'];
    $department_values[] = (float)$row['collected_amount'];
}

/*
|--------------------------------------------------------------------------
| Chart 4: Payment Status Distribution
|--------------------------------------------------------------------------
*/

$stmt = $conn->query("
    SELECT
        payment_status,
        COUNT(*) AS total
    FROM payments
    GROUP BY payment_status
");
$payment_status_raw = $stmt->fetchAll();

$payment_status_labels = [];
$payment_status_values = [];

foreach ($payment_status_raw as $row) {
    $payment_status_labels[] = ucfirst($row['payment_status']);
    $payment_status_values[] = (int)$row['total'];
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .dashboard-title {
        font-size: 26px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 4px;
    }

    .dashboard-subtitle {
        color: #6B7280;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .dashboard-stat-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: all 0.25s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .dashboard-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .dashboard-stat-card::after {
        content: "";
        position: absolute;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        right: -35px;
        top: -35px;
        background: rgba(79, 70, 229, 0.07);
    }

    .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B7280;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 25px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 0;
    }

    .stat-icon-box {
        width: 54px;
        height: 54px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        z-index: 2;
    }

    .icon-primary {
        background: #EEF2FF;
        color: #4F46E5;
    }

    .icon-success {
        background: #D1FAE5;
        color: #10B981;
    }

    .icon-danger {
        background: #FEE2E2;
        color: #EF4444;
    }

    .icon-warning {
        background: #FEF3C7;
        color: #F59E0B;
    }

    .icon-info {
        background: #DBEAFE;
        color: #3B82F6;
    }

    .modern-card {
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        background: #ffffff;
        overflow: hidden;
    }

    .modern-card .card-header {
        background: #ffffff;
        border-bottom: 1px solid #E5E7EB;
        padding: 18px 22px;
    }

    .modern-card .card-header h5 {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        color: #111827;
    }

    .modern-card .card-body {
        padding: 22px;
    }

    .modern-table thead th {
        background: #F9FAFB;
        font-size: 12px;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        font-weight: 700;
        border-bottom: 1px solid #E5E7EB;
        padding: 14px;
    }

    .modern-table tbody td {
        padding: 14px;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F6;
        color: #374151;
        font-size: 14px;
    }

    .modern-table tbody tr:hover {
        background: #F9FAFB;
    }

    .status-badge {
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .chart-box {
        min-height: 320px;
    }

    canvas {
        max-height: 320px;
    }

    @media (max-width: 768px) {
        .dashboard-title {
            font-size: 22px;
        }

        .stat-value {
            font-size: 21px;
        }

        canvas {
            max-height: 260px;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
    <div>
        <h2 class="dashboard-title">Dashboard</h2>
        <p class="dashboard-subtitle">
            Welcome to ASCT Fees Management System — Bhopal
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="students.php" class="btn btn-primary">
            <i class="bi bi-people"></i>
            Students
        </a>
        <a href="payments.php" class="btn btn-outline-primary">
            <i class="bi bi-credit-card"></i>
            Payments
        </a>
    </div>
</div>

<?php display_message(); ?>

<!-- Stat Cards -->
<div class="row g-4 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Students</div>
                    <h3 class="stat-value"><?php echo $total_students; ?></h3>
                </div>
                <div class="stat-icon-box icon-primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Active Students</div>
                    <h3 class="stat-value"><?php echo $active_students; ?></h3>
                </div>
                <div class="stat-icon-box icon-success">
                    <i class="bi bi-person-check"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Departments</div>
                    <h3 class="stat-value"><?php echo $total_departments; ?></h3>
                </div>
                <div class="stat-icon-box icon-info">
                    <i class="bi bi-building"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Courses</div>
                    <h3 class="stat-value"><?php echo $total_courses; ?></h3>
                </div>
                <div class="stat-icon-box icon-warning">
                    <i class="bi bi-book"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Collected</div>
                    <h3 class="stat-value text-success">
                        <?php echo format_currency($total_collected); ?>
                    </h3>
                </div>
                <div class="stat-icon-box icon-success">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Pending Fees</div>
                    <h3 class="stat-value text-danger">
                        <?php echo format_currency($total_pending); ?>
                    </h3>
                </div>
                <div class="stat-icon-box icon-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Today Collection</div>
                    <h3 class="stat-value">
                        <?php echo format_currency($today_collection); ?>
                    </h3>
                </div>
                <div class="stat-icon-box icon-primary">
                    <i class="bi bi-calendar-day"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="dashboard-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Month Collection</div>
                    <h3 class="stat-value">
                        <?php echo format_currency($month_collection); ?>
                    </h3>
                </div>
                <div class="stat-icon-box icon-info">
                    <i class="bi bi-calendar-month"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Charts -->
<div class="row g-4 mb-4">

    <div class="col-xl-8">
        <div class="modern-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                    Last 7 Days Collection
                </h5>
            </div>
            <div class="card-body chart-box">
                <canvas id="dailyCollectionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="modern-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-pie-chart text-success me-2"></i>
                    Collected vs Pending
                </h5>
            </div>
            <div class="card-body chart-box">
                <canvas id="paidPendingChart"></canvas>
            </div>
        </div>
    </div>

</div>

<div class="row g-4 mb-4">

    <div class="col-xl-8">
        <div class="modern-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-bar-chart-fill text-info me-2"></i>
                    Department-wise Collection
                </h5>
            </div>
            <div class="card-body chart-box">
                <canvas id="departmentCollectionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="modern-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-diagram-3 text-warning me-2"></i>
                    Payment Status
                </h5>
            </div>
            <div class="card-body chart-box">
                <canvas id="paymentStatusChart"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Tables -->
<div class="row g-4">

    <!-- Recent Payments -->
    <div class="col-xl-7">
        <div class="modern-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="bi bi-clock-history text-primary me-2"></i>
                    Recent Payments
                </h5>
                <a href="payments.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>Receipt</th>
                                <th>Student</th>
                                <th>Semester</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_payments)): ?>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($payment['enrollment_no']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['semester_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <strong><?php echo format_currency($payment['amount']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $status = strtolower($payment['payment_status']);
                                            $badgeClass = match ($status) {
                                                'success' => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'created' => 'bg-secondary',
                                                'pending' => 'bg-warning',
                                                'refunded' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?> status-badge">
                                                <?php echo ucfirst($payment['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($payment['payment_status'] === 'success'): ?>
                                                <a href="view-receipt.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No recent payments found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Fees -->
    <div class="col-xl-5">
        <div class="modern-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>
                    <i class="bi bi-exclamation-circle text-danger me-2"></i>
                    Pending Fees
                </h5>
                <a href="student-fees.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table modern-table mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Pending</th>
                                <th>View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pending_fees)): ?>
                                <?php foreach ($pending_fees as $fee): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($fee['student_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($fee['enrollment_no']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($fee['course_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($fee['semester_name'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <strong class="text-danger">
                                                <?php echo format_currency($fee['pending_amount']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="student-view.php?id=<?php echo $fee['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        No pending fees found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';
Chart.defaults.font.size = 12;

const chartColors = {
    primary: '#4F46E5',
    primaryLight: 'rgba(79, 70, 229, 0.12)',
    success: '#10B981',
    successLight: 'rgba(16, 185, 129, 0.12)',
    danger: '#EF4444',
    dangerLight: 'rgba(239, 68, 68, 0.12)',
    warning: '#F59E0B',
    info: '#3B82F6',
    purple: '#8B5CF6',
    pink: '#EC4899',
    gray: '#9CA3AF'
};

function formatINR(value) {
    return '₹' + Number(value).toLocaleString('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

/*
|--------------------------------------------------------------------------
| Chart 1: Daily Collection Line Chart
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('dailyCollectionChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'Collection',
            data: <?php echo json_encode($daily_values); ?>,
            borderColor: chartColors.primary,
            backgroundColor: chartColors.primaryLight,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: chartColors.primary,
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return 'Collection: ' + formatINR(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6'
                },
                ticks: {
                    callback: function(value) {
                        return formatINR(value);
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Chart 2: Paid vs Pending Doughnut Chart
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('paidPendingChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($paid_pending_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($paid_pending_values); ?>,
            backgroundColor: [
                chartColors.success,
                chartColors.danger
            ],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '68%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 18
                }
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + formatINR(context.parsed);
                    }
                }
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Chart 3: Department-wise Collection Bar Chart
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('departmentCollectionChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($department_labels); ?>,
        datasets: [{
            label: 'Collection',
            data: <?php echo json_encode($department_values); ?>,
            backgroundColor: [
                chartColors.primary,
                chartColors.info,
                chartColors.success,
                chartColors.warning,
                chartColors.purple
            ],
            borderRadius: 10,
            barThickness: 45
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 12,
                callbacks: {
                    label: function(context) {
                        return 'Collection: ' + formatINR(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#F3F4F6'
                },
                ticks: {
                    callback: function(value) {
                        return formatINR(value);
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Chart 4: Payment Status Pie Chart
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('paymentStatusChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($payment_status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($payment_status_values); ?>,
            backgroundColor: [
                chartColors.success,
                chartColors.warning,
                chartColors.danger,
                chartColors.info,
                chartColors.gray
            ],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 16
                }
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 12
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
