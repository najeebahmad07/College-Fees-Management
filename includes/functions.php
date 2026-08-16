<?php
// includes/functions.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

// Generate unique receipt number
function generate_receipt_number() {
    $db = new Database();
    $conn = $db->getConnection();

    $prefix = get_setting('receipt_prefix', 'ASCT');
    $year = date('Y');

    // Get last receipt number for current year
    $stmt = $conn->prepare("SELECT receipt_no FROM payments WHERE receipt_no LIKE ? ORDER BY id DESC LIMIT 1");
    $pattern = $prefix . '/' . $year . '/%';
    $stmt->execute([$pattern]);
    $result = $stmt->fetch();

    if ($result) {
        // Extract number and increment
        $parts = explode('/', $result['receipt_no']);
        $last_number = intval($parts[2]);
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }

    return $prefix . '/' . $year . '/' . str_pad($new_number, 6, '0', STR_PAD_LEFT);
}

// Get department by ID
function get_department($id) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get course by ID
function get_course($id) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT c.*, d.department_name
                           FROM courses c
                           JOIN departments d ON c.department_id = d.id
                           WHERE c.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get student by ID
function get_student($id) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT s.*, d.department_name, c.course_name, c.course_code
                           FROM students s
                           JOIN departments d ON s.department_id = d.id
                           JOIN courses c ON s.course_id = c.id
                           WHERE s.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get student by enrollment number
function get_student_by_enrollment($enrollment_no) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT s.*, d.department_name, c.course_name, c.course_code, c.total_semesters
                           FROM students s
                           JOIN departments d ON s.department_id = d.id
                           JOIN courses c ON s.course_id = c.id
                           WHERE s.enrollment_no = ?");
    $stmt->execute([$enrollment_no]);
    return $stmt->fetch();
}

// Get fee structure
function get_fee_structure($course_id, $semester_id, $academic_session) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT * FROM fee_structures
                           WHERE course_id = ? AND semester_id = ? AND academic_session = ? AND status = 'active'");
    $stmt->execute([$course_id, $semester_id, $academic_session]);
    return $stmt->fetch();
}

// Get or create student fee record
function get_or_create_student_fee($student_id, $semester_id, $academic_session) {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if record exists
    $stmt = $conn->prepare("SELECT * FROM student_fees
                           WHERE student_id = ? AND semester_id = ? AND academic_session = ?");
    $stmt->execute([$student_id, $semester_id, $academic_session]);
    $existing = $stmt->fetch();

    if ($existing) {
        return $existing;
    }

    // Get student details
    $student = get_student($student_id);

    // Get fee structure
    $fee_structure = get_fee_structure($student['course_id'], $semester_id, $academic_session);

    if (!$fee_structure) {
        return false;
    }

    // Create new record
    $stmt = $conn->prepare("INSERT INTO student_fees
                           (student_id, fee_structure_id, semester_id, academic_session, total_fee, due_date)
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $student_id,
        $fee_structure['id'],
        $semester_id,
        $academic_session,
        $fee_structure['total_fee'],
        $fee_structure['due_date']
    ]);

    $id = $conn->lastInsertId();

    // Fetch and return the new record
    $stmt = $conn->prepare("SELECT * FROM student_fees WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Get all semesters for a student's course
function get_student_semesters($student_id) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT s.* FROM semesters s
                           JOIN students st ON s.course_id = st.course_id
                           WHERE st.id = ? AND s.status = 'active'
                           ORDER BY s.semester_number");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

// Update payment status
function update_student_fee_status($student_fee_id) {
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT total_fee, paid_amount FROM student_fees WHERE id = ?");
    $stmt->execute([$student_fee_id]);
    $fee = $stmt->fetch();

    if ($fee) {
        if ($fee['paid_amount'] >= $fee['total_fee']) {
            $status = 'paid';
        } elseif ($fee['paid_amount'] > 0) {
            $status = 'partial';
        } else {
            $status = 'pending';
        }

        $stmt = $conn->prepare("UPDATE student_fees SET status = ? WHERE id = ?");
        $stmt->execute([$status, $student_fee_id]);
    }
}

// Calculate dashboard statistics
function get_dashboard_stats() {
    $db = new Database();
    $conn = $db->getConnection();

    $stats = [];

    // Total students
    $stmt = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
    $stats['total_students'] = $stmt->fetch()['count'];

    // Total departments
    $stmt = $conn->query("SELECT COUNT(*) as count FROM departments WHERE status = 'active'");
    $stats['total_departments'] = $stmt->fetch()['count'];

    // Total courses
    $stmt = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $stats['total_courses'] = $stmt->fetch()['count'];

    // Total fee collected
    $stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_status = 'success'");
    $stats['total_collected'] = $stmt->fetch()['total'];

    // Total pending fees
    $stmt = $conn->query("SELECT COALESCE(SUM(pending_amount), 0) as total FROM student_fees");
    $stats['total_pending'] = $stmt->fetch()['total'];

    // Today's collection
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments
                           WHERE payment_status = 'success' AND DATE(payment_date) = CURDATE()");
    $stmt->execute();
    $stats['today_collection'] = $stmt->fetch()['total'];

    // This month's collection
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments
                           WHERE payment_status = 'success'
                           AND MONTH(payment_date) = MONTH(CURDATE())
                           AND YEAR(payment_date) = YEAR(CURDATE())");
    $stmt->execute();
    $stats['month_collection'] = $stmt->fetch()['total'];

    // Total transactions
    $stmt = $conn->query("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'success'");
    $stats['total_transactions'] = $stmt->fetch()['count'];

    return $stats;
}
?>