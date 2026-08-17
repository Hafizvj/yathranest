<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('admin/places/index.php');
}

$id = (int) post('id');
$stmt = db()->prepare('SELECT images_json FROM places WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if ($row) {
    foreach (json_decode_array($row['images_json'] ?? null) as $path) {
        admin_delete_upload((string) $path);
    }
}

db()->prepare('DELETE FROM places WHERE id = ?')->execute([$id]);
flash_set('success', 'Place deleted.');
redirect('admin/places/index.php');
