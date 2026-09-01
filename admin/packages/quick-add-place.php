<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_form_helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!verify_csrf(post('_csrf'))) {
    echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token.']);
    exit;
}

$label = trim(post('label'));
$scope = strtolower(trim(post('catalog_scope', 'kerala')));
$scopes = $scope !== '' ? [$scope] : ['kerala'];

try {
    $place = admin_quick_insert_place($label, $scopes);
    echo json_encode([
        'ok' => true,
        'slug' => $place['slug'],
        'label' => $place['label'],
        'message' => 'Place added.',
    ]);
} catch (InvalidArgumentException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Could not add place.']);
}
