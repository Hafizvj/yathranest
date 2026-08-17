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
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/places/index.php');
    }
    $oldImages = json_decode_array($row['images_json'] ?? null);
    $slug = post('slug') !== '' ? post('slug') : slugify(post('label'));
    $tags = array_values(array_filter(array_map('trim', explode(',', post('tags')))));
    $images = admin_collect_media_paths(
        'images_keep',
        'images_paths',
        $_FILES['image_files'] ?? null,
        'places'
    );
    if (admin_upload_last_error()) {
        $errors[] = admin_upload_last_error();
    }
    if (post('label') === '') {
        $errors[] = 'Label is required.';
    }

    if (!$errors) {
        $payload = [
            $slug,
            post('label'),
            json_encode($tags, JSON_UNESCAPED_UNICODE),
            post('arrive_text'),
            post('sightseeing_text'),
            json_encode($images, JSON_UNESCAPED_UNICODE),
            (int) post('sort_order', '0'),
        ];
        if ($id) {
            $payload[] = $id;
            db()->prepare('UPDATE places SET slug=?, label=?, tags_json=?, arrive_text=?, sightseeing_text=?, images_json=?, sort_order=? WHERE id=?')->execute($payload);
            admin_remove_missing_uploads($oldImages, $images);
            flash_set('success', 'Place updated.');
        } else {
            db()->prepare('INSERT INTO places (slug, label, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (?,?,?,?,?,?,?)')->execute($payload);
            flash_set('success', 'Place created.');
        }
        redirect('admin/places/index.php');
    }

    $row = [
        'slug' => $slug,
        'label' => post('label'),
        'tags_json' => json_encode($tags, JSON_UNESCAPED_UNICODE),
        'arrive_text' => post('arrive_text'),
        'sightseeing_text' => post('sightseeing_text'),
        'images_json' => json_encode($images, JSON_UNESCAPED_UNICODE),
        'sort_order' => post('sort_order', '0'),
    ];
}

$tags = implode(', ', json_decode_array($row['tags_json'] ?? null));
$imagePaths = json_decode_array($row['images_json'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tags = post('tags');
}

ob_start();
?>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <div class="form-grid">
      <section class="form-section">
        <h2 class="form-section__title">Basics</h2>
        <div class="form-group">
          <label for="label">Label</label>
          <input class="form-control" id="label" name="label" required value="<?= e($row['label'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="slug">Slug</label>
          <input class="form-control" id="slug" name="slug" value="<?= e($row['slug'] ?? '') ?>" />
        </div>
        <div class="form-group full">
          <label for="tags">Tags (comma-separated)</label>
          <input class="form-control" id="tags" name="tags" value="<?= e($tags) ?>" />
        </div>
        <div class="form-group full">
          <label for="arrive_text">Arrive text</label>
          <textarea class="form-control" id="arrive_text" name="arrive_text"><?= e($row['arrive_text'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label for="sightseeing_text">Sightseeing text</label>
          <textarea class="form-control" id="sightseeing_text" name="sightseeing_text"><?= e($row['sightseeing_text'] ?? '') ?></textarea>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Images</h2>
        <p class="form-section__hint">Upload one or more images, or add existing asset filenames. Uncheck Keep to remove.</p>
        <div class="form-group full media-field">
          <?= admin_media_preview_items($imagePaths, 'images_keep') ?>
          <div class="media-drop">
            <label for="image_files">Upload images</label>
            <input class="form-control" id="image_files" type="file" name="image_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#place-images-new" />
            <div id="place-images-new" class="media-preview" style="margin-top:0.75rem"></div>
          </div>
          <label for="images_paths">Add image paths (one per line)</label>
          <textarea class="form-control" id="images_paths" name="images_paths" rows="3" placeholder="alleppey.jpg"></textarea>
        </div>
        <div class="form-group">
          <label for="sort_order">Sort order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </div>
      </section>
    </div>
    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save</button>
      <a class="btn btn--secondary" href="<?= e(url('admin/places/index.php')) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit place' : 'Add place';
$activeNav = 'places';
require dirname(__DIR__) . '/_layout.php';
