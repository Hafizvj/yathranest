<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_form_helpers.php';

$row = null;
$places = places_all();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $data = package_form_data_from_post(null);
        $errors = array_merge($errors, package_form_validate($data));

        $takeUploadError = static function () use (&$errors): void {
            $message = admin_upload_last_error();
            if ($message) {
                $errors[] = $message;
                admin_upload_last_error('');
            }
        };

        $newHero = !empty($_FILES['image_file']['name']);
        $libraryCover = trim(post('library_image'));
        $hasCover = $newHero || $libraryCover !== '';
        if (!$hasCover) {
            $errors[] = 'A cover image is required.';
        }

        $gallery = [];
        if (!$errors) {
            $gallery = admin_collect_media_paths('gallery_keep', '', $_FILES['gallery_files'] ?? null, 'packages');
            $takeUploadError();
            if (count($gallery) > 10) {
                $gallery = array_slice($gallery, 0, 10);
            }
            $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

            if ($newHero) {
                $uploaded = admin_apply_image_upload($_FILES['image_file'], 'packages', '');
                $takeUploadError();
                if ($uploaded) {
                    $data['image'] = $uploaded;
                }
            } elseif ($libraryCover !== '') {
                $data['image'] = ltrim($libraryCover, '/');
                media_ensure_row($data['image']);
            }

            $data['itinerary_pdf'] = admin_apply_pdf_field(
                'itinerary_pdf_file',
                'remove_itinerary_pdf',
                '',
                'packages'
            );
            $takeUploadError();
            $data['price_chart_pdf'] = admin_apply_pdf_field(
                'price_chart_pdf_file',
                'remove_price_chart_pdf',
                '',
                'packages'
            );
            $takeUploadError();
        }

        if (!$errors) {
            admin_package_insert($data);
            flash_set('success', 'Package created.');
            redirect('admin/packages/index.php');
        }
        $row = $data;
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

$startStep = $errors ? 3 : 1;
$hasAiKey = trim((string) config('gemini_api_key', '')) !== '';

ob_start();
?>
<div class="ai-wizard" data-ai-wizard data-step="<?= (int) $startStep ?>">
  <div class="ai-wizard__top">
    <nav class="ai-steps" data-ai-steps aria-label="Create package steps">
      <ol class="ai-steps__list">
        <li class="ai-steps__item is-active" data-ai-step-indicator="1">
          <span class="ai-steps__num">1</span>
          <span class="ai-steps__label">Facts</span>
        </li>
        <li class="ai-steps__item" data-ai-step-indicator="2">
          <span class="ai-steps__num">2</span>
          <span class="ai-steps__label">Content</span>
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
    <span>Add <code>gemini_api_key</code> in <code>config/config.php</code> to enable AI. Step 2 can still be filled manually.</span>
  </div>
<?php endif; ?>

<form
  class="form-cards ai-wizard__form"
  method="post"
  enctype="multipart/form-data"
  data-rich-form
  data-ai-package-form
  data-ai-generate-url="<?= e(url('admin/packages/ai-generate.php')) ?>"
  data-ai-start-step="<?= (int) $startStep ?>"
