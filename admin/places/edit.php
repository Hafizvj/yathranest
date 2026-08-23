<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
require_admin();

$id = (int) get_query('id', '0');
$row = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM places WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    if (!$row) {
        flash_set('error', 'Place not found.');
        redirect('admin/places/index.php');
    }
}

/** Categories posted as checkboxes, falling back to what the row already has. */
function place_selected_scopes(?array $row): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $posted = isset($_POST['catalog_scopes']) && is_array($_POST['catalog_scopes'])
            ? $_POST['catalog_scopes']
            : [];
        $out = [];
        foreach ($posted as $scope) {
            $scope = strtolower(trim((string) $scope));
            if (isset(catalog_scope_options()[$scope]) && !in_array($scope, $out, true)) {
                $out[] = $scope;
            }
        }
        return $out;
    }
    return $row ? place_catalog_scopes($row) : [];
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/places/index.php');
    }
    $oldImages = json_decode_array($row['images_json'] ?? null);
    $label = post('label');
    // Slugs are referenced by packages, so an existing place keeps its own.
    $slug = $id ? (string) $row['slug'] : unique_place_slug(slugify($label));
    $tags = place_tags_from_post();
    $scopes = place_selected_scopes($row);

    if ($label === '') {
        $errors[] = 'Label is required.';
    }
    if (!$scopes) {
        $errors[] = 'Select at least one category.';
    }

    $images = $oldImages;
    if (!$errors) {
        $images = admin_collect_media_paths('images_keep', '', $_FILES['image_files'] ?? null, 'places');
        if (admin_upload_last_error()) {
            $errors[] = admin_upload_last_error();
            admin_upload_last_error('');
        }
    }

    if (!$errors) {
        $payload = [
            $slug,
            $label,
            $scopes[0],
            json_encode($scopes, JSON_UNESCAPED_UNICODE),
            json_encode($tags, JSON_UNESCAPED_UNICODE),
            post('arrive_text'),
            post('sightseeing_text'),
            json_encode($images, JSON_UNESCAPED_UNICODE),
            (int) post('sort_order', '0'),
        ];
        if ($id) {
            $payload[] = $id;
            db()->prepare('UPDATE places SET slug=?, label=?, catalog_scope=?, catalog_scopes_json=?, tags_json=?, arrive_text=?, sightseeing_text=?, images_json=?, sort_order=? WHERE id=?')->execute($payload);
            admin_remove_missing_uploads($oldImages, $images);
            flash_set('success', 'Place updated.');
        } else {
            db()->prepare('INSERT INTO places (slug, label, catalog_scope, catalog_scopes_json, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (?,?,?,?,?,?,?,?,?)')->execute($payload);
            flash_set('success', 'Place created.');
        }
        redirect('admin/places/index.php');
    }

    $row = array_merge($row ?: [], [
        'slug' => $slug,
        'label' => $label,
        'catalog_scope' => $scopes[0] ?? '',
        'catalog_scopes_json' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
        'tags_json' => json_encode($tags, JSON_UNESCAPED_UNICODE),
        'arrive_text' => post('arrive_text'),
        'sightseeing_text' => post('sightseeing_text'),
        'images_json' => json_encode($images, JSON_UNESCAPED_UNICODE),
        'sort_order' => post('sort_order', '0'),
    ]);
}

$selectedScopes = place_selected_scopes($row);
$tags = json_decode_array($row['tags_json'] ?? null);
$imagePaths = json_decode_array($row['images_json'] ?? null);

