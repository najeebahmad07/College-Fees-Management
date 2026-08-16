<?php
// admin/student-edit.php

$page_title = "Edit Student";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id <= 0) {
    set_message('error', 'Invalid student ID');
    redirect('admin/students.php');
}

$student = get_student($student_id);

if (!$student) {
    set_message('error', 'Student not found');
    redirect('admin/students.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
        redirect('admin/students.php');
    }

    // Collect input
    $student_name = clean_input($_POST['student_name']);
    $father_name = clean_input($_POST['father_name']);
    $mother_name = clean_input($_POST['mother_name']);
    $dob = clean_input($_POST['dob']);
    $gender = clean_input($_POST['gender']);
    $mobile = clean_input($_POST['mobile']);
    $email = clean_input($_POST['email']);
    $address = clean_input($_POST['address']);
    $city = clean_input($_POST['city']);
    $state = clean_input($_POST['state']);
    $pincode = clean_input($_POST['pincode']);
    $current_semester = intval($_POST['current_semester']);
    $status = clean_input($_POST['status']);

    try {
        $stmt = $conn->prepare("UPDATE students SET
                               student_name = ?, father_name = ?, mother_name = ?, dob = ?, gender = ?,
                               mobile = ?, email = ?, address = ?, city = ?, state = ?, pincode = ?,
                               current_semester = ?, status = ?
                               WHERE id = ?");
        $stmt->execute([
            $student_name, $father_name, $mother_name, $dob, $gender,
            $mobile, $email, $address, $city, $state, $pincode,
            $current_semester, $status, $student_id
        ]);

        set_message('success', 'Student updated successfully');
        redirect('admin/student-view.php?id=' . $student_id);
    } catch (Exception $e) {
        set_message('error', 'Failed to update student');
    }
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="mb-4">
    <a href="student-view.php?id=<?php echo $student_id; ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Student Details
    </a>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Student Information</h5>
    </div>
    <div class="card-body">
        <?php display_message(); ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Enrollment Number</label>
                    <input type="text" class="form-control" value="<?php echo $student['enrollment_no']; ?>" readonly>
                    <small class="text-muted">Enrollment number cannot be changed</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Student Name *</label>
                    <input type="text" class="form-control" name="student_name" value="<?php echo $student['student_name']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Father's Name *</label>
                    <input type="text" class="form-control" name="father_name" value="<?php echo $student['father_name']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Mother's Name</label>
                    <input type="text" class="form-control" name="mother_name" value="<?php echo $student['mother_name']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" name="dob" value="<?php echo $student['dob']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender *</label>
                    <select class="form-select" name="gender" required>
                        <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo $student['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile Number *</label>
                    <input type="text" class="form-control" name="mobile" value="<?php echo $student['mobile']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="<?php echo $student['email']; ?>">
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">Address Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label class="form-label">Address *</label>
                    <textarea class="form-control" name="address" rows="2" required><?php echo $student['address']; ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City *</label>
                    <input type="text" class="form-control" name="city" value="<?php echo $student['city']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">State *</label>
                    <input type="text" class="form-control" name="state" value="<?php echo $student['state']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pincode *</label>
                    <input type="text" class="form-control" name="pincode" value="<?php echo $student['pincode']; ?>" required>
                </div>
            </div>

            <h6 class="border-bottom pb-2 mb-3">Academic Information</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <input type="text" class="form-control" value="<?php echo $student['department_name']; ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Course</label>
                    <input type="text" class="form-control" value="<?php echo $student['course_name']; ?>" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Current Semester *</label>
                    <input type="number" class="form-control" name="current_semester" value="<?php echo $student['current_semester']; ?>" min="1" max="<?php echo $student['total_semesters']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="status" required>
                        <option value="active" <?php echo $student['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $student['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="passed" <?php echo $student['status'] === 'passed' ? 'selected' : ''; ?>>Passed</option>
                        <option value="left" <?php echo $student['status'] === 'left' ? 'selected' : ''; ?>>Left</option>
                    </select>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i>Update Student
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>