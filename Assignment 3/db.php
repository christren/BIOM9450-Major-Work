<?php

try {
    $configPath = __DIR__ . '/config.php';

    if (!is_file($configPath)) {
        throw new RuntimeException(
            'Missing config.php. Copy config.example.php and configure it to your local settings.'
        );
    }

    $config = require $configPath;

    if (!is_array($config)) {
        throw new RuntimeException('config.php must return a configuration array.');
    }

    foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
        if (!array_key_exists($key, $config)) {
            throw new RuntimeException(
                'Missing database configuration key: ' . $key
            );
        }
    }

    $dsn = "mysql:host={$config['host']};port={$config['port']};"
         . "dbname={$config['database']};charset=utf8mb4";

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

} catch (Throwable $error) {
    error_log($error->getMessage());

    http_response_code(500);
    header('Cache-Control: no-store');

    $message = 'Database connection unavailable. '
        . 'Check the local configuration and MySQL server.';

    if (defined('JSON_RESPONSE') && JSON_RESPONSE === true) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
    }

    exit;
}