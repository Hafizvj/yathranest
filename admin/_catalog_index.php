<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_catalog.php';
require_once __DIR__ . '/_feature_toggle.php';
require_admin();

$cfg = catalog_config($catalogKey ?? '');
if (!$cfg) {
    http_response_code(404);
    echo 'Unknown catalog';
    exit;
}

$featureKey = (string) ($cfg['feature_key'] ?? '');
if ($featureKey !== '') {
    feature_toggle_handle_post($featureKey, 'admin/' . $cfg['nav'] . '/index.php');
}

$table = $cfg['table'];
$rows = db()->query("SELECT * FROM {$table} ORDER BY sort_order, title")->fetchAll();

ob_start();
?>
<div class="admin-toolbar">
  <p class="admin-toolbar__meta"><?= count($rows) ?> items</p>
  <div class="admin-toolbar__actions">
    <?php if ($featureKey !== ''): ?>
      <?= feature_toggle_html($featureKey) ?>
    <?php endif; ?>
    <a class="btn btn--primary" href="<?= e(url('admin/' . $cfg['nav'] . '/edit.php')) ?>">Add</a>
  </div>
</div>
<div class="admin-panel">
  <?php if (!$rows): ?>
    <p class="admin-empty">No items yet.</p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <?php if (!empty($row['image'])): ?>
                <img class="admin-thumb" src="<?= e(image_url($row['image'])) ?>" alt="" width="48" height="48" loading="lazy" />
              <?php endif; ?>
            </td>
            <td><?= e($row['title']) ?></td>
            <td><?= e($row['slug']) ?></td>
            <td>
              <span class="badge badge--<?= !empty($row['is_published']) ? 'published' : 'draft' ?>">
                <?= !empty($row['is_published']) ? 'Published' : 'Draft' ?>
              </span>
            </td>
            <td class="admin-row-actions">
              <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/' . $cfg['nav'] . '/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= e(url('admin/' . $cfg['nav'] . '/delete.php')) ?>" data-confirm="Delete this item?">
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
$pageTitle = $cfg['label'];
$activeNav = $cfg['nav'];
require __DIR__ . '/_layout.php';
