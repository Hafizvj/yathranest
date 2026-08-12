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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/' . $cfg['nav'] . '/index.php');
    }
    $slug = post('slug') !== '' ? post('slug') : slugify(post('title'));
    $data = [
        'slug' => $slug,
        'title' => post('title'),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'sort_order' => (int) post('sort_order', '0'),
        'image' => post('image'),
    ];
    if (!empty($_FILES['image_file']['name'])) {
        $up = admin_store_upload($_FILES['image_file'], $cfg['nav']);
        if ($up) {
            $data['image'] = $up;
        }
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
            $data['gallery_json'] = catalog_lines_to_json(post('gallery'));
        }
        if (!empty($cfg['has_amenities'])) {
            $data['amenities_json'] = catalog_lines_to_json(post('amenities'));
        }
    }

    $fields = $cfg['fields'];
    $vals = [];
    foreach ($fields as $f) {
        $vals[] = $data[$f] ?? ($f === 'gallery_json' || $f === 'amenities_json' || $f === 'features_json' ? '[]' : '');
    }
    if ($id) {
        $sets = implode(', ', array_map(static fn($f) => "$f = ?", $fields));
        $vals[] = $id;
        db()->prepare("UPDATE {$table} SET {$sets} WHERE id = ?")->execute($vals);
        flash_set('success', 'Updated.');
    } else {
        $cols = implode(', ', $fields);
        $ph = implode(', ', array_fill(0, count($fields), '?'));
        db()->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$ph})")->execute($vals);
        flash_set('success', 'Created.');
    }
    redirect('admin/' . $cfg['nav'] . '/index.php');
}

$features = implode("\n", json_decode_array($row['features_json'] ?? null));
$gallery = implode("\n", json_decode_array($row['gallery_json'] ?? null));
$amenities = implode("\n", json_decode_array($row['amenities_json'] ?? null));

ob_start();
?>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group full"><label>Title</label><input class="form-control" name="title" required value="<?= e($row['title'] ?? '') ?>" /></div>
      <div class="form-group"><label>Slug</label><input class="form-control" name="slug" value="<?= e($row['slug'] ?? '') ?>" /></div>
      <?php if (!empty($cfg['blurb_field'])): ?>
        <div class="form-group full"><label>Blurb</label><textarea class="form-control" name="blurb"><?= e($row['blurb'] ?? '') ?></textarea></div>
        <div class="form-group full"><label>Features (one per line)</label><textarea class="form-control" name="features"><?= e($features) ?></textarea></div>
      <?php else: ?>
        <div class="form-group"><label>Location</label><input class="form-control" name="location" value="<?= e($row['location'] ?? '') ?>" /></div>
        <?php if (!empty($cfg['has_category'])): ?>
          <div class="form-group"><label>Category</label><input class="form-control" name="category" value="<?= e($row['category'] ?? '') ?>" /></div>
        <?php endif; ?>
        <?php if (!empty($cfg['has_duration'])): ?>
          <div class="form-group"><label>Duration</label><input class="form-control" name="duration" value="<?= e($row['duration'] ?? '') ?>" /></div>
        <?php endif; ?>
        <div class="form-group full"><label>Summary</label><textarea class="form-control" name="summary"><?= e($row['summary'] ?? '') ?></textarea></div>
        <div class="form-group full"><label>Body</label><textarea class="form-control" name="body" rows="6"><?= e($row['body'] ?? '') ?></textarea></div>
        <?php if (!empty($cfg['has_amenities'])): ?>
          <div class="form-group full"><label>Amenities (one per line)</label><textarea class="form-control" name="amenities"><?= e($amenities) ?></textarea></div>
        <?php endif; ?>
        <?php if (!empty($cfg['has_gallery'])): ?>
          <div class="form-group full"><label>Gallery paths (one per line)</label><textarea class="form-control" name="gallery"><?= e($gallery) ?></textarea></div>
        <?php endif; ?>
      <?php endif; ?>
      <div class="form-group"><label>Image path</label><input class="form-control" name="image" value="<?= e($row['image'] ?? '') ?>" /></div>
      <div class="form-group"><label>Upload</label><input class="form-control" type="file" name="image_file" accept="image/*" /></div>
      <div class="form-group"><label>Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" /></div>
      <div class="form-group"><label class="checks"><input type="checkbox" name="is_published" value="1" <?= !isset($row['is_published']) || !empty($row['is_published']) ? 'checked' : '' ?> /> Published</label></div>
    </div>
    <div class="btn-group" style="margin-top:1rem">
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
