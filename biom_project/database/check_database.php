<?php
// Run locally from terminal: php check_database.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require __DIR__ . '/db.php';
foreach (['CancerType','Patient','TumorSample','Gene','Variant','SampleMutation'] as $table) {
    echo $table . ': ' . $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() . PHP_EOL;
}
$stmt = $pdo->prepare('SELECT Password FROM AppUser WHERE Email = ?');
$stmt->execute(['oncologist@example.test']);
if (!password_verify('DemoCancer!2026', $stmt->fetchColumn())) {
    throw new RuntimeException('Demo password verification failed');
}
echo "Demo password verified.
";
