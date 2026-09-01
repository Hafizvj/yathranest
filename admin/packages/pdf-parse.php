<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/gemini.php';
require_once __DIR__ . '/_form_helpers.php';
require_admin();

@set_time_limit(180);
ignore_user_abort(true);

header('Content-Type: application/json; charset=utf-8');

function pdf_parse_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    pdf_parse_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf(is_string($token) ? $token : null)) {
    pdf_parse_json(['ok' => false, 'error' => 'Invalid CSRF token.'], 403);
}

$apiKey = trim((string) config('gemini_api_key', ''));
if ($apiKey === '') {
    pdf_parse_json(['ok' => false, 'error' => 'Gemini API key is not configured. Add gemini_api_key in config/config.php.'], 503);
}

$file = $_FILES['pdf_file'] ?? null;
if (!is_array($file)) {
    pdf_parse_json(['ok' => false, 'error' => 'No PDF file uploaded.'], 400);
}

$stored = admin_store_temp_pdf($file);
if ($stored === null) {
    pdf_parse_json(['ok' => false, 'error' => admin_upload_last_error() ?: 'Could not store PDF.'], 400);
}

$fullPath = admin_temp_pdf_full_path($stored['token']);
if ($fullPath === null) {
    pdf_parse_json(['ok' => false, 'error' => 'Could not read uploaded PDF.'], 500);
}

$pdfBytes = file_get_contents($fullPath);
if ($pdfBytes === false || $pdfBytes === '') {
    pdf_parse_json(['ok' => false, 'error' => 'Could not read uploaded PDF.'], 500);
}

$places = places_all();
$typeOptions = package_type_options();
$prompt = package_pdf_parse_prompt($places, $typeOptions);
$schema = package_pdf_parse_schema();
$model = gemini_default_model();

$parts = gemini_build_parts($prompt, $pdfBytes);
$result = gemini_call_json_schema($apiKey, $model, $parts, $schema, 150);
if (!$result['ok']) {
    pdf_parse_json(['ok' => false, 'error' => $result['error'] ?? 'PDF parsing failed.'], (int) ($result['code'] ?? 502));
}

$rawPlans = $result['data']['plans'] ?? [];
if (!is_array($rawPlans) || $rawPlans === []) {
    pdf_parse_json(['ok' => false, 'error' => 'No itinerary plans found in this PDF.'], 422);
}

$plans = [];
$warnings = [];
foreach (array_values($rawPlans) as $rawPlan) {
    if (!is_array($rawPlan)) {
        continue;
    }
    $normalized = package_normalize_pdf_plan($rawPlan, $places);
    $plans[] = $normalized['plan'];
    $warnings = array_merge($warnings, $normalized['warnings']);
}

if ($plans === []) {
    pdf_parse_json(['ok' => false, 'error' => 'No valid itinerary plans found in this PDF.'], 422);
}

$unknownPlaces = [];
foreach ($plans as $plan) {
    foreach ($plan['unmatched_destinations'] ?? [] as $name) {
        $name = trim((string) $name);
        if ($name !== '' && !in_array($name, $unknownPlaces, true)) {
            $unknownPlaces[] = $name;
        }
    }
    foreach ($plan['unmatched_stays'] ?? [] as $name) {
        $name = trim((string) $name);
        if ($name !== '' && !in_array($name, $unknownPlaces, true)) {
            $unknownPlaces[] = $name;
        }
    }
}

pdf_parse_json([
    'ok' => true,
    'token' => $stored['token'],
    'pdf_filename' => $stored['filename'],
    'plans' => $plans,
    'unknown_places' => $unknownPlaces,
    'warnings' => array_values(array_unique($warnings)),
]);
