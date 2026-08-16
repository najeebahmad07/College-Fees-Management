<?php
// includes/admin-header.php

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ASCT Admin Panel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --primary-light: #EEF2FF;
            --secondary-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --info-color: #3B82F6;
            --dark-color: #1F2937;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --background: #F9FAFB;
            --sidebar-width: 280px;
            --header-height: 70px;
            --border-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ===================== SIDEBAR ===================== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: #FFFFFF;
            border-right: 1px solid var(--border-color);
            padding: 0;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-color);
            background: #FFFFFF;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .sidebar-brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }

        .sidebar-brand-text h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .sidebar-brand-text small {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .nav-menu {
            padding: 20px 12px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 8px 12px;
            margin-top: 16px;
        }

        .nav-section-title:first-child {
            margin-top: 0;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link {
            color: var(--text-secondary);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 10px;
            transition: var(--transition);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .nav-link:hover {
            background: var(--primary-light);
            color: var(--primary-color);
            transform: translateX(2px);
        }

        .nav-link.active {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
            border-color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }

        .nav-link i {
            width: 20px;
            font-size: 18px;
            text-align: center;
        }

        /* ===================== MAIN CONTENT ===================== */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--background);
        }

        .top-navbar {
            background: #FFFFFF;
            padding: 0 32px;
            height: var(--header-height);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
            background: transparent;
            font-size: 13px;
        }

        .breadcrumb-item {
            color: var(--text-secondary);
        }

        .breadcrumb-item.active {
            color: var(--text-primary);
            font-weight: 500;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 280px;
            padding: 10px 16px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: var(--transition);
            background: var(--background);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .notification-icon {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--background);
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .notification-icon:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            background: var(--danger-color);
            border-radius: 50%;
            font-size: 10px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            border: 2px solid white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 6px 6px;
            border-radius: 10px;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }

        .user-profile:hover {
            background: var(--background);
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-info {
            line-height: 1.3;
        }

        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }

        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* ===================== CONTENT WRAPPER ===================== */
        .content-wrapper {
            padding: 32px;
        }

        /* ===================== CARDS ===================== */
        .card {
            background: white;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .card-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .card-body {
            padding: 24px;
        }

        /* ===================== STAT CARDS ===================== */
        .stat-card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background: white;
            transition: var(--transition);
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .stat-card .card-body {
            padding: 24px;
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stat-info h6 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .stat-info .trend {
            font-size: 12px;
            margin-top: 8px;
            font-weight: 500;
        }

        .stat-info .trend.up {
            color: var(--secondary-color);
        }

        .stat-info .trend.down {
            color: var(--danger-color);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.primary {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .stat-icon.success {
            background: #D1FAE5;
            color: var(--secondary-color);
        }

        .stat-icon.danger {
            background: #FEE2E2;
            color: var(--danger-color);
        }

        .stat-icon.warning {
            background: #FEF3C7;
            color: var(--warning-color);
        }

        .stat-icon.info {
            background: #DBEAFE;
            color: var(--info-color);
        }

        /* ===================== TABLES ===================== */
        .table {
            font-size: 14px;
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--background);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            border-bottom: 2px solid var(--border-color);
        }

        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: var(--background);
        }

        /* ===================== BADGES ===================== */
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge.bg-success {
            background: var(--secondary-color) !important;
        }

        .badge.bg-danger {
            background: var(--danger-color) !important;
        }

        .badge.bg-warning {
            background: var(--warning-color) !important;
            color: white;
        }

        .badge.bg-info {
            background: var(--info-color) !important;
        }

        .badge.bg-primary {
            background: var(--primary-color) !important;
        }

        /* ===================== BUTTONS ===================== */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--secondary-color);
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger-color);
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-lg {
            padding: 14px 28px;
            font-size: 16px;
        }

        /* ===================== FORMS ===================== */
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            padding: 10px 14px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* ===================== ALERTS ===================== */
        .alert {
            border-radius: 10px;
            padding: 16px 20px;
            border: none;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
        }

        .alert-warning {
            background: #FEF3C7;
            color: #92400E;
        }

        /* ===================== RESPONSIVE ===================== */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-wrapper {
                padding: 20px;
            }

            .top-navbar {
                padding: 0 20px;
            }

            .search-box input {
                width: 200px;
            }

            .user-info {
                display: none;
            }

            .page-title {
                font-size: 20px;
            }
        }

        /* ===================== UTILITIES ===================== */
        .text-muted {
            color: var(--text-secondary) !important;
        }

        .border-bottom {
            border-bottom: 1px solid var(--border-color) !important;
        }

        .bg-light {
            background: var(--background) !important;
        }


    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-bank"></i>
                </div>
                <div class="sidebar-brand-text">
                    <h5>ASCT</h5>
                    <small>Admin Panel</small>
                </div>
            </a>
        </div>

        <ul class="nav flex-column nav-menu">
            <div class="nav-section-title">Main</div>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="nav-section-title">Management</div>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'departments' ? 'active' : ''; ?>" href="departments.php">
                    <i class="bi bi-building"></i>
                    <span>Departments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'courses' ? 'active' : ''; ?>" href="courses.php">
                    <i class="bi bi-book"></i>
                    <span>Courses</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['students', 'student-add', 'student-edit', 'student-view']) ? 'active' : ''; ?>" href="students.php">
                    <i class="bi bi-people"></i>
                    <span>Students</span>
                </a>
            </li>

            <div class="nav-section-title">Finance</div>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'fee-structures' ? 'active' : ''; ?>" href="fee-structures.php">
                    <i class="bi bi-cash-stack"></i>
                    <span>Fee Structures</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'student-fees' ? 'active' : ''; ?>" href="student-fees.php">
                    <i class="bi bi-receipt"></i>
                    <span>Student Fees</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'payments' ? 'active' : ''; ?>" href="payments.php">
                    <i class="bi bi-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'reports' ? 'active' : ''; ?>" href="reports.php">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </li>

            <div class="nav-section-title">System</div>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="navbar-left">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
            </div>

            <div class="navbar-right">
                <div class="search-box d-none d-md-block">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search...">
                </div>

                <div class="notification-icon">
                    <i class="bi bi-bell"></i>
                    <span class="notification-badge">3</span>
                </div>

                <div class="user-profile dropdown">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'], 0, 1)); ?>
                    </div>
                    <div class="user-info d-none d-md-block">
                        <div class="user-name"><?php echo $_SESSION['name']; ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">