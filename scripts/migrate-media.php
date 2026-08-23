<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require dirname(__DIR__) . '/admin/packages/_form_helpers.php';
require dirname(__DIR__) . '/admin/_media.php';

$sql = file_get_contents(dirname(__DIR__) . '/sql/migrations/005-media-library.sql');
db()->exec($sql);
echo "media table ok\n";
$n = media_import_referenced_uploads();
echo "imported {$n}\n";
