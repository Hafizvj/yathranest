<?php
/**
 * Copy to config.php and fill in credentials.
 * config.php is gitignored — never commit real passwords.
 *
 * One HostMaria DB serves both local and live, so there is nothing to sync.
 * The catch is that it answers on two hostnames:
 * - From the live server: the internal sdb-XX.hosting.stackcp.net (port 3306).
 *   This name does not resolve anywhere outside the hosting network.
 * - From your PC: mysql.gb.stackcp.com on the port that StackCP issues under
 *   Web Tools > Remote MySQL Access once your IP is whitelisted.
 * Git push deploys code only (the FTP workflow excludes config.php).
 */

$isLive = DIRECTORY_SEPARATOR === '/';

// Port from the Remote MySQL Access page; null keeps local dev on XAMPP.
$remoteMysqlPort = null;

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
    $db = $hostmaria + ['host' => 'sdb-65.hosting.stackcp.net', 'port' => 3306];
} elseif ($remoteMysqlPort !== null) {
    $db = $hostmaria + ['host' => 'mysql.gb.stackcp.com', 'port' => (int) $remoteMysqlPort];
} else {
    $db = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'yathranest',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
        'timeout' => 5,
    ];
}

return [
    'db' => $db,
    'base_url' => $isLive ? '' : '/yathranest', // no trailing slash
    'upload_dir' => __DIR__ . '/../uploads',
    'upload_url' => 'uploads',
    'session_name' => 'yn_admin',
    'timezone' => 'Asia/Kolkata',
];
