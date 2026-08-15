<?php
/**
 * Applies the .sql files in sql/migrations in filename order.
 * Statements that fail because the change is already in place are skipped.
 * Usage: php tools/migrate.php
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$dir = dirname(__DIR__) . '/sql/migrations';
$files = glob($dir . '/*.sql') ?: [];
sort($files);

if (!$files) {
    echo "No migrations found." . PHP_EOL;
    exit;
}

foreach ($files as $file) {
    $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
    $statements = array_filter(array_map('trim', explode(';', (string) $sql)));

    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        try {
            db()->exec($statement);
            echo 'applied   ' . basename($file) . PHP_EOL;
        } catch (PDOException $e) {
            $duplicate = in_array($e->errorInfo[1] ?? 0, [1060, 1061, 1050, 1091], true);
            echo ($duplicate ? 'skipped   ' : 'FAILED    ') . basename($file) . ' — ' . $e->getMessage() . PHP_EOL;
        }
    }
}
