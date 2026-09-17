<?php
/**
 * jwt_portal.php — VaultTech JWT-Authenticated Admin Area
 *
 * VULNERABILITY: A02:2021 — Cryptographic Failures (JWT 'none' Algorithm Attack)
 *
 * The portal issues a JWT token signed with HS256 using a weak hardcoded secret.
 * The token verification code ALSO accepts the 'none' algorithm, meaning a student
 * can forge a JWT with {"alg":"none"} and an arbitrary payload (e.g. "role":"admin")
 * to bypass authentication entirely and access the admin flag.
 *
 * Attack vector:
 *   1. Get a valid JWT by logging in with any credentials (role=employee).
 *   2. Decode the base64url payload, change "role" to "admin".
 *   3. Re-encode header with {"alg":"none"}, keep modified payload, strip signature.
 *   4. Submit the forged token to the portal: ?token=<header>.<payload>.
 *   5. The server accepts it and reveals Flag 3 (JWT).
 */

$currentPage = 'account'; // shows as Account in nav
include('header.php');

// ---- Minimal JWT helpers (intentionally vulnerable) ----
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function base64url_decode(string $data): string {
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

// VULNERABILITY: Hardcoded weak HS256 secret
define('JWT_SECRET', 'vaulttech-jwt-secret-2024');

function issue_jwt(string $username, string $role): string {
    $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode([
        'sub'  => $username,
        'role' => $role,
        'iat'  => time(),
        'exp'  => time() + 3600,
    ]));
    $sig = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function verify_jwt(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) < 2) return null; // need at least header + payload

    [$headerB64, $payloadB64] = $parts;
    $sigB64 = $parts[2] ?? '';

    $header  = json_decode(base64url_decode($headerB64), true);
    $payload = json_decode(base64url_decode($payloadB64), true);

    if (!$header || !$payload) return null;

    $alg = $header['alg'] ?? 'HS256';

    // VULNERABILITY: 'none' algorithm accepted — signature check skipped entirely
    if ($alg === 'none' || $alg === 'None' || $alg === 'NONE') {
        // No signature validation — trust the payload blindly
        return $payload;
    }

    // HS256 path
    $expectedSig = base64url_encode(hash_hmac('sha256', "$headerB64.$payloadB64", JWT_SECRET, true));
    if (!hash_equals($expectedSig, $sigB64)) return null;

    return $payload;
}

// ---- Simulated users for JWT issuance ----
$validUsers = [
    'employee1' => 'emp2024',
    'analyst'   => 'an@lyst99',
];

$issuedToken = null;
$loginError  = null;
$jwtPayload  = null;
$tokenError  = null;
$flagContent = null;

// Step 1: Issue a JWT token via login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'jwt_login') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    if (isset($validUsers[$u]) && $validUsers[$u] === $p) {
        $issuedToken = issue_jwt($u, 'employee');
    } else {
        $loginError = "Invalid credentials.";
    }
}

// Step 2: Submit a JWT token to access the admin area
if (isset($_GET['token']) && strlen($_GET['token']) > 0) {
    $jwtPayload = verify_jwt($_GET['token']);
    if (!$jwtPayload) {
        $tokenError = "Token verification failed. Invalid or malformed JWT.";
    } else {
        // Check if admin role — flag is revealed
        if (($jwtPayload['role'] ?? '') === 'admin') {
            $flagFile = '/etc/lab_jwt_flag';
            if (file_exists($flagFile)) {
                $flagContent = trim(file_get_contents($flagFile));
            } else {
                $flagContent = 'FLAG{demo_jwt_flag}';
            }
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Secure JWT Portal</h1>
    <p class="page-subtitle">Token-based authentication for restricted administrative functions</p>
</div>

<div class="alert alert-info">
    <span>🔑 This portal uses <strong>JSON Web Tokens (JWT)</strong> for stateless authentication. Log in below to receive your token, then use it to access restricted areas.</span>
</div>

<!-- Step 1: Login to get a token -->
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title">Step 1 — Obtain Your JWT Token</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        Authenticate with your employee credentials to receive a signed JWT.
    </p>
    <form method="POST" action="jwt_portal.php">
        <input type="hidden" name="action" value="jwt_login">
        <div class="form-group">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" placeholder="e.g. employee1">
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter password">
        </div>
        <button type="submit" class="btn btn-primary">Get JWT Token</button>
    </form>

    <?php if ($loginError): ?>
    <div class="alert" style="background:rgba(244,63,94,0.15); border:1px solid rgba(244,63,94,0.3); color:#fca5a5; margin-top:1rem;">
        ❌ <?= htmlspecialchars($loginError) ?>
    </div>
    <?php endif; ?>

    <?php if ($issuedToken): ?>
    <div style="margin-top:1.25rem;">
        <p style="font-size:0.85rem; color:var(--accent-emerald); margin-bottom:0.5rem;">✅ Token issued (role: <strong>employee</strong>):</p>
        <div class="terminal-box" style="word-break:break-all;"><?= htmlspecialchars($issuedToken) ?></div>
        <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.75rem;">
            💡 <strong>Hint:</strong> JWT tokens are just base64url-encoded JSON. Decode the header and payload.
            What algorithm is being used? What happens if you change it to <code>none</code>?
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Step 2: Submit a token to access admin area -->
<div class="card">
    <div class="card-title">Step 2 — Access Restricted Area with Token</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        Submit a JWT token to access admin-restricted functionality. Only tokens with <code>"role": "admin"</code> are accepted.
    </p>
    <form method="GET" action="jwt_portal.php">
        <div class="form-group">
            <label class="form-label">JWT Token</label>
            <input type="text" name="token" class="form-control"
                   placeholder="Paste your JWT here (header.payload.signature)"
                   value="<?= isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '' ?>">
        </div>
        <button type="submit" class="btn btn-primary">Submit Token</button>
    </form>

    <?php if ($tokenError): ?>
    <div class="alert" style="background:rgba(244,63,94,0.15); border:1px solid rgba(244,63,94,0.3); color:#fca5a5; margin-top:1rem;">
        ❌ <?= htmlspecialchars($tokenError) ?>
    </div>
    <?php endif; ?>

    <?php if ($jwtPayload && !$flagContent): ?>
    <div class="alert alert-info" style="margin-top:1rem;">
        ✅ Token accepted. Decoded payload:
        <div class="terminal-box" style="margin-top:0.5rem;"><?= htmlspecialchars(json_encode($jwtPayload, JSON_PRETTY_PRINT)) ?></div>
        <p style="margin-top:0.75rem; font-size:0.85rem; color:var(--text-muted);">
            Your role is <strong><?= htmlspecialchars($jwtPayload['role'] ?? 'unknown') ?></strong>. Admin access required for restricted content.
        </p>
    </div>
    <?php endif; ?>

    <?php if ($flagContent): ?>
    <div class="card" style="border-color:rgba(245,158,11,0.4); margin-top:1.25rem;">
        <div class="card-title" style="color:var(--accent-amber);">🚨 Admin Access Granted — JWT Flag</div>
        <div class="terminal-box">
[JWT Algorithm Bypass — 'none' Attack Successful]
Decoded Payload: <?= htmlspecialchars(json_encode($jwtPayload, JSON_PRETTY_PRINT)) ?>

Admin Flag: <?= htmlspecialchars($flagContent) ?>

[!] Security Finding: Server accepts JWTs with alg=none.
    Recommendation: Explicitly whitelist allowed algorithms.
    Never accept 'none' as a valid algorithm in production.
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include('footer.php'); ?>
