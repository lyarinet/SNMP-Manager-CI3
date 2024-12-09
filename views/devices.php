<?php require_once BASE_PATH . '/views/layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Devices</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
            <i class="fas fa-plus me-2"></i> Add Device
        </button>
    </div>

    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?> alert-dismissible fade show">
            <?php 
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Devices Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>SNMP Version</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Last Check</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($devices) && !empty($devices)): ?>
                            <?php foreach($devices as $device): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($device['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($device['snmp_version']); ?></td>
                                <td><?php echo htmlspecialchars($device['description']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $device['status'] === 'active' ? 'success' : 
                                            ($device['status'] === 'error' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($device['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($device['updated_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info me-1" onclick="monitorDevice(<?php echo $device['id']; ?>)">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning me-1" onclick="editDevice(<?php echo $device['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDevice(<?php echo $device['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No devices found. Add a device to start monitoring.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addDeviceForm">
                <div class="modal-body">
                    <div id="formAlerts"></div>
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" required 
                               pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                               placeholder="192.168.1.1">
                    </div>
                    <div class="mb-3">
                        <label for="snmp_version" class="form-label">SNMP Version</label>
                        <select class="form-select" id="snmp_version" name="snmp_version" required>
                            <option value="1">Version 1</option>
                            <option value="2c" selected>Version 2c</option>
                            <option value="3">Version 3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="community_string" class="form-label">Community String</label>
                        <input type="password" class="form-control" id="community_string" name="community_string" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Device Modal -->
<div class="modal fade" id="editDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Device</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDeviceForm">
                <div class="modal-body">
                    <div id="editFormAlerts"></div>
                    <input type="hidden" id="edit_device_id" name="id">
                    <div class="mb-3">
                        <label for="edit_ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="edit_ip_address" name="ip_address" required 
                               pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                               placeholder="192.168.1.1">
                    </div>
                    <div class="mb-3">
                        <label for="edit_snmp_version" class="form-label">SNMP Version</label>
                        <select class="form-select" id="edit_snmp_version" name="snmp_version" required>
                            <option value="1">Version 1</option>
                            <option value="2c">Version 2c</option>
                            <option value="3">Version 3</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_community_string" class="form-label">Community String</label>
                        <input type="password" class="form-control" id="edit_community_string" name="community_string" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Device Monitoring Modal -->
<div class="modal fade" id="monitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Device Monitoring</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- System Information -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">System Information</h6>
                            </div>
                            <div class="card-body">
                                <div id="systemInfo">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Performance Metrics -->
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="card-title mb-0">Performance Metrics</h6>
                            </div>
                            <div class="card-body">
                                <div id="performanceMetrics">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Interface Status -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">Interface Status</h6>
                    </div>
                    <div class="card-body">
                        <div id="interfaceStatus">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Network Connections -->
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">Network Connections</h6>
                    </div>
                    <div class="card-body">
                        <div id="networkConnections">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form submission handler
document.getElementById('addDeviceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Clear previous alerts
    const alertsContainer = document.getElementById('formAlerts');
    alertsContainer.innerHTML = '';
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
    
    // Get form data
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    try {
        console.log('Sending request to add device:', data);
        
        // Send AJAX request
        const response = await fetch('/devices', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries([...response.headers]));

        // Get response text
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        // Try to parse JSON
        let result;
        try {
            result = responseText ? JSON.parse(responseText) : null;
            console.log('Parsed response:', result);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error(`Server returned invalid JSON response: ${responseText.substring(0, 100)}...`);
        }

        // Check if we have a valid result
        if (!result) {
            throw new Error('Empty response from server');
        }

        if (result.success) {
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${result.message || 'Device added successfully!'}
            `;
            alertsContainer.appendChild(successAlert);
            
            // Close modal and reload page after a short delay
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addDeviceModal'));
                modal.hide();
                location.reload();
            }, 1500);
        } else {
            // Show error message
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>
                ${result.message || 'Error adding device'}
            `;
            alertsContainer.appendChild(errorAlert);
            
            // Reset button
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Request failed:', error);
        
        // Show error message
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger';
        errorAlert.innerHTML = `
            <i class="fas fa-times-circle me-2"></i>
            ${error.message || 'Failed to add device. Please try again.'}
        `;
        alertsContainer.appendChild(errorAlert);
        
        // Reset button
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
});

// Device monitoring function
function monitorDevice(deviceId) {
    const modal = new bootstrap.Modal(document.getElementById('monitorModal'));
    modal.show();
    
    // Show loading spinners
    const loadingSpinner = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    document.getElementById('systemInfo').innerHTML = loadingSpinner;
    document.getElementById('performanceMetrics').innerHTML = loadingSpinner;
    document.getElementById('interfaceStatus').innerHTML = loadingSpinner;
    
    // Fetch device metrics
    fetch(`/monitor/${deviceId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Failed to fetch device metrics');
            }
            updateMonitoringData(data.data);
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = `<div class="alert alert-danger mb-0">${error.message}</div>`;
            document.getElementById('systemInfo').innerHTML = errorMessage;
            document.getElementById('performanceMetrics').innerHTML = errorMessage;
            document.getElementById('interfaceStatus').innerHTML = errorMessage;
        });
}

// Update monitoring data
function updateMonitoringData(data) {
    // Update system information
    if (data.system) {
        const systemInfo = `
            <table class="table table-sm mb-0">
                <tbody>
                    <tr>
                        <th width="30%">Description</th>
                        <td>${escapeHtml(data.system.description || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Operating System</th>
                        <td>${escapeHtml(data.system.os || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Hostname</th>
                        <td>${escapeHtml(data.system.hostname || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Uptime</th>
                        <td>${escapeHtml(data.system.uptime || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td>${escapeHtml(data.system.contact || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Location</th>
                        <td>${escapeHtml(data.system.location || 'N/A')}</td>
                    </tr>
                    <tr>
                        <th>Services</th>
                        <td>
                            ${Object.entries(data.system.services || {}).map(([service, status]) => `
                                <span class="badge bg-${status === 'Running' ? 'success' : 'danger'} me-1">
                                    ${escapeHtml(service)}: ${escapeHtml(status)}
                                </span>
                            `).join('')}
                        </td>
                    </tr>
                </tbody>
            </table>
        `;
        document.getElementById('systemInfo').innerHTML = systemInfo;
    } else {
        document.getElementById('systemInfo').innerHTML = '<div class="alert alert-warning mb-0">No system information available</div>';
    }

    // Update performance metrics
    if (data.performance) {
        const metrics = `
            <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                    <span>CPU Usage</span>
                    <span>${data.performance.cpu}%</span>
                </label>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getProgressBarClass(data.performance.cpu)}" 
                         role="progressbar" 
                         style="width: ${data.performance.cpu}%">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                    <span>Memory Usage</span>
                    <span>${data.performance.memory}%</span>
                </label>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getProgressBarClass(data.performance.memory)}" 
                         role="progressbar" 
                         style="width: ${data.performance.memory}%">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label d-flex justify-content-between">
                    <span>Disk Usage</span>
                    <span>${data.performance.disk}%</span>
                </label>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getProgressBarClass(data.performance.disk)}" 
                         role="progressbar" 
                         style="width: ${data.performance.disk}%">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body p-2 text-center">
                            <small class="text-muted">Network In</small>
                            <h5 class="mb-0">${escapeHtml(data.performance.network.in_traffic)}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body p-2 text-center">
                            <small class="text-muted">Network Out</small>
                            <h5 class="mb-0">${escapeHtml(data.performance.network.out_traffic)}</h5>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.getElementById('performanceMetrics').innerHTML = metrics;
    } else {
        document.getElementById('performanceMetrics').innerHTML = '<div class="alert alert-warning mb-0">No performance metrics available</div>';
    }

    // Update network connections
    if (data.connections && Array.isArray(data.connections)) {
        if (data.connections.length > 0) {
            const connectionsHtml = `
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Protocol</th>
                                <th>Local Address</th>
                                <th>Remote Address</th>
                                <th>State</th>
                                <th>Process</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.connections.map(conn => `
                                <tr>
                                    <td>
                                        <span class="badge bg-${conn.protocol === 'TCP' ? 'primary' : 'info'}">
                                            ${escapeHtml(conn.protocol)}
                                        </span>
                                    </td>
                                    <td>
                                        ${escapeHtml(conn.local_address)}:${escapeHtml(conn.local_port)}
                                    </td>
                                    <td>
                                        ${conn.remote_address === '*' ? '*' : 
                                          `${escapeHtml(conn.remote_address)}:${escapeHtml(conn.remote_port)}`}
                                    </td>
                                    <td>
                                        <span class="badge bg-${getConnectionStateClass(conn.state)}">
                                            ${escapeHtml(conn.state)}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-${conn.process === 'unknown' ? 'muted' : 'dark'}">
                                            ${escapeHtml(conn.process)}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
            document.getElementById('networkConnections').innerHTML = connectionsHtml;
        } else {
            document.getElementById('networkConnections').innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No active network connections found on this device.
                </div>
            `;
        }
    } else {
        document.getElementById('networkConnections').innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="fas fa-times-circle me-2"></i>
                Failed to fetch network connections. Please check:
                <ul class="mb-0 mt-2">
                    <li>SNMP service is running on the device</li>
                    <li>Community string has access to TCP/UDP tables</li>
                    <li>Network connectivity to the device</li>
                </ul>
            </div>
        `;
    }

    // Update interface status with additional information
    if (data.interfaces && Array.isArray(data.interfaces)) {
        if (data.interfaces.length > 0) {
            const interfaces = data.interfaces.map(iface => `
                <div class="card mb-2">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="mb-0">
                                    ${escapeHtml(iface.name)}
                                    ${iface.type !== 'unknown' ? `<small class="text-muted">(${escapeHtml(iface.type)})</small>` : ''}
                                </h6>
                                <small class="text-muted">
                                    ${iface.mac_address !== 'N/A' ? `MAC: ${escapeHtml(iface.mac_address)} | ` : ''}
                                    ${iface.ip_address !== 'N/A' ? `IP: ${escapeHtml(iface.ip_address)} | ` : ''}
                                    MTU: ${escapeHtml(iface.mtu)}
                                </small>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge bg-${iface.admin_status === 'up' ? 'success' : 'warning'} me-1">
                                        Admin: ${escapeHtml(iface.admin_status.toUpperCase())}
                                    </span>
                                    <span class="badge bg-${iface.status === 'up' ? 'success' : 'danger'}">
                                        Oper: ${escapeHtml(iface.status.toUpperCase())}
                                    </span>
                                </div>
                                <span class="badge bg-info">${escapeHtml(iface.speed)}</span>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-2">Traffic</h6>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>In:</small>
                                            <span>${formatBytes(iface.statistics.in_octets)}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small>Out:</small>
                                            <span>${formatBytes(iface.statistics.out_octets)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body p-2">
                                        <h6 class="card-title mb-2">Errors/Discards</h6>
                                        <div class="d-flex justify-content-between mb-1">
                                            <small>Errors (In/Out):</small>
                                            <span class="text-${iface.statistics.in_errors + iface.statistics.out_errors > 0 ? 'danger' : 'muted'}">
                                                ${iface.statistics.in_errors}/${iface.statistics.out_errors}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <small>Discards (In/Out):</small>
                                            <span class="text-${iface.statistics.in_discards + iface.statistics.out_discards > 0 ? 'warning' : 'muted'}">
                                                ${iface.statistics.in_discards}/${iface.statistics.out_discards}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            document.getElementById('interfaceStatus').innerHTML = interfaces;
        } else {
            document.getElementById('interfaceStatus').innerHTML = `
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No network interfaces were found on this device. This might be because:
                    <ul class="mb-0 mt-2">
                        <li>The device doesn't expose interface information via SNMP</li>
                        <li>The SNMP community string doesn't have permission to access interface data</li>
                        <li>The device's SNMP agent is not properly configured</li>
                    </ul>
                </div>
            `;
        }
    } else {
        document.getElementById('interfaceStatus').innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="fas fa-times-circle me-2"></i>
                Failed to fetch interface information. Please check:
                <ul class="mb-0 mt-2">
                    <li>SNMP service is running on the device</li>
                    <li>Community string is correct</li>
                    <li>Network connectivity to the device</li>
                    <li>Firewall settings (UDP port 161)</li>
                </ul>
            </div>
        `;
    }
}

// Helper function to get progress bar class based on value
function getProgressBarClass(value) {
    if (value >= 90) return 'bg-danger';
    if (value >= 75) return 'bg-warning';
    if (value >= 50) return 'bg-info';
    return 'bg-success';
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return 'N/A';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Helper function to get connection state class
function getConnectionStateClass(state) {
    switch (state.toUpperCase()) {
        case 'ESTABLISHED':
            return 'success';
        case 'LISTEN':
        case 'LISTENING':
            return 'info';
        case 'TIME_WAIT':
        case 'CLOSE_WAIT':
            return 'warning';
        case 'SYN_SENT':
        case 'SYN_RECEIVED':
            return 'primary';
        case 'CLOSED':
        case 'CLOSING':
        case 'LAST_ACK':
        case 'DELETE_TCB':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Delete device
function deleteDevice(deviceId) {
    if (confirm('Are you sure you want to delete this device?')) {
        fetch(`/devices/delete/${deviceId}`, {
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
                alert(data.message || 'Error deleting device');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting device');
        });
    }
}

// Clear modal on hide
document.getElementById('addDeviceModal').addEventListener('hidden.bs.modal', function () {
    // Remove any existing alerts
    this.querySelector('#formAlerts').innerHTML = '';
    // Reset the form
    document.getElementById('addDeviceForm').reset();
    // Reset submit button
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = false;
    submitButton.innerHTML = 'Add Device';
});

// Add this helper function for formatting bytes
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Edit device function
async function editDevice(deviceId) {
    try {
        // Show loading state
        const response = await fetch(`/devices/${deviceId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch device data');
        }

        // Populate form with device data
        document.getElementById('edit_device_id').value = data.device.id;
        document.getElementById('edit_ip_address').value = data.device.ip_address;
        document.getElementById('edit_snmp_version').value = data.device.snmp_version;
        document.getElementById('edit_community_string').value = data.device.community_string;
        document.getElementById('edit_description').value = data.device.description;

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editDeviceModal'));
        modal.show();
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading device data: ' + error.message);
    }
}

// Edit device form submission
document.getElementById('editDeviceForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Clear previous alerts
    const alertsContainer = document.getElementById('editFormAlerts');
    alertsContainer.innerHTML = '';
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    // Get form data
    const formData = new FormData(this);
    const deviceId = formData.get('id');
    const data = Object.fromEntries(formData.entries());
    
    try {
        // Send AJAX request
        const response = await fetch(`/devices/${deviceId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                ${result.message || 'Device updated successfully!'}
            `;
            alertsContainer.appendChild(successAlert);
            
            // Close modal and reload page after a short delay
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editDeviceModal'));
                modal.hide();
                location.reload();
            }, 1500);
        } else {
            // Show error message
            const errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger';
            errorAlert.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>
                ${result.message || 'Error updating device'}
            `;
            alertsContainer.appendChild(errorAlert);
            
            // Reset button
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Error:', error);
        
        // Show error message
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger';
        errorAlert.innerHTML = `
            <i class="fas fa-times-circle me-2"></i>
            ${error.message || 'Failed to update device. Please try again.'}
        `;
        alertsContainer.appendChild(errorAlert);
        
        // Reset button
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    }
});

// Clear edit modal on hide
document.getElementById('editDeviceModal').addEventListener('hidden.bs.modal', function () {
    // Remove any existing alerts
    this.querySelector('#editFormAlerts').innerHTML = '';
    // Reset the form
    document.getElementById('editDeviceForm').reset();
    // Reset submit button
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = false;
    submitButton.innerHTML = 'Save Changes';
});
</script>

<?php require_once BASE_PATH . '/views/layout/footer.php'; ?>