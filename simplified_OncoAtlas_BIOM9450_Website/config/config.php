<?php
// LOCAL DEVELOPMENT CONFIGURATION. Change these credentials to match your MySQL server.
// Optional: put your own settings in config.local.php (git-ignored); they override the defaults below.
$settings = [
    'db_host' => '127.0.0.1',
    'db_port' => '3306',
    'db_name' => 'cancer_portal',
    'db_user' => 'root',
    'db_pass' => '',
    'app_name' => 'OncoAtlas',
];
$local = __DIR__ . '/config.local.php';
return is_file($local) ? array_replace($settings, require $local) : $settings;
