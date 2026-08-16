<?php
// admin/settings.php

$page_title = "Settings";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
        redirect('admin/settings.php');
    }

    $settings = [
        'college_name' => clean_input($_POST['college_name']),
        'college_short_name' => clean_input($_POST['college_short_name']),
        'college_address' => clean_input($_POST['college_address']),
        'college_phone' => clean_input($_POST['college_phone']),
        'college_email' => clean_input($_POST['college_email']),
        'college_website' => clean_input($_POST['college_website']),
        'razorpay_key_id' => clean_input($_POST['razorpay_key_id']),
        'currency' => clean_input($_POST['currency']),
        'receipt_prefix' => clean_input($_POST['receipt_prefix']),
        'current_academic_session' => clean_input($_POST['current_academic_session'])
    ];

    // Only update razorpay_key_secret if provided
    if (!empty($_POST['razorpay_key_secret'])) {
        $settings['razorpay_key_secret'] = clean_input($_POST['razorpay_key_secret']);
    }

    try {
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value)
                                   VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }

        set_message('success', 'Settings updated successfully');
        redirect('admin/settings.php');
    } catch (Exception $e) {
        set_message('error', 'Failed to update settings');
    }
}

// Fetch current settings
$stmt = $conn->query("SELECT * FROM settings");
$settings_data = [];
while ($row = $stmt->fetch()) {
    $settings_data[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/../includes/admin-header.php';
?>

<h2 class="mb-4">System Settings</h2>

<?php display_message(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

            <h5 class="mb-3">College Information</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">College Name *</label>
                    <input type="text" class="form-control" name="college_name"
                           value="<?php echo $settings_data['college_name'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Short Name *</label>
                    <input type="text" class="form-control" name="college_short_name"
                           value="<?php echo $settings_data['college_short_name'] ?? ''; ?>" required>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="college_address" rows="2"><?php echo $settings_data['college_address'] ?? ''; ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="college_phone"
                           value="<?php echo $settings_data['college_phone'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="college_email"
                           value="<?php echo $settings_data['college_email'] ?? ''; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Website</label>
                    <input type="text" class="form-control" name="college_website"
                           value="<?php echo $settings_data['college_website'] ?? ''; ?>">
                </div>
            </div>

            <h5 class="mb-3">Razorpay Configuration</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Razorpay Key ID *</label>
                    <input type="text" class="form-control" name="razorpay_key_id"
                           value="<?php echo $settings_data['razorpay_key_id'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Razorpay Key Secret</label>
                    <input type="password" class="form-control" name="razorpay_key_secret"
                           placeholder="Leave blank to keep current">
                    <small class="text-muted">Enter only if you want to change</small>
                </div>
            </div>

            <h5 class="mb-3">General Settings</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <select class="form-select" name="currency">
                        <option value="INR" <?php echo ($settings_data['currency'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                        <option value="USD" <?php echo ($settings_data['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Receipt Prefix</label>
                    <input type="text" class="form-control" name="receipt_prefix"
                           value="<?php echo $settings_data['receipt_prefix'] ?? 'ASCT'; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Current Academic Session</label>
                    <input type="text" class="form-control" name="current_academic_session"
                           value="<?php echo $settings_data['current_academic_session'] ?? ''; ?>"
                           placeholder="2025-26">
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>