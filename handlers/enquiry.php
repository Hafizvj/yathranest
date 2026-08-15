<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

if (!verify_csrf(post('_csrf'))) {
    $payload = ['ok' => false, 'error' => 'Invalid security token. Refresh the page and try again.'];
    if (request_is_json() || isset($_POST['ajax'])) {
        json_response($payload, 403);
    }
    flash_set('error', $payload['error']);
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$type = post('type', 'general');
$allowed = ['general', 'contact', 'taxi', 'resort', 'gift', 'investment'];
if (!in_array($type, $allowed, true)) {
    $type = 'general';
}

$name = post('name');
$phone = post('phone');
$email = post('email');
$interest = post('interest');
$message = post('message');
$source = post('source_page');
$packageSlug = post('package_slug');

$travelDate = post('travel_date');
if ($travelDate !== '') {
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $travelDate);
    $travelDate = $parsed && $parsed->format('Y-m-d') === $travelDate ? $travelDate : '';
}

$errors = [];
if ($name === '') {
    $errors[] = 'Name is required.';
}
if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 10) {
    $errors[] = 'Valid phone is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required.';
}

$extra = [];
if ($type === 'taxi') {
    foreach (['pickup', 'drop', 'date', 'time', 'tripType', 'vehicle', 'passengers', 'notes'] as $k) {
        if (isset($_POST[$k]) && trim((string) $_POST[$k]) !== '') {
            $extra[$k] = trim((string) $_POST[$k]);
        }
    }
}

if ($errors) {
    $payload = ['ok' => false, 'error' => implode(' ', $errors)];
    if (request_is_json() || isset($_POST['ajax'])) {
        json_response($payload, 422);
    }
    flash_set('error', $payload['error']);
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$stmt = db()->prepare(
    'INSERT INTO inquiries (type, name, phone, email, interest, travel_date, message, extra_json, source_page, package_slug, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->execute([
    $type,
    $name,
    $phone,
    $email,
    $interest,
    $travelDate !== '' ? $travelDate : null,
    $message,
    $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
    $source,
    $packageSlug,
    'new',
]);

$whatsapp = enquiry_whatsapp_url([
    'id' => (int) db()->lastInsertId(),
    'type' => $type,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'interest' => $interest,
    'travel_date' => $travelDate,
    'message' => $message,
    'extra' => $extra,
]);

$payload = ['ok' => true, 'message' => 'Enquiry submitted.', 'whatsapp' => $whatsapp];
if (request_is_json() || isset($_POST['ajax'])) {
    json_response($payload);
}

if ($whatsapp !== '') {
    redirect($whatsapp);
}

flash_set('success', 'Enquiry submitted. We will contact you shortly.');
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
