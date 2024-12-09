<?php require_once BASE_PATH . '/views/layout/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#snmp-settings">SNMP Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#monitoring-settings">Monitoring</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#email-settings">Email Notifications</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#system-settings">System</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- SNMP Settings -->
                        <div class="tab-pane fade show active" id="snmp-settings">
                            <form id="snmpSettingsForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Default SNMP Version</label>
                                        <select class="form-select" name="snmp_version">
                                            <option value="1">Version 1</option>
                                            <option value="2c" selected>Version 2c</option>
                                            <option value="3">Version 3</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Default Community String</label>
                                        <input type="text" class="form-control" name="community_string" value="public">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SNMP Timeout (seconds)</label>
                                        <input type="number" class="form-control" name="snmp_timeout" value="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SNMP Retries</label>
                                        <input type="number" class="form-control" name="snmp_retries" value="3">
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Monitoring Settings -->
                        <div class="tab-pane fade" id="monitoring-settings">
                            <form id="monitoringSettingsForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Monitoring Interval (minutes)</label>
                                        <input type="number" class="form-control" name="monitoring_interval" value="5">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Data Retention Period (days)</label>
                                        <input type="number" class="form-control" name="data_retention" value="30">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="auto_discovery">
                                            <label class="form-check-label">Enable Auto-Discovery</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="alert_enabled" checked>
                                            <label class="form-check-label">Enable Alert Monitoring</label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Email Settings -->
                        <div class="tab-pane fade" id="email-settings">
                            <form id="emailSettingsForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Server</label>
                                        <input type="text" class="form-control" name="smtp_server">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="smtp_port" value="587">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="smtp_username">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" name="smtp_password">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">From Email</label>
                                        <input type="email" class="form-control" name="from_email">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="smtp_auth" checked>
                                            <label class="form-check-label">Enable SMTP Authentication</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="smtp_secure" checked>
                                            <label class="form-check-label">Enable TLS/SSL</label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- System Settings -->
                        <div class="tab-pane fade" id="system-settings">
                            <form id="systemSettingsForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Time Zone</label>
                                        <select class="form-select" name="timezone">
                                            <option value="UTC">UTC</option>
                                            <option value="America/New_York">America/New_York</option>
                                            <option value="Europe/London">Europe/London</option>
                                            <option value="Asia/Tokyo">Asia/Tokyo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date Format</label>
                                        <select class="form-select" name="date_format">
                                            <option value="Y-m-d">YYYY-MM-DD</option>
                                            <option value="d/m/Y">DD/MM/YYYY</option>
                                            <option value="m/d/Y">MM/DD/YYYY</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="debug_mode">
                                            <label class="form-check-label">Enable Debug Mode</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="maintenance_mode">
                                            <label class="form-check-label">Enable Maintenance Mode</label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="button" class="btn btn-secondary" onclick="resetSettings()">Reset to Default</button>
                    <button type="button" class="btn btn-primary" onclick="saveSettings()">Save Settings</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saveSettings() {
    const activeTab = document.querySelector('.tab-pane.active');
    const form = activeTab.querySelector('form');
    const formData = new FormData(form);
    
    // Convert FormData to object
    const data = {};
    formData.forEach((value, key) => {
        // Handle checkboxes
        if (form.querySelector(`[name="${key}"]`).type === 'checkbox') {
            data[key] = form.querySelector(`[name="${key}"]`).checked;
        } else {
            data[key] = value;
        }
    });
    
    // Show loading state
    const saveBtn = document.querySelector('button[onclick="saveSettings()"]');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    saveBtn.disabled = true;

    fetch('/settings/save', {
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
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.card-body').insertBefore(alert, document.querySelector('.tab-content'));
            
            // Update form values if settings were returned
            if (data.settings) {
                Object.entries(data.settings).forEach(([key, value]) => {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = value === 'true';
                        } else {
                            input.value = value;
                        }
                    }
                });
            }
        } else {
            // Show error message
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.card-body').insertBefore(alert, document.querySelector('.tab-content'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Show error message
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger alert-dismissible fade show';
        alert.innerHTML = `
            Error saving settings. Please try again.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.card-body').insertBefore(alert, document.querySelector('.tab-content'));
    })
    .finally(() => {
        // Restore button state
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        
        // Auto-hide alert after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
}

function resetSettings() {
    if (confirm('Are you sure you want to reset settings to default values?')) {
        fetch('/settings/reset', {
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
                alert(data.message || 'Error resetting settings');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error resetting settings');
        });
    }
}
</script>

<?php require_once BASE_PATH . '/views/layout/footer.php'; ?> 