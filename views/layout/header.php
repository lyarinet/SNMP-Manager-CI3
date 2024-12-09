<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNMP Manager - Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 56px; /* Height of navbar */
        }
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 56px; /* Height of navbar */
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 20px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            width: 250px;
            background-color: #f8f9fa;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 76px); /* Viewport height minus navbar height and padding */
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        /* Main content */
        .main {
            margin-left: 250px;
            padding: 20px;
        }
        /* Navbar */
        .navbar {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            height: 56px;
            padding: 0 1rem;
        }
        /* Avatar */
        .avatar {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Cards */
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        /* Active nav link */
        .nav-link.active {
            background-color: #e9ecef;
            color: #0d6efd !important;
        }
        .nav-link {
            color: #495057;
            padding: 0.5rem 1rem;
            margin: 0.2rem 0;
        }
        .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
                transition: margin 0.3s ease-in-out;
            }
            .sidebar.show {
                margin-left: 0;
            }
            .main {
                margin-left: 0;
                transition: margin 0.3s ease-in-out;
            }
            body.sidebar-open .main {
                margin-left: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler me-2" type="button" onclick="toggleSidebar()">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-network-wired me-2"></i>
                SNMP Manager
            </a>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $content === 'dashboard' ? 'active' : ''; ?>" href="/dashboard">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $content === 'devices' ? 'active' : ''; ?>" href="/devices">
                        <i class="fas fa-server me-2"></i>
                        Devices
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $content === 'alerts' ? 'active' : ''; ?>" href="/alerts">
                        <i class="fas fa-bell me-2"></i>
                        Alerts
                        <?php if(isset($metrics['unread_alerts']) && $metrics['unread_alerts'] > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-2"><?php echo $metrics['unread_alerts']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $content === 'reports' ? 'active' : ''; ?>" href="/reports">
                        <i class="fas fa-chart-bar me-2"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $content === 'settings' ? 'active' : ''; ?>" href="/settings">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main content -->
    <main class="main"> 