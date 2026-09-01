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

$_POST['save_mode'] = 'draft';
$wizardType = trim(post('wizard_type', 'ai'));

if ($wizardType === 'pdf') {
    $pdfToken = trim(post('pdf_token'));
    $postedPlans = isset($_POST['plans']) && is_array($_POST['plans']) ? array_values($_POST['plans']) : [];
    if ($postedPlans === []) {
        echo json_encode(['ok' => false, 'error' => 'No plans to save.']);
        exit;
    }

    $shared = [
        'itinerary_pdf' => '',
        'save_mode' => 'draft',
        'sort_order' => (int) post('sort_order', '0'),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
    ];

    $errors = [];
    if ($pdfToken !== '' && admin_temp_pdf_full_path($pdfToken) !== null) {
        $itineraryPdf = admin_finalize_temp_pdf($pdfToken, 'packages');
        if ($itineraryPdf) {
            $shared['itinerary_pdf'] = $itineraryPdf;
        } else {
            $message = admin_upload_last_error();
            if ($message) {
                $errors[] = $message;
                admin_upload_last_error('');
            }
        }
    }
    $reservedSlugs = [];
    $saved = [];
    $step = (int) post('wizard_step', '2');

    foreach ($postedPlans as $i => $planPost) {
        if (!is_array($planPost)) {
            continue;
        }
        $packageId = (int) ($planPost['package_id'] ?? 0);
        $existing = $packageId > 0 ? admin_package_by_id($packageId) : null;
        $planShared = $shared;
        if ($planShared['itinerary_pdf'] === '' && $existing && !empty($existing['itinerary_pdf'])) {
            $planShared['itinerary_pdf'] = (string) $existing['itinerary_pdf'];
        }
        $data = package_form_data_from_plan_post($planPost, $planShared, $reservedSlugs);
        $planLabel = trim((string) ($planPost['plan_label'] ?? ('Plan ' . ($i + 1))));
        $planErrors = package_form_validate($data, 'draft');
        foreach ($planErrors as $err) {
            $errors[] = $planLabel . ': ' . $err;
        }
        if ($planErrors) {
            continue;
        }

        if ($step >= 3) {
            $mediaErrors = admin_package_apply_plan_media_uploads($data, $i, $existing, false);
            foreach ($mediaErrors as $err) {
                $errors[] = $planLabel . ': ' . $err;
            }
            if ($mediaErrors) {
                continue;
            }
        }

        $result = admin_package_upsert($data, $packageId);
        $saved[] = $result;
    }

    if ($errors) {
        echo json_encode(['ok' => false, 'error' => implode(' ', $errors), 'packages' => $saved]);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Saved ' . count($saved) . ' draft' . (count($saved) === 1 ? '' : 's') . '.',
        'packages' => $saved,
    ]);
    exit;
}

$packageId = (int) post('package_id', '0');
$existing = $packageId > 0 ? admin_package_by_id($packageId) : null;
$data = package_form_data_from_post($existing);
$errors = package_form_validate($data, 'draft');

$step = (int) post('wizard_step', '1');
if ($step >= 3) {
    $errors = array_merge($errors, admin_package_apply_media_uploads($data, $existing, false));
}

if ($errors) {
    echo json_encode(['ok' => false, 'error' => implode(' ', $errors)]);
    exit;
}

$result = admin_package_upsert($data, $packageId);
echo json_encode([
    'ok' => true,
    'message' => 'Draft saved.',
    'id' => $result['id'],
    'slug' => $result['slug'],
    'preview_url' => $result['preview_url'],
    'packages' => [$result],
]);
