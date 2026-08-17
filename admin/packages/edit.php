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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $oldImage = (string) ($row['image'] ?? '');
        $oldGallery = json_decode_array($row['gallery_json'] ?? null);
        $data = package_form_data_from_post();

        if ($data['title'] === '' || $data['slug'] === '') {
            $errors[] = 'Title and slug are required.';
        }
        if (!$errors) {
            $check = db()->prepare('SELECT id FROM packages WHERE slug = ? AND id <> ? LIMIT 1');
            $check->execute([$data['slug'], $id ?: 0]);
            if ($check->fetch()) {
                $errors[] = 'Slug already in use.';
            }
        }

        $gallery = admin_collect_media_paths(
            'gallery_keep',
            'gallery_paths',
            $_FILES['gallery_files'] ?? null,
            'packages'
        );
        if (admin_upload_last_error()) {
            $errors[] = admin_upload_last_error();
        }
        $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

        if (!empty($_FILES['image_file']['name'])) {
            $uploaded = admin_apply_image_upload($_FILES['image_file'], 'packages', $oldImage);
            if ($uploaded) {
                $data['image'] = $uploaded;
            } elseif (admin_upload_last_error()) {
                $errors[] = admin_upload_last_error();
            }
        } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
            admin_delete_upload($oldImage);
            $data['image'] = '';
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
        $row = array_merge($row ?: [], [
            'title' => post('title'),
            'slug' => post('slug'),
            'type' => post('type'),
            'sheet' => post('sheet'),
            'group_name' => post('group_name'),
            'pickup' => post('pickup'),
            'drop_point' => post('drop_point'),
            'pickup_slug' => post('pickup_slug'),
            'days' => post('days'),
            'nights' => post('nights'),
            'duration_bucket' => post('duration_bucket'),
            'state' => post('state'),
            'dest_line' => post('dest_line'),
            'stay_split' => post('stay_split'),
            'stay_summary' => post('stay_summary'),
            'card_text' => post('card_text'),
            'overview' => post('overview'),
            'accommodation' => post('accommodation'),
            'image' => $data['image'],
            'gallery_json' => $data['gallery_json'],
            'sort_order' => post('sort_order'),
            'has_houseboat' => isset($_POST['has_houseboat']) ? 1 : 0,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'pages_json' => json_encode(isset($_POST['pages']) && is_array($_POST['pages']) ? $_POST['pages'] : [], JSON_UNESCAPED_UNICODE),
            'destinations_json' => json_encode(csv_to_array(post('destinations')), JSON_UNESCAPED_UNICODE),
            'highlights_json' => json_encode(lines_to_array(post('highlights')), JSON_UNESCAPED_UNICODE),
            'itinerary_json' => json_encode(textarea_to_itinerary(post('itinerary')), JSON_UNESCAPED_UNICODE),
        ]);
    }
}

