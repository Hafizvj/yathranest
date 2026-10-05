<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_catalog.php';
require_once __DIR__ . '/packages/_form_helpers.php';
require_admin();

$cfg = catalog_config($catalogKey ?? '');
if (!$cfg) {
    http_response_code(404);
    echo 'Unknown catalog';
    exit;
}

$table = $cfg['table'];
$isBlurb = !empty($cfg['blurb_field']);
$galleryMax = 12;
$id = (int) get_query('id', '0');
$row = null;
if ($id) {
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/' . $cfg['nav'] . '/index.php');
    }
    $oldImage = (string) ($row['image'] ?? '');
    $oldGallery = json_decode_array($row['gallery_json'] ?? null);
    $slug = post('slug') !== '' ? slugify(post('slug')) : slugify(post('title'));
    $data = [
        'slug' => $slug,
        'title' => post('title'),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'sort_order' => (int) post('sort_order', '0'),
        'image' => $oldImage,
    ];

    $libraryImage = ltrim(post('library_image'), '/');
    if (!empty($_FILES['image_file']['name'])) {
        $up = admin_apply_image_upload($_FILES['image_file'], $cfg['nav'], $oldImage);
        if ($up) {
            $data['image'] = $up;
        } elseif (admin_upload_last_error()) {
            $errors[] = admin_upload_last_error();
        }
    } elseif (post('remove_image') === '1') {
        $data['image'] = '';
    } elseif ($libraryImage !== '' && strpos($libraryImage, '..') === false) {
        $data['image'] = $libraryImage;
        media_ensure_row($libraryImage);
    }

    if ($isBlurb) {
        $data['blurb'] = post('blurb');
        $data['features_json'] = json_encode(chips_from_post('features'), JSON_UNESCAPED_UNICODE);
        if (!empty($cfg['has_resorts'])) {
            $data['resorts_json'] = json_encode(catalog_resort_ids_from_post());
        }
    } else {
        $data['location'] = post('location');
        $data['summary'] = post('summary');
        $data['body'] = post('body');
        if (!empty($cfg['has_category'])) {
            $data['category'] = post('category');
        }
        if (!empty($cfg['has_duration'])) {
            $data['duration'] = post('duration');
        }
        if (!empty($cfg['has_gallery'])) {
            $gallery = admin_collect_media_paths('gallery_keep', '', $_FILES['gallery_files'] ?? null, $cfg['nav']);
            if (admin_upload_last_error()) {
                $errors[] = admin_upload_last_error();
            }
            $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($cfg['has_amenities'])) {
            $data['amenities_json'] = json_encode(chips_from_post('amenities'), JSON_UNESCAPED_UNICODE);
        }
    }

    if ($data['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if (!$errors) {
        $fields = $cfg['fields'];
        $vals = [];
        foreach ($fields as $f) {
            $vals[] = $data[$f] ?? (in_array($f, ['gallery_json', 'amenities_json', 'features_json', 'resorts_json'], true) ? '[]' : '');
        }
        if ($id) {
            $sets = implode(', ', array_map(static fn($f) => "$f = ?", $fields));
            $vals[] = $id;
            db()->prepare("UPDATE {$table} SET {$sets} WHERE id = ?")->execute($vals);
            if (!empty($cfg['has_gallery'])) {
                admin_remove_missing_uploads($oldGallery, json_decode_array($data['gallery_json'] ?? '[]'));
            }
            flash_set('success', 'Updated.');
        } else {
            $cols = implode(', ', $fields);
            $ph = implode(', ', array_fill(0, count($fields), '?'));
            db()->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})")->execute($vals);
            flash_set('success', 'Created.');
        }
        redirect('admin/' . $cfg['nav'] . '/index.php');
    }

    $row = array_merge($row ?: [], $data);
}

