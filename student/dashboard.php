<?php
// student/dashboard.php

$page_title = "Dashboard";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

/*
|--------------------------------------------------------------------------
| Get Student Details
|--------------------------------------------------------------------------
*/

$student = get_student($_SESSION['user_id']);

/*
|--------------------------------------------------------------------------
| Fee Summary Statistics
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(total_fee), 0) AS total_fee,
        COALESCE(SUM(paid_amount), 0) AS paid_amount,
        COALESCE(SUM(pending_amount), 0) AS pending_amount,
        COUNT(*) AS total_semesters
    FROM student_fees
    WHERE student_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$fee_summary = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Current Semester Fee
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT sf.*, s.semester_name
    FROM student_fees sf
    LEFT JOIN semesters s ON sf.semester_id = s.id
    WHERE sf.student_id = ? AND s.semester_number = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $student['current_semester']]);
$current_semester_fee = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Recent Payments
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT p.*, s.semester_name
    FROM payments p
    LEFT JOIN student_fees sf ON p.student_fee_id = sf.id
    LEFT JOIN semesters s ON sf.semester_id = s.id
    WHERE p.student_id = ? AND p.payment_status = 'success'
    ORDER BY p.payment_date DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_payments = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| All Semester-wise Fees
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT sf.*, s.semester_name, s.semester_number
    FROM student_fees sf
    LEFT JOIN semesters s ON sf.semester_id = s.id
    WHERE sf.student_id = ?
    ORDER BY s.semester_number ASC
");
$stmt->execute([$_SESSION['user_id']]);
$all_semester_fees = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Payment History for Chart (Last 6 Months)
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        DATE_FORMAT(payment_date, '%b %Y') AS month_label,
        MONTH(payment_date) AS payment_month,
        YEAR(payment_date) AS payment_year,
        SUM(amount) AS total_amount
    FROM payments
    WHERE student_id = ?
    AND payment_status = 'success'
    AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(payment_date), MONTH(payment_date)
    ORDER BY payment_year ASC, payment_month ASC
");
$stmt->execute([$_SESSION['user_id']]);
$monthly_payments_raw = $stmt->fetchAll();

$payment_months = [];
$payment_amounts = [];

foreach ($monthly_payments_raw as $row) {
    $payment_months[] = $row['month_label'];
    $payment_amounts[] = (float)$row['total_amount'];
}

/*
|--------------------------------------------------------------------------
| Semester-wise Fee Status for Chart
|--------------------------------------------------------------------------
*/

$semester_labels = [];
$semester_total = [];
$semester_paid = [];
$semester_pending = [];

foreach ($all_semester_fees as $fee) {
    $semester_labels[] = $fee['semester_name'] ?? 'Sem ' . $fee['semester_number'];
    $semester_total[] = (float)$fee['total_fee'];
    $semester_paid[] = (float)$fee['paid_amount'];
    $semester_pending[] = (float)$fee['pending_amount'];
}

/*
|--------------------------------------------------------------------------
| Fee Status Distribution
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        status,
        COUNT(*) AS count
    FROM student_fees
    WHERE student_id = ?
    GROUP BY status
");
$stmt->execute([$_SESSION['user_id']]);
$fee_status_raw = $stmt->fetchAll();

$fee_status_labels = [];
$fee_status_values = [];

foreach ($fee_status_raw as $row) {
    $fee_status_labels[] = ucfirst($row['status']);
    $fee_status_values[] = (int)$row['count'];
}

/*
|--------------------------------------------------------------------------
| Payment Progress Percentage
|--------------------------------------------------------------------------
*/

