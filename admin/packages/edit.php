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
        $data = package_form_data_from_post();
        if ($data['title'] === '' || $data['slug'] === '') {
            $errors[] = 'Title and slug are required.';
        }
        if (!$errors) {
            // unique slug
            $check = db()->prepare('SELECT id FROM packages WHERE slug = ? AND id <> ? LIMIT 1');
            $check->execute([$data['slug'], $id ?: 0]);
            if ($check->fetch()) {
                $errors[] = 'Slug already in use.';
            }
        }
        if (!$errors) {
            if (!empty($_FILES['image_file']['name'])) {
                $uploaded = admin_store_upload($_FILES['image_file'], 'packages');
                if ($uploaded) {
                    $data['image'] = $uploaded;
                }
            }
            if ($id) {
                admin_package_update($id, $data);
                flash_set('success', 'Package updated.');
            } else {
                admin_package_insert($data);
                flash_set('success', 'Package created.');
            }
            redirect('admin/packages/index.php');
        }
        $row = array_merge($row ?: [], $_POST);
    }
}

$pages = json_decode_array($row['pages_json'] ?? null);
if (!$pages && isset($_POST['pages'])) {
    $pages = (array) $_POST['pages'];
}
$highlights = implode("\n", json_decode_array($row['highlights_json'] ?? null));
$itineraryText = itinerary_to_textarea(json_decode_array($row['itinerary_json'] ?? null));
$galleryText = implode("\n", json_decode_array($row['gallery_json'] ?? null));
$destText = implode(', ', json_decode_array($row['destinations_json'] ?? null));

ob_start();
?>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <div class="form-grid">
      <div class="form-group full">
        <label>Title</label>
        <input class="form-control" name="title" required value="<?= e($row['title'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Slug</label>
        <input class="form-control" name="slug" required value="<?= e($row['slug'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Type</label>
        <select class="form-control" name="type">
          <?php foreach (['leisure','adventure','family','couple','heritage'] as $t): ?>
            <option value="<?= $t ?>" <?= (($row['type'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Sheet</label>
        <input class="form-control" name="sheet" value="<?= e($row['sheet'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Group</label>
        <input class="form-control" name="group_name" value="<?= e($row['group_name'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Pickup</label>
        <input class="form-control" name="pickup" value="<?= e($row['pickup'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Drop</label>
        <input class="form-control" name="drop_point" value="<?= e($row['drop_point'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Pickup slug</label>
        <input class="form-control" name="pickup_slug" value="<?= e($row['pickup_slug'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Days</label>
        <input class="form-control" type="number" min="1" name="days" value="<?= e((string) ($row['days'] ?? '2')) ?>" />
      </div>
      <div class="form-group">
        <label>Nights</label>
        <input class="form-control" type="number" min="0" name="nights" value="<?= e((string) ($row['nights'] ?? '1')) ?>" />
      </div>
      <div class="form-group">
        <label>Duration bucket</label>
        <input class="form-control" name="duration_bucket" value="<?= e($row['duration_bucket'] ?? '') ?>" placeholder="2-4 / 5-7 / 8-10" />
      </div>
      <div class="form-group">
        <label>State</label>
        <input class="form-control" name="state" value="<?= e($row['state'] ?? '') ?>" />
      </div>
      <div class="form-group full">
        <label>Pages</label>
        <div class="checks">
          <?php foreach (['kerala','south','domestic','international'] as $p): ?>
            <label><input type="checkbox" name="pages[]" value="<?= $p ?>" <?= in_array($p, $pages, true) ? 'checked' : '' ?> /> <?= $p ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="form-group full">
        <label>Destinations (comma-separated slugs)</label>
        <input class="form-control" name="destinations" value="<?= e($destText ?: ($row['destinations'] ?? '')) ?>" />
      </div>
      <div class="form-group full">
        <label>Dest line</label>
        <input class="form-control" name="dest_line" value="<?= e($row['dest_line'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Stay split</label>
        <input class="form-control" name="stay_split" value="<?= e($row['stay_split'] ?? '') ?>" />
      </div>
      <div class="form-group">
        <label>Stay summary</label>
        <input class="form-control" name="stay_summary" value="<?= e($row['stay_summary'] ?? '') ?>" />
      </div>
      <div class="form-group full">
        <label>Card text</label>
        <textarea class="form-control" name="card_text"><?= e($row['card_text'] ?? '') ?></textarea>
      </div>
      <div class="form-group full">
        <label>Overview</label>
        <textarea class="form-control" name="overview" rows="5"><?= e($row['overview'] ?? '') ?></textarea>
      </div>
      <div class="form-group full">
        <label>Highlights (one per line)</label>
        <textarea class="form-control" name="highlights"><?= e($highlights ?: ($row['highlights'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>Itinerary (Day N | Title | Text — one day per line)</label>
        <textarea class="form-control" name="itinerary" rows="8"><?= e($itineraryText ?: ($row['itinerary'] ?? '')) ?></textarea>
      </div>
      <div class="form-group full">
        <label>Accommodation</label>
        <textarea class="form-control" name="accommodation"><?= e($row['accommodation'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label>Image path</label>
        <input class="form-control" name="image" value="<?= e($row['image'] ?? '') ?>" placeholder="packages/slug.jpg" />
      </div>
      <div class="form-group">
        <label>Or upload image</label>
        <input class="form-control" type="file" name="image_file" accept="image/*" />
      </div>
      <div class="form-group full">
        <label>Gallery paths (one per line)</label>
        <textarea class="form-control" name="gallery"><?= e($galleryText ?: ($row['gallery'] ?? '')) ?></textarea>
      </div>
      <div class="form-group">
        <label>Sort order</label>
        <input class="form-control" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
      </div>
      <div class="form-group">
        <label class="checks"><input type="checkbox" name="has_houseboat" value="1" <?= !empty($row['has_houseboat']) ? 'checked' : '' ?> /> Houseboat</label>
        <label class="checks" style="margin-top:0.5rem"><input type="checkbox" name="is_published" value="1" <?= !isset($row['is_published']) || !empty($row['is_published']) ? 'checked' : '' ?> /> Published</label>
      </div>
    </div>
    <div class="btn-group" style="margin-top:1rem">
      <button class="btn btn--primary" type="submit">Save</button>
      <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit package' : 'Add package';
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
