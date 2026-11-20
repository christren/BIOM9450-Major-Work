<?php
// ONLY the existing prediction model uses this connection.
// The website uses MySQLi through app/bootstrap.php instead.
//
// The model's prediction_save(PDO $pdo, ...) function expects PDO.
// Keeping that interface unchanged avoids modifying the ML code.
// This file opens a connection only when the model actually calls db().
function db(): PDO {
    static $connection = null;

    if ($connection === null) {
        $settings = config();

        $dsn = "mysql:host={$settings['db_host']};"
             . "port={$settings['db_port']};"
             . "dbname={$settings['db_name']};charset=utf8mb4";

        $connection = new PDO(
            $dsn,
            $settings['db_user'],
            $settings['db_pass']
        );

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    return $connection;
}
