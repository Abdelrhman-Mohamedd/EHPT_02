<?php
/**
 * account.php — Employee Login & Account Management
 *
 * VULNERABILITY: A02:2021 — Cryptographic Failures (Weak MD5 Password Hashing)
 *
 * Exploit chain:
 *  1. Student discovers /backup/ directory listing (Apache misconfiguration).
 *  2. Downloads users.db.sql — sees MD5 hashes with no salt.
 *  3. Cracks admin MD5 hash using hashcat/john against rockyou.txt.
 *  4. Logs in as 'admin' — admin panel renders and shows FLAG{WEAKHASH}.
 *
 * Root cause: password_hash() / bcrypt was never used; md5() was kept from
 * a 2018 legacy codebase migration that was never completed.
 */

$currentPage = 'account';
include('header.php');

// Simulated user store — mirrors users.db.sql (MD5, no salt)
$users = [
    'admin'       => ['hash' => md5('vaulttech2024'), 'role' => 'admin',    'email' => 'admin@vaulttech.com'],
    'j.smith'     => ['hash' => md5('password'),      'role' => 'employee', 'email' => 'j.smith@vaulttech.com'],
    'm.williams'  => ['hash' => md5('letmein'),       'role' => 'manager',  'email' => 'm.williams@vaulttech.com'],
    'b.chen'      => ['hash' => md5('123456'),        'role' => 'employee', 'email' => 'b.chen@vaulttech.com'],
];

$loginMessage = '';
$loginSuccess = false;
$loggedInUser = null;
$debugHash    = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $username  = $_POST['username'] ?? '';
    $password  = $_POST['password'] ?? '';
    $inputHash = md5($password); // VULNERABILITY: MD5, no salt

    if (isset($users[$username])) {
        if (hash_equals($users[$username]['hash'], $inputHash)) {
            $loginSuccess = true;
            $loggedInUser = $username;
            $loginMessage = 'success';
        } else {
            $loginMessage = 'error';
            // VULNERABILITY: Leaks hash of attempted password in error response
            $debugHash = $inputHash;
        }
    } else {
        $loginMessage = 'error';
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Employee Account</h1>
    <p class="page-subtitle">Secure login to manage your credentials and account settings</p>
</div>

<?php if ($loginMessage === 'success' && $loggedInUser): ?>

<div class="alert alert-success">
    ✅ Authenticated as <strong><?= htmlspecialchars($loggedInUser) ?></strong>
    &nbsp;|&nbsp; Role: <strong><?= htmlspecialchars($users[$loggedInUser]['role']) ?></strong>
</div>

<?php if ($users[$loggedInUser]['role'] === 'admin'): ?>
<div class="card" style="border-color:rgba(245,158,11,0.4); margin-bottom:1.5rem;">
    <div class="card-title" style="color:var(--accent-amber);">🔑 Admin Credential Vault</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
        Administrative access confirmed. The following credential-vault flag is bound to this deployment.
    </p>
    <div class="terminal-box">
<?php
    $dbFile = '/srv/labs/lab02/public/backup/users.db.sql';
    if (file_exists($dbFile) && preg_match('/FLAG\{[a-f0-9]+\}/', file_get_contents($dbFile), $m)) {
        echo "[✓] Admin login accepted\n";
        echo "Credential Vault Flag : " . $m[0] . "\n\n";
        echo "[!] Security Finding:\n";
        echo "    Passwords stored as unsalted MD5.\n";
        echo "    Admin hash '" . htmlspecialchars($users['admin']['hash']) . "' cracked from rockyou.txt.\n";
        echo "    Fix: Use password_hash(\$pass, PASSWORD_BCRYPT, ['cost'=>12]) + password_verify().\n";
    } else {
        echo "[✓] Admin login accepted\nCredential Vault Flag: FLAG{demo_weakhash_flag}\n";
    }
?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">Account Details</div>
    <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
        <tr style="border-bottom:1px solid var(--border-color);">
            <td style="padding:0.75rem;color:var(--text-muted);width:160px;">Username</td>
            <td style="padding:0.75rem;"><?= htmlspecialchars($loggedInUser) ?></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border-color);">
            <td style="padding:0.75rem;color:var(--text-muted);">Email</td>
            <td style="padding:0.75rem;"><?= htmlspecialchars($users[$loggedInUser]['email']) ?></td>
        </tr>
        <tr style="border-bottom:1px solid var(--border-color);">
            <td style="padding:0.75rem;color:var(--text-muted);">Role</td>
            <td style="padding:0.75rem;"><?= htmlspecialchars($users[$loggedInUser]['role']) ?></td>
        </tr>
        <tr>
            <td style="padding:0.75rem;color:var(--text-muted);">Password Hash</td>
            <!-- VULNERABILITY: Displaying the raw MD5 hash to the logged-in user -->
            <td style="padding:0.75rem;font-family:monospace;font-size:0.82rem;color:var(--accent-amber);">
                <?= htmlspecialchars($users[$loggedInUser]['hash']) ?>
                <span style="font-size:0.7rem;color:var(--text-muted);">(MD5, no salt)</span>
            </td>
        </tr>
    </table>
</div>

<?php elseif ($loginMessage === 'error'): ?>
<div class="alert" style="background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.3);color:#fca5a5;">
    ❌ <strong>Login failed.</strong> Invalid username or password.
    <?php if ($debugHash): ?>
    <br><small style="font-family:monospace;color:var(--text-muted);">
        [Debug] Computed hash: <?= htmlspecialchars($debugHash) ?>
    </small>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$loginSuccess): ?>
<div class="card">
    <div class="card-title">🔐 Employee Portal Login</div>
    <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:1.5rem;">
        Enter your employee credentials to access your account.
    </p>
    <form method="POST" action="account.php">
        <input type="hidden" name="action" value="login">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="form-control"
                   placeholder="e.g. admin, j.smith" autocomplete="off">
        </div>
        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;">Sign In</button>
    </form>
    <p style="margin-top:1.5rem;font-size:0.82rem;color:var(--text-muted);">
        How are passwords stored? Are they recoverable?
    </p>
</div>
<?php endif; ?>

<?php include('footer.php'); ?>
