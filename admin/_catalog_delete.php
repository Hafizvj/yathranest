<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_catalog.php';
require_admin();

$cfg = catalog_config($catalogKey ?? '');
if (!$cfg) {
    http_response_code(404);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('admin/' . $cfg['nav'] . '/index.php');
}

$id = (int) post('id');
db()->prepare('DELETE FROM ' . $cfg['table'] . ' WHERE id = ?')->execute([$id]);
flash_set('success', 'Deleted.');
redirect('admin/' . $cfg['nav'] . '/index.php');
