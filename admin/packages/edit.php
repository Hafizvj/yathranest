<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_form_helpers.php';

$id = (int) get_query('id', '0');
$row = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    if (!$row) {
        flash_set('error', 'Package not found.');
        redirect('admin/packages/index.php');
    }
}

$places = places_all();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $oldImage = (string) ($row['image'] ?? '');
        $oldGallery = json_decode_array($row['gallery_json'] ?? null);

        $data = package_form_data_from_post($row);
        $errors = array_merge($errors, package_form_validate($data));

        $takeUploadError = static function () use (&$errors): void {
            $message = admin_upload_last_error();
            if ($message) {
                $errors[] = $message;
                admin_upload_last_error('');
            }
        };

        $newHero = !empty($_FILES['image_file']['name']);
        $removeHero = !empty($_POST['remove_image']);
        if (!$newHero && ($removeHero || $oldImage === '')) {
            $errors[] = 'A cover image is required.';
        }

        // Uploads only run once the rest of the form is valid, so a rejected
        // save does not leave orphaned files behind.
        $gallery = $oldGallery;
        if (!$errors) {
            $gallery = admin_collect_media_paths('gallery_keep', '', $_FILES['gallery_files'] ?? null, 'packages');
            $takeUploadError();
            $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

            if ($newHero) {
                $uploaded = admin_apply_image_upload($_FILES['image_file'], 'packages', $oldImage);
                $takeUploadError();
                if ($uploaded) {
                    $data['image'] = $uploaded;
                }
            } elseif ($removeHero) {
                admin_delete_upload($oldImage);
                $data['image'] = '';
            }

            $data['itinerary_pdf'] = admin_apply_pdf_field(
                'itinerary_pdf_file',
                'remove_itinerary_pdf',
                (string) ($row['itinerary_pdf'] ?? ''),
                'packages'
            );
            $takeUploadError();
            $data['price_chart_pdf'] = admin_apply_pdf_field(
                'price_chart_pdf_file',
                'remove_price_chart_pdf',
                (string) ($row['price_chart_pdf'] ?? ''),
                'packages'
            );
            $takeUploadError();
        }

        if (!$errors) {
            if ($id) {
                admin_package_update($id, $data);
                admin_remove_missing_uploads($oldGallery, $gallery);
                flash_set('success', 'Package updated.');
            } else {
                admin_package_insert($data);
                flash_set('success', 'Package created.');
            }
            redirect('admin/packages/index.php');
        }
        $row = array_merge($row ?: [], $data);
    }
}

$selectedTypes = package_selected_types($row);
$typeOptions = package_type_options();
foreach ($selectedTypes as $type) {
    if (!isset($typeOptions[$type])) {
        $typeOptions[$type] = ucwords(str_replace('-', ' ', $type));
    }
}
$selectedDestinations = package_selected_destinations($row);
$highlights = json_decode_array($row['highlights_json'] ?? null);
$galleryPaths = json_decode_array($row['gallery_json'] ?? null);

$days = max(1, (int) ($row['days'] ?? 4));
$nights = max(0, (int) ($row['nights'] ?? 3));
$stays = package_stays_from_row($row, $nights);

$itinerary = json_decode_array($row['itinerary_json'] ?? null);
if (!$itinerary) {
    $itinerary = array_fill(0, $days, ['title' => '', 'text' => '']);
}

$placeOptions = [];
foreach ($places as $place) {
    $placeOptions[] = ['slug' => $place['slug'], 'label' => $place['label']];
}

