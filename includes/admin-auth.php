<?php
// includes/admin-auth.php

require_once __DIR__ . '/auth-check.php';

// Check if user is admin
if ($_SESSION['user_type'] !== 'admin') {
    set_message('error', 'Unauthorized access!');
    redirect('auth/login.php');
}
?>