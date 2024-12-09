<?php

class ReportsController {
    private $db;
    private $devices;

    public function __construct($db) {
        $this->db = $db;
        $this->devices = $this->getDevices();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $reports = $this->getReports();
        $devices = $this->devices;
        
        require_once BASE_PATH . '/views/reports.php';
    }

    private function getDevices() {
        $stmt = $this->db->prepare("SELECT id, ip_address, description FROM devices");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReports() {
        $stmt = $this->db->prepare("
            SELECT r.*, d.ip_address as device_name 
            FROM reports r 
            LEFT JOIN devices d ON r.device_id = d.id 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generate() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request data']);
            return;
        }

        try {
            // Generate report name
            $reportName = $this->generateReportName($data);
            
            // Insert report record
            $stmt = $this->db->prepare("
                INSERT INTO reports (name, device_id, metric, date_range, format, status, created_by)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)
            ");
            
            $stmt->execute([
                $reportName,
                $data['device'] ?: null,
                $data['metric'] ?: null,
                $this->getDateRangeString($data),
                $data['format'],
                $_SESSION['user_id']
            ]);
            
            $reportId = $this->db->lastInsertId();
            
            // Start report generation process
            $this->processReport($reportId, $data);
            
            $this->jsonResponse(['success' => true, 'message' => 'Report generation started']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()]);
        }
    }

    public function schedule() {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request data']);
            return;
        }

        try {
            // Validate email recipients
            $recipients = array_map('trim', explode(',', $data['recipients']));
            foreach ($recipients as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address: ' . $email);
                }
            }

            // Insert scheduled report
            $stmt = $this->db->prepare("
                INSERT INTO scheduled_reports (
                    name, schedule_type, schedule_time, schedule_day, 
                    recipients, created_by, device_id, metric, format
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['reportName'],
                $data['schedule'],
                $data['scheduleTime'],
                $data['scheduleDay'] ?? null,
                $data['recipients'],
                $_SESSION['user_id'],
                $data['device'] ?? null,
                $data['metric'] ?? null,
                $data['format'] ?? 'pdf'
            ]);

            $this->jsonResponse(['success' => true, 'message' => 'Report scheduled successfully']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error scheduling report: ' . $e->getMessage()]);
        }
    }

    public function download($reportId) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM reports 
            WHERE id = ? AND status = 'completed'
        ");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            header('Location: /reports');
            exit;
        }

        $filePath = BASE_PATH . '/storage/reports/' . $report['file_path'];
        
        if (!file_exists($filePath)) {
            header('Location: /reports');
            exit;
        }

        $mimeTypes = [
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'excel' => 'application/vnd.ms-excel'
        ];

        header('Content-Type: ' . ($mimeTypes[$report['format']] ?? 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($report['file_path']) . '"');
        header('Content-Length: ' . filesize($filePath));
        
        readfile($filePath);
        exit;
    }

    public function delete($reportId) {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            $stmt = $this->db->prepare("SELECT file_path FROM reports WHERE id = ?");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($report && $report['file_path']) {
                $filePath = BASE_PATH . '/storage/reports/' . $report['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $stmt = $this->db->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$reportId]);

            $this->jsonResponse(['success' => true, 'message' => 'Report deleted successfully']);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error deleting report: ' . $e->getMessage()]);
        }
    }

    private function generateReportName($data) {
        $parts = [];
        
        if ($data['device']) {
            foreach ($this->devices as $device) {
                if ($device['id'] == $data['device']) {
                    $parts[] = $device['ip_address'];
                    break;
                }
            }
        }
        
        if ($data['metric']) {
            $parts[] = str_replace('_', ' ', ucfirst($data['metric']));
        }
        
        $parts[] = 'Report';
        $parts[] = date('Y-m-d_H-i-s');
        
        return implode('_', $parts);
    }

    private function getDateRangeString($data) {
        if ($data['dateRange'] === 'custom') {
            return $data['startDate'] . ' to ' . $data['endDate'];
        } else {
            return 'Last ' . $data['dateRange'] . ' ' . ($data['dateRange'] == 1 ? 'day' : 'days');
        }
    }

    private function processReport($reportId, $data) {
        try {
            // Get report data based on filters
            $reportData = $this->collectReportData($data);
            
            // Generate report file
            $filePath = $this->generateReportFile($reportId, $reportData, $data['format']);
            
            // Update report status
            $stmt = $this->db->prepare("
                UPDATE reports 
                SET status = 'completed', 
                    file_path = ?,
                    completed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$filePath, $reportId]);
        } catch (Exception $e) {
            // Update report status to failed
            $stmt = $this->db->prepare("
                UPDATE reports 
                SET status = 'failed',
                    error_message = ?
                WHERE id = ?
            ");
            $stmt->execute([$e->getMessage(), $reportId]);
        }
    }

    private function collectReportData($filters) {
        $data = [];
        $params = [];
        $where = [];
        
        $sql = "SELECT m.*, d.ip_address, d.description 
                FROM metrics m 
                JOIN devices d ON m.device_id = d.id 
                WHERE 1=1";
        
        if (!empty($filters['device'])) {
            $where[] = "m.device_id = ?";
            $params[] = $filters['device'];
        }
        
        if (!empty($filters['metric'])) {
            $where[] = "m.metric_type = ?";
            $params[] = $filters['metric'];
        }
        
        if ($filters['dateRange'] === 'custom') {
            $where[] = "m.timestamp BETWEEN ? AND ?";
            $params[] = $filters['startDate'] . ' 00:00:00';
            $params[] = $filters['endDate'] . ' 23:59:59';
        } else {
            $where[] = "m.timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $params[] = $filters['dateRange'];
        }
        
        if (!empty($where)) {
            $sql .= " AND " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY m.timestamp DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function generateReportFile($reportId, $data, $format) {
        $filename = 'report_' . $reportId . '_' . date('YmdHis');
        $storageDir = BASE_PATH . '/storage/reports/';
        
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        switch ($format) {
            case 'csv':
                return $this->generateCSV($filename, $data);
            case 'excel':
                return $this->generateExcel($filename, $data);
            case 'pdf':
            default:
                return $this->generatePDF($filename, $data);
        }
    }

    private function generateCSV($filename, $data) {
        $filepath = BASE_PATH . '/storage/reports/' . $filename . '.csv';
        $fp = fopen($filepath, 'w');
        
        // Write headers
        if (!empty($data)) {
            fputcsv($fp, array_keys($data[0]));
        }
        
        // Write data
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
        return $filename . '.csv';
    }

    private function generateExcel($filename, $data) {
        // Implement Excel generation using a library like PHPSpreadsheet
        // For now, fallback to CSV
        return $this->generateCSV($filename, $data);
    }

    private function generatePDF($filename, $data) {
        // Implement PDF generation using a library like TCPDF or FPDF
        // For now, create a simple HTML to PDF
        $html = '<html><body>';
        $html .= '<h1>SNMP Monitoring Report</h1>';
        $html .= '<table border="1">';
        
        // Headers
        if (!empty($data)) {
            $html .= '<tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
        }
        
        // Data
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        $html .= '</body></html>';
        
        $filepath = BASE_PATH . '/storage/reports/' . $filename . '.html';
        file_put_contents($filepath, $html);
        
        return $filename . '.html';
    }

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 