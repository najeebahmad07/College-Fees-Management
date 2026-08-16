<?php
// student/logout.php
// admin/logout.php

session_start();
session_unset();
session_destroy();

header("Location: ../auth/login.php");
exit();
?>