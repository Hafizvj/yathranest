<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$rows = db()->query(
    'SELECT id, slug, title, days, nights, type, types_json, image, is_published, is_featured, pages_json
     FROM packages ORDER BY is_featured DESC, sort_order, title'
)->fetchAll();

ob_start();
?>
<div class="admin-toolbar">
  <p class="admin-toolbar__meta"><?= count($rows) ?> packages</p>
  <a class="btn btn--primary" href="<?= e(url('admin/packages/edit.php')) ?>">Add package</a>
</div>
<div class="admin-panel">
  <?php if (!$rows): ?>
    <p class="admin-empty">No packages yet. Create your first package to get started.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>Duration</th><th>Type</th><th>Pages</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <?php if (!empty($row['image'])): ?>
                <img class="admin-thumb" src="<?= e(image_url($row['image'])) ?>" alt="" width="48" height="48" loading="lazy" />
              <?php endif; ?>
            </td>
            <td><strong><?= e($row['title']) ?></strong><br><small><?= e($row['slug']) ?></small></td>
            <td><?= (int) $row['days'] ?>D / <?= (int) $row['nights'] ?>N</td>
            <?php $types = json_decode_array($row['types_json']) ?: array_filter([(string) $row['type']]); ?>
            <td><?= e(package_types_label(['types' => $types])) ?></td>
            <td><?= e(implode(', ', json_decode_array($row['pages_json']))) ?></td>
            <td>
              <span class="badge badge--<?= (int) $row['is_published'] ? 'published' : 'draft' ?>">
                <?= (int) $row['is_published'] ? 'Published' : 'Draft' ?>
              </span>
              <?php if (!empty($row['is_featured'])): ?>
                <br><small>Featured</small>
              <?php endif; ?>
            </td>
            <td class="admin-row-actions">
              <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/packages/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= e(url('admin/packages/delete.php')) ?>" data-confirm="Delete this package?">
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
$pageTitle = 'Packages';
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
