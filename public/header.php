<?php
// Header component for VaultTech Financial Services Portal
require_once(__DIR__ . '/student_config.php');

if (!isset($currentPage)) {
    $currentPage = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VaultTech Financial Portal (Lab02 - <?= htmlspecialchars($GLOBALS['STUDENT_ID']) ?>)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-logo">V</div>
            <span>VaultTech Portal</span>
        </a>
        <ul class="nav-links">
            <li class="nav-item"><a href="index.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
            <li class="nav-item"><a href="account.php" class="<?= $currentPage === 'account' ? 'active' : '' ?>">My Account</a></li>
            <li class="nav-item"><a href="reports.php" class="<?= $currentPage === 'reports' ? 'active' : '' ?>">Reports</a></li>
            <li class="nav-item"><a href="api_status.php" class="<?= $currentPage === 'api' ? 'active' : '' ?>">API Status</a></li>
            <li class="nav-item"><a href="support.php" class="<?= $currentPage === 'support' ? 'active' : '' ?>">Support</a></li>
        </ul>
        <div class="user-badge">
            <div class="avatar">ID</div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($GLOBALS['STUDENT_ID']) ?></div>
                <div class="user-role">Lab02 Instance Boundary</div>
            </div>
        </div>
    </nav>
    <div class="container">
