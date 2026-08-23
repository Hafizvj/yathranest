<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('admin/packages/index.php');
}

$id = (int) post('id');
$stmt = db()->prepare('DELETE FROM packages WHERE id = ?');
$stmt->execute([$id]);
flash_set('success', 'Package deleted.');
redirect('admin/packages/index.php');
