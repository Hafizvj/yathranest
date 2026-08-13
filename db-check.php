<?php
/**
 * Temporary DB check — upload to site root, open once, then DELETE.
 * https://uat.yathranest.com/db-check.php
 */
header('Content-Type: text/plain; charset=utf-8');

$configPath = __DIR__ . '/config/config.php';
if (!is_file($configPath)) {
    echo "FAIL: config/config.php is missing on the server.\n";
    echo "Upload config.php via FTP/cPanel File Manager (it is not deployed by GitHub).\n";
    exit;
}

$cfg = require $configPath;
$db = $cfg['db'] ?? [];

$name = $db['name'] ?? '';
$user = $db['user'] ?? '';
$pass = $db['pass'] ?? '';
$charset = $db['charset'] ?? 'utf8mb4';
$port = (int) ($db['port'] ?? 3306);
$configHost = $db['host'] ?? '127.0.0.1';

echo "Config found.\n";
echo "Configured host: {$configHost}\n";
echo "Name: {$name}\n";
echo "User: {$user}\n";
echo 'Pass: ' . ($pass !== '' ? '(set)' : '(empty)') . "\n\n";

$hostsToTry = array_values(array_unique([
    $configHost,
    '127.0.0.1',
    'localhost',
    'sdb-65.hosting.stackcp.net',
]));

$connected = null;
$workingHost = null;

foreach ($hostsToTry as $host) {
    echo "Trying host={$host} port={$port} ... ";
    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset={$charset}",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
        );
        echo "OK\n";
        $connected = $pdo;
        $workingHost = $host;
        break;
    } catch (Throwable $e) {
        echo "FAIL — " . $e->getMessage() . "\n";
    }
}

echo "\n";

if (!$connected) {
    echo "FAIL: none of the hosts worked.\n";
    echo "In cPanel MySQL, confirm the database user is assigned to this database.\n";
    echo "Also check HostMaria docs / phpMyAdmin for the correct MySQL hostname.\n";
    exit;
}

echo "SUCCESS with host: {$workingHost}\n";
echo "Put this in config/config.php:\n";
echo "  'host' => '{$workingHost}',\n";
echo "  'port' => {$port},\n\n";

$tables = ['packages', 'places', 'admins', 'inquiries', 'settings', 'resorts'];
foreach ($tables as $t) {
    try {
        $n = (int) $connected->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
        echo "  {$t}: {$n} rows\n";
    } catch (Throwable $e) {
        echo "  {$t}: missing or error — " . $e->getMessage() . "\n";
    }
}

echo "\nDelete this file (db-check.php) after fixing config.\n";
