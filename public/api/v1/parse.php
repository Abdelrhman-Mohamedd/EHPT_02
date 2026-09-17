<?php
/**
 * /api/v1/parse.php — Legacy XML Processing Endpoint
 *
 * VULNERABILITY: A06:2021 — Vulnerable and Outdated Components
 * This endpoint simulates a legacy PHP-XML integration using LIBXML_NOENT,
 * which enables external entity (XXE) processing — a known vulnerability in
 * older libxml2 builds / misconfigured PHP XML parsers.
 *
 * Attack vector (XXE injection):
 *   POST /api/v1/parse.php
 *   Content-Type: application/xml
 *   X-API-Key: vaulttech-api-key-2024
 *
 *   <?xml version="1.0"?>
 *   <!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/lab_vulncomp_flag">]>
 *   <request><data>&xxe;</data></request>
 *
 * The entity &xxe; will be resolved to the contents of the flag file,
 * which is then returned in the <parsed> element of the response.
 */

// Disable HTML output — this is a raw XML API
header('Content-Type: application/xml; charset=utf-8');

// VULNERABILITY: No input length check (DoS possible with large payloads)
$rawInput = file_get_contents('php://input');
$apiKey   = $_SERVER['HTTP_X_API_KEY'] ?? '';

// Simulated API key check (intentionally weak — key is documented in app.conf)
$validApiKey = 'vaulttech-api-key-2024';

if (empty($rawInput)) {
    // GET request — show documentation
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Content-Type: text/html; charset=utf-8');
        // Load page header for the HTML view
        require_once(__DIR__ . '/../../student_config.php');
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VaultTech Legacy XML API v1</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div style="max-width:900px; margin:2rem auto; padding:0 1.5rem;">
    <div style="background:var(--glass-bg,rgba(30,41,59,0.7)); border:1px solid rgba(255,255,255,0.12); border-radius:12px; padding:2rem;">
        <h1 style="color:#f8fafc; margin-bottom:0.5rem;">🔴 VaultTech Legacy XML API — v1</h1>
        <p style="color:#94a3b8; margin-bottom:1.5rem;">End-of-life. Known vulnerabilities. Migrate to v2 immediately.</p>

        <div style="background:#020617; border:1px solid #1e293b; border-radius:6px; padding:1rem; font-family:monospace; font-size:0.85rem; color:#38bdf8; margin-bottom:1.5rem; white-space:pre-wrap;">
Endpoint : POST /api/v1/parse.php
Auth     : X-API-Key header required
           Key: vaulttech-api-key-2024 (see /backup/ for full config)
Content  : application/xml

XML Request Format:
  &lt;?xml version="1.0"?&gt;
  &lt;request&gt;
    &lt;data&gt;Your data here&lt;/data&gt;
  &lt;/request&gt;

XML Response Format:
  &lt;response&gt;
    &lt;status&gt;ok&lt;/status&gt;
    &lt;parsed&gt;Your data here&lt;/parsed&gt;
  &lt;/response&gt;

WARNING: This endpoint uses libxml2 with LIBXML_NOENT enabled.
         External entity injection (XXE) is possible.
        </div>

        <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:6px; padding:1rem; color:#fcd34d; font-size:0.85rem;">
            ⚠️ <strong>Security Notice:</strong> This component has not received security updates since 2022.
            The XML parser does not disable external entity processing (LIBXML_NOENT is ON).
            Attackers can read arbitrary files from the server filesystem using XXE injection.
        </div>
    </div>
</div>
</body></html>
        <?php
        exit;
    }
    echo '<?xml version="1.0"?><response><status>error</status><message>No XML body provided</message></response>';
    exit;
}

// API Key validation
if ($apiKey !== $validApiKey) {
    http_response_code(401);
    echo '<?xml version="1.0"?><response><status>error</status><message>Unauthorized: Invalid API key</message></response>';
    exit;
}

// VULNERABILITY: XML parsed with LIBXML_NOENT — external entities are resolved
// This is the core of the XXE vulnerability (A06:2021 — Vulnerable Components)
libxml_disable_entity_loader(false); // Intentionally RE-ENABLE entity loading (default is disabled in PHP 8)
$prevErrors = libxml_use_internal_errors(true);

$xml = simplexml_load_string($rawInput, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_DTDLOAD);

if ($xml === false) {
    $errors = libxml_get_errors();
    libxml_clear_errors();
    libxml_use_internal_errors($prevErrors);
    $errMsg = !empty($errors) ? $errors[0]->message : 'Parse error';
    echo '<?xml version="1.0"?><response><status>error</status><message>' . htmlspecialchars(trim($errMsg)) . '</message></response>';
    exit;
}

libxml_use_internal_errors($prevErrors);

// Extract the <data> field — if XXE was used, entity is already resolved here
$parsedData = (string)($xml->data ?? '');

// Return the parsed result (which may contain file contents if XXE was used)
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<response>';
echo '<status>ok</status>';
echo '<component>libxml2-' . (defined('LIBXML_DOTTED_VERSION') ? LIBXML_DOTTED_VERSION : 'unknown') . '</component>';
echo '<parsed>' . htmlspecialchars($parsedData) . '</parsed>';
echo '</response>';
?>
