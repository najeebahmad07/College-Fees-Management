<?php
// includes/student-header.php (Complete Redesign with Sidebar)

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>ASCT Student Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --primary-light: #EEF2FF;
            --secondary-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --info-color: #3B82F6;
            --success-light: #D1FAE5;
            --danger-light: #FEE2E2;
            --warning-light: #FEF3C7;
            --dark-color: #1F2937;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --background: #F9FAFB;
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ===================== SIDEBAR ===================== */
        .student-sidebar {
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

        .student-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .student-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .student-sidebar::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 10px;
        }

        /* Sidebar Header */
        .sidebar-student-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
        }

        .student-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            margin-bottom: 16px;
        }

        .student-brand-icon {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .student-brand-text h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
        }

        .student-brand-text small {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        /* Student Info Card in Sidebar */
        .sidebar-student-info {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 12px;
            backdrop-filter: blur(10px);
        }

        .student-avatar-large {
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 700;
            font-size: 24px;
            margin: 0 auto 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .student-info-text {
            text-align: center;
            color: white;
        }

        .student-info-text h6 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: white;
        }

        .student-info-text small {
            font-size: 11px;
            opacity: 0.9;
            display: block;
        }

        .student-info-text .enrollment-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }

        /* Navigation Menu */
        .student-nav-menu {
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

        .student-nav-item {
            margin-bottom: 4px;
        }

        .student-nav-link {
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
            position: relative;
            border: 1px solid transparent;
        }

        .student-nav-link:hover {
            background: var(--primary-light);
            color: var(--primary-color);
            transform: translateX(2px);
        }

        .student-nav-link.active {
            background: var(--primary-light);
            color: var(--primary-color);
            font-weight: 600;
            border-left: 3px solid var(--primary-color);
        }

        .student-nav-link i {
            width: 20px;
            font-size: 18px;
            text-align: center;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--danger-color);
            color: white;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 10px;
            font-weight: 700;
        }

        /* ===================== MAIN CONTENT ===================== */
        .student-main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--background);
        }

        /* Top Header */
        .student-top-header {
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

        .header-left h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .header-left p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .notification-btn {
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
            position: relative;
        }

        .notification-btn:hover {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .notification-count {
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

        .current-date {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: var(--background);
            border-radius: 10px;
            font-size: 13px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .current-date i {
            color: var(--primary-color);
        }

        /* Content Wrapper */
        .student-content-wrapper {
            padding: 32px;
        }

        /* ===================== CARDS ===================== */
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .welcome-card p {
            font-size: 14px;
            opacity: 0.95;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .student-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            position: relative;
            z-index: 1;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 16px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .detail-item label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 4px;
            display: block;
            font-weight: 600;
        }

        .detail-item strong {
            font-size: 15px;
            font-weight: 600;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 24px;
            transition: var(--transition);
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-info h6 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-info h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
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
            background: var(--success-light);
            color: var(--secondary-color);
        }

        .stat-icon.danger {
            background: var(--danger-light);
            color: var(--danger-color);
        }

        .stat-icon.warning {
            background: var(--warning-light);
            color: var(--warning-color);
        }

        .stat-footer {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color);
        }

        .stat-footer small {
            font-size: 12px;
            color: var(--text-secondary);
        }

        /* Regular Cards */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            background: white;
            border-radius: 12px 12px 0 0;
        }

        .card-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 24px;
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
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-success {
            background: var(--secondary-color);
        }

        .btn-success:hover {
            background: #059669;
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

        /* ===================== TABLES ===================== */
        .table {
            font-size: 14px;
        }

        .table thead th {
            background: var(--background);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
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

        /* ===================== PROGRESS BAR ===================== */
        .progress {
            height: 10px;
            border-radius: 10px;
            background: var(--background);
        }

        .progress-bar {
            border-radius: 10px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }

        /* ===================== RESPONSIVE ===================== */
        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%);
            }

            .student-sidebar.show {
                transform: translateX(0);
            }

            .student-main-content {
                margin-left: 0;
            }

            .student-content-wrapper {
                padding: 20px;
            }

            .student-top-header {
                padding: 0 20px;
            }

            .welcome-card {
                padding: 24px;
            }

            .student-details-grid {
                grid-template-columns: 1fr;
            }

            .current-date {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="student-sidebar" id="studentSidebar">
        <div class="sidebar-student-header">
            <a href="dashboard.php" class="student-brand">
                <div class="student-brand-icon">
                    <i class="bi bi-bank"></i>
                </div>
                <div class="student-brand-text">
                    <h5>ASCT</h5>
                    <small>Student Portal</small>
                </div>
            </a>

            <div class="sidebar-student-info">
                <div class="student-avatar-large">
                    <?php echo strtoupper(substr($_SESSION['name'], 0, 2)); ?>
                </div>
                <div class="student-info-text">
                    <h6><?php echo $_SESSION['name']; ?></h6>
                    <small class="enrollment-badge"><?php echo $_SESSION['enrollment_no']; ?></small>
                </div>
            </div>
        </div>

        <ul class="nav flex-column student-nav-menu">
            <div class="nav-section-title">Main Menu</div>
            <li class="student-nav-item">
                <a class="student-nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="student-nav-item">
                <a class="student-nav-link <?php echo $current_page === 'profile' ? 'active' : ''; ?>" href="profile.php">
                    <i class="bi bi-person"></i>
                    <span>My Profile</span>
                </a>
            </li>

            <div class="nav-section-title">Fees & Payments</div>
            <li class="student-nav-item">
                <a class="student-nav-link <?php echo $current_page === 'fees' ? 'active' : ''; ?>" href="fees.php">
                    <i class="bi bi-receipt"></i>
                    <span>My Fees</span>
                </a>
            </li>
            <li class="student-nav-item">
                <a class="student-nav-link <?php echo $current_page === 'payment-history' ? 'active' : ''; ?>" href="payment-history.php">
                    <i class="bi bi-clock-history"></i>
                    <span>Payment History</span>
                </a>
            </li>

            <div class="nav-section-title">Account</div>
            <li class="student-nav-item">
                <a class="student-nav-link <?php echo $current_page === 'change-password' ? 'active' : ''; ?>" href="change-password.php">
                    <i class="bi bi-key"></i>
                    <span>Change Password</span>
                </a>
            </li>
            <li class="student-nav-item">
                <a class="student-nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="student-main-content">
        <!-- Top Header -->
        <div class="student-top-header">
            <div class="header-left">
                <button class="btn btn-link d-md-none p-0" id="sidebarToggle">
                    <i class="bi bi-list fs-3"></i>
                </button>
                <h4 class="d-none d-md-block"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
            </div>

            <div class="header-right">
                <div class="current-date d-none d-md-flex">
                    <i class="bi bi-calendar3"></i>
                    <span><?php echo date('l, F d, Y'); ?></span>
                </div>

                <div class="notification-btn">
                    <i class="bi bi-bell"></i>
                    <span class="notification-count">2</span>
                </div>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="student-content-wrapper">