<?php
// Load bootstrap
require_once __DIR__ . '/bootstrap.php';

// Helper function to send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
    }
    echo json_encode($data);
    exit;
}

// Helper function to check if request is AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Authentication middleware
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        if (isAjaxRequest()) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }
        header('Location: /login');
        exit;
    }
}

try {
    // Initialize controllers with database connection
    $adminController = new AdminController($db);
    $snmpController = new SNMPController($db);
    $reportsController = new ReportsController($db);
    $settingsController = new SettingsController($db);

    // Get the request path and remove query string
    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Debug log
    error_log("Request URI: " . $request);
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

    // Handle routes
    switch (true) {
        case $request === '/':
            if (isset($_SESSION['user_id'])) {
                header('Location: /dashboard');
            } else {
                header('Location: /login');
            }
            exit;
            break;

        case $request === '/login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if ($adminController->login($username, $password)) {
                    header('Location: /dashboard');
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Invalid credentials';
                    $_SESSION['flash_type'] = 'danger';
                }
            }
            require_once BASE_PATH . '/views/login.php';
            break;

        case $request === '/logout':
            session_destroy();
            header('Location: /login');
            exit;
            break;

        case $request === '/dashboard':
            requireAuth();
            $dashboardStats = $adminController->getDashboardStats();
            $metrics = $dashboardStats['metrics'];
            $chart_data = $dashboardStats['chart_data'];
            $recent_alerts = $adminController->getRecentAlerts();
            require_once BASE_PATH . '/views/dashboard.php';
            break;

        case $request === '/dashboard/chart-data':
            requireAuth();
            $range = $_GET['range'] ?? '24h';
            $dashboardStats = $adminController->getDashboardStats($range);
            sendJsonResponse([
                'success' => true,
                'chart_data' => $dashboardStats['chart_data']
            ]);
            break;

        case $request === '/devices' && $_SERVER['REQUEST_METHOD'] === 'GET':
            requireAuth();
            $devices = $snmpController->getAllDevices();
            require_once BASE_PATH . '/views/devices.php';
            break;

        case $request === '/devices' && $_SERVER['REQUEST_METHOD'] === 'POST':
            requireAuth();
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid request data'], 400);
            }
            $result = $snmpController->addDevice($data);
            sendJsonResponse($result);
            break;

        case $request === '/settings':
            requireAuth();
            $settingsController->index();
            break;

        case $request === '/settings/save':
            requireAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('HTTP/1.1 405 Method Not Allowed');
                exit;
            }
            $settingsController->save();
            break;

        case $request === '/settings/reset':
            requireAuth();
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('HTTP/1.1 405 Method Not Allowed');
                exit;
            }
            $settingsController->reset();
            break;

        case $request === '/alerts':
            requireAuth();
            $devices = $snmpController->getAllDevices();
            $alerts = $snmpController->getAlerts();
            require_once BASE_PATH . '/views/alerts.php';
            break;

        case $request === '/reports':
            requireAuth();
            $reportsController->index();
            break;

        case preg_match('#^/monitor/(\d+)$#', $request, $matches):
            requireAuth();
            $device_id = intval($matches[1]);
            $result = $snmpController->monitorDevice($device_id);
            sendJsonResponse($result);
            break;

        default:
            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
            } else {
                header('HTTP/1.0 404 Not Found');
                echo '<h1>404 Not Found</h1>';
            }
            exit;
            break;
    }
} catch (Exception $e) {
    error_log("Application Error: " . $e->getMessage());
    if (isAjaxRequest()) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Internal Server Error'
        ], 500);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo '<h1>500 Internal Server Error</h1>';
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 1) {
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }
}
