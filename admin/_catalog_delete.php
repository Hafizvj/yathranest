<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_catalog.php';
require_once __DIR__ . '/packages/_form_helpers.php';
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
$stmt = db()->prepare('SELECT * FROM ' . $cfg['table'] . ' WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
    admin_delete_upload((string) ($row['image'] ?? ''));
    if (!empty($cfg['has_gallery'])) {
        foreach (json_decode_array($row['gallery_json'] ?? null) as $path) {
            admin_delete_upload((string) $path);
        }
    }
}

db()->prepare('DELETE FROM ' . $cfg['table'] . ' WHERE id = ?')->execute([$id]);
flash_set('success', 'Deleted.');
redirect('admin/' . $cfg['nav'] . '/index.php');
