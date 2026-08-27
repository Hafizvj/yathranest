<?php
/**
 * Copy to config.php and fill in credentials.
 * config.php is gitignored — never commit real passwords.
 *
 * HostMaria only — one hostname from StackCP Remote MySQL Access.
 * Whitelist your public IP first.
 */

$isLive = DIRECTORY_SEPARATOR === '/';

return [
    'db' => [
        'host' => 'mysql.gb.stackcp.com',
        'port' => 44866,
        'name' => 'yathranest-web-3530333919c0',
        'user' => 'yathranest-web-3530333919c0',
        'pass' => '',
        'charset' => 'utf8mb4',
        'timeout' => 15,
    ],
    'base_url' => $isLive ? '' : '/yathranest',
    'upload_dir' => __DIR__ . '/../uploads',
    'upload_url' => 'uploads',
    'session_name' => 'yn_admin',
    'timezone' => 'Asia/Kolkata',
    // Google AI Studio free key — https://aistudio.google.com/apikey
    'gemini_api_key' => '',
    'gemini_model' => 'gemini-3.6-flash',
];
