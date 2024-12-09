<?php require_once BASE_PATH . '/views/layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Alert Management</h5>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                            <i class="fas fa-plus me-2"></i>Add New Alert
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <select class="form-select" id="deviceFilter">
                                <option value="">All Devices</option>
                                <?php foreach($devices as $device): ?>
                                    <option value="<?php echo $device['id']; ?>">
                                        <?php echo htmlspecialchars($device['ip_address']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="severityFilter">
                                <option value="">All Severities</option>
                                <option value="critical">Critical</option>
                                <option value="warning">Warning</option>
                                <option value="info">Info</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Statuses</option>
                                <option value="triggered">Triggered</option>
                                <option value="active">Active</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="metricFilter">
                                <option value="">All Metrics</option>
                                <option value="cpu_usage">CPU Usage</option>
                                <option value="memory_usage">Memory Usage</option>
                                <option value="disk_usage">Disk Usage</option>
                                <option value="interface_status">Interface Status</option>
                                <option value="response_time">Response Time</option>
                            </select>
                        </div>
                    </div>

                    <!-- Alerts Table -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>Metric</th>
                                    <th>Threshold</th>
                                    <th>Last Value</th>
                                    <th>Severity</th>
                                    <th>Status</th>
                                    <th>Last Triggered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($alerts)): ?>
                                    <?php foreach($alerts as $alert): ?>
                                        <tr class="alert-row" 
                                            data-device="<?php echo $alert['device_id']; ?>"
                                            data-severity="<?php echo $alert['severity']; ?>"
                                            data-status="<?php echo $alert['status']; ?>"
                                            data-metric="<?php echo $alert['metric']; ?>">
                                            <td><?php echo htmlspecialchars($alert['device_name'] ?? 'Unknown Device'); ?></td>
                                            <td>
                                                <?php
                                                    $metricNames = [
                                                        'cpu_usage' => 'CPU Usage',
                                                        'memory_usage' => 'Memory Usage',
                                                        'disk_usage' => 'Disk Usage',
                                                        'interface_status' => 'Interface Status',
                                                        'response_time' => 'Response Time'
                                                    ];
                                                    echo $metricNames[$alert['metric']] ?? $alert['metric'];
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    echo $alert['condition'] . ' ' . $alert['threshold'];
                                                    if (in_array($alert['metric'], ['cpu_usage', 'memory_usage', 'disk_usage'])) {
                                                        echo '%';
                                                    } elseif ($alert['metric'] === 'response_time') {
                                                        echo 'ms';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if ($alert['last_value'] !== null) {
                                                        echo $alert['last_value'];
                                                        if (in_array($alert['metric'], ['cpu_usage', 'memory_usage', 'disk_usage'])) {
                                                            echo '%';
                                                        } elseif ($alert['metric'] === 'response_time') {
                                                            echo 'ms';
                                                        }
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $alert['severity'] === 'critical' ? 'danger' : 
                                                        ($alert['severity'] === 'warning' ? 'warning' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($alert['severity']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $alert['status'] === 'triggered' ? 'danger' : 
                                                        ($alert['status'] === 'resolved' ? 'success' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($alert['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    echo $alert['last_triggered'] ? 
                                                        date('Y-m-d H:i:s', strtotime($alert['last_triggered'])) : 
                                                        'Never';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if($alert['status'] === 'triggered'): ?>
                                                    <button class="btn btn-sm btn-success me-1" onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-info me-1" onclick="editAlert(<?php echo $alert['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteAlert(<?php echo $alert['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No alerts found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Alert Modal -->
<div class="modal fade" id="addAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addAlertForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Device</label>
                        <select class="form-select" name="device_id" required>
                            <?php foreach($devices as $device): ?>
                                <option value="<?php echo $device['id']; ?>">
                                    <?php echo htmlspecialchars($device['ip_address']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metric</label>
                        <select class="form-select" name="metric" required>
                            <option value="cpu_usage">CPU Usage</option>
                            <option value="memory_usage">Memory Usage</option>
                            <option value="disk_usage">Disk Usage</option>
                            <option value="interface_status">Interface Status</option>
                            <option value="response_time">Response Time</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Condition</label>
                        <select class="form-select" name="condition" required>
                            <option value=">">Greater than</option>
                            <option value="<">Less than</option>
                            <option value="=">Equal to</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Threshold</label>
                        <input type="number" class="form-control" name="threshold" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select class="form-select" name="severity" required>
                            <option value="critical">Critical</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Filter functionality
function applyFilters() {
    const device = document.getElementById('deviceFilter').value;
    const severity = document.getElementById('severityFilter').value;
    const status = document.getElementById('statusFilter').value;
    const metric = document.getElementById('metricFilter').value;

    document.querySelectorAll('.alert-row').forEach(row => {
        let show = true;

        if (device && row.dataset.device !== device) show = false;
        if (severity && row.dataset.severity !== severity) show = false;
        if (status && row.dataset.status !== status) show = false;
        if (metric && row.dataset.metric !== metric) show = false;

        row.style.display = show ? '' : 'none';
    });
}

// Add event listeners to filters
document.getElementById('deviceFilter').addEventListener('change', applyFilters);
document.getElementById('severityFilter').addEventListener('change', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('metricFilter').addEventListener('change', applyFilters);

// Add Alert form submission
document.getElementById('addAlertForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    fetch('/alerts/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Error adding alert');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding alert');
    });
});

// Acknowledge Alert
function acknowledgeAlert(alertId) {
    if (confirm('Are you sure you want to acknowledge this alert?')) {
        fetch(`/alerts/acknowledge/${alertId}`, {
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
                alert(data.message || 'Error acknowledging alert');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error acknowledging alert');
        });
    }
}

// Delete Alert
function deleteAlert(alertId) {
    if (confirm('Are you sure you want to delete this alert?')) {
        fetch(`/alerts/delete/${alertId}`, {
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
                alert(data.message || 'Error deleting alert');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting alert');
        });
    }
}

// Edit Alert
function editAlert(alertId) {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon!');
}
</script>

<?php require_once BASE_PATH . '/views/layout/footer.php'; ?> 