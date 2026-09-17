    </div> <!-- .container -->
    <footer>
        <p>&copy; <?= date('Y') ?> VaultTech Financial Services. All rights reserved. | Secure Client Portal v3.1.2</p>
        <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.3rem;">
            Assigned Student VM: <strong style="color:var(--accent-cyan);"><?= htmlspecialchars($GLOBALS['STUDENT_ID']) ?></strong> | Build Timestamp: <?= htmlspecialchars($GLOBALS['BUILD_TIME']) ?>
        </p>
    </footer>
</body>
</html>
