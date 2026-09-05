<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_form_helpers.php';

$places = places_all();
$typeOptions = package_type_options();
$errors = [];
$pdfToken = '';
$pdfFilename = '';
$postedPlans = [];
$startStep = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $saveMode = package_save_mode_from_post();
        $pdfToken = trim(post('pdf_token'));
        $pdfFilename = trim(post('pdf_filename'));
        $postedPlans = isset($_POST['plans']) && is_array($_POST['plans']) ? array_values($_POST['plans']) : [];

        if ($pdfToken === '' || admin_temp_pdf_full_path($pdfToken) === null) {
            $errors[] = 'Uploaded PDF session expired. Go back and parse the PDF again.';
        }
        if ($postedPlans === []) {
            $errors[] = 'No plans to save.';
        }

        $shared = [
            'itinerary_pdf' => '',
            'save_mode' => $saveMode,
            'sort_order' => (int) post('sort_order', '0'),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        ];

        $takeUploadError = static function () use (&$errors): void {
            $message = admin_upload_last_error();
            if ($message) {
                $errors[] = $message;
                admin_upload_last_error('');
            }
        };

        if (!$errors) {
            $itineraryPdf = admin_finalize_temp_pdf($pdfToken, 'packages');
            $takeUploadError();
            if ($itineraryPdf) {
                $shared['itinerary_pdf'] = $itineraryPdf;
            } else {
                $errors[] = admin_upload_last_error() ?: 'Could not attach itinerary PDF.';
            }
        }

        $requireCover = $saveMode === 'publish';
        $reservedSlugs = [];
        $planRows = [];
        if (!$errors) {
            foreach ($postedPlans as $i => $planPost) {
                if (!is_array($planPost)) {
                    continue;
                }
                $packageId = (int) ($planPost['package_id'] ?? 0);
                $existing = $packageId > 0 ? admin_package_by_id($packageId) : null;
                $data = package_form_data_from_plan_post($planPost, $shared, $reservedSlugs);
                $planLabel = trim((string) ($planPost['plan_label'] ?? ('Plan ' . ($i + 1))));
                $planErrors = package_form_validate($data, $saveMode);
                foreach ($planErrors as $err) {
                    $errors[] = $planLabel . ': ' . $err;
                }
                if ($planErrors) {
                    continue;
                }

                $mediaErrors = admin_package_apply_plan_media_uploads($data, $i, $existing, $requireCover);
                foreach ($mediaErrors as $err) {
                    $errors[] = $planLabel . ': ' . $err;
                }
                if ($mediaErrors) {
                    continue;
                }

                $planRows[] = ['id' => $packageId, 'data' => $data];
            }
        }

        if (!$errors && $planRows !== []) {
            foreach ($planRows as $row) {
                admin_package_upsert($row['data'], (int) $row['id']);
            }
            $count = count($planRows);
            flash_set(
                'success',
                $saveMode === 'publish'
                    ? 'Published ' . $count . ' package' . ($count === 1 ? '' : 's') . ' from PDF.'
                    : 'Saved ' . $count . ' draft' . ($count === 1 ? '' : 's') . '.'
            );
            redirect('admin/packages/index.php');
        }

        $startStep = 3;
    }
}

$placeOptions = [];
foreach ($places as $place) {
    $placeOptions[] = ['slug' => $place['slug'], 'label' => $place['label']];
}
$highlightSuggestions = package_highlight_suggestions();
$pickupSuggestions = package_pickup_suggestions();
$catalogScopes = catalog_scope_options();
$hasAiKey = trim((string) config('gemini_api_key', '')) !== '';

ob_start();
?>
<div class="ai-wizard" data-ai-wizard data-step="<?= (int) $startStep ?>">
  <div class="ai-wizard__top">
    <nav class="ai-steps" data-ai-steps aria-label="Create package from PDF">
      <ol class="ai-steps__list">
        <li class="ai-steps__item is-active" data-ai-step-indicator="1">
          <span class="ai-steps__num">1</span>
          <span class="ai-steps__label">Upload</span>
        </li>
        <li class="ai-steps__item" data-ai-step-indicator="2">
          <span class="ai-steps__num">2</span>
          <span class="ai-steps__label">Review plans</span>
        </li>
        <li class="ai-steps__item" data-ai-step-indicator="3">
          <span class="ai-steps__num">3</span>
          <span class="ai-steps__label">Media</span>
        </li>
      </ol>
      <span class="ai-steps__fill" data-ai-progress hidden></span>
    </nav>
    <a class="ai-wizard__manual" href="<?= e(url('admin/packages/edit.php')) ?>">Manual form</a>
  </div>

