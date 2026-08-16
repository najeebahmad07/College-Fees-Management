<?php
// includes/student-auth.php

require_once __DIR__ . '/auth-check.php';

// Check if user is student
if ($_SESSION['user_type'] !== 'student') {
    set_message('error', 'Unauthorized access!');
    redirect('auth/login.php');
}
?>