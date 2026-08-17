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
    $slug = post('slug') !== '' ? post('slug') : slugify(post('title'));
    $data = [
        'slug' => $slug,
        'title' => post('title'),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'sort_order' => (int) post('sort_order', '0'),
        'image' => post('image'),
    ];

    if (!empty($_FILES['image_file']['name'])) {
        $up = admin_apply_image_upload($_FILES['image_file'], $cfg['nav'], $oldImage);
        if ($up) {
            $data['image'] = $up;
        } elseif (admin_upload_last_error()) {
            $errors[] = admin_upload_last_error();
        }
    } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        admin_delete_upload($oldImage);
        $data['image'] = '';
    }

    if (!empty($cfg['blurb_field'])) {
        $data['blurb'] = post('blurb');
        $data['features_json'] = catalog_lines_to_json(post('features'));
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
            $gallery = admin_collect_media_paths(
                'gallery_keep',
                'gallery_paths',
                $_FILES['gallery_files'] ?? null,
                $cfg['nav']
            );
            if (admin_upload_last_error()) {
                $errors[] = admin_upload_last_error();
            }
            $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($cfg['has_amenities'])) {
            $data['amenities_json'] = catalog_lines_to_json(post('amenities'));
        }
    }

    if ($data['title'] === '') {
        $errors[] = 'Title is required.';
    }

    if (!$errors) {
        $fields = $cfg['fields'];
        $vals = [];
        foreach ($fields as $f) {
            $vals[] = $data[$f] ?? ($f === 'gallery_json' || $f === 'amenities_json' || $f === 'features_json' ? '[]' : '');
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

    $row = array_merge($row ?: [], $data, [
        'features_json' => $data['features_json'] ?? ($row['features_json'] ?? '[]'),
        'gallery_json' => $data['gallery_json'] ?? ($row['gallery_json'] ?? '[]'),
        'amenities_json' => $data['amenities_json'] ?? ($row['amenities_json'] ?? '[]'),
    ]);
}

$features = implode("\n", json_decode_array($row['features_json'] ?? null));
$galleryPaths = json_decode_array($row['gallery_json'] ?? null);
$amenities = implode("\n", json_decode_array($row['amenities_json'] ?? null));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $features = post('features');
    $amenities = post('amenities');
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
        <div class="form-group full">
          <label for="title">Title</label>
          <input class="form-control" id="title" name="title" required value="<?= e($row['title'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="slug">Slug</label>
          <input class="form-control" id="slug" name="slug" value="<?= e($row['slug'] ?? '') ?>" />
        </div>
        <?php if (!empty($cfg['blurb_field'])): ?>
          <div class="form-group full">
            <label for="blurb">Blurb</label>
            <textarea class="form-control" id="blurb" name="blurb"><?= e($row['blurb'] ?? '') ?></textarea>
          </div>
          <div class="form-group full">
            <label for="features">Features (one per line)</label>
            <textarea class="form-control" id="features" name="features"><?= e($features) ?></textarea>
          </div>
        <?php else: ?>
          <div class="form-group">
            <label for="location">Location</label>
            <input class="form-control" id="location" name="location" value="<?= e($row['location'] ?? '') ?>" />
          </div>
          <?php if (!empty($cfg['has_category'])): ?>
            <div class="form-group">
              <label for="category">Category</label>
              <input class="form-control" id="category" name="category" value="<?= e($row['category'] ?? '') ?>" />
            </div>
          <?php endif; ?>
          <?php if (!empty($cfg['has_duration'])): ?>
            <div class="form-group">
              <label for="duration">Duration</label>
              <input class="form-control" id="duration" name="duration" value="<?= e($row['duration'] ?? '') ?>" />
            </div>
          <?php endif; ?>
          <div class="form-group full">
            <label for="summary">Summary</label>
            <textarea class="form-control" id="summary" name="summary"><?= e($row['summary'] ?? '') ?></textarea>
          </div>
          <div class="form-group full">
            <label for="body">Body</label>
            <textarea class="form-control" id="body" name="body" rows="6"><?= e($row['body'] ?? '') ?></textarea>
          </div>
          <?php if (!empty($cfg['has_amenities'])): ?>
            <div class="form-group full">
              <label for="amenities">Amenities (one per line)</label>
              <textarea class="form-control" id="amenities" name="amenities"><?= e($amenities) ?></textarea>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Media</h2>
        <p class="form-section__hint">Upload JPG, PNG, WEBP or GIF up to 5 MB.</p>
        <div class="form-group full media-field">
          <label>Hero image</label>
          <?= admin_hero_preview($row['image'] ?? null) ?>
          <div class="media-live-preview" hidden>
            <div class="media-preview">
              <div class="media-preview__item media-preview__item--hero">
                <img id="catalog-hero-preview" alt="New hero preview" hidden />
              </div>
            </div>
          </div>
          <div class="media-drop">
            <label for="image_file">Upload image</label>
            <input class="form-control" id="image_file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#catalog-hero-preview" />
          </div>
          <label for="image">Or image path</label>
          <input class="form-control" id="image" name="image" value="<?= e($row['image'] ?? '') ?>" />
          <?php if (!empty($row['image'])): ?>
            <label class="checks"><input type="checkbox" name="remove_image" value="1" /> Remove current image</label>
          <?php endif; ?>
        </div>
        <?php if (!empty($cfg['has_gallery'])): ?>
          <div class="form-group full media-field">
            <label>Gallery</label>
            <?= admin_media_preview_items($galleryPaths, 'gallery_keep') ?>
            <div class="media-drop">
              <label for="gallery_files">Upload gallery images</label>
              <input class="form-control" id="gallery_files" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#catalog-gallery-new" />
              <div id="catalog-gallery-new" class="media-preview" style="margin-top:0.75rem"></div>
            </div>
            <label for="gallery_paths">Add gallery paths (one per line)</label>
            <textarea class="form-control" id="gallery_paths" name="gallery_paths" rows="3"></textarea>
          </div>
        <?php endif; ?>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Publish</h2>
        <div class="form-group">
          <label for="sort_order">Sort order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </div>
        <div class="form-group">
          <label class="checks"><input type="checkbox" name="is_published" value="1" <?= !isset($row['is_published']) || !empty($row['is_published']) ? 'checked' : '' ?> /> Published</label>
        </div>
      </section>
    </div>
    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save</button>
      <a class="btn btn--secondary" href="<?= e(url('admin/' . $cfg['nav'] . '/index.php')) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = ($id ? 'Edit ' : 'Add ') . $cfg['label'];
$activeNav = $cfg['nav'];
require __DIR__ . '/_layout.php';
