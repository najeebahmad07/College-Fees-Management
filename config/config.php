<?php
// config/config.php

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Base URL configuration
define('BASE_URL', 'http://localhost/asct-fees/');
define('SITE_NAME', 'ASCT Fees Management System');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('RECEIPT_DIR', __DIR__ . '/../receipts/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Pagination
define('RECORDS_PER_PAGE', 20);

// Check session timeout
function check_session_timeout() {
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['LAST_ACTIVITY'] = time();
    return true;
}

// CSRF Token Generation
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF Token Validation
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Redirect function
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Alert message display
function set_message($type, $message) {
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
}

function display_message() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'];
        $text = $_SESSION['message']['text'];
        $alertClass = $type === 'success' ? 'alert-success' :
                     ($type === 'error' ? 'alert-danger' : 'alert-info');

        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$text}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['message']);
    }
}

// Format currency
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date
function format_date($date, $format = 'd-m-Y') {
    return date($format, strtotime($date));
}

// Get setting value from database
function get_setting($key, $default = '') {
    require_once __DIR__ . '/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();

    return $result ? $result['setting_value'] : $default;
}
?>