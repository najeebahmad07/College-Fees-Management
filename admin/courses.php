<?php
// admin/courses.php

$page_title = "Courses";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
        redirect('admin/courses.php');
    }

    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $department_id = intval($_POST['department_id']);
        $course_name = clean_input($_POST['course_name']);
        $course_code = clean_input($_POST['course_code']);
        $duration = intval($_POST['duration']);
        $total_semesters = intval($_POST['total_semesters']);
        $status = clean_input($_POST['status']);

        if (empty($course_name) || empty($course_code) || $department_id <= 0) {
            set_message('error', 'All required fields must be filled');
        } else {
            try {
                $conn->beginTransaction();

                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO courses (department_id, course_name, course_code, duration, total_semesters, status)
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$department_id, $course_name, $course_code, $duration, $total_semesters, $status]);
                    $course_id = $conn->lastInsertId();

                    // Create semesters for this course
                    $stmt = $conn->prepare("INSERT INTO semesters (course_id, semester_number, semester_name) VALUES (?, ?, ?)");
                    for ($i = 1; $i <= $total_semesters; $i++) {
                        $stmt->execute([$course_id, $i, "Semester " . $i]);
                    }

                    set_message('success', 'Course added successfully');
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $conn->prepare("UPDATE courses SET department_id = ?, course_name = ?, course_code = ?,
                                           duration = ?, total_semesters = ?, status = ? WHERE id = ?");
                    $stmt->execute([$department_id, $course_name, $course_code, $duration, $total_semesters, $status, $id]);

                    // Update semesters
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM semesters WHERE course_id = ?");
                    $stmt->execute([$id]);
                    $current_semesters = $stmt->fetch()['count'];

                    if ($total_semesters > $current_semesters) {
                        $stmt = $conn->prepare("INSERT INTO semesters (course_id, semester_number, semester_name) VALUES (?, ?, ?)");
                        for ($i = $current_semesters + 1; $i <= $total_semesters; $i++) {
                            $stmt->execute([$id, $i, "Semester " . $i]);
                        }
                    }

                    set_message('success', 'Course updated successfully');
                }

                $conn->commit();
            } catch (PDOException $e) {
                $conn->rollBack();
                if ($e->getCode() == 23000) {
                    set_message('error', 'Course code already exists');
                } else {
                    set_message('error', 'Error: ' . $e->getMessage());
                }
            }
        }
        redirect('admin/courses.php');
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$id]);
            set_message('success', 'Course deleted successfully');
        } catch (PDOException $e) {
            set_message('error', 'Cannot delete course. It may have associated students or fee structures.');
        }
        redirect('admin/courses.php');
    }
}

// Fetch all courses with department info
$stmt = $conn->query("SELECT c.*, d.department_name
                     FROM courses c
                     JOIN departments d ON c.department_id = d.id
                     ORDER BY c.id DESC");
$courses = $stmt->fetchAll();

// Fetch departments for dropdown
$stmt = $conn->query("SELECT * FROM departments WHERE status = 'active' ORDER BY department_name");
$departments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Courses</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
        <i class="bi bi-plus-circle me-2"></i>Add Course
    </button>
</div>

<?php display_message(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department</th>
                        <th>Course Name</th>
                        <th>Course Code</th>
                        <th>Duration</th>
                        <th>Semesters</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($courses) > 0): ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo $course['id']; ?></td>
                                <td><?php echo $course['department_name']; ?></td>
                                <td><strong><?php echo $course['course_name']; ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo $course['course_code']; ?></span></td>
                                <td><?php echo $course['duration']; ?> years</td>
                                <td><?php echo $course['total_semesters']; ?></td>
                                <td>
                                    <span class="badge <?php echo $course['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn"
                                            data-id="<?php echo $course['id']; ?>"
                                            data-department="<?php echo $course['department_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                                            data-code="<?php echo $course['course_code']; ?>"
                                            data-duration="<?php echo $course['duration']; ?>"
                                            data-semesters="<?php echo $course['total_semesters']; ?>"
                                            data-status="<?php echo $course['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $course['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No courses found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <select class="form-select" name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="course_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Code *</label>
                        <input type="text" class="form-control" name="course_code" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Years) *</label>
                            <input type="number" class="form-control" name="duration" min="1" max="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Semesters *</label>
                            <input type="number" class="form-control" name="total_semesters" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <select class="form-select" name="department_id" id="edit_department" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Name *</label>
                        <input type="text" class="form-control" name="course_name" id="edit_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Course Code *</label>
                        <input type="text" class="form-control" name="course_code" id="edit_code" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration (Years) *</label>
                            <input type="number" class="form-control" name="duration" id="edit_duration" min="1" max="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Total Semesters *</label>
                            <input type="number" class="form-control" name="total_semesters" id="edit_semesters" min="1" max="20" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete this course?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_department').value = this.dataset.department;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_code').value = this.dataset.code;
        document.getElementById('edit_duration').value = this.dataset.duration;
        document.getElementById('edit_semesters').value = this.dataset.semesters;
        document.getElementById('edit_status').value = this.dataset.status;
        new bootstrap.Modal(document.getElementById('editCourseModal')).show();
    });
});

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_id').value = this.dataset.id;
        new bootstrap.Modal(document.getElementById('deleteCourseModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>