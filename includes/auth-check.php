<?php
// includes/auth-check.php

require_once __DIR__ . '/../config/config.php';

// Check if session is active and not timed out
if (!check_session_timeout()) {
    redirect('auth/login.php');
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    redirect('auth/login.php');
}
?>