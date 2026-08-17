<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$rows = db()->query('SELECT * FROM places ORDER BY sort_order, label')->fetchAll();

ob_start();
?>
<div class="admin-toolbar">
  <p class="admin-toolbar__meta"><?= count($rows) ?> places</p>
  <a class="btn btn--primary" href="<?= e(url('admin/places/edit.php')) ?>">Add place</a>
</div>
<div class="admin-panel">
  <?php if (!$rows): ?>
    <p class="admin-empty">No places yet.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th></th><th>Label</th><th>Slug</th><th>Category</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row):
          $images = json_decode_array($row['images_json'] ?? null);
          $thumb = $images[0] ?? '';
          ?>
          <tr>
            <td>
              <?php if ($thumb !== ''): ?>
                <img class="admin-thumb" src="<?= e(image_url($thumb)) ?>" alt="" width="48" height="48" loading="lazy" />
              <?php endif; ?>
            </td>
            <td><?= e($row['label']) ?></td>
            <td><?= e($row['slug']) ?></td>
            <td><?= e(catalog_scopes_label(place_catalog_scopes($row))) ?></td>
            <td class="admin-row-actions">
              <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/places/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= e(url('admin/places/delete.php')) ?>" data-confirm="Delete this place?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                <button class="btn btn--danger btn--sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Places';
$activeNav = 'places';
require dirname(__DIR__) . '/_layout.php';
