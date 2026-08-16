<?php
// admin/fee-structures.php

$page_title = "Fee Structures";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
        redirect('admin/fee-structures.php');
    }

    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $department_id = intval($_POST['department_id']);
        $course_id = intval($_POST['course_id']);
        $semester_id = intval($_POST['semester_id']);
        $academic_session = clean_input($_POST['academic_session']);
        $tuition_fee = floatval($_POST['tuition_fee']);
        $examination_fee = floatval($_POST['examination_fee']);
        $development_fee = floatval($_POST['development_fee']);
        $library_fee = floatval($_POST['library_fee']);
        $laboratory_fee = floatval($_POST['laboratory_fee']);
        $other_fee = floatval($_POST['other_fee']);
        $late_fee = floatval($_POST['late_fee']);
        $due_date = clean_input($_POST['due_date']);
        $status = clean_input($_POST['status']);

        try {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO fee_structures
                                       (department_id, course_id, semester_id, academic_session, tuition_fee,
                                        examination_fee, development_fee, library_fee, laboratory_fee, other_fee,
                                        late_fee, due_date, status)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $department_id, $course_id, $semester_id, $academic_session, $tuition_fee,
                    $examination_fee, $development_fee, $library_fee, $laboratory_fee, $other_fee,
                    $late_fee, $due_date, $status
                ]);
                set_message('success', 'Fee structure added successfully');
            } else {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE fee_structures SET
                                       department_id = ?, course_id = ?, semester_id = ?, academic_session = ?,
                                       tuition_fee = ?, examination_fee = ?, development_fee = ?, library_fee = ?,
                                       laboratory_fee = ?, other_fee = ?, late_fee = ?, due_date = ?, status = ?
                                       WHERE id = ?");
                $stmt->execute([
                    $department_id, $course_id, $semester_id, $academic_session, $tuition_fee,
                    $examination_fee, $development_fee, $library_fee, $laboratory_fee, $other_fee,
                    $late_fee, $due_date, $status, $id
                ]);
                set_message('success', 'Fee structure updated successfully');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                set_message('error', 'Fee structure already exists for this course, semester and session');
            } else {
                set_message('error', 'Error: ' . $e->getMessage());
            }
        }
        redirect('admin/fee-structures.php');
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $conn->prepare("DELETE FROM fee_structures WHERE id = ?");
            $stmt->execute([$id]);
            set_message('success', 'Fee structure deleted successfully');
        } catch (PDOException $e) {
            set_message('error', 'Cannot delete fee structure');
        }
        redirect('admin/fee-structures.php');
    }
}

// Fetch fee structures
$stmt = $conn->query("SELECT fs.*, d.department_name, c.course_name, s.semester_name
                     FROM fee_structures fs
                     JOIN departments d ON fs.department_id = d.id
                     JOIN courses c ON fs.course_id = c.id
                     JOIN semesters s ON fs.semester_id = s.id
                     ORDER BY fs.id DESC");
$fee_structures = $stmt->fetchAll();

// Get dropdowns data
$departments = $conn->query("SELECT * FROM departments WHERE status = 'active'")->fetchAll();
$courses = $conn->query("SELECT c.*, d.department_name FROM courses c JOIN departments d ON c.department_id = d.id WHERE c.status = 'active'")->fetchAll();
$semesters = $conn->query("SELECT s.*, c.course_name FROM semesters s JOIN courses c ON s.course_id = c.id WHERE s.status = 'active'")->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Fee Structures</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">
        <i class="bi bi-plus-circle me-2"></i>Add Fee Structure
    </button>
</div>

<?php display_message(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Semester</th>
                        <th>Session</th>
                        <th>Tuition</th>
                        <th>Examination</th>
                        <th>Development</th>
                        <th>Library</th>
                        <th>Lab</th>
                        <th>Other</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fee_structures as $fee): ?>
                        <tr>
                            <td><?php echo $fee['course_name']; ?></td>
                            <td><?php echo $fee['semester_name']; ?></td>
                            <td><?php echo $fee['academic_session']; ?></td>
                            <td><?php echo format_currency($fee['tuition_fee']); ?></td>
                            <td><?php echo format_currency($fee['examination_fee']); ?></td>
                            <td><?php echo format_currency($fee['development_fee']); ?></td>
                            <td><?php echo format_currency($fee['library_fee']); ?></td>
                            <td><?php echo format_currency($fee['laboratory_fee']); ?></td>
                            <td><?php echo format_currency($fee['other_fee']); ?></td>
                            <td><strong><?php echo format_currency($fee['total_fee']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $fee['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo ucfirst($fee['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info edit-btn"
                                        data-fee='<?php echo json_encode($fee); ?>'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $fee['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Fee Structure Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Fee Structure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Department *</label>
                            <select class="form-select" name="department_id" id="add_department" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Course *</label>
                            <select class="form-select" name="course_id" id="add_course" required>
                                <option value="">Select Course</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester *</label>
                            <select class="form-select" name="semester_id" id="add_semester" required>
                                <option value="">Select Semester</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Academic Session *</label>
                            <input type="text" class="form-control" name="academic_session" value="<?php echo get_setting('current_academic_session'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tuition Fee</label>
                            <input type="number" class="form-control" name="tuition_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Examination Fee</label>
                            <input type="number" class="form-control" name="examination_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Development Fee</label>
                            <input type="number" class="form-control" name="development_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Library Fee</label>
                            <input type="number" class="form-control" name="library_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Laboratory Fee</label>
                            <input type="number" class="form-control" name="laboratory_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Other Fee</label>
                            <input type="number" class="form-control" name="other_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Late Fee</label>
                            <input type="number" class="form-control" name="late_fee" step="0.01" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Fee Structure</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const allCourses = <?php echo json_encode($courses); ?>;
const allSemesters = <?php echo json_encode($semesters); ?>;

// Add modal - department change
document.getElementById('add_department').addEventListener('change', function() {
    const deptId = parseInt(this.value);
    const courseSelect = document.getElementById('add_course');
    courseSelect.innerHTML = '<option value="">Select Course</option>';
    document.getElementById('add_semester').innerHTML = '<option value="">Select Semester</option>';

    if (deptId) {
        const filteredCourses = allCourses.filter(c => c.department_id == deptId);
        filteredCourses.forEach(course => {
            courseSelect.innerHTML += `<option value="${course.id}">${course.course_name}</option>`;
        });
    }
});

// Add modal - course change
document.getElementById('add_course').addEventListener('change', function() {
    const courseId = parseInt(this.value);
    const semesterSelect = document.getElementById('add_semester');
    semesterSelect.innerHTML = '<option value="">Select Semester</option>';

    if (courseId) {
        const filteredSemesters = allSemesters.filter(s => s.course_id == courseId);
        filteredSemesters.forEach(sem => {
            semesterSelect.innerHTML += `<option value="${sem.id}">${sem.semester_name}</option>`;
        });
    }
});

// Delete button
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this fee structure?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${this.dataset.id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>