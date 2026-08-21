<?php
/**
 * Copy to config.php and fill in credentials.
 * config.php is gitignored — never commit real passwords.
 *
 * Always use the HostMaria live DB (no local MySQL).
 * Same DB, two hostnames:
 * - Live server: sdb-XX.hosting.stackcp.net:3306 (internal only).
 * - Your PC: mysql.gb.stackcp.com + port from Web Tools → Remote MySQL Access
 *   (whitelist your public IP first).
 * Git/FTP deploy never overwrites server config.php.
 */

$isLive = DIRECTORY_SEPARATOR === '/';

// Always HostMaria live DB — never local XAMPP MySQL.
// On your PC: set this to the port from Web Tools → Remote MySQL Access
// (and whitelist your public IP). Live server ignores this and uses sdb-65.
$remoteMysqlPort = 0;

$hostmaria = [
    'name' => 'yathranest-web-3530333919c0',
    'user' => 'yathranest-web-3530333919c0',
    'pass' => '',
    'charset' => 'utf8mb4',
    // Seconds to wait for the DB connect before failing (keeps an
    // unreachable host from hanging every page request).
    'timeout' => 15,
];

if ($isLive) {
    // Internal hostname — only resolves on HostMaria's network.
    $db = $hostmaria + ['host' => 'sdb-65.hosting.stackcp.net', 'port' => 3306];
} else {
    // Same live DB from your PC via Remote MySQL Access.
    $db = $hostmaria + [
        'host' => 'mysql.gb.stackcp.com',
        'port' => (int) $remoteMysqlPort,
    ];
}

return [
    'db' => $db,
    'base_url' => $isLive ? '' : '/yn', // no trailing slash
    'upload_dir' => __DIR__ . '/../uploads',
    'upload_url' => 'uploads',
    'session_name' => 'yn_admin',
    'timezone' => 'Asia/Kolkata',
];