$singular = $cfg['singular'] ?? $cfg['label'];
$singularLower = strtolower($singular);
$features = json_decode_array($row['features_json'] ?? null);
$amenities = json_decode_array($row['amenities_json'] ?? null);
$galleryPaths = array_values(array_filter(array_map('strval', json_decode_array($row['gallery_json'] ?? null))));
$galleryCount = count($galleryPaths);
$coverPath = (string) ($row['image'] ?? '');
$coverName = $coverPath !== '' ? basename(str_replace('\\', '/', $coverPath)) : '';
$coverSrc = $coverPath !== '' ? image_url($coverPath) : '';
$isPublished = !isset($row['is_published']) || !empty($row['is_published']);
$publicUrl = ($id && !empty($cfg['public_path']) && !empty($row['slug']) && !empty($row['is_published']))
    ? url($cfg['public_path'] . rawurlencode((string) $row['slug']))
    : '';

$locationSuggestions = $isBlurb ? [] : catalog_distinct_values($table, 'location');
$categorySuggestions = !empty($cfg['has_category']) ? catalog_distinct_values($table, 'category') : [];
$amenitySuggestions = !empty($cfg['has_amenities']) ? catalog_json_list_suggestions($table, 'amenities_json') : [];
$featureSuggestions = $isBlurb ? catalog_json_list_suggestions($table, 'features_json') : [];
$hasResorts = !empty($cfg['has_resorts']);
$resortOptions = $hasResorts ? catalog_resort_options() : [];
$selectedResorts = array_map('intval', json_decode_array($row['resorts_json'] ?? null));

