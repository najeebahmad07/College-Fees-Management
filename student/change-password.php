<?php
// student/change-password.php

$page_title = "Change Password";
require_once __DIR__ . '/../includes/student-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Get current password from database
        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();

        // Validate
        $errors = [];

        if (!password_verify($current_password, $student['password'])) {
            $errors[] = "Current password is incorrect";
        }

        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirm password do not match";
        }

        if (count($errors) > 0) {
            set_message('error', implode('<br>', $errors));
        } else {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);

            set_message('success', 'Password changed successfully');
            redirect('student/change-password.php');
        }
    }
}

require_once __DIR__ . '/../includes/student-header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-key me-2"></i>Change Password</h5>
            </div>
            <div class="card-body">
                <?php display_message(); ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label class="form-label">Current Password *</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-shield-check me-2"></i>Change Password
                        </button>
                    </div>
                </form>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Password Tips:</strong><br>
                    <small>
                        • Use at least 6 characters<br>
                        • Include uppercase and lowercase letters<br>
                        • Add numbers and special characters<br>
                        • Don't use common words or personal information
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/student-footer.php'; ?>