>
  <?= csrf_field() ?>
  <?php if ($errors): ?>
    <div class="admin-alert admin-alert--err" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
  <?php endif; ?>
  <div class="admin-alert admin-alert--err" role="alert" data-ai-error hidden><span></span></div>

  <div data-ai-panel="1">
    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Trip facts</h2>
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
            <p class="field__hint">Add places first.</p>
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
          <?php endif; ?>
        </div>
        <div class="field">
          <label for="pickup">Pickup / Drop <span class="field__req">*</span></label>
          <input class="form-control" id="pickup" name="pickup" value="<?= e($row['pickup'] ?? '') ?>" placeholder="Calicut" />
        </div>
        <div class="field">
          <label for="days">Duration <span class="field__req">*</span></label>
          <div class="duration-grid">
            <span class="input-icon">
              <?= yn_icon('sun') ?>
              <input class="form-control" id="days" type="number" name="days" min="1" max="60" value="<?= $days ?>" aria-label="Days" data-days-count />
              <span class="input-icon__suffix">Days</span>
            </span>
            <span class="input-icon">
              <?= yn_icon('moon') ?>
              <input class="form-control" id="nights" type="number" name="nights" min="0" max="60" value="<?= $nights ?>" aria-label="Nights" data-nights />
              <span class="input-icon__suffix">Nights</span>
            </span>
          </div>
        </div>
        <div class="field full" data-stays data-places="<?= e(json_encode($placeOptions, JSON_UNESCAPED_UNICODE)) ?>">
          <span class="field__label">Stays <span class="field__req">*</span></span>
          <div class="stay-grid" data-stay-grid>
            <?php if ($nights < 1): ?>
              <p class="field__hint">No overnight stays.</p>
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
        </div>
        <div class="field full" data-chips="highlights">
          <label for="highlight-entry">Highlights</label>
          <div class="chips-input" data-chips-list>
            <?php foreach ($highlights as $highlight): ?>
              <span class="chip">
                <?= e((string) $highlight) ?>
                <input type="hidden" name="highlights[]" value="<?= e((string) $highlight) ?>" />
                <button class="chip__remove" type="button" data-chip-remove aria-label="Remove <?= e((string) $highlight) ?>">&times;</button>
              </span>
            <?php endforeach; ?>
            <input class="chips-input__entry" id="highlight-entry" type="text" name="highlights_extra" data-chips-entry placeholder="Add highlight + Enter" />
          </div>
          <button class="chips-add" type="button" data-chips-add><?= yn_icon('plus') ?>Add</button>
        </div>
      </div>
    </section>
  </div>

  <div data-ai-panel="2" hidden>
    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Content</h2>
        </div>
        <button class="btn btn--secondary btn--sm" type="button" data-ai-generate>
          <?= yn_icon('sparkle') ?>Generate
        </button>
      </div>
      <div class="form-card__body">
        <div class="ai-status" data-ai-status hidden></div>
        <div class="field full">
          <label for="title">Title <span class="field__req">*</span></label>
          <input class="form-control" id="title" name="title" value="<?= e($row['title'] ?? '') ?>" placeholder="e.g., Munnar Alleppey 4 Days Package" />
        </div>
        <div class="field full">
          <label for="card_text">Card text</label>
          <input class="form-control" id="card_text" name="card_text" maxlength="90" value="<?= e($row['card_text'] ?? '') ?>" placeholder="One line for package cards" />
          <span class="field__counter" data-counter-for="card_text"></span>
        </div>
        <div class="field full">
          <label for="overview">Overview <span class="field__req">*</span></label>
          <textarea class="form-control" id="overview" name="overview" rows="4" maxlength="500" placeholder="Brief package description"><?= e($row['overview'] ?? '') ?></textarea>
          <span class="field__counter" data-counter-for="overview"></span>
        </div>
      </div>
    </section>

    <section class="form-card ai-panel-card" data-days>
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Itinerary</h2>
        </div>
        <button class="btn btn--secondary btn--sm" type="button" data-day-add><?= yn_icon('plus') ?>Day</button>
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
  </div>

  <div data-ai-panel="3" hidden>
    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Media</h2>
        </div>
      </div>
      <div class="form-card__body form-card__body--media">
        <?php
          $coverPath = (string) ($row['image'] ?? '');
          $coverName = $coverPath !== '' ? basename(str_replace('\\', '/', $coverPath)) : '';
          $coverSrc = $coverPath !== '' ? image_url($coverPath) : '';
          $galleryCount = count(array_filter($galleryPaths));
        ?>
        <div class="media-split" data-package-media data-gallery-max="10">
          <div class="media-col media-col--cover">
            <span class="field__label">Cover <span class="field__req">*</span></span>
            <p class="field__hint">Main card image.</p>

            <input type="hidden" name="remove_image" value="0" data-cover-remove />
            <input type="hidden" name="library_image" value="<?= e($coverPath) ?>" data-cover-library />
            <input
              id="image_file"
              class="media-file-input"
              type="file"
              name="image_file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              data-cover-input
              <?= $coverPath === '' ? 'data-cover-required' : '' ?>
            />

            <div class="media-drop media-drop--cover" data-dropzone data-cover-empty<?= $coverPath !== '' ? ' hidden' : '' ?>>
              <span class="media-drop__icon" aria-hidden="true"><?= yn_icon('upload') ?></span>
              <span class="media-drop__title">Upload cover image</span>
              <span class="media-drop__hint">JPG, PNG, WEBP up to 5MB</span>
              <span class="media-drop__divider"><span>or</span></span>
              <button class="media-drop__browse" type="button" data-cover-library>
                <?= yn_icon('image') ?>
                Choose from library
              </button>
            </div>

            <div class="media-cover-card" data-cover-filled<?= $coverPath === '' ? ' hidden' : '' ?>>
              <div class="media-cover-card__preview">
                <img src="<?= e($coverSrc) ?>" alt="Cover preview" data-cover-img<?= $coverSrc === '' ? ' hidden' : '' ?> />
                <button class="media-thumb__remove" type="button" data-cover-clear aria-label="Remove cover image"><?= yn_icon('trash') ?></button>
              </div>
              <div class="media-cover-card__meta">
                <span class="media-file-meta">
                  <span class="media-file-meta__check" aria-hidden="true"><?= yn_icon('check') ?></span>
                  <span data-cover-name><?= e($coverName !== '' ? $coverName : 'cover-image.jpg') ?></span>
                </span>
                <span class="media-file-meta__size" data-cover-size><?= $coverPath !== '' ? 'Saved' : '' ?></span>
              </div>
              <button class="btn btn--secondary media-cover-card__replace" type="button" data-cover-replace>
                <?= yn_icon('upload') ?>
                Replace image
              </button>
            </div>

            <p class="field__error" data-cover-error hidden>Choose a cover image before saving.</p>
          </div>

          <div class="media-col media-col--gallery">
            <span class="field__label">Gallery</span>
            <p class="field__hint">Up to 10 images.</p>

            <input
              id="gallery_files"
              class="media-file-input"
              type="file"
              name="gallery_files[]"
              accept="image/jpeg,image/png,image/webp,image/gif"
              multiple
              data-gallery-input
            />

            <div class="media-drop media-drop--gallery" data-dropzone data-gallery-empty<?= $galleryCount > 0 ? ' hidden' : '' ?>>
              <span class="media-drop__icon" aria-hidden="true"><?= yn_icon('upload') ?></span>
              <span class="media-drop__title">Upload gallery images</span>
              <span class="media-drop__hint">Up to 10 images (5MB each)</span>
              <span class="media-drop__divider"><span>or</span></span>
              <button class="media-drop__browse" type="button" data-gallery-library>
                <?= yn_icon('image') ?>
                Choose from library
              </button>
              <p class="media-drop__note"><?= yn_icon('info') ?> You can upload up to 10 images</p>
            </div>

            <div class="media-gallery-panel" data-gallery-panel<?= $galleryCount === 0 ? ' hidden' : '' ?>>
              <div class="media-gallery-grid" data-gallery-grid>
                <?php foreach ($galleryPaths as $gPath):
                  $gPath = (string) $gPath;
                  if ($gPath === '') {
                      continue;
                  }
                  $gName = basename(str_replace('\\', '/', $gPath));
                  ?>
                  <div class="media-thumb" data-gallery-item data-existing="1">
                    <div class="media-thumb__frame">
                      <img src="<?= e(image_url($gPath)) ?>" alt="" />
                      <button class="media-thumb__remove" type="button" data-gallery-remove aria-label="Remove image"><?= yn_icon('trash') ?></button>
                    </div>
                    <p class="media-thumb__name">
                      <span class="media-file-meta__check" aria-hidden="true"><?= yn_icon('check') ?></span>
                      <span><?= e($gName) ?></span>
                    </p>
                    <input type="hidden" name="gallery_keep[]" value="<?= e($gPath) ?>" />
                  </div>
                <?php endforeach; ?>
                <button class="media-thumb media-thumb--add" type="button" data-gallery-add<?= $galleryCount >= 10 ? ' hidden' : '' ?>>
                  <span class="media-thumb__add-icon" aria-hidden="true"><?= yn_icon('plus') ?></span>
                  <span class="media-thumb__add-title">Add more images</span>
                  <span class="media-thumb__add-hint">Up to 10 images</span>
                </button>
              </div>
              <div class="media-gallery-foot">
                <span data-gallery-count>Selected Images (<?= (int) $galleryCount ?>/10)</span>
                <span data-gallery-status><?= $galleryCount > 0 ? 'Saved images' : 'No images selected' ?></span>
              </div>
              <p class="media-gallery-note"><?= yn_icon('info') ?> You can upload up to 10 images. Each image up to 5MB.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="form-card ai-panel-card">
      <div class="form-card__head">
        <div class="form-card__titles">
          <h2 class="form-card__title">Files &amp; listing</h2>
        </div>
      </div>
      <div class="form-card__body">
        <div class="field">
          <span class="field__label">Itinerary PDF</span>
          <div class="file-pick">
            <label class="file-pick__btn"><?= yn_icon('upload') ?>Upload
              <input id="itinerary_pdf_file" type="file" name="itinerary_pdf_file" accept="application/pdf,.pdf" />
            </label>
            <span class="file-pick__name" data-file-name-for="itinerary_pdf_file">No file chosen</span>
          </div>
        </div>
        <div class="field">
          <span class="field__label">Price chart PDF</span>
          <div class="file-pick">
            <label class="file-pick__btn"><?= yn_icon('upload') ?>Upload
              <input id="price_chart_pdf_file" type="file" name="price_chart_pdf_file" accept="application/pdf,.pdf" />
            </label>
            <span class="file-pick__name" data-file-name-for="price_chart_pdf_file">No file chosen</span>
          </div>
        </div>
        <div class="field">
          <label for="sort_order">Order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </div>
        <div class="field">
          <span class="field__label">Featured</span>
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
  </div>

  <div class="ai-wizard__footer form-footer" data-ai-footer>
    <div class="ai-wizard__footer-left">
      <a class="btn btn--ghost" href="<?= e(url('admin/packages/index.php')) ?>" data-ai-cancel>Cancel</a>
      <button class="btn btn--ghost" type="button" data-ai-back hidden>Back</button>
    </div>
    <div class="ai-wizard__footer-right">
      <button class="btn btn--secondary" type="button" data-ai-generate-footer hidden><?= yn_icon('sparkle') ?>Regenerate</button>
      <button class="btn btn--primary" type="button" data-ai-next>Continue</button>
      <button class="btn btn--primary" type="submit" data-ai-save hidden>Save</button>
    </div>
  </div>
</form>
</div>
<template id="icon-copy"><?= yn_icon('copy') ?></template>
<template id="icon-trash"><?= yn_icon('trash') ?></template>
<template id="icon-chevron-down"><?= yn_icon('chevron-down') ?></template>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Add Package with AI';
$pageSubtitle = 'Facts → content → media.';
$adminScripts = ['admin/assets/admin-form.js', 'admin/assets/admin-ai-package.js'];
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
