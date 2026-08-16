<?php
// admin/students.php

$page_title = "Students";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Search and filter
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : '';

// Build query
$sql = "SELECT s.*, d.department_name, c.course_name, c.course_code
        FROM students s
        JOIN departments d ON s.department_id = d.id
        JOIN courses c ON s.course_id = c.id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (s.enrollment_no LIKE ? OR s.student_name LIKE ? OR s.mobile LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($course_filter > 0) {
    $sql .= " AND s.course_id = ?";
    $params[] = $course_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY s.id DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get courses for filter
$courses_stmt = $conn->query("SELECT * FROM courses WHERE status = 'active' ORDER BY course_name");
$courses = $courses_stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Students Management</h2>
    <a href="student-add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Add Student
    </a>
</div>

<?php display_message(); ?>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Search by Enrollment No, Name, Mobile" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="course">
                    <option value="">All Courses</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                            <?php echo $course['course_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="passed" <?php echo $status_filter === 'passed' ? 'selected' : ''; ?>>Passed</option>
                    <option value="left" <?php echo $status_filter === 'left' ? 'selected' : ''; ?>>Left</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Students Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Enrollment No</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><strong><?php echo $student['enrollment_no']; ?></strong></td>
                                <td><?php echo $student['student_name']; ?></td>
                                <td><?php echo $student['course_name']; ?></td>
                                <td><?php echo $student['department_name']; ?></td>
                                <td>Semester <?php echo $student['current_semester']; ?></td>
                                <td><?php echo $student['mobile']; ?></td>
                                <td>
                                    <?php
                                    $statusClass = $student['status'] === 'active' ? 'bg-success' :
                                                  ($student['status'] === 'passed' ? 'bg-info' : 'bg-danger');
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($student['status']); ?></span>
                                </td>
                                <td>
                                    <a href="student-view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="student-edit.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No students found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>