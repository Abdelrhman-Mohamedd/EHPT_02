<?php
$currentPage = 'reports';
include('header.php');
?>

<div class="page-header">
    <h1 class="page-title">Financial Reports</h1>
    <p class="page-subtitle">Access quarterly financial statements and audit logs</p>
</div>

<div class="card">
    <div class="card-title">Deprecation Notice</div>
    <div class="alert alert-info" style="background: rgba(234, 179, 8, 0.1); border-left: 4px solid #eab308; padding: 15px;">
        <strong>Notice:</strong> The legacy file-based reports system has been deprecated and removed. All financial reporting is now handled via the REST API v2.
    </div>
    
    <p style="margin-top: 20px;">
        Please use the <a href="api_status.php" style="color: var(--accent-cyan);">API Integration Dashboard</a> to manage your API keys and fetch the latest reports programmatically.
    </p>
</div>

<?php include('footer.php'); ?>

