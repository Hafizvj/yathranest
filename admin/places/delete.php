<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('admin/places/index.php');
}
db()->prepare('DELETE FROM places WHERE id = ?')->execute([(int) post('id')]);
flash_set('success', 'Place deleted.');
redirect('admin/places/index.php');
