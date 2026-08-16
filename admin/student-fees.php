<?php
// admin/student-fees.php

$page_title = "Student Fees Management";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

/*
|--------------------------------------------------------------------------
| Search and Filters
|--------------------------------------------------------------------------
*/

$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
$semester_filter = isset($_GET['semester']) ? intval($_GET['semester']) : 0;
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$academic_session = isset($_GET['session']) ? clean_input($_GET['session']) : '';

/*
|--------------------------------------------------------------------------
| Build Query with Filters
|--------------------------------------------------------------------------
*/

$sql = "SELECT
            sf.id,
            sf.total_fee,
            sf.paid_amount,
            sf.pending_amount,
            sf.status,
            sf.due_date,
            sf.academic_session,
            st.id AS student_id,
            st.enrollment_no,
            st.student_name,
            st.mobile,
            c.course_name,
            d.department_name,
            sem.semester_name,
            sem.semester_number
        FROM student_fees sf
        JOIN students st ON sf.student_id = st.id
        JOIN courses c ON st.course_id = c.id
        JOIN departments d ON st.department_id = d.id
        LEFT JOIN semesters sem ON sf.semester_id = sem.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (st.enrollment_no LIKE ? OR st.student_name LIKE ? OR st.mobile LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($course_filter > 0) {
    $sql .= " AND st.course_id = ?";
    $params[] = $course_filter;
}

if ($semester_filter > 0) {
    $sql .= " AND sf.semester_id = ?";
    $params[] = $semester_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND sf.status = ?";
    $params[] = $status_filter;
}

if (!empty($academic_session)) {
    $sql .= " AND sf.academic_session = ?";
    $params[] = $academic_session;
}

$sql .= " ORDER BY sf.id DESC LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$student_fees = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get Courses for Filter
|--------------------------------------------------------------------------
*/

$courses_stmt = $conn->query("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name");
$courses = $courses_stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get Semesters for Filter
|--------------------------------------------------------------------------
*/

$semesters_stmt = $conn->query("SELECT DISTINCT s.id, s.semester_name, s.semester_number
                                FROM semesters s
                                WHERE s.status = 'active'
                                ORDER BY s.semester_number");
$semesters = $semesters_stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Get Academic Sessions
|--------------------------------------------------------------------------
*/

$sessions_stmt = $conn->query("SELECT DISTINCT academic_session FROM student_fees ORDER BY academic_session DESC");
$sessions = $sessions_stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary Statistics
|--------------------------------------------------------------------------
*/

$stats_sql = "SELECT
                COUNT(DISTINCT sf.student_id) AS total_students,
                COALESCE(SUM(sf.total_fee), 0) AS total_fees,
                COALESCE(SUM(sf.paid_amount), 0) AS total_paid,
                COALESCE(SUM(sf.pending_amount), 0) AS total_pending
              FROM student_fees sf";

$stats_params = [];

if (!empty($search) || $course_filter > 0 || $semester_filter > 0 || !empty($status_filter) || !empty($academic_session)) {
    $stats_sql .= " JOIN students st ON sf.student_id = st.id WHERE 1=1";

    if (!empty($search)) {
        $stats_sql .= " AND (st.enrollment_no LIKE ? OR st.student_name LIKE ?)";
        $stats_params[] = "%{$search}%";
        $stats_params[] = "%{$search}%";
    }

    if ($course_filter > 0) {
        $stats_sql .= " AND st.course_id = ?";
        $stats_params[] = $course_filter;
    }

    if ($semester_filter > 0) {
        $stats_sql .= " AND sf.semester_id = ?";
        $stats_params[] = $semester_filter;
    }

    if (!empty($status_filter)) {
        $stats_sql .= " AND sf.status = ?";
        $stats_params[] = $status_filter;
    }

    if (!empty($academic_session)) {
        $stats_sql .= " AND sf.academic_session = ?";
        $stats_params[] = $academic_session;
    }
}

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute($stats_params);
$summary = $stats_stmt->fetch();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<style>
    .fees-stat-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        transition: all 0.25s ease;
    }

    .fees-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .fees-stat-card .stat-label {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #6B7280;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .fees-stat-card .stat-value {
        font-size: 26px;
        font-weight: 800;
        margin: 0;
    }

    .fees-stat-card .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .filter-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    .fees-table-card {
        background: #ffffff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .fees-table-card .card-header {
        background: #ffffff;
        border-bottom: 1px solid #E5E7EB;
        padding: 20px 24px;
    }

    .fees-table-card .card-header h5 {
        font-size: 17px;
        font-weight: 800;
        margin: 0;
        color: #111827;
    }

    .modern-table thead th {
        background: #F9FAFB;
        font-size: 11px;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        border-bottom: 2px solid #E5E7EB;
        padding: 14px 16px;
    }

    .modern-table tbody td {
        padding: 14px 16px;
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
        letter-spacing: 0.3px;
    }

    .progress-bar-fee {
        height: 8px;
        border-radius: 999px;
        background: #F3F4F6;
        overflow: hidden;
    }

    .progress-bar-fee-fill {
        height: 100%;
        background: linear-gradient(90deg, #10B981, #34D399);
        border-radius: 999px;
        transition: width 0.6s ease;
    }

    .student-info-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .student-avatar {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: linear-gradient(135deg, #4F46E5, #7C3AED);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 14px;
    }

    .student-details {
        flex: 1;
    }

    .student-name {
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
    }

    .student-enrollment {
        font-size: 12px;
        color: #6B7280;
    }

    @media (max-width: 768px) {
        .fees-stat-card .stat-value {
            font-size: 22px;
        }

        .filter-card {
            padding: 18px;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 style="font-size: 26px; font-weight: 800; color: #111827; margin-bottom: 4px;">
            Student Fees Management
        </h2>
        <p style="color: #6B7280; font-size: 14px; margin: 0;">
            Manage and track student fee payments
        </p>
    </div>
</div>

<?php display_message(); ?>

<!-- Summary Statistics -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="fees-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Students</div>
                    <h3 class="stat-value"><?php echo $summary['total_students']; ?></h3>
                </div>
                <div class="stat-icon" style="background: #EEF2FF; color: #4F46E5;">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="fees-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Fees</div>
                    <h3 class="stat-value"><?php echo format_currency($summary['total_fees']); ?></h3>
                </div>
                <div class="stat-icon" style="background: #DBEAFE; color: #3B82F6;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="fees-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Paid</div>
                    <h3 class="stat-value text-success"><?php echo format_currency($summary['total_paid']); ?></h3>
                </div>
                <div class="stat-icon" style="background: #D1FAE5; color: #10B981;">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="fees-stat-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="stat-label">Total Pending</div>
                    <h3 class="stat-value text-danger"><?php echo format_currency($summary['total_pending']); ?></h3>
                </div>
                <div class="stat-icon" style="background: #FEE2E2; color: #EF4444;">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filter-card">
    <form method="GET" class="row g-3">
        <div class="col-lg-3 col-md-4">
            <label class="form-label fw-semibold">Search</label>
            <input type="text" class="form-control" name="search"
                   placeholder="Enrollment, Name, Mobile"
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold">Course</label>
            <select class="form-select" name="course">
                <option value="">All Courses</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"
                            <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold">Semester</label>
            <select class="form-select" name="semester">
                <option value="">All Semesters</option>
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?php echo $sem['id']; ?>"
                            <?php echo $semester_filter == $sem['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sem['semester_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold">Status</label>
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="partial" <?php echo $status_filter === 'partial' ? 'selected' : ''; ?>>Partial</option>
                <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
            </select>
        </div>

        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold">Session</label>
            <select class="form-select" name="session">
                <option value="">All Sessions</option>
                <?php foreach ($sessions as $sess): ?>
                    <option value="<?php echo htmlspecialchars($sess['academic_session']); ?>"
                            <?php echo $academic_session === $sess['academic_session'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sess['academic_session']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-lg-1 col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-search"></i>
            </button>
        </div>
    </form>
</div>

<!-- Student Fees Table -->
<div class="fees-table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>
            <i class="bi bi-receipt text-primary me-2"></i>
            Student Fees Records
        </h5>
        <span class="badge bg-primary" style="font-size: 13px; padding: 8px 14px;">
            <?php echo count($student_fees); ?> Records
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table modern-table mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th>Total Fee</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($student_fees)): ?>
                        <?php foreach ($student_fees as $fee): ?>
                            <?php
                            $progress_percentage = 0;
                            if ($fee['total_fee'] > 0) {
                                $progress_percentage = round(($fee['paid_amount'] / $fee['total_fee']) * 100, 1);
                            }

                            $status_class = match($fee['status']) {
                                'paid' => 'bg-success',
                                'partial' => 'bg-info',
                                'pending' => 'bg-warning',
                                'overdue' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <tr>
                                <td>
                                    <div class="student-info-cell">
                                        <div class="student-avatar">
                                            <?php echo strtoupper(substr($fee['student_name'], 0, 2)); ?>
                                        </div>
                                        <div class="student-details">
                                            <div class="student-name">
                                                <?php echo htmlspecialchars($fee['student_name']); ?>
                                            </div>
                                            <div class="student-enrollment">
                                                <?php echo htmlspecialchars($fee['enrollment_no']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #111827;">
                                        <?php echo htmlspecialchars($fee['course_name']); ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($fee['department_name']); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary status-badge">
                                        <?php echo htmlspecialchars($fee['semester_name'] ?? 'Sem ' . $fee['semester_number']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($fee['academic_session']); ?></td>
                                <td>
                                    <strong><?php echo format_currency($fee['total_fee']); ?></strong>
                                </td>
                                <td>
                                    <strong class="text-success">
                                        <?php echo format_currency($fee['paid_amount']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <strong class="text-danger">
                                        <?php echo format_currency($fee['pending_amount']); ?>
                                    </strong>
                                </td>
                                <td style="width: 120px;">
                                    <div class="progress-bar-fee">
                                        <div class="progress-bar-fee-fill"
                                             style="width: <?php echo $progress_percentage; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                        <?php echo $progress_percentage; ?>% Paid
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                        <?php echo ucfirst($fee['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="student-view.php?id=<?php echo $fee['student_id']; ?>"
                                           class="btn btn-sm btn-info"
                                           title="View Student">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($fee['pending_amount'] > 0): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    title="Record Manual Payment"
                                                    onclick="openManualPaymentModal(<?php echo $fee['id']; ?>, '<?php echo htmlspecialchars($fee['student_name']); ?>', '<?php echo htmlspecialchars($fee['enrollment_no']); ?>', <?php echo $fee['pending_amount']; ?>)">
                                                <i class="bi bi-cash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 64px; color: #E5E7EB;"></i>
                                <p class="text-muted mt-3 mb-0">No fee records found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Payment Modal -->
<div class="modal fade" id="manualPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 16px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #E5E7EB;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-cash-stack text-primary me-2"></i>
                    Record Manual Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="record-manual-payment.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="student_fee_id" id="modal_fee_id">

                    <div class="alert alert-info" style="border-radius: 12px;">
                        <strong>Student:</strong> <span id="modal_student_name"></span><br>
                        <strong>Enrollment:</strong> <span id="modal_enrollment"></span><br>
                        <strong>Pending Amount:</strong> <span id="modal_pending_amount" class="text-danger fw-bold"></span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" class="form-control" name="amount"
                                   id="modal_amount" step="0.01" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Mode *</label>
                        <select class="form-select" name="payment_mode" required>
                            <option value="">Select Payment Mode</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference Number</label>
                        <input type="text" class="form-control" name="reference_no"
                               placeholder="Transaction ID, Cheque No, etc.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date *</label>
                        <input type="date" class="form-control" name="payment_date"
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="3"
                                  placeholder="Additional notes (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #E5E7EB;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openManualPaymentModal(feeId, studentName, enrollment, pendingAmount) {
    document.getElementById('modal_fee_id').value = feeId;
    document.getElementById('modal_student_name').textContent = studentName;
    document.getElementById('modal_enrollment').textContent = enrollment;
    document.getElementById('modal_pending_amount').textContent = '₹' + parseFloat(pendingAmount).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('modal_amount').max = pendingAmount;
    document.getElementById('modal_amount').value = pendingAmount;

    const modal = new bootstrap.Modal(document.getElementById('manualPaymentModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>