<?php
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNMP Manager - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background: #495057;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4>SNMP Manager</h4>
                </div>
                <nav>
                    <a href="/dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
                    <a href="/devices"><i class="fas fa-server me-2"></i> Devices</a>
                    <a href="/alerts"><i class="fas fa-bell me-2"></i> Alerts</a>
                    <a href="/users"><i class="fas fa-users me-2"></i> Users</a>
                    <a href="/settings"><i class="fas fa-cog me-2"></i> Settings</a>
                    <a href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <?php if(isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                        <?php 
                            echo $_SESSION['flash_message'];
                            unset($_SESSION['flash_message']);
                            unset($_SESSION['flash_type']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php 
                if (isset($content)) {
                    $view_file = APP_ROOT . '/views/' . $content . '.php';
                    if (file_exists($view_file)) {
                        require_once $view_file;
                    } else {
                        echo "View file not found: " . $content;
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html> 