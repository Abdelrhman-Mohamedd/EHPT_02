<?php
/**
 * internal_api.php — Protected Internal Reporting Endpoint
 *
 * VULNERABILITY: A02:2021 — Cryptographic Failures (Hardcoded Secret / Broken Authentication)
 *
 * A bearer token is hardcoded inside /assets/js/portal.js (visible via View Source).
 * This endpoint accepts that token as an Authorization header or ?token= query param.
 * When the correct token is submitted, it returns FLAG{HARDCODED}.
 *
 * Exploit steps:
 *  1. View page source on any portal page (Ctrl+U) — see <script src="assets/js/portal.js">
 *  2. Open portal.js — find the INTERNAL_API_TOKEN constant with the hardcoded bearer token.
 *  3. Call this endpoint: GET /internal_api.php?token=<value>
 *     OR: curl -H "Authorization: Bearer <token>" http://vaulttech.local/internal_api.php
 *  4. Receive the flag in the JSON response.
 */

require_once(__DIR__ . '/student_config.php');

// Load the expected token from the server-side secrets file
$tokenFile = '/srv/labs/lab02/data/secrets/.api_token';
$flagFile  = '/srv/labs/lab02/data/secrets/.hardcoded_flag';

$expectedToken = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : 'vtk-internal-demo';
$flag          = file_exists($flagFile)  ? trim(file_get_contents($flagFile))  : 'FLAG{demo_hardcoded}';

// Accept token from Authorization: Bearer header or ?token= query param
$submittedToken = '';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    $submittedToken = trim($m[1]);
} elseif (isset($_GET['token'])) {
    $submittedToken = trim($_GET['token']);
}

// JSON response helper
function json_out(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// If no token submitted — show API docs
if ($submittedToken === '') {
    // HTML view for browser access
    if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') !== false) {
        $currentPage = 'api';
        include('header.php');
        ?>
<div class="page-header">
    <h1 class="page-title">Internal Reporting API</h1>
    <p class="page-subtitle">Authenticated internal endpoint — requires bearer token</p>
</div>
<div class="card">
    <div class="card-title">📡 API Documentation</div>
    <div class="terminal-box">
Endpoint : GET /internal_api.php
Auth     : Authorization: Bearer &lt;INTERNAL_API_TOKEN&gt;
           OR: ?token=&lt;INTERNAL_API_TOKEN&gt;

Example (curl):
  curl -H "Authorization: Bearer &lt;token&gt;" \
       http://vaulttech.local/internal_api.php

Response (authenticated):
  { "status": "ok", "flag": "FLAG{...}", "message": "..." }

Response (unauthenticated):
  { "status": "error", "code": 401, "message": "Unauthorized" }

Hint: Where might a developer have accidentally left the token?
    </div>
</div>
        <?php
        include('footer.php');
        exit;
    }
    json_out(401, ['status' => 'error', 'code' => 401, 'message' => 'Unauthorized: Authorization header or ?token= required.']);
}

// Validate token
if (!hash_equals($expectedToken, $submittedToken)) {
    json_out(403, ['status' => 'error', 'code' => 403, 'message' => 'Forbidden: Invalid token.']);
}

// Valid token — return the flag
json_out(200, [
    'status'  => 'ok',
    'flag'    => $flag,
    'message' => 'Hardcoded API token accepted. ' .
                 'Finding: INTERNAL_API_TOKEN was committed to the JavaScript source bundle (A02 — Cryptographic Failures). ' .
                 'Fix: Use short-lived OAuth tokens from a secrets manager; never embed credentials in client-facing code.',
    'student' => $GLOBALS['STUDENT_ID'],
]);