ob_start();
?>
<form class="form-cards" method="post" enctype="multipart/form-data" data-package-form>
  <?= csrf_field() ?>
  <?php if ($errors): ?>
    <div class="admin-alert admin-alert--err" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
  <?php endif; ?>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('file-text') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Basic Information</h2>
        <p class="form-card__hint">Give your package a title and short description.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field">
        <label for="title">Title <span class="field__req">*</span></label>
        <input class="form-control" id="title" name="title" required value="<?= e($row['title'] ?? '') ?>" placeholder="e.g., Wayanad 4 Days 3 Nights" />
      </div>
      <div class="field">
        <label for="card_text">Card Text (one line)</label>
        <input class="form-control" id="card_text" name="card_text" maxlength="90" value="<?= e($row['card_text'] ?? '') ?>" placeholder="Short text shown on package card" />
        <span class="field__counter" data-counter-for="card_text"></span>
      </div>
      <div class="field">
        <label for="overview">Overview <span class="field__req">*</span></label>
        <textarea class="form-control" id="overview" name="overview" rows="5" maxlength="500" required placeholder="Describe this package in brief..."><?= e($row['overview'] ?? '') ?></textarea>
        <span class="field__counter" data-counter-for="overview"></span>
      </div>
      <div class="field" data-highlights>
        <label for="highlight-entry">Highlights</label>
        <div class="chips-input" data-highlight-list>
          <?php foreach ($highlights as $highlight): ?>
            <span class="chip">
              <?= e((string) $highlight) ?>
              <input type="hidden" name="highlights[]" value="<?= e((string) $highlight) ?>" />
              <button class="chip__remove" type="button" data-chip-remove aria-label="Remove <?= e((string) $highlight) ?>">&times;</button>
            </span>
          <?php endforeach; ?>
          <input class="chips-input__entry" id="highlight-entry" type="text" name="highlights_extra" data-highlight-entry placeholder="Add a highlight and press Enter" />
        </div>
        <button class="chips-add" type="button" data-highlight-add><?= yn_icon('plus') ?>Add highlight</button>
      </div>
    </div>
  </section>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('compass') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Package Details</h2>
        <p class="form-card__hint">Select type, destinations, duration and pickup point.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field">
        <span class="field__label">Type <span class="field__req">*</span></span>
        <div class="picker" data-picker>
          <div class="picker__control" data-picker-control>
            <span class="picker__chips" data-picker-chips></span>
            <button class="picker__toggle" type="button" data-picker-toggle aria-expanded="false">
              <span class="picker__placeholder" data-picker-empty>Select types</span>
              <span class="picker__caret"><?= yn_icon('chevron-down') ?></span>
            </button>
          </div>
          <div class="picker__panel" data-picker-panel>
            <?php foreach ($typeOptions as $value => $label): ?>
              <label><input type="checkbox" name="types[]" value="<?= e($value) ?>" <?= in_array($value, $selectedTypes, true) ? 'checked' : '' ?> /> <?= e($label) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="field">
        <span class="field__label">Destinations <span class="field__req">*</span></span>
        <?php if (!$places): ?>
          <p class="field__hint">Add places first so they can be selected here.</p>
        <?php else: ?>
          <div class="picker" data-picker>
            <div class="picker__control" data-picker-control>
              <span class="picker__chips" data-picker-chips></span>
              <button class="picker__toggle" type="button" data-picker-toggle aria-expanded="false">
                <span class="picker__placeholder" data-picker-empty>Select destinations</span>
                <span class="picker__caret"><?= yn_icon('chevron-down') ?></span>
              </button>
            </div>
            <div class="picker__panel" data-picker-panel>
              <?php foreach ($places as $place): ?>
                <label><input type="checkbox" name="destinations[]" value="<?= e($place['slug']) ?>" <?= in_array($place['slug'], $selectedDestinations, true) ? 'checked' : '' ?> /> <?= e($place['label']) ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <p class="field__hint">Listing pages are set from these places.</p>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="pickup">Pickup / Drop <span class="field__req">*</span></label>
        <input class="form-control" id="pickup" name="pickup" required value="<?= e($row['pickup'] ?? '') ?>" placeholder="Calicut" />
        <p class="field__hint">Pickup and drop are the same place.</p>
      </div>
      <div class="field full">
        <label for="days">Duration <span class="field__req">*</span></label>
        <div class="duration-grid">
          <span class="input-icon">
            <?= yn_icon('sun') ?>
            <input class="form-control" id="days" type="number" name="days" min="1" max="60" required value="<?= $days ?>" aria-label="Days" data-days-count />
            <span class="input-icon__suffix">Days</span>
          </span>
          <span class="input-icon">
            <?= yn_icon('moon') ?>
            <input class="form-control" id="nights" type="number" name="nights" min="0" max="60" required value="<?= $nights ?>" aria-label="Nights" data-nights />
            <span class="input-icon__suffix">Nights</span>
          </span>
        </div>
      </div>
      <div class="field full" data-stays data-places="<?= e(json_encode($placeOptions, JSON_UNESCAPED_UNICODE)) ?>">
        <span class="field__label">Stays <span class="field__req">*</span></span>
        <div class="stay-grid" data-stay-grid>
          <?php if ($nights < 1): ?>
            <p class="field__hint">No overnight stays for this duration.</p>
          <?php else: ?>
            <?php for ($i = 0; $i < $nights; $i++): ?>
              <div class="stay-grid__item">
                <label for="stay-<?= $i ?>">Night <?= $i + 1 ?></label>
                <select class="form-control" id="stay-<?= $i ?>" name="stays[]">
                  <option value="">Select a place</option>
                  <?php foreach ($places as $place): ?>
                    <option value="<?= e($place['slug']) ?>" <?= (($stays[$i] ?? '') === $place['slug']) ? 'selected' : '' ?>><?= e($place['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endfor; ?>
          <?php endif; ?>
        </div>
        <p class="field__hint">Where guests sleep each night — this becomes the stay summary.</p>
      </div>
    </div>
  </section>

  <section class="form-card" data-days>
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('list') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Itinerary</h2>
        <p class="form-card__hint">Add a day by day plan for this package.</p>
      </div>
      <button class="btn btn--primary btn--sm" type="button" data-day-add><?= yn_icon('plus') ?>Add Day</button>
    </div>
    <div class="form-card__body">
      <div class="day-list full" data-day-list>
        <?php foreach (array_values($itinerary) as $index => $day): ?>
          <div class="day-item<?= $index === 0 ? '' : ' is-collapsed' ?>">
            <div class="day-item__head">
              <span class="day-item__no"><?= $index + 1 ?></span>
              <span class="day-item__label">Day <?= $index + 1 ?></span>
              <input class="day-item__title" type="text" name="itinerary_title[]" value="<?= e((string) ($day['title'] ?? '')) ?>" placeholder="Day title" aria-label="Day <?= $index + 1 ?> title" />
              <span class="day-item__actions">
                <button class="icon-btn" type="button" data-day-copy aria-label="Duplicate day"><?= yn_icon('copy') ?></button>
                <button class="icon-btn icon-btn--danger" type="button" data-day-remove aria-label="Delete day"><?= yn_icon('trash') ?></button>
                <button class="icon-btn day-toggle" type="button" data-day-toggle aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-label="Toggle day details"><?= yn_icon('chevron-down') ?></button>
              </span>
            </div>
            <div class="day-item__body">
              <label class="field__label" for="day-text-<?= $index ?>">Details (optional)</label>
              <textarea class="form-control" id="day-text-<?= $index ?>" name="itinerary_text[]" rows="3" maxlength="1000" placeholder="Add more details about this day (activities, places, meals, etc.)"><?= e((string) ($day['text'] ?? '')) ?></textarea>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('image') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Media</h2>
        <p class="form-card__hint">Upload images to showcase your package.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field media-field">
        <span class="field__label">Cover Image <span class="field__req">*</span></span>
        <p class="field__hint">This will be shown as the main image on package cards.</p>
        <?php if (!empty($row['image'])): ?>
          <?= admin_hero_preview($row['image']) ?>
        <?php endif; ?>
        <div class="media-live-preview" hidden>
          <div class="media-preview">
            <div class="media-preview__item media-preview__item--hero">
              <img id="package-hero-preview" alt="New cover preview" hidden />
            </div>
          </div>
        </div>
        <label class="dropzone" data-dropzone>
          <span class="dropzone__icon"><?= yn_icon('upload') ?></span>
          <span class="dropzone__body">
            <span class="dropzone__text">Upload cover image</span>
            <span class="dropzone__hint">JPG, PNG, WEBP up to 5MB</span>
          </span>
          <input id="image_file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#package-hero-preview" <?= empty($row['image']) ? 'data-cover-required' : '' ?> />
        </label>
        <span class="file-pick__name" data-file-name-for="image_file"><?= empty($row['image']) ? 'No file chosen' : 'Keeping current image' ?></span>
        <p class="field__error" data-cover-error hidden>Choose a cover image before saving.</p>
        <?php if (!empty($row['image'])): ?>
          <label class="checks"><input type="checkbox" name="remove_image" value="1" /> Remove current cover image</label>
        <?php endif; ?>
      </div>
      <div class="field media-field">
        <span class="field__label">Gallery Images</span>
        <p class="field__hint">Add multiple images to showcase this package.</p>
        <?= admin_media_preview_items($galleryPaths, 'gallery_keep') ?>
        <label class="dropzone" data-dropzone>
          <span class="dropzone__icon"><?= yn_icon('upload') ?></span>
          <span class="dropzone__body">
            <span class="dropzone__text">Upload gallery images</span>
            <span class="dropzone__hint">Up to 10 images (5MB each)</span>
          </span>
          <input id="gallery_files" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#package-gallery-new" />
        </label>
        <span class="file-pick__name" data-file-name-for="gallery_files">No files chosen</span>
        <div id="package-gallery-new" class="media-preview"></div>
      </div>
    </div>
  </section>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('download') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Downloads &amp; Publishing</h2>
        <p class="form-card__hint">Upload documents and set listing preferences.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field">
        <span class="field__label">Itinerary PDF</span>
        <p class="field__hint">Visitors can download this file.</p>
        <div class="file-pick">
          <label class="file-pick__btn"><?= yn_icon('upload') ?>Upload PDF
            <input id="itinerary_pdf_file" type="file" name="itinerary_pdf_file" accept="application/pdf,.pdf" />
          </label>
          <span class="file-pick__name" data-file-name-for="itinerary_pdf_file">No file chosen</span>
        </div>
        <?php if (!empty($row['itinerary_pdf'])): ?>
          <p class="field__hint"><a href="<?= e(asset_url((string) $row['itinerary_pdf'])) ?>" target="_blank" rel="noopener">Current itinerary PDF</a></p>
          <label class="checks"><input type="checkbox" name="remove_itinerary_pdf" value="1" /> Remove</label>
        <?php endif; ?>
      </div>
      <div class="field">
        <span class="field__label">Price Chart PDF</span>
        <p class="field__hint">Upload price chart / rate details.</p>
        <div class="file-pick">
          <label class="file-pick__btn"><?= yn_icon('upload') ?>Upload PDF
            <input id="price_chart_pdf_file" type="file" name="price_chart_pdf_file" accept="application/pdf,.pdf" />
          </label>
          <span class="file-pick__name" data-file-name-for="price_chart_pdf_file">No file chosen</span>
        </div>
        <?php if (!empty($row['price_chart_pdf'])): ?>
          <p class="field__hint"><a href="<?= e(asset_url((string) $row['price_chart_pdf'])) ?>" target="_blank" rel="noopener">Current price chart PDF</a></p>
          <label class="checks"><input type="checkbox" name="remove_price_chart_pdf" value="1" /> Remove</label>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="sort_order">Display Order</label>
        <p class="field__hint">Set display order in listings.</p>
        <span class="input-icon">
          <?= yn_icon('list') ?>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </span>
      </div>
      <div class="field">
        <span class="field__label">Featured Package</span>
        <p class="field__hint">Show on homepage and rank first in listings.</p>
        <div class="switch-field">
          <span class="switch">
            <input id="is_featured" type="checkbox" name="is_featured" value="1" aria-label="Featured package" <?= !empty($row['is_featured']) ? 'checked' : '' ?> />
            <span class="switch__track"></span>
          </span>
          <span class="switch-field__state" data-switch-state="is_featured">No</span>
        </div>
      </div>
    </div>
  </section>

  <div class="form-footer">
    <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit"><?= yn_icon('check') ?>Save Package</button>
  </div>
</form>
<template id="icon-copy"><?= yn_icon('copy') ?></template>
<template id="icon-trash"><?= yn_icon('trash') ?></template>
<template id="icon-chevron-down"><?= yn_icon('chevron-down') ?></template>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit Package' : 'Add Package';
$pageSubtitle = $id ? 'Update this travel package.' : 'Create and publish a new travel package.';
$adminScripts = ['admin/assets/package-form.js'];
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
