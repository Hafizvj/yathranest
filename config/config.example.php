<?php
/**
 * Copy to config.php and fill in HostMaria / local credentials.
 * config.php is gitignored — never commit real passwords.
 */
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'yathranest',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'base_url' => '', // e.g. '' or '/uat-yathranest' — no trailing slash
    'upload_dir' => __DIR__ . '/../uploads',
    'upload_url' => 'uploads',
    'session_name' => 'yn_admin',
    'timezone' => 'Asia/Kolkata',
];
