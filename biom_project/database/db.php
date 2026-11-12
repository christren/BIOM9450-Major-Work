<?php
// Local configuration is deliberately not committed.
try {
    $config = require __DIR__ . '/config.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
        $config['username'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database connection unavailable. Check config.php and MySQL.');
}
