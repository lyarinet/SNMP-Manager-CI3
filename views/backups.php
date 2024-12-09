<?php
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Backup Management</h1>
    <div class="row mt-4">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Create Backup
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button id="createDbBackup" class="btn btn-primary mb-3">
                                <i class="fas fa-database me-2"></i>Create Database Backup
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button id="createFilesBackup" class="btn btn-success mb-3">
                                <i class="fas fa-file-archive me-2"></i>Create Files Backup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    Backup History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="backupsTable">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Filename</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($backups) && is_array($backups)): ?>
                                    <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <?php if ($backup['type'] === 'database'): ?>
                                                <i class="fas fa-database text-primary"></i>
                                            <?php else: ?>
                                                <i class="fas fa-file-archive text-success"></i>
                                            <?php endif; ?>
                                            <?= ucfirst($backup['type']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($backup['filename']) ?></td>
                                        <td><?= date('Y-m-d H:i:s', strtotime($backup['created_at'])) ?></td>
                                        <td>
                                            <a href="/backup/download/<?= urlencode($backup['filename']) ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                            <button class="btn btn-sm btn-danger delete-backup" 
                                                    data-filename="<?= htmlspecialchars($backup['filename']) ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#backupsTable').DataTable({
        order: [[2, 'desc']],
        pageLength: 10,
        language: {
            emptyTable: "No backups available"
        }
    });

    // Create Database Backup
    $('#createDbBackup').click(function() {
        $(this).prop('disabled', true);
        $.ajax({
            url: '/backup/createDatabase',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                showAlert('error', 'Error creating database backup');
            },
            complete: function() {
                $('#createDbBackup').prop('disabled', false);
            }
        });
    });

    // Create Files Backup
    $('#createFilesBackup').click(function() {
        $(this).prop('disabled', true);
        $.ajax({
            url: '/backup/createFiles',
            method: 'POST',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                showAlert('error', 'Error creating files backup');
            },
            complete: function() {
                $('#createFilesBackup').prop('disabled', false);
            }
        });
    });

    // Delete Backup
    $('.delete-backup').click(function() {
        const filename = $(this).data('filename');
        if (confirm('Are you sure you want to delete this backup?')) {
            $.ajax({
                url: '/backup/delete/' + encodeURIComponent(filename),
                method: 'POST',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function(xhr) {
                    showAlert('error', 'Error deleting backup');
                }
            });
        }
    });

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alert = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>');
        
        $('.container-fluid').prepend(alert);
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }
});
</script> 