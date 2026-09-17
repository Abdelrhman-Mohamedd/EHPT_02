<?php
/**
 * admin/index.php — VaultTech Admin Control Panel
 *
 * VULNERABILITY: A05:2021 — Security Misconfiguration (Default Credentials)
 *
 * The admin panel was deployed with default credentials that were never changed:
 *   Username: admin
 *   Password: admin
 *
 * Additional misconfigurations on this page:
 *   - Verbose PHP error messages leaking internal paths (display_errors = On)
 *   - Server version disclosed in HTTP headers (ServerTokens Full)
 *   - Missing security headers (no X-Frame-Options, no CSP, no X-Content-Type-Options)
 *   - Session token stored in URL (session.use_only_cookies = Off)
 *
 * Exploit steps:
 *  1. Discover /admin/ via directory enumeration (gobuster/ffuf).
 *  2. Try default credentials: admin / admin (or admin / password, admin / 1234).
 *  3. Admin dashboard renders and displays FLAG{MISCONFIG}.
 */

session_start();

// Intentional misconfiguration: session ID leaked in URL
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid',    '1');

require_once(__DIR__ . '/../student_config.php');

// Default credentials — never changed from factory defaults (A05 violation)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin');

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if ($u === ADMIN_USER && $p === ADMIN_PASS) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_user'] = $u;
    } else {
        // Misconfiguration: verbose error leaks valid username
        if ($u === ADMIN_USER) {
            $loginError = "Incorrect password for user 'admin'.";
        } else {
            $loginError = "User '$u' not found.";
        }
    }
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: /admin/');
    exit;
}

$isAuthenticated = isset($_SESSION['admin_auth']) && $_SESSION['admin_auth'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Misconfiguration: No Content-Security-Policy header -->
    <!-- Misconfiguration: No X-Frame-Options header -->
    <!-- Misconfiguration: No X-Content-Type-Options header -->
    <title>VaultTech Admin Panel — Lab02</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: radial-gradient(ellipse at top left, #1a0a2e, #0a0f1e 55%); }
        .admin-badge { display:inline-block; background:rgba(244,63,94,0.2); border:1px solid rgba(244,63,94,0.4); color:#f87171; padding:0.3rem 0.8rem; border-radius:4px; font-size:0.78rem; font-weight:600; margin-bottom:1.5rem; }
    </style>
</head>
<body>
<div class="container" style="max-width:780px;">

<?php if (!$isAuthenticated): ?>

<div class="page-header" style="margin-top:3rem; text-align:center;">
    <div style="font-size:2.5rem; margin-bottom:0.5rem;">🔒</div>
    <h1 class="page-title">Admin Control Panel</h1>
    <p class="page-subtitle">VaultTech Financial Services — Restricted Access</p>
</div>

<?php if ($loginError): ?>
<div class="alert" style="background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.3);color:#fca5a5;">
    <!-- Misconfiguration: error message reveals whether the username is valid -->
    ❌ <?= htmlspecialchars($loginError) ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Administrator Login</div>
    <form method="POST" action="/admin/">
        <div class="form-group">
            <label class="form-label" for="adm-user">Username</label>
            <input type="text" id="adm-user" name="username" class="form-control"
                   placeholder="admin" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label" for="adm-pass">Password</label>
            <input type="password" id="adm-pass" name="password" class="form-control"
                   placeholder="Enter admin password">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Login to Admin Panel</button>
    </form>
    <p style="margin-top:1.25rem;font-size:0.82rem;color:var(--text-muted);">
        What are the most commonly used default admin username/password combinations?
    </p>
</div>

<?php else: ?>

<div class="page-header" style="margin-top:2rem;">
    <div class="admin-badge">🔴 ADMIN SESSION ACTIVE</div>
    <h1 class="page-title">Admin Dashboard</h1>
    <p class="page-subtitle">VaultTech Internal Administration — Full Access</p>
</div>

<div class="alert" style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.3);color:#6ee7b7;">
    ✅ Authenticated as <strong><?= htmlspecialchars($_SESSION['admin_user']) ?></strong>
    &nbsp;|&nbsp; Session ID: <code style="font-size:0.8rem;"><?= session_id() ?></code>
    <!-- Misconfiguration: Session ID displayed and available in URL -->
</div>

<div class="card" style="border-color:rgba(244,63,94,0.4); margin-bottom:1.5rem;">
    <div class="card-title" style="color:var(--accent-rose);">🚨 Security Misconfiguration Flag</div>
    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1rem;">
        This admin panel was accessible using default credentials. The following flag confirms exploitation.
    </p>
    <div class="terminal-box">
<?php
$flagFile = '/srv/labs/lab02/data/secrets/.admin_flag';
$flag = file_exists($flagFile) ? trim(file_get_contents($flagFile)) : 'FLAG{demo_misconfig_flag}';
echo "[✓] Admin panel accessed via default credentials (admin:admin)\n\n";
echo "Security Misconfiguration Flag : " . $flag . "\n\n";
echo "Findings summary:\n";
echo "  [1] Default credentials never rotated after deployment\n";
echo "  [2] Verbose login errors reveal valid usernames\n";
echo "  [3] Session ID exposed in URL (session.use_trans_sid=On)\n";
echo "  [4] Missing security headers: CSP, X-Frame-Options, X-Content-Type-Options\n";
echo "  [5] Server version disclosed in HTTP response headers\n\n";
echo "Remediation:\n";
echo "  - Enforce password change on first admin login\n";
echo "  - Return generic 'Invalid credentials' for all failed logins\n";
echo "  - Set session.use_only_cookies = 1 in php.ini\n";
echo "  - Add security headers via Apache Header directives\n";
?>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title">📊 System Statistics</div>
    <ul class="doc-list">
        <li class="doc-item"><div class="doc-info"><span class="doc-icon">👥</span><div><strong>Total Users</strong><div style="font-size:0.8rem;color:var(--text-muted);">4 active employees + 1 system account</div></div></div><span style="color:var(--accent-emerald);">4</span></li>
        <li class="doc-item"><div class="doc-info"><span class="doc-icon">🔑</span><div><strong>API Keys Active</strong><div style="font-size:0.8rem;color:var(--text-muted);">1 hardcoded (see portal.js), 2 managed</div></div></div><span style="color:var(--accent-rose);">3</span></li>
        <li class="doc-item"><div class="doc-info"><span class="doc-icon">📋</span><div><strong>Audit Log Entries</strong><div style="font-size:0.8rem;color:var(--text-muted);">Last 30 days</div></div></div><span>1,247</span></li>
    </ul>
</div>

<form method="POST">
    <input type="hidden" name="logout" value="1">
    <button type="submit" class="btn btn-secondary">Logout</button>
</form>

<?php endif; ?>

<footer style="margin-top:2rem;text-align:center;color:var(--text-muted);font-size:0.8rem;padding:1rem 0;border-top:1px solid var(--border-color);">
    VaultTech Admin Panel | Student: <?= htmlspecialchars($GLOBALS['STUDENT_ID']) ?>
</footer>
</div>
</body>
</html>
