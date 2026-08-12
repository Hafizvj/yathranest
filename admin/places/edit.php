<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$id = (int) get_query('id', '0');
$row = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM places WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/places/index.php');
    }
    $slug = post('slug') !== '' ? post('slug') : slugify(post('label'));
    $tags = array_values(array_filter(array_map('trim', explode(',', post('tags')))));
    $images = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', post('images')) ?: [])));
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
        flash_set('success', 'Place updated.');
    } else {
        db()->prepare('INSERT INTO places (slug, label, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (?,?,?,?,?,?,?)')->execute($payload);
        flash_set('success', 'Place created.');
    }
    redirect('admin/places/index.php');
}

$tags = implode(', ', json_decode_array($row['tags_json'] ?? null));
$images = implode("\n", json_decode_array($row['images_json'] ?? null));

ob_start();
?>
<div class="admin-panel">
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group"><label>Label</label><input class="form-control" name="label" required value="<?= e($row['label'] ?? '') ?>" /></div>
      <div class="form-group"><label>Slug</label><input class="form-control" name="slug" value="<?= e($row['slug'] ?? '') ?>" /></div>
      <div class="form-group full"><label>Tags (comma-separated)</label><input class="form-control" name="tags" value="<?= e($tags) ?>" /></div>
      <div class="form-group full"><label>Arrive text</label><textarea class="form-control" name="arrive_text"><?= e($row['arrive_text'] ?? '') ?></textarea></div>
      <div class="form-group full"><label>Sightseeing text</label><textarea class="form-control" name="sightseeing_text"><?= e($row['sightseeing_text'] ?? '') ?></textarea></div>
      <div class="form-group full"><label>Images (one filename per line)</label><textarea class="form-control" name="images"><?= e($images) ?></textarea></div>
      <div class="form-group"><label>Sort order</label><input class="form-control" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" /></div>
    </div>
    <div class="btn-group" style="margin-top:1rem">
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
