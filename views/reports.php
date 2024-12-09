<?php require_once BASE_PATH . '/views/layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reports</h2>
        <div>
            <button type="button" class="btn btn-primary me-2" onclick="generateReport()">
                <i class="fas fa-file-export me-2"></i> Generate Report
            </button>
            <button type="button" class="btn btn-success" onclick="scheduleReport()">
                <i class="fas fa-clock me-2"></i> Schedule Report
            </button>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="reportFilters" class="row g-3">
                <div class="col-md-3">
                    <label for="device" class="form-label">Device</label>
                    <select class="form-select" id="device" name="device">
                        <option value="">All Devices</option>
                        <?php foreach($devices as $device): ?>
                            <option value="<?php echo $device['id']; ?>">
                                <?php echo htmlspecialchars($device['ip_address'] . ' - ' . $device['description']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="metric" class="form-label">Metric</label>
                    <select class="form-select" id="metric" name="metric">
                        <option value="">All Metrics</option>
                        <option value="cpu_usage">CPU Usage</option>
                        <option value="memory_usage">Memory Usage</option>
                        <option value="disk_usage">Disk Usage</option>
                        <option value="interface_status">Interface Status</option>
                        <option value="response_time">Response Time</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateRange" class="form-label">Date Range</label>
                    <select class="form-select" id="dateRange" name="dateRange">
                        <option value="1">Last 24 Hours</option>
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="format" class="form-label">Format</label>
                    <select class="form-select" id="format" name="format">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                        <option value="excel">Excel</option>
                    </select>
                </div>
                <div class="col-12 custom-date-range d-none">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="startDate">
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="endDate" name="endDate">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Generated Reports Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Report Name</th>
                            <th>Device</th>
                            <th>Metric</th>
                            <th>Date Range</th>
                            <th>Generated On</th>
                            <th>Format</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($reports) && !empty($reports)): ?>
                            <?php foreach($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['name']); ?></td>
                                <td><?php echo htmlspecialchars($report['device_name']); ?></td>
                                <td><?php echo htmlspecialchars($report['metric']); ?></td>
                                <td><?php echo htmlspecialchars($report['date_range']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($report['generated_at'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo strtoupper(htmlspecialchars($report['format'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $report['status'] === 'completed' ? 'success' : 
                                            ($report['status'] === 'pending' ? 'warning' : 
                                            ($report['status'] === 'failed' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($report['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($report['status'] === 'completed'): ?>
                                        <button class="btn btn-sm btn-primary me-1" onclick="downloadReport(<?php echo $report['id']; ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info me-1" onclick="viewReport(<?php echo $report['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteReport(<?php echo $report['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No reports generated yet. Use the filters above to generate a report.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Report Modal -->
<div class="modal fade" id="scheduleReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleReportForm">
                <div class="modal-body">
                    <div id="scheduleFormAlerts"></div>
                    <div class="mb-3">
                        <label for="reportName" class="form-label">Report Name</label>
                        <input type="text" class="form-control" id="reportName" name="reportName" required>
                    </div>
                    <div class="mb-3">
                        <label for="schedule" class="form-label">Schedule</label>
                        <select class="form-select" id="schedule" name="schedule" required>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="mb-3 schedule-time">
                        <label for="scheduleTime" class="form-label">Time</label>
                        <input type="time" class="form-control" id="scheduleTime" name="scheduleTime" required>
                    </div>
                    <div class="mb-3 schedule-day d-none">
                        <label for="scheduleDay" class="form-label">Day</label>
                        <select class="form-select" id="scheduleDay" name="scheduleDay">
                            <option value="1">Monday</option>
                            <option value="2">Tuesday</option>
                            <option value="3">Wednesday</option>
                            <option value="4">Thursday</option>
                            <option value="5">Friday</option>
                            <option value="6">Saturday</option>
                            <option value="7">Sunday</option>
                        </select>
                    </div>
                    <div class="mb-3 schedule-date d-none">
                        <label for="scheduleDate" class="form-label">Date</label>
                        <select class="form-select" id="scheduleDate" name="scheduleDate">
                            <?php for($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="recipients" class="form-label">Email Recipients</label>
                        <input type="text" class="form-control" id="recipients" name="recipients" 
                               placeholder="email1@example.com, email2@example.com" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Schedule Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Show/hide custom date range based on selection
document.getElementById('dateRange').addEventListener('change', function() {
    const customDateRange = document.querySelector('.custom-date-range');
    if (this.value === 'custom') {
        customDateRange.classList.remove('d-none');
    } else {
        customDateRange.classList.add('d-none');
    }
});

// Show/hide schedule options based on selection
document.getElementById('schedule').addEventListener('change', function() {
    const scheduleDay = document.querySelector('.schedule-day');
    const scheduleDate = document.querySelector('.schedule-date');
    
    scheduleDay.classList.add('d-none');
    scheduleDate.classList.add('d-none');
    
    if (this.value === 'weekly') {
        scheduleDay.classList.remove('d-none');
    } else if (this.value === 'monthly') {
        scheduleDate.classList.remove('d-none');
    }
});

// Generate report function
function generateReport() {
    const form = document.getElementById('reportFilters');
    const formData = new FormData(form);
    
    fetch('/reports/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error generating report');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating report');
    });
}

// Schedule report function
function scheduleReport() {
    const modal = new bootstrap.Modal(document.getElementById('scheduleReportModal'));
    modal.show();
}

// Schedule report form submission
document.getElementById('scheduleReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/reports/schedule', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error scheduling report');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error scheduling report');
    });
});

// Download report function
function downloadReport(reportId) {
    window.location.href = `/reports/download/${reportId}`;
}

// View report function
function viewReport(reportId) {
    window.open(`/reports/view/${reportId}`, '_blank');
}

// Delete report function
function deleteReport(reportId) {
    if (confirm('Are you sure you want to delete this report?')) {
        fetch(`/reports/delete/${reportId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting report');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting report');
        });
    }
}
</script>

<?php require_once BASE_PATH . '/views/layout/footer.php'; ?> 