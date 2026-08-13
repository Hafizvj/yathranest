<?php
/**
 * Copy to config.php and fill in HostMaria / local credentials.
 * config.php is gitignored — never commit real passwords.
 */
return [
    'db' => [
        // StackCP tip: prefer 127.0.0.1 over localhost (avoids unix socket errors).
        // If needed, use the hostname from cPanel (e.g. sdb-XX.hosting.stackcp.net).
        'host' => '127.0.0.1',
        'port' => 3306,
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
