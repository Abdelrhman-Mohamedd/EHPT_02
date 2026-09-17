<?php
// student_config.php - Load personalized student build metadata
$studentInfoFile = "/srv/labs/lab02/data/.studentinfo";
if (!file_exists($studentInfoFile)) {
    $studentInfoFile = __DIR__ . "/../data/.studentinfo";
}

$studentId = "STUDENT_DEMO_2026";
$buildTime = "Default Build";

if (file_exists($studentInfoFile)) {
    $lines = file($studentInfoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'STUDENT_ID=') === 0) {
            $studentId = trim(substr($line, strlen('STUDENT_ID=')));
        } elseif (strpos($line, 'BUILD_TIME=') === 0) {
            $buildTime = trim(substr($line, strlen('BUILD_TIME=')));
        }
    }
}

$GLOBALS['STUDENT_ID'] = $studentId;
$GLOBALS['BUILD_TIME'] = $buildTime;
?>