ob_start();
?>
<form class="form-layout" method="post" enctype="multipart/form-data" data-rich-form data-catalog-form>
  <?= csrf_field() ?>
  <?php if ($errors): ?>
    <div class="admin-alert admin-alert--err form-layout__alert" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
  <?php endif; ?>

  <div class="form-layout__main form-cards">
    <section class="form-card">
      <div class="form-card__head">
        <span class="form-card__icon"><?= yn_icon($isBlurb ? 'gift' : 'buildings') ?></span>
        <div class="form-card__titles">
          <h2 class="form-card__title">Basic information</h2>
          <p class="form-card__hint">The name and key details guests see first.</p>
        </div>
      </div>
      <div class="form-card__body">
        <div class="field full">
          <label for="title"><?= e($singular) ?> name <span class="field__req">*</span></label>
          <input class="form-control" id="title" name="title" required value="<?= e($row['title'] ?? '') ?>" placeholder="<?= $isBlurb ? 'e.g., Honeymoon Escape Gift Card' : 'e.g., Misty Hills Resort & Spa' ?>" data-preview-source="title" />
        </div>

        <?php if ($isBlurb): ?>
          <div class="field full">
            <label for="blurb">Short description</label>
            <textarea class="form-control" id="blurb" name="blurb" rows="4" placeholder="A few lines about what this includes and who it's for." data-preview-source="text"><?= e($row['blurb'] ?? '') ?></textarea>
            <span class="field__counter" data-counter-for="blurb"></span>
          </div>
        <?php else: ?>
          <div class="field<?= empty($cfg['has_category']) && empty($cfg['has_duration']) ? ' full' : '' ?>" data-suggest-input data-suggest="<?= e(json_encode($locationSuggestions, JSON_UNESCAPED_UNICODE)) ?>">
            <label for="location">Location</label>
            <div class="suggest">
              <input class="form-control" id="location" name="location" value="<?= e($row['location'] ?? '') ?>" placeholder="e.g., Vythiri, Wayanad" autocomplete="off" data-suggest-field data-preview-source="location" />
              <?php if ($locationSuggestions): ?>
                <button class="suggest__toggle" type="button" data-suggest-toggle aria-label="Show location suggestions" aria-expanded="false"><?= yn_icon('chevron-down') ?></button>
              <?php endif; ?>
              <ul class="suggest__list" data-suggest-list hidden></ul>
            </div>
          </div>
          <?php if (!empty($cfg['has_category'])): ?>
            <div class="field" data-suggest-input data-suggest="<?= e(json_encode($categorySuggestions, JSON_UNESCAPED_UNICODE)) ?>">
              <label for="category">Category</label>
              <div class="suggest">
                <input class="form-control" id="category" name="category" value="<?= e($row['category'] ?? '') ?>" placeholder="e.g., Jungle resort" autocomplete="off" data-suggest-field data-preview-source="category" />
                <?php if ($categorySuggestions): ?>
                  <button class="suggest__toggle" type="button" data-suggest-toggle aria-label="Show category suggestions" aria-expanded="false"><?= yn_icon('chevron-down') ?></button>
                <?php endif; ?>
                <ul class="suggest__list" data-suggest-list hidden></ul>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($cfg['has_duration'])): ?>
            <div class="field">
              <label for="duration">Duration</label>
              <span class="input-icon">
                <?= yn_icon('clock') ?>
                <input class="form-control" id="duration" name="duration" value="<?= e($row['duration'] ?? '') ?>" placeholder="e.g., 2 Days 1 Night" data-preview-source="category" />
              </span>
            </div>
          <?php endif; ?>
          <div class="field full">
            <label for="summary">Summary</label>
            <textarea class="form-control" id="summary" name="summary" rows="3" placeholder="One or two lines shown on listing cards." data-preview-source="text"><?= e($row['summary'] ?? '') ?></textarea>
            <span class="field__counter" data-counter-for="summary"></span>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if (!$isBlurb): ?>
      <section class="form-card">
        <div class="form-card__head">
          <span class="form-card__icon"><?= yn_icon('file-text') ?></span>
          <div class="form-card__titles">
            <h2 class="form-card__title">Description</h2>
            <p class="form-card__hint">The full write-up on the details page — rooms, setting, experiences.</p>
          </div>
        </div>
        <div class="form-card__body">
          <div class="field full">
            <label for="body" class="visually-hidden">Description</label>
            <textarea class="form-control form-control--tall" id="body" name="body" rows="9" placeholder="Describe the property, its surroundings and what makes a stay here special..."><?= e($row['body'] ?? '') ?></textarea>
            <span class="field__counter" data-counter-for="body"></span>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($isBlurb || !empty($cfg['has_amenities'])):
      $chipName = $isBlurb ? 'features' : 'amenities';
      $chipValues = $isBlurb ? $features : $amenities;
      $chipSuggestions = $isBlurb ? $featureSuggestions : $amenitySuggestions;
      ?>
      <section class="form-card">
        <div class="form-card__head">
          <span class="form-card__icon"><?= yn_icon($isBlurb ? 'check' : 'star') ?></span>
          <div class="form-card__titles">
            <h2 class="form-card__title"><?= $isBlurb ? 'Features' : 'Amenities' ?></h2>
            <p class="form-card__hint"><?= $isBlurb ? 'What is included — shown as a checklist.' : 'Facilities and services available to guests.' ?></p>
          </div>
        </div>
        <div class="form-card__body">
          <div class="field full" data-chips="<?= e($chipName) ?>" data-suggest="<?= e(json_encode($chipSuggestions, JSON_UNESCAPED_UNICODE)) ?>">
            <label for="<?= e($chipName) ?>-entry" class="visually-hidden"><?= $isBlurb ? 'Features' : 'Amenities' ?></label>
            <div class="chips-suggest">
              <div class="chips-input" data-chips-list>
                <?php foreach ($chipValues as $value): ?>
                  <span class="chip">
                    <?= e((string) $value) ?>
                    <input type="hidden" name="<?= e($chipName) ?>[]" value="<?= e((string) $value) ?>" />
                    <button class="chip__remove" type="button" data-chip-remove aria-label="Remove <?= e((string) $value) ?>">&times;</button>
                  </span>
                <?php endforeach; ?>
                <input class="chips-input__entry" id="<?= e($chipName) ?>-entry" type="text" name="<?= e($chipName) ?>_extra" data-chips-entry placeholder="<?= $isBlurb ? 'e.g., 2 nights stay — press Enter' : 'e.g., Infinity pool — press Enter' ?>" autocomplete="off" />
              </div>
              <ul class="suggest__list" data-suggest-list hidden></ul>
            </div>
            <button class="chips-add" type="button" data-chips-add><?= yn_icon('plus') ?>Add <?= $isBlurb ? 'feature' : 'amenity' ?></button>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <?php if ($hasResorts): ?>
      <section class="form-card" data-resort-picker>
        <div class="form-card__head">
          <span class="form-card__icon"><?= yn_icon('bed') ?></span>
          <div class="form-card__titles">
            <h2 class="form-card__title">Available resorts</h2>
            <p class="form-card__hint">Resorts where this gift card can be redeemed. Guests see them in a popup on the gift cards page.</p>
          </div>
          <?php if ($resortOptions): ?>
            <span class="resort-picker__count" data-resort-count aria-live="polite"><?= count(array_intersect($selectedResorts, array_map('intval', array_column($resortOptions, 'id')))) ?> selected</span>
          <?php endif; ?>
        </div>
        <?php if (!$resortOptions): ?>
          <div class="resort-picker__empty">
            <span class="resort-picker__empty-icon"><?= yn_icon('bed') ?></span>
            <p>No resorts yet. Add resorts first, then come back to link them to this gift card.</p>
            <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/resorts/edit.php')) ?>"><?= yn_icon('plus') ?>Add resort</a>
          </div>
        <?php else: ?>
          <div class="resort-picker__toolbar">
            <span class="input-icon resort-picker__search">
              <?= yn_icon('search') ?>
              <input class="form-control" type="search" placeholder="Search resorts by name or location" aria-label="Search resorts" data-resort-search />
            </span>
            <div class="resort-picker__bulk">
              <button class="btn btn--ghost btn--sm" type="button" data-resort-all>Select all</button>
              <button class="btn btn--ghost btn--sm" type="button" data-resort-none>Clear</button>
            </div>
          </div>
          <div class="resort-picker__grid">
            <?php foreach ($resortOptions as $resort):
              $rid = (int) $resort['id'];
              $rImg = (string) ($resort['image'] ?? '');
              $rMeta = implode(' · ', array_filter([(string) $resort['location'], (string) $resort['category']]));
              ?>
              <label class="resort-option" data-resort-option data-search="<?= e(strtolower($resort['title'] . ' ' . $rMeta)) ?>">
                <input class="resort-option__input" type="checkbox" name="resorts[]" value="<?= $rid ?>" <?= in_array($rid, $selectedResorts, true) ? 'checked' : '' ?> />
                <span class="resort-option__media">
                  <?php if ($rImg !== ''): ?>
                    <img src="<?= e(image_url($rImg)) ?>" alt="" loading="lazy" />
                  <?php else: ?>
                    <?= yn_icon('image') ?>
                  <?php endif; ?>
                </span>
                <span class="resort-option__body">
                  <span class="resort-option__title"><?= e($resort['title']) ?></span>
                  <?php if ($rMeta !== ''): ?>
                    <span class="resort-option__meta"><?= e($rMeta) ?></span>
                  <?php endif; ?>
                  <?php if (empty($resort['is_published'])): ?>
                    <span class="resort-option__tag">Draft</span>
                  <?php endif; ?>
                </span>
                <span class="resort-option__check" aria-hidden="true"><?= yn_icon('check') ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <p class="resort-picker__none" data-resort-nomatch hidden>No resorts match your search.</p>
          <p class="field__hint">Draft resorts are hidden from guests until they are published.</p>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="form-card">
      <div class="form-card__head">
        <span class="form-card__icon"><?= yn_icon('image') ?></span>
        <div class="form-card__titles">
          <h2 class="form-card__title">Media</h2>
          <p class="form-card__hint">Use bright, high-resolution photos. JPG, PNG, WEBP or GIF up to 5 MB each.</p>
        </div>
      </div>
      <div class="form-card__body form-card__body--media">
        <div class="media-split<?= empty($cfg['has_gallery']) ? ' media-split--single' : '' ?>" data-package-media data-gallery-max="<?= $galleryMax ?>">
          <div class="media-col media-col--cover">
            <span class="field__label">Cover image</span>
            <p class="field__hint">Main image on listing cards and the details page header.</p>

            <input type="hidden" name="remove_image" value="0" data-cover-remove />
            <input type="hidden" name="library_image" value="<?= e($coverPath) ?>" data-cover-library />
            <input id="image_file" class="media-file-input" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-cover-input />

            <div class="media-drop media-drop--cover" data-dropzone data-cover-empty<?= $coverPath !== '' ? ' hidden' : '' ?>>
              <span class="media-drop__icon" aria-hidden="true"><?= yn_icon('upload') ?></span>
              <span class="media-drop__title">Drop an image or click to upload</span>
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
          </div>

          <?php if (!empty($cfg['has_gallery'])): ?>
            <div class="media-col media-col--gallery">
              <span class="field__label">Gallery</span>
              <p class="field__hint">Rooms, views, dining and activities — up to <?= $galleryMax ?> photos.</p>

              <input id="gallery_files" class="media-file-input" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-gallery-input />

              <div class="media-drop media-drop--gallery" data-dropzone data-gallery-empty<?= $galleryCount > 0 ? ' hidden' : '' ?>>
                <span class="media-drop__icon" aria-hidden="true"><?= yn_icon('upload') ?></span>
                <span class="media-drop__title">Upload gallery images</span>
                <span class="media-drop__hint">Up to <?= $galleryMax ?> images (5MB each)</span>
                <span class="media-drop__divider"><span>or</span></span>
                <button class="media-drop__browse" type="button" data-gallery-library>
                  <?= yn_icon('image') ?>
                  Choose from library
                </button>
              </div>

              <div class="media-gallery-panel" data-gallery-panel<?= $galleryCount === 0 ? ' hidden' : '' ?>>
                <div class="media-gallery-grid" data-gallery-grid>
                  <?php foreach ($galleryPaths as $gPath): ?>
                    <div class="media-thumb" data-gallery-item data-existing="1">
                      <div class="media-thumb__frame">
                        <img src="<?= e(image_url($gPath)) ?>" alt="" />
                        <button class="media-thumb__remove" type="button" data-gallery-remove aria-label="Remove image"><?= yn_icon('trash') ?></button>
                      </div>
                      <p class="media-thumb__name">
                        <span class="media-file-meta__check" aria-hidden="true"><?= yn_icon('check') ?></span>
                        <span><?= e(basename(str_replace('\\', '/', $gPath))) ?></span>
                      </p>
                      <input type="hidden" name="gallery_keep[]" value="<?= e($gPath) ?>" />
                    </div>
                  <?php endforeach; ?>
                  <button class="media-thumb media-thumb--add" type="button" data-gallery-add<?= $galleryCount >= $galleryMax ? ' hidden' : '' ?>>
                    <span class="media-thumb__add-icon" aria-hidden="true"><?= yn_icon('plus') ?></span>
                    <span class="media-thumb__add-title">Add more images</span>
                    <span class="media-thumb__add-hint">Up to <?= $galleryMax ?> images</span>
                  </button>
                </div>
                <div class="media-gallery-foot">
                  <span data-gallery-count>Selected Images (<?= $galleryCount ?>/<?= $galleryMax ?>)</span>
                  <span data-gallery-status><?= $galleryCount > 0 ? 'Saved images' : 'No images selected' ?></span>
                </div>
                <button class="media-drop__browse media-gallery-library" type="button" data-gallery-library>
                  <?= yn_icon('image') ?>
                  Add from library
                </button>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>

  <aside class="form-layout__aside">
    <section class="form-card">
      <div class="form-card__head">
        <span class="form-card__icon"><?= yn_icon('globe') ?></span>
        <div class="form-card__titles">
          <h2 class="form-card__title">Publishing</h2>
          <p class="form-card__hint">Visibility, URL and listing order.</p>
        </div>
      </div>
      <div class="form-card__body form-card__body--stack">
        <div class="field">
          <span class="field__label">Visible on website</span>
          <div class="switch-field">
            <span class="switch">
              <input id="is_published" type="checkbox" name="is_published" value="1" aria-label="Visible on website" <?= $isPublished ? 'checked' : '' ?> />
              <span class="switch__track"></span>
            </span>
            <span class="switch-field__state" data-switch-state="is_published"><?= $isPublished ? 'Yes' : 'No' ?></span>
          </div>
        </div>
        <div class="field">
          <label for="slug">URL slug</label>
          <span class="input-icon">
            <?= yn_icon('globe') ?>
            <input class="form-control" id="slug" name="slug" value="<?= e($row['slug'] ?? '') ?>" placeholder="generated-from-name" autocomplete="off" data-slug-input />
          </span>
          <p class="field__hint">Leave blank to generate it from the name.</p>
        </div>
        <div class="field">
          <label for="sort_order">Display order</label>
          <span class="input-icon">
            <?= yn_icon('list') ?>
            <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
          </span>
          <p class="field__hint">Lower numbers appear first.</p>
        </div>
      </div>
      <div class="form-card__actions">
        <button class="btn btn--primary" type="submit"><?= yn_icon('check') ?><?= $id ? 'Save changes' : 'Create ' . e($singularLower) ?></button>
        <?php if ($publicUrl !== ''): ?>
          <a class="btn btn--secondary" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">View on site</a>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('admin/' . $cfg['nav'] . '/index.php')) ?>">Cancel</a>
      </div>
    </section>

    <section class="form-card listing-preview" aria-label="Card preview">
      <div class="form-card__head">
        <span class="form-card__icon"><?= yn_icon('star') ?></span>
        <div class="form-card__titles">
          <h2 class="form-card__title">Card preview</h2>
          <p class="form-card__hint">Roughly how this appears in listings.</p>
        </div>
      </div>
      <div class="listing-preview__card">
        <div class="listing-preview__media">
          <img src="<?= e($coverSrc) ?>" alt="" data-preview-img<?= $coverSrc === '' ? ' hidden' : '' ?> />
          <span class="listing-preview__empty" data-preview-img-empty<?= $coverSrc !== '' ? ' hidden' : '' ?>><?= yn_icon('image') ?>No cover image</span>
        </div>
        <div class="listing-preview__body">
          <?php if (!$isBlurb): ?>
            <span class="listing-preview__meta"><?= yn_icon('pin') ?><span data-preview-meta></span></span>
          <?php endif; ?>
          <strong class="listing-preview__title" data-preview-title><?= e($singular) ?> name</strong>
          <p class="listing-preview__text" data-preview-text></p>
          <?php if ($hasResorts): ?>
            <span class="listing-preview__meta" data-preview-resorts><?= yn_icon('bed') ?><span></span></span>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </aside>
</form>
<template id="icon-trash"><?= yn_icon('trash') ?></template>
<?php
$adminContent = ob_get_clean();
$pageTitle = ($id ? 'Edit ' : 'Add ') . $singular;
$pageSubtitle = $id ? 'Update the details for this ' . $singularLower . '.' : 'Create a new ' . $singularLower . ' listing for the website.';
$activeNav = $cfg['nav'];
$adminScripts = ['admin/assets/admin-form.js', 'admin/assets/admin-catalog-form.js'];
require __DIR__ . '/_layout.php';
