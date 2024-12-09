<?php 
require_once BASE_PATH . '/views/layout/header.php';

// Initialize variables with default values if not set
$metrics = $metrics ?? [
    'total_devices' => 0,
    'active_devices' => 0,
    'inactive_devices' => 0,
    'error_devices' => 0,
    'total_alerts' => 0,
    'critical_issues' => 0,
    'unread_alerts' => 0
];

$chart_data = $chart_data ?? [
    'labels' => [],
    'cpu' => [],
    'memory' => []
];

$recent_alerts = $recent_alerts ?? [];
?>

<div class="container-fluid py-4">
    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm bg-primary bg-opacity-10 rounded">
                                <i class="fas fa-server fa-fw text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Total Devices</h6>
                            <h4 class="mb-0"><?php echo htmlspecialchars($metrics['total_devices']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm bg-success bg-opacity-10 rounded">
                                <i class="fas fa-check-circle fa-fw text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Active Devices</h6>
                            <h4 class="mb-0"><?php echo htmlspecialchars($metrics['active_devices']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm bg-warning bg-opacity-10 rounded">
                                <i class="fas fa-exclamation-triangle fa-fw text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Alerts</h6>
                            <h4 class="mb-0"><?php echo htmlspecialchars($metrics['total_alerts']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm bg-danger bg-opacity-10 rounded">
                                <i class="fas fa-times-circle fa-fw text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="card-title mb-1">Critical Issues</h6>
                            <h4 class="mb-0"><?php echo htmlspecialchars($metrics['critical_issues']); ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">System Performance</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="performanceOptions" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" data-range="1h">Last Hour</a></li>
                            <li><a class="dropdown-item" href="#" data-range="24h">Last 24 Hours</a></li>
                            <li><a class="dropdown-item" href="#" data-range="7d">Last 7 Days</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Device Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="260"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Alerts</h5>
            <a href="/alerts" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Alert Type</th>
                        <th>Message</th>
                        <th>Severity</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($recent_alerts)): ?>
                        <?php foreach($recent_alerts as $alert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($alert['device_name'] ?? 'Unknown Device'); ?></td>
                            <td><?php echo htmlspecialchars($alert['alert_type'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($alert['message'] ?? ''); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo isset($alert['severity']) ? 
                                        ($alert['severity'] === 'critical' ? 'danger' : 
                                        ($alert['severity'] === 'high' ? 'warning' : 
                                        ($alert['severity'] === 'medium' ? 'info' : 'success'))) : 'secondary'; 
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($alert['severity'] ?? 'unknown')); ?>
                                </span>
                            </td>
                            <td><?php echo isset($alert['created_at']) ? date('Y-m-d H:i:s', strtotime($alert['created_at'])) : 'N/A'; ?></td>
                            <td>
                                <?php if(isset($alert['id'])): ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No recent alerts</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Charts JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Performance Chart
const performanceCtx = document.getElementById('performanceChart').getContext('2d');
const performanceChart = new Chart(performanceCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_data['labels']); ?>,
        datasets: [
            {
                label: 'CPU Usage',
                data: <?php echo json_encode($chart_data['cpu']); ?>,
                borderColor: '#0d6efd',
                tension: 0.4,
                fill: false
            },
            {
                label: 'Memory Usage',
                data: <?php echo json_encode($chart_data['memory']); ?>,
                borderColor: '#198754',
                tension: 0.4,
                fill: false
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    callback: function(value) {
                        return value + '%';
                    }
                }
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Inactive', 'Error'],
        datasets: [{
            data: [
                <?php echo $metrics['active_devices']; ?>,
                <?php echo $metrics['inactive_devices']; ?>,
                <?php echo $metrics['error_devices']; ?>
            ],
            backgroundColor: ['#198754', '#6c757d', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
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

// Update chart data based on time range
document.querySelectorAll('[data-range]').forEach(item => {
    item.addEventListener('click', function(e) {
        e.preventDefault();
        const range = this.dataset.range;
        
        fetch(`/dashboard/chart-data?range=${range}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                performanceChart.data.labels = data.chart_data.labels;
                performanceChart.data.datasets[0].data = data.chart_data.cpu;
                performanceChart.data.datasets[1].data = data.chart_data.memory;
                performanceChart.update();
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
});
</script>

<?php require_once BASE_PATH . '/views/layout/footer.php'; ?> 