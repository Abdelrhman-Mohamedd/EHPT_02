<?php
$currentPage = 'dashboard';
include('header.php');
?>

<div class="page-header">
    <h1 class="page-title">Welcome back, Alex</h1>
    <p class="page-subtitle">VaultTech Financial Services — Secure Client & Employee Portal</p>
</div>

<div class="alert alert-info">
    <span>🔒 <strong>Notice:</strong> VaultTech uses industry-standard security practices.
    The legacy XML integration endpoint will be decommissioned in Q4 2024.</span>
</div>

<div class="grid">
    <div class="card">
        <div class="card-title">
            <span>👤 My Account</span>
            <small style="color:var(--accent-emerald);font-weight:normal;font-size:0.8rem;">Active</small>
        </div>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
            View your profile, update your password, and manage your security settings.
        </p>
        <a href="account.php" class="btn btn-primary" style="width:100%;">Go to My Account</a>
    </div>

    <div class="card">
        <div class="card-title">
            <span>🔐 Secure Token Portal</span>
            <small style="color:var(--text-muted);font-weight:normal;font-size:0.8rem;">JWT Auth</small>
        </div>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
            Access role-restricted functions using our JSON Web Token authentication system.
        </p>
        <a href="jwt_portal.php" class="btn btn-primary" style="width:100%;">JWT Portal</a>
    </div>

    <div class="card">
        <div class="card-title">
            <span>🔌 API Integration</span>
            <small style="color:var(--accent-rose);font-weight:normal;font-size:0.8rem;">Legacy EOL</small>
        </div>
        <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
            Monitor API health and access the legacy XML data processing endpoint (v1).
        </p>
        <a href="api_status.php" class="btn btn-primary" style="width:100%;">API Dashboard</a>
    </div>
</div>

<div class="card" style="margin-top:1.5rem;">
    <div class="card-title"><span>📢 System Announcements</span></div>
    <ul class="doc-list">
        <li class="doc-item">
            <div class="doc-info"><span class="doc-icon">🔔</span>
            <div><strong>Automated Database Backup Available</strong>
            <div style="font-size:0.8rem;color:var(--text-muted);">
                Nightly database export is stored at <code>/backup/</code> for authorized infrastructure staff.
            </div></div></div>
            <span style="font-size:0.8rem;color:var(--text-muted);">Today</span>
        </li>
        <li class="doc-item">
            <div class="doc-info"><span class="doc-icon">🔔</span>
            <div><strong>Frontend Bundle v2.4.1 Deployed</strong>
            <div style="font-size:0.8rem;color:var(--text-muted);">
                New portal.js shipped — minification disabled for legacy browser compatibility.
            </div></div></div>
            <span style="font-size:0.8rem;color:var(--text-muted);">Yesterday</span>
        </li>
        <li class="doc-item">
            <div class="doc-info"><span class="doc-icon">🔔</span>
            <div><strong>XML API v1 — Deprecation Notice</strong>
            <div style="font-size:0.8rem;color:var(--text-muted);">
                The <code>/api/v1/parse.php</code> endpoint uses an outdated XML library. Migrate to v2 before Q4.
            </div></div></div>
            <span style="font-size:0.8rem;color:var(--text-muted);">2 days ago</span>
        </li>
    </ul>
</div>

<?php include('footer.php'); ?>
