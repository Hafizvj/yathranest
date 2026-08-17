<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/_form_helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('admin/packages/index.php');
}

$id = (int) post('id');
$stmt = db()->prepare('SELECT image, gallery_json FROM packages WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
    admin_delete_upload((string) ($row['image'] ?? ''));
    foreach (json_decode_array($row['gallery_json'] ?? null) as $path) {
        admin_delete_upload((string) $path);
    }
}

$stmt = db()->prepare('DELETE FROM packages WHERE id = ?');
$stmt->execute([$id]);
flash_set('success', 'Package deleted.');
redirect('admin/packages/index.php');
