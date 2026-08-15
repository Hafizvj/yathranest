<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$cfg = config('db');
echo 'host: ' . $cfg['host'] . '  db: ' . $cfg['name'] . '  user: ' . $cfg['user'] . PHP_EOL;

try {
    $row = db()->query('SELECT DATABASE() AS db, @@hostname AS server, VERSION() AS version')->fetch();
    echo 'OK connected' . PHP_EOL;
    echo '  database: ' . $row['db'] . PHP_EOL;
    echo '  server:   ' . $row['server'] . PHP_EOL;
    echo '  version:  ' . $row['version'] . PHP_EOL;

    foreach (['packages', 'inquiries', 'settings'] as $table) {
        try {
            $count = db()->query("SELECT COUNT(*) AS c FROM {$table}")->fetch()['c'];
            echo '  ' . str_pad($table, 12) . $count . ' rows' . PHP_EOL;
        } catch (Throwable $e) {
            echo '  ' . str_pad($table, 12) . 'MISSING (' . $e->getMessage() . ')' . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