<?php if (!$hasAiKey): ?>
  <div class="admin-alert admin-alert--err" role="status">
    <span>Add <code>gemini_api_key</code> in <code>config/config.php</code> to parse PDFs.</span>
  </div>
<?php endif; ?>

<form
  class="form-cards ai-wizard__form"
  method="post"
  enctype="multipart/form-data"
  data-rich-form
  data-pdf-package-form
  data-pdf-parse-url="<?= e(url('admin/packages/pdf-parse.php')) ?>"
  data-save-draft-url="<?= e(url('admin/packages/save-draft.php')) ?>"
  data-quick-add-place-url="<?= e(url('admin/packages/quick-add-place.php')) ?>"
  data-catalog-scopes="<?= e(json_encode($catalogScopes, JSON_UNESCAPED_UNICODE)) ?>"
  data-pdf-start-step="<?= (int) $startStep ?>"
  data-places="<?= e(json_encode($placeOptions, JSON_UNESCAPED_UNICODE)) ?>"
  data-types="<?= e(json_encode($typeOptions, JSON_UNESCAPED_UNICODE)) ?>"
  data-highlights="<?= e(json_encode($highlightSuggestions, JSON_UNESCAPED_UNICODE)) ?>"
  data-pickups="<?= e(json_encode($pickupSuggestions, JSON_UNESCAPED_UNICODE)) ?>"
  data-repost-plans="<?= e(json_encode($postedPlans, JSON_UNESCAPED_UNICODE)) ?>"
>
  <?= csrf_field() ?>
  <input type="hidden" name="save_mode" value="publish" data-save-mode />
  <input type="hidden" name="wizard_step" value="1" data-wizard-step />
  <input type="hidden" name="pdf_token" value="<?= e($pdfToken) ?>" data-pdf-token />
  <input type="hidden" name="pdf_filename" value="<?= e($pdfFilename) ?>" data-pdf-filename />

  <?php if ($errors): ?>
    <div class="admin-alert admin-alert--err" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
  <?php endif; ?>
  <div class="admin-alert admin-alert--err" role="alert" data-pdf-error hidden><span></span></div>
  <div class="admin-alert admin-alert--warn" role="status" data-pdf-warnings hidden><span></span></div>
  <div class="ai-status" data-pdf-toast hidden></div>

  <div data-pdf-panel="1">
    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Upload itinerary PDF</h2>
          <p class="form-card__hint">Each plan variant in the PDF (e.g. Sightseeing Plan, Trekking Plan) becomes its own package.</p>
        </div>
      </div>
      <div class="form-card__body">
        <div class="field full">
          <span class="field__label">PDF file <span class="field__req">*</span></span>
          <div class="file-pick">
            <label class="file-pick__btn"><?= yn_icon('upload') ?>Choose PDF
              <input id="pdf_file_input" type="file" accept="application/pdf,.pdf" data-pdf-file />
            </label>
            <span class="file-pick__name" data-pdf-file-name>No file chosen</span>
          </div>
          <p class="field__hint">PDF up to 10 MB. The same file is attached as itinerary PDF on every package created.</p>
        </div>
        <div class="ai-status" data-pdf-status hidden></div>
        <div class="pdf-parse-summary" data-pdf-summary hidden></div>
      </div>
    </section>
  </div>

  <div data-pdf-panel="2" hidden>
    <div class="pdf-plans-layout">
      <aside class="pdf-plans-sidebar" aria-label="Extracted plans">
        <p class="pdf-plans-sidebar__title">Plans</p>
        <ul class="pdf-plans-sidebar__list" data-pdf-plans-sidebar></ul>
      </aside>
      <div class="pdf-plans-editor" data-pdf-plans-editor>
        <div data-pdf-plans-list></div>
      </div>
    </div>
  </div>

  <div data-pdf-panel="3" hidden>
    <div class="pdf-plans-layout">
      <aside class="pdf-plans-sidebar" aria-label="Package media">
        <p class="pdf-plans-sidebar__title">Plans</p>
        <ul class="pdf-plans-sidebar__list" data-pdf-media-sidebar></ul>
      </aside>
      <div class="pdf-plans-editor" data-pdf-media-editor></div>
    </div>

    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Listing options</h2>
        </div>
      </div>
      <div class="form-card__body">
        <div class="field">
          <span class="field__label">Itinerary PDF</span>
          <p class="field__hint" data-pdf-attached-hint>Attached automatically from your upload.</p>
        </div>
        <div class="field">
          <label for="sort_order">Order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="0" />
        </div>
        <div class="field">
          <span class="field__label">Featured</span>
          <div class="switch-field">
            <span class="switch">
              <input id="is_featured" type="checkbox" name="is_featured" value="1" aria-label="Featured package" />
              <span class="switch__track"></span>
            </span>
            <span class="switch-field__state" data-switch-state="is_featured">No</span>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="ai-wizard__footer form-footer" data-pdf-footer>
    <div class="ai-wizard__footer-left">
      <a class="btn btn--ghost" href="<?= e(url('admin/packages/index.php')) ?>" data-pdf-cancel>Cancel</a>
      <button class="btn btn--ghost" type="button" data-pdf-back hidden>Back</button>
    </div>
    <div class="ai-wizard__footer-right">
      <button class="btn btn--primary" type="button" data-pdf-parse><?= yn_icon('upload') ?>Upload and Parse</button>
      <button class="btn btn--secondary" type="button" data-pdf-save-draft hidden>Save</button>
      <button class="btn btn--primary" type="button" data-pdf-next hidden>Continue</button>
      <button class="btn btn--secondary" type="button" data-pdf-preview hidden>Preview</button>
      <button class="btn btn--primary" type="submit" data-pdf-publish hidden>Publish</button>
    </div>
  </div>