$payment_percentage = 0;
if ($fee_summary['total_fee'] > 0) {
    $payment_percentage = round(($fee_summary['paid_amount'] / $fee_summary['total_fee']) * 100, 1);
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<style>
    .student-welcome-card {
        background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        border-radius: 20px;
        padding: 36px;
        color: white;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 12px 35px rgba(79, 70, 229, 0.25);
    }

    .student-welcome-card::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 50%;
        top: -150px;
        right: -100px;
    }

    .student-welcome-card h2 {
        font-size: 32px;
        font-weight: 800;
        margin-bottom: 10px;
        position: relative;
        z-index: 2;
    }

    .student-welcome-card p {
        font-size: 15px;
        opacity: 0.95;
        margin-bottom: 28px;
        position: relative;
        z-index: 2;
    }

    .student-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 18px;
        position: relative;
        z-index: 2;
    }

    .student-info-item {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 18px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .student-info-item label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        opacity: 0.9;
        margin-bottom: 6px;
        display: block;
        font-weight: 700;
    }

    .student-info-item strong {
        font-size: 16px;
        font-weight: 700;
    }

    .stat-card-modern {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: all 0.25s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .stat-card-modern:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        border-color: #4F46E5;
    }

    .stat-card-modern::after {
        content: "";
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        right: -40px;
        top: -40px;
        opacity: 0.08;
    }

    .stat-card-modern.primary::after {
        background: #4F46E5;
    }

    .stat-card-modern.success::after {
        background: #10B981;
    }

    .stat-card-modern.danger::after {
        background: #EF4444;
    }

    .stat-card-modern.warning::after {
        background: #F59E0B;
    }

    .stat-header-modern {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
    }

    .stat-info-modern h6 {
        font-size: 12px;
        font-weight: 700;
        color: #6B7280;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-bottom: 8px;
    }

    .stat-info-modern h3 {
        font-size: 28px;
        font-weight: 800;
        margin: 0;
        color: #111827;
    }

    .stat-icon-modern {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
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

    .stat-footer-modern {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #E5E7EB;
        font-size: 13px;
        color: #6B7280;
    }

    .modern-card-student {
        border: 1px solid #E5E7EB;
        border-radius: 18px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        background: #ffffff;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .modern-card-student .card-header {
        background: #ffffff;
        border-bottom: 1px solid #E5E7EB;
        padding: 20px 24px;
    }

    .modern-card-student .card-header h5 {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modern-card-student .card-body {
        padding: 24px;
    }

    .progress-modern {
        height: 14px;
        border-radius: 999px;
        background: #F3F4F6;
        overflow: hidden;
        margin-bottom: 12px;
    }

    .progress-bar-modern {
        height: 100%;
        background: linear-gradient(90deg, #10B981, #34D399);
        border-radius: 999px;
        transition: width 0.6s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: white;
    }

    .table-modern thead th {
        background: #F9FAFB;
        font-size: 12px;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        border-bottom: 1px solid #E5E7EB;
        padding: 14px;
    }

    .table-modern tbody td {
        padding: 14px;
        vertical-align: middle;
        border-bottom: 1px solid #F3F4F6;
        color: #374151;
        font-size: 14px;
    }

    .table-modern tbody tr:hover {
        background: #F9FAFB;
    }

    .semester-fee-row {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 14px;
        transition: all 0.3s ease;
    }

    .semester-fee-row:hover {
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        border-color: #4F46E5;
    }

    .badge-modern {
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    canvas {
        max-height: 340px;
    }

    @media (max-width: 768px) {
        .student-welcome-card h2 {
            font-size: 24px;
        }

        .student-info-grid {
            grid-template-columns: 1fr;
        }

        .stat-info-modern h3 {
            font-size: 24px;
        }

        canvas {
            max-height: 260px;
        }
    }
</style>

<!-- Welcome Card -->
<div class="student-welcome-card">
    <h2>Welcome back, <?php echo htmlspecialchars(explode(' ', $student['student_name'])[0]); ?>! 👋</h2>
    <p>Here's your fee overview and payment summary</p>

    <div class="student-info-grid">
        <div class="student-info-item">
            <label>Enrollment Number</label>
            <strong><?php echo htmlspecialchars($student['enrollment_no']); ?></strong>
        </div>
        <div class="student-info-item">
            <label>Course</label>
            <strong><?php echo htmlspecialchars($student['course_name']); ?></strong>
        </div>
        <div class="student-info-item">
            <label>Department</label>
            <strong><?php echo htmlspecialchars($student['department_name']); ?></strong>
        </div>
        <div class="student-info-item">
            <label>Current Semester</label>
            <strong>Semester <?php echo $student['current_semester']; ?></strong>
        </div>
        <div class="student-info-item">
            <label>Academic Session</label>
            <strong><?php echo htmlspecialchars($student['academic_session']); ?></strong>
        </div>
        <div class="student-info-item">
            <label>Payment Progress</label>
            <strong><?php echo $payment_percentage; ?>% Complete</strong>
        </div>
    </div>
</div>

<?php display_message(); ?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">

    <div class="col-xl-3 col-md-6">
        <div class="stat-card-modern primary">
            <div class="stat-header-modern">
                <div class="stat-info-modern">
                    <h6>Total Fees</h6>
                    <h3><?php echo format_currency($fee_summary['total_fee']); ?></h3>
                </div>
                <div class="stat-icon-modern icon-primary">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <div class="stat-footer-modern">
                <i class="bi bi-info-circle me-1"></i>
                All semesters combined
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card-modern success">
            <div class="stat-header-modern">
                <div class="stat-info-modern">
                    <h6>Paid Amount</h6>
                    <h3 class="text-success"><?php echo format_currency($fee_summary['paid_amount']); ?></h3>
                </div>
                <div class="stat-icon-modern icon-success">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
            <div class="stat-footer-modern">
                <i class="bi bi-check me-1"></i>
                Successfully paid
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card-modern danger">
            <div class="stat-header-modern">
                <div class="stat-info-modern">
                    <h6>Pending Amount</h6>
                    <h3 class="text-danger"><?php echo format_currency($fee_summary['pending_amount']); ?></h3>
                </div>
                <div class="stat-icon-modern icon-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-footer-modern">
                <i class="bi bi-clock me-1"></i>
                Payment due
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="stat-card-modern warning">
            <div class="stat-header-modern">
                <div class="stat-info-modern">
                    <h6>Current Semester</h6>
                    <h3><?php echo format_currency($current_semester_fee['total_fee'] ?? 0); ?></h3>
                </div>
                <div class="stat-icon-modern icon-warning">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
            <div class="stat-footer-modern">
                <i class="bi bi-mortarboard me-1"></i>
                Semester <?php echo $student['current_semester']; ?> fees
            </div>
        </div>
    </div>

</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">

    <!-- Payment History Chart -->
    <div class="col-xl-8">
        <div class="modern-card-student">
            <div class="card-header">
                <h5>
                    <i class="bi bi-graph-up-arrow text-primary"></i>
                    Payment History (Last 6 Months)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="paymentHistoryChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Fee Status Distribution -->
    <div class="col-xl-4">
        <div class="modern-card-student">
            <div class="card-header">
                <h5>
                    <i class="bi bi-pie-chart text-success"></i>
                    Fee Status
                </h5>
            </div>
            <div class="card-body">
                <canvas id="feeStatusChart" style="max-height: 300px;"></canvas>
            </div>
        </div>
    </div>

</div>

<!-- Semester-wise Chart -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="modern-card-student">
            <div class="card-header">
                <h5>
                    <i class="bi bi-bar-chart-fill text-info"></i>
                    Semester-wise Fee Breakdown
                </h5>
            </div>
            <div class="card-body">
                <canvas id="semesterFeeChart" style="max-height: 320px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Current Semester Progress -->
<?php if ($current_semester_fee): ?>
<div class="modern-card-student">
    <div class="card-header">
        <h5>
            <i class="bi bi-info-circle-fill text-primary"></i>
            Current Semester Fee Status
        </h5>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="mb-3 fw-bold">
                    <?php echo htmlspecialchars($current_semester_fee['semester_name']); ?> -
                    <?php echo htmlspecialchars($student['academic_session']); ?>
                </h6>

                <?php
                $current_percentage = 0;
                if ($current_semester_fee['total_fee'] > 0) {
                    $current_percentage = round(($current_semester_fee['paid_amount'] / $current_semester_fee['total_fee']) * 100, 1);
                }
                ?>

                <div class="progress-modern">
                    <div class="progress-bar-modern" style="width: <?php echo $current_percentage; ?>%">
                        <?php echo $current_percentage; ?>%
                    </div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-4">
                        <small class="text-muted d-block mb-1 fw-semibold">Total Fee</small>
                        <h5 class="mb-0"><?php echo format_currency($current_semester_fee['total_fee']); ?></h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block mb-1 fw-semibold">Paid</small>
                        <h5 class="mb-0 text-success"><?php echo format_currency($current_semester_fee['paid_amount']); ?></h5>
                    </div>
                    <div class="col-4">
                        <small class="text-muted d-block mb-1 fw-semibold">Pending</small>
                        <h5 class="mb-0 text-danger"><?php echo format_currency($current_semester_fee['pending_amount']); ?></h5>
                    </div>
                </div>
            </div>

            <div class="col-md-4 text-center">
                <?php if ($current_semester_fee['pending_amount'] > 0): ?>
                    <a href="fees.php" class="btn btn-primary btn-lg w-100 mb-2">
                        <i class="bi bi-credit-card me-2"></i>Pay Now
                    </a>
                    <small class="text-muted d-block">
                        <i class="bi bi-shield-check me-1"></i>
                        Secure payment via Razorpay
                    </small>
                <?php else: ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Fully Paid!</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Payments -->
<div class="modern-card-student mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="bi bi-clock-history text-primary"></i>
            Recent Payments
        </h5>
        <a href="payment-history.php" class="btn btn-sm btn-outline-primary">
            View All
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($recent_payments)): ?>
            <div class="table-responsive">
                <table class="table table-modern mb-0">
                    <thead>
                        <tr>
                            <th>Receipt No</th>
                            <th>Semester</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></td>
                                <td><?php echo htmlspecialchars($payment['semester_name'] ?? 'N/A'); ?></td>
                                <td><strong class="text-success"><?php echo format_currency($payment['amount']); ?></strong></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-success badge-modern">Success</span>
                                </td>
                                <td>
                                    <a href="receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 64px; color: #E5E7EB;"></i>
                <p class="text-muted mt-3 mb-0">No payment history available</p>
                <a href="fees.php" class="btn btn-primary mt-3">
                    <i class="bi bi-cash me-2"></i>View Fees
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';
Chart.defaults.font.size = 12;

const colors = {
    primary: '#4F46E5',
    primaryLight: 'rgba(79, 70, 229, 0.1)',
    success: '#10B981',
    successLight: 'rgba(16, 185, 129, 0.1)',
    danger: '#EF4444',
    dangerLight: 'rgba(239, 68, 68, 0.1)',
    warning: '#F59E0B',
    warningLight: 'rgba(245, 158, 11, 0.1)',
    info: '#3B82F6',
    purple: '#8B5CF6',
    pink: '#EC4899'
};

function formatINR(value) {
    return '₹' + Number(value).toLocaleString('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

/*
|--------------------------------------------------------------------------
| Chart 1: Payment History (Line Chart)
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('paymentHistoryChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($payment_months); ?>,
        datasets: [{
            label: 'Payment Amount',
            data: <?php echo json_encode($payment_amounts); ?>,
            borderColor: colors.primary,
            backgroundColor: colors.primaryLight,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointBackgroundColor: colors.primary,
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
                        return 'Payment: ' + formatINR(context.parsed.y);
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
| Chart 2: Fee Status (Doughnut Chart)
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('feeStatusChart'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($fee_status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($fee_status_values); ?>,
            backgroundColor: [
                colors.warning,
                colors.info,
                colors.success,
                colors.danger
            ],
            borderWidth: 0,
            hoverOffset: 12
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    usePointStyle: true,
                    padding: 18,
                    font: {
                        size: 13
                    }
                }
            },
            tooltip: {
                backgroundColor: '#111827',
                padding: 12
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Chart 3: Semester-wise Fee (Grouped Bar Chart)
|--------------------------------------------------------------------------
*/
new Chart(document.getElementById('semesterFeeChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($semester_labels); ?>,
        datasets: [
            {
                label: 'Total Fee',
                data: <?php echo json_encode($semester_total); ?>,
                backgroundColor: colors.primary,
                borderRadius: 8,
                barThickness: 30
            },
            {
                label: 'Paid Amount',
                data: <?php echo json_encode($semester_paid); ?>,
                backgroundColor: colors.success,
                borderRadius: 8,
                barThickness: 30
            },
            {
                label: 'Pending Amount',
                data: <?php echo json_encode($semester_pending); ?>,
                backgroundColor: colors.danger,
                borderRadius: 8,
                barThickness: 30
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
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
                        return context.dataset.label + ': ' + formatINR(context.parsed.y);
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
</script>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>