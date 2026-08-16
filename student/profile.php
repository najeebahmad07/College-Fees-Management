<?php
// student/profile.php

$page_title = "My Profile";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$student = get_student($_SESSION['user_id']);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
    } else {
        $mobile = clean_input($_POST['mobile']);
        $email = clean_input($_POST['email']);
        $address = clean_input($_POST['address']);
        $city = clean_input($_POST['city']);
        $state = clean_input($_POST['state']);
        $pincode = clean_input($_POST['pincode']);

        try {
            $stmt = $conn->prepare("UPDATE students SET
                                   mobile = ?, email = ?, address = ?, city = ?, state = ?, pincode = ?
                                   WHERE id = ?");
            $stmt->execute([$mobile, $email, $address, $city, $state, $pincode, $_SESSION['user_id']]);

            set_message('success', 'Profile updated successfully');
            redirect('student/profile.php');
        } catch (Exception $e) {
            set_message('error', 'Failed to update profile');
        }
    }
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<h2 class="mb-4">My Profile</h2>

<?php display_message(); ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white; font-weight: 600;">
                        <?php echo strtoupper(substr($student['student_name'], 0, 2)); ?>
                    </div>
                </div>
                <h4><?php echo $student['student_name']; ?></h4>
                <p class="text-muted mb-0"><?php echo $student['enrollment_no']; ?></p>
                <p class="text-muted"><?php echo $student['course_name']; ?></p>
                <span class="badge bg-success">Active Student</span>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="mb-3">Academic Information</h6>
                <p class="mb-2"><strong>Department:</strong><br><?php echo $student['department_name']; ?></p>
                <p class="mb-2"><strong>Course Code:</strong><br><?php echo $student['course_code']; ?></p>
                <p class="mb-2"><strong>Current Semester:</strong><br>Semester <?php echo $student['current_semester']; ?></p>
                <p class="mb-2"><strong>Admission Year:</strong><br><?php echo $student['admission_year']; ?></p>
                <p class="mb-0"><strong>Academic Session:</strong><br><?php echo $student['academic_session']; ?></p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Enrollment Number</label>
                            <input type="text" class="form-control" value="<?php echo $student['enrollment_no']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Student Name</label>
                            <input type="text" class="form-control" value="<?php echo $student['student_name']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" class="form-control" value="<?php echo $student['father_name']; ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mother's Name</label>
                            <input type="text" class="form-control" value="<?php echo $student['mother_name']; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="text" class="form-control" value="<?php echo format_date($student['dob']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <input type="text" class="form-control" value="<?php echo $student['gender']; ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number *</label>
                            <input type="text" class="form-control" name="mobile" value="<?php echo $student['mobile']; ?>" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $student['email']; ?>">
                        </div>
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

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>