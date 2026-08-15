<?php
/**
 * HostMaria reference copy — what the live server's config/config.php holds
 * (that file is not deployed by git/FTP).
 * The sdb-65 hostname is internal and only resolves on the hosting network;
 * from a local machine the same DB is reached through mysql.gb.stackcp.com on
 * the port issued by Remote MySQL Access. See config.example.php.
 */
return [
    'db' => [
        'host' => 'sdb-65.hosting.stackcp.net',
        'port' => 3306,
        'name' => 'yathranest-web-3530333919c0',
        'user' => 'yathranest-web-3530333919c0',
        'pass' => 'jKGqSq+L]|$t',
        'charset' => 'utf8mb4',
        'timeout' => 15,
    ],
    'base_url' => '',
    'upload_dir' => __DIR__ . '/../uploads',
    'upload_url' => 'uploads',
    'session_name' => 'yn_admin',
    'timezone' => 'Asia/Kolkata',
];
