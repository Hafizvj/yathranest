<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/models.php';
require_once __DIR__ . '/ui.php';

$tz = config('timezone', 'Asia/Kolkata');
if (is_string($tz) && $tz !== '') {
    date_default_timezone_set($tz);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name((string) config('session_name', 'yn_admin'));
    session_start();
}