$pages = json_decode_array($row['pages_json'] ?? null);
if (!$pages && isset($_POST['pages'])) {
    $pages = (array) $_POST['pages'];
}
$highlights = implode("\n", json_decode_array($row['highlights_json'] ?? null));
$itineraryText = itinerary_to_textarea(json_decode_array($row['itinerary_json'] ?? null));
$galleryPaths = json_decode_array($row['gallery_json'] ?? null);
$destText = implode(', ', json_decode_array($row['destinations_json'] ?? null));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destText = post('destinations');
    $highlights = post('highlights');
    $itineraryText = post('itinerary');
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
          <input class="form-control" id="slug" name="slug" required value="<?= e($row['slug'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="type">Type</label>
          <select class="form-control" id="type" name="type">
            <?php foreach (['leisure','adventure','family','couple','heritage'] as $t): ?>
              <option value="<?= $t ?>" <?= (($row['type'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="sheet">Sheet</label>
          <input class="form-control" id="sheet" name="sheet" value="<?= e($row['sheet'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="group_name">Group</label>
          <input class="form-control" id="group_name" name="group_name" value="<?= e($row['group_name'] ?? '') ?>" />
        </div>
        <div class="form-group full">
          <label>Pages</label>
          <div class="checks">
            <?php foreach (['kerala','south','domestic','international'] as $p): ?>
              <label><input type="checkbox" name="pages[]" value="<?= $p ?>" <?= in_array($p, $pages, true) ? 'checked' : '' ?> /> <?= $p ?></label>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Trip details</h2>
        <div class="form-group">
          <label for="pickup">Pickup</label>
          <input class="form-control" id="pickup" name="pickup" value="<?= e($row['pickup'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="drop_point">Drop</label>
          <input class="form-control" id="drop_point" name="drop_point" value="<?= e($row['drop_point'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="pickup_slug">Pickup slug</label>
          <input class="form-control" id="pickup_slug" name="pickup_slug" value="<?= e($row['pickup_slug'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="state">State</label>
          <input class="form-control" id="state" name="state" value="<?= e($row['state'] ?? '') ?>" />
        </div>
        <div class="form-group">
          <label for="days">Days</label>
          <input class="form-control" id="days" type="number" min="1" name="days" value="<?= e((string) ($row['days'] ?? '2')) ?>" />
        </div>
        <div class="form-group">
          <label for="nights">Nights</label>
          <input class="form-control" id="nights" type="number" min="0" name="nights" value="<?= e((string) ($row['nights'] ?? '1')) ?>" />
        </div>
        <div class="form-group">
          <label for="duration_bucket">Duration bucket</label>
          <input class="form-control" id="duration_bucket" name="duration_bucket" value="<?= e($row['duration_bucket'] ?? '') ?>" placeholder="2-4 / 5-7 / 8-10" />
        </div>
        <div class="form-group">
          <label for="stay_split">Stay split</label>
          <input class="form-control" id="stay_split" name="stay_split" value="<?= e($row['stay_split'] ?? '') ?>" />
        </div>
        <div class="form-group full">
          <label for="stay_summary">Stay summary</label>
          <input class="form-control" id="stay_summary" name="stay_summary" value="<?= e($row['stay_summary'] ?? '') ?>" />
        </div>
        <div class="form-group full">
          <label for="destinations">Destinations (comma-separated slugs)</label>
          <input class="form-control" id="destinations" name="destinations" value="<?= e($destText) ?>" />
        </div>
        <div class="form-group full">
          <label for="dest_line">Dest line</label>
          <input class="form-control" id="dest_line" name="dest_line" value="<?= e($row['dest_line'] ?? '') ?>" />
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Content</h2>
        <div class="form-group full">
          <label for="card_text">Card text</label>
          <textarea class="form-control" id="card_text" name="card_text"><?= e($row['card_text'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label for="overview">Overview</label>
          <textarea class="form-control" id="overview" name="overview" rows="5"><?= e($row['overview'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label for="highlights">Highlights (one per line)</label>
          <textarea class="form-control" id="highlights" name="highlights"><?= e($highlights) ?></textarea>
        </div>
        <div class="form-group full">
          <label for="itinerary">Itinerary (Day N | Title | Text — one day per line)</label>
          <textarea class="form-control" id="itinerary" name="itinerary" rows="8"><?= e($itineraryText) ?></textarea>
        </div>
        <div class="form-group full">
          <label for="accommodation">Accommodation</label>
          <textarea class="form-control" id="accommodation" name="accommodation"><?= e($row['accommodation'] ?? '') ?></textarea>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Media</h2>
        <p class="form-section__hint">Upload JPG, PNG, WEBP or GIF up to 5 MB. Uncheck Keep to remove gallery images.</p>
        <div class="form-group full media-field">
          <label>Hero image</label>
          <?= admin_hero_preview($row['image'] ?? null) ?>
          <div class="media-live-preview" hidden>
            <div class="media-preview">
              <div class="media-preview__item media-preview__item--hero">
                <img id="package-hero-preview" alt="New hero preview" hidden />
              </div>
            </div>
          </div>
          <div class="media-drop">
            <label for="image_file">Upload new hero</label>
            <input class="form-control" id="image_file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#package-hero-preview" />
          </div>
          <label for="image">Or image path</label>
          <input class="form-control" id="image" name="image" value="<?= e($row['image'] ?? '') ?>" placeholder="packages/slug.jpg or uploads/packages/..." />
          <?php if (!empty($row['image'])): ?>
            <label class="checks"><input type="checkbox" name="remove_image" value="1" /> Remove current hero image</label>
          <?php endif; ?>
        </div>
        <div class="form-group full media-field">
          <label>Gallery</label>
          <?= admin_media_preview_items($galleryPaths, 'gallery_keep') ?>
          <div class="media-drop">
            <label for="gallery_files">Upload gallery images</label>
            <input class="form-control" id="gallery_files" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#package-gallery-new" />
            <div id="package-gallery-new" class="media-preview" style="margin-top:0.75rem"></div>
          </div>
          <label for="gallery_paths">Add gallery paths (one per line)</label>
          <textarea class="form-control" id="gallery_paths" name="gallery_paths" rows="3" placeholder="packages/extra.jpg"></textarea>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Publish</h2>
        <div class="form-group">
          <label for="sort_order">Sort order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </div>
        <div class="form-group">
          <label>Options</label>
          <div class="checks">
            <label><input type="checkbox" name="has_houseboat" value="1" <?= !empty($row['has_houseboat']) ? 'checked' : '' ?> /> Houseboat</label>
            <label><input type="checkbox" name="is_published" value="1" <?= !isset($row['is_published']) || !empty($row['is_published']) ? 'checked' : '' ?> /> Published</label>
          </div>
        </div>
      </section>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save package</button>
      <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit package' : 'Add package';
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