ob_start();
?>
<form class="form-cards" method="post" enctype="multipart/form-data" data-rich-form>
  <?= csrf_field() ?>
  <?php if ($errors): ?>
    <div class="admin-alert admin-alert--err" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
  <?php endif; ?>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('pin') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Basic Information</h2>
        <p class="form-card__hint">Name this place and choose where its packages are listed.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field">
        <label for="label">Label <span class="field__req">*</span></label>
        <input class="form-control" id="label" name="label" required value="<?= e($row['label'] ?? '') ?>" placeholder="e.g., Wayanad" />
        <?php if ($id): ?>
          <p class="field__hint">Web address stays <code><?= e((string) $row['slug']) ?></code>.</p>
        <?php endif; ?>
      </div>
      <div class="field">
        <span class="field__label">Category <span class="field__req">*</span></span>
        <div class="picker" data-picker>
          <div class="picker__control" data-picker-control>
            <span class="picker__chips" data-picker-chips></span>
            <button class="picker__toggle" type="button" data-picker-toggle aria-expanded="false">
              <span class="picker__placeholder" data-picker-empty>Select categories</span>
              <span class="picker__caret"><?= yn_icon('chevron-down') ?></span>
            </button>
          </div>
          <div class="picker__panel" data-picker-panel>
            <?php foreach (catalog_scope_options() as $value => $optionLabel): ?>
              <label><input type="checkbox" name="catalog_scopes[]" value="<?= e($value) ?>" <?= in_array($value, $selectedScopes, true) ? 'checked' : '' ?> /> <?= e($optionLabel) ?></label>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="field__hint">Packages visiting this place appear on each selected listing page.</p>
      </div>
      <div class="field full" data-chips="tags">
        <label for="tag-entry">Tags</label>
        <div class="chips-input" data-chips-list>
          <?php foreach ($tags as $tag): ?>
            <span class="chip">
              <?= e((string) $tag) ?>
              <input type="hidden" name="tags[]" value="<?= e((string) $tag) ?>" />
              <button class="chip__remove" type="button" data-chip-remove aria-label="Remove <?= e((string) $tag) ?>">&times;</button>
            </span>
          <?php endforeach; ?>
          <input class="chips-input__entry" id="tag-entry" type="text" name="tags_extra" data-chips-entry placeholder="Add a tag and press Enter" />
        </div>
        <button class="chips-add" type="button" data-chips-add><?= yn_icon('plus') ?>Add tag</button>
      </div>
    </div>
  </section>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('file-text') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Descriptions</h2>
        <p class="form-card__hint">Text shown when this place appears in an itinerary.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field">
        <label for="arrive_text">Arrive text</label>
        <textarea class="form-control" id="arrive_text" name="arrive_text" rows="4" placeholder="What the drive in and check-in looks like."><?= e($row['arrive_text'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label for="sightseeing_text">Sightseeing text</label>
        <textarea class="form-control" id="sightseeing_text" name="sightseeing_text" rows="4" placeholder="What guests see and do here."><?= e($row['sightseeing_text'] ?? '') ?></textarea>
      </div>
    </div>
  </section>

  <section class="form-card">
    <div class="form-card__head">
      <span class="form-card__icon"><?= yn_icon('image') ?></span>
      <div class="form-card__titles">
        <h2 class="form-card__title">Media &amp; Publishing</h2>
        <p class="form-card__hint">Upload images and set the listing order.</p>
      </div>
    </div>
    <div class="form-card__body">
      <div class="field media-field">
        <span class="field__label">Images</span>
        <p class="field__hint">The first image is used as the thumbnail. Uncheck Keep to remove one.</p>
        <?= admin_media_preview_items($imagePaths, 'images_keep') ?>
        <label class="dropzone" data-dropzone>
          <span class="dropzone__icon"><?= yn_icon('upload') ?></span>
          <span class="dropzone__body">
            <span class="dropzone__text">Upload images</span>
            <span class="dropzone__hint">JPG, PNG, WEBP up to 5MB each</span>
          </span>
          <input id="image_files" type="file" name="image_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#place-images-new" />
        </label>
        <button class="btn btn--secondary btn--sm media-library-browse" type="button" data-open-media-picker data-media-mode="multiple" data-media-max="20" data-media-event="yn:place-images">
          <?= yn_icon('image') ?> Choose from library
        </button>
        <div id="place-library-keep"></div>
        <span class="file-pick__name" data-file-name-for="image_files">No files chosen</span>
        <div id="place-images-new" class="media-preview"></div>
      </div>
      <div class="field">
        <label for="sort_order">Display Order</label>
        <p class="field__hint">Set display order in listings.</p>
        <span class="input-icon">
          <?= yn_icon('list') ?>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </span>
      </div>
    </div>
  </section>

  <div class="form-footer">
    <a class="btn btn--secondary" href="<?= e(url('admin/places/index.php')) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit"><?= yn_icon('check') ?>Save Place</button>
  </div>
</form>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit Place' : 'Add Place';
$pageSubtitle = $id ? 'Update this destination.' : 'Create a destination used by packages.';
$adminScripts = ['admin/assets/admin-form.js', 'admin/assets/admin-place-media.js'];
$activeNav = 'places';
require dirname(__DIR__) . '/_layout.php';
