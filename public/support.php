<?php
/**
 * support.php — VaultTech Support Page
 * Simple static page for nav completeness.
 */
$currentPage = 'support';
include('header.php');
?>

<div class="page-header">
    <h1 class="page-title">Support Center</h1>
    <p class="page-subtitle">Contact the VaultTech IT Security team for assistance</p>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title">📞 Contact IT Security</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        For portal access issues or security concerns, contact the internal IT security team.
    </p>
    <div class="terminal-box">
Email    : itsecurity@vaulttech-internal.com
Phone    : ext. 4422 (Internal)
Ticketing: http://helpdesk.vaulttech-internal.com/new

Emergency (security incidents): security@vaulttech-internal.com
    </div>
</div>

<div class="card">
    <div class="card-title">🗒️ Lab Objectives Quick Reference</div>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        During this assessment, look for the following vulnerability categories:
    </p>
    <ul style="color:var(--text-muted); font-size:0.9rem; list-style:none; padding:0;">
        <li style="padding:0.6rem 0; border-bottom:1px solid var(--border-color);">
            🔑 <strong style="color:var(--text-main);">Cryptographic Failures (x2)</strong> — Weak password hashing & plaintext secrets in config files
        </li>
        <li style="padding:0.6rem 0; border-bottom:1px solid var(--border-color);">
            🔐 <strong style="color:var(--text-main);">JWT Algorithm Attack</strong> — Forging tokens using the 'none' algorithm bypass
        </li>
        <li style="padding:0.6rem 0; border-bottom:1px solid var(--border-color);">
            ⚙️ <strong style="color:var(--text-main);">Security Misconfiguration</strong> — Debug endpoints, directory listing, verbose errors
        </li>
        <li style="padding:0.6rem 0;">
            🧩 <strong style="color:var(--text-main);">Vulnerable Components</strong> — Outdated XML parser with XXE injection capability
        </li>
    </ul>
</div>

<?php include('footer.php'); ?>