</form>
</div>

<div class="pdf-place-modal" data-pdf-place-modal hidden>
  <div class="pdf-place-modal__backdrop" data-pdf-place-modal-backdrop></div>
  <div class="pdf-place-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pdf-place-modal-title">
    <div class="pdf-place-modal__head">
      <h2 class="pdf-place-modal__title" id="pdf-place-modal-title">Unknown location</h2>
      <p class="pdf-place-modal__hint" data-pdf-place-modal-hint></p>
    </div>
    <div class="pdf-place-modal__body">
      <div class="field">
        <label for="pdf-place-modal-label">Place name</label>
        <input class="form-control" id="pdf-place-modal-label" type="text" data-pdf-place-modal-label />
      </div>
      <div class="field">
        <label for="pdf-place-modal-scope">Listing category</label>
        <select class="form-control" id="pdf-place-modal-scope" data-pdf-place-modal-scope>
          <?php foreach ($catalogScopes as $scopeKey => $scopeLabel): ?>
            <option value="<?= e($scopeKey) ?>"<?= $scopeKey === 'kerala' ? ' selected' : '' ?>><?= e($scopeLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <p class="field__hint" data-pdf-place-modal-error hidden></p>
    </div>
    <div class="pdf-place-modal__footer">
      <button class="btn btn--ghost" type="button" data-pdf-place-modal-skip>Skip</button>
      <button class="btn btn--primary" type="button" data-pdf-place-modal-add>Add place</button>
    </div>
  </div>
</div>

<template id="icon-copy"><?= yn_icon('copy') ?></template>
<template id="icon-trash"><?= yn_icon('trash') ?></template>
<template id="icon-chevron-down"><?= yn_icon('chevron-down') ?></template>
<template id="icon-upload"><?= yn_icon('upload') ?></template>
<template id="icon-image"><?= yn_icon('image') ?></template>
<template id="icon-check"><?= yn_icon('check') ?></template>
<template id="icon-plus"><?= yn_icon('plus') ?></template>
<template id="icon-info"><?= yn_icon('info') ?></template>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Add Package from PDF';
$pageSubtitle = 'Upload → review plans → media.';
$adminScripts = ['admin/assets/admin-form.js', 'admin/assets/admin-pdf-package.js'];
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
