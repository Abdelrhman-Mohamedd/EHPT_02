<?php
/**
 * api_status.php — VaultTech API Integration Dashboard
 *
 * This page describes the API endpoints and links to the legacy XML parser.
 * The hint toward Flag 5 (VULNCOMP) is placed here prominently.
 */

$currentPage = 'api';
include('header.php');
?>

<div class="page-header">
    <h1 class="page-title">API Integration Dashboard</h1>
    <p class="page-subtitle">Monitor API health and manage integration endpoints</p>
</div>

<div class="alert" style="background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.3); color:#fcd34d;">
    ⚠️ <strong>Deprecation Notice:</strong> The Legacy XML API (v1) is end-of-life and will be removed in Q4 2024.
    Please migrate to the REST API (v2) immediately.
</div>

<div class="grid" style="margin-bottom:1.5rem;">
    <div class="card">
        <div class="card-title">
            <span>🟢 REST API v2</span>
            <small style="color:var(--accent-emerald); font-size:0.8rem;">Operational</small>
        </div>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
            Modern JSON-based REST API. Supports OAuth 2.0 authentication and rate limiting.
        </p>
        <div style="font-size:0.82rem; color:var(--text-muted);">
            Base URL: <code style="color:var(--accent-cyan);">/api/v2/</code><br>
            Auth: Bearer Token (OAuth 2.0)
        </div>
    </div>

    <div class="card" style="border-color:rgba(244,63,94,0.3);">
        <div class="card-title">
            <span>🔴 Legacy XML API v1</span>
            <small style="color:var(--accent-rose); font-size:0.8rem;">End-of-Life</small>
        </div>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
            Legacy SOAP/XML-based API running on an outdated XML parsing library.
            Known vulnerabilities: CVE-style XXE injection possible.
        </p>
        <div style="font-size:0.82rem; color:var(--text-muted); margin-bottom:1rem;">
            Endpoint: <code style="color:var(--accent-rose);">/api/v1/parse.php</code><br>
            Auth: API Key (header)
        </div>
        <a href="api/v1/parse.php" class="btn btn-secondary" style="font-size:0.8rem;">Open Legacy API</a>
    </div>
</div>

<div class="card">
    <div class="card-title">📖 Legacy API v1 Documentation</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        The <code>/api/v1/parse.php</code> endpoint accepts raw XML data via POST and returns
        parsed results. It uses an <strong>outdated libxml2 configuration</strong> that has
        external entity processing <strong>enabled by default</strong>.
    </p>
    <div class="terminal-box">
# Example request to the legacy XML parser:

curl -X POST http://vaulttech.local/api/v1/parse.php \
     -H "Content-Type: application/xml" \
     -H "X-API-Key: vaulttech-api-key-2024" \
     -d '&lt;?xml version="1.0"?&gt;&lt;request&gt;&lt;data&gt;Hello World&lt;/data&gt;&lt;/request&gt;'

# Expected response:
# &lt;response&gt;&lt;status&gt;ok&lt;/status&gt;&lt;parsed&gt;Hello World&lt;/parsed&gt;&lt;/response&gt;

# NOTE: This endpoint uses libxml2 with LIBXML_NOENT enabled — external entities processed!
# Vulnerable to XXE (XML External Entity) injection.
# Try injecting: &lt;!DOCTYPE foo [&lt;!ENTITY xxe SYSTEM "file:///etc/passwd"&gt;]&gt;
    </div>

    <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid var(--border-color);">
        <p style="font-size:0.82rem; color:var(--text-muted);">
            💡 <strong>Hint:</strong> The legacy API was built with an outdated PHP-XML library that has
            a known XXE vulnerability (A06:2021 — Vulnerable Components). Read sensitive server files
            by injecting an external entity referencing <code>file:///etc/lab_vulncomp_flag</code>.
        </p>
    </div>
</div>

<div class="card" style="margin-top:1.5rem;">
    <div class="card-title">📊 API Health Status</div>
    <ul class="doc-list">
        <li class="doc-item">
            <div class="doc-info">
                <span class="doc-icon" style="color:var(--accent-emerald);">●</span>
                <div><strong>/api/v2/accounts</strong><div style="font-size:0.8rem;color:var(--text-muted);">Response: 200 OK | Avg: 45ms</div></div>
            </div>
            <span style="font-size:0.8rem; color:var(--accent-emerald);">Healthy</span>
        </li>
        <li class="doc-item">
            <div class="doc-info">
                <span class="doc-icon" style="color:var(--accent-emerald);">●</span>
                <div><strong>/api/v2/reports</strong><div style="font-size:0.8rem;color:var(--text-muted);">Response: 200 OK | Avg: 120ms</div></div>
            </div>
            <span style="font-size:0.8rem; color:var(--accent-emerald);">Healthy</span>
        </li>
        <li class="doc-item">
            <div class="doc-info">
                <span class="doc-icon" style="color:var(--accent-rose);">●</span>
                <div><strong>/api/v1/parse.php</strong><div style="font-size:0.8rem;color:var(--text-muted);">End-of-life — security patches not applied</div></div>
            </div>
            <span style="font-size:0.8rem; color:var(--accent-rose);">Vulnerable</span>
        </li>
    </ul>
</div>

<?php include('footer.php'); ?>
