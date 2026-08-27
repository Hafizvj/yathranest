<?php

/**
 * Media library JSON API.
 * Lives at admin root — HostMaria returns 403 for /admin/media/*.php.
 */

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/packages/_form_helpers.php';
require_once __DIR__ . '/_media.php';

header('Content-Type: application/json; charset=utf-8');

function media_api_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!admin_user()) {
    media_api_json(['ok' => false, 'error' => 'Please sign in again.'], 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $q = trim((string) ($_GET['q'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    try {
        $result = media_list($q, $page, 200);
        media_api_json(['ok' => true] + $result);
    } catch (Throwable $e) {
        media_api_json(['ok' => false, 'error' => 'Library error: ' . $e->getMessage()], 500);
    }
}

if ($method === 'POST') {
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf(is_string($token) ? $token : null)) {
        media_api_json(['ok' => false, 'error' => 'Invalid CSRF.'], 403);
    }

    $action = (string) ($_POST['action'] ?? 'upload');
    if ($action === 'upload') {
        $file = $_FILES['file'] ?? null;
        if (!$file) {
            media_api_json(['ok' => false, 'error' => 'No file uploaded.'], 400);
        }
        $row = media_register_upload($file, 'library');
        if (!$row) {
            media_api_json(['ok' => false, 'error' => admin_upload_last_error() ?: 'Upload failed.'], 400);
        }
        media_api_json(['ok' => true, 'item' => $row]);
    }

    media_api_json(['ok' => false, 'error' => 'Unknown action.'], 400);
}

media_api_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
