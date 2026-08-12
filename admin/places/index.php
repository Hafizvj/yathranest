<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$rows = db()->query('SELECT * FROM places ORDER BY sort_order, label')->fetchAll();

ob_start();
?>
<div class="admin-toolbar">
  <p style="margin:0;color:#6b6560"><?= count($rows) ?> places</p>
  <a class="btn btn--primary" href="<?= e(url('admin/places/edit.php')) ?>">Add place</a>
</div>
<div class="admin-panel">
  <table class="admin-table">
    <thead><tr><th>Label</th><th>Slug</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['label']) ?></td>
          <td><?= e($row['slug']) ?></td>
          <td>
            <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/places/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
            <form method="post" action="<?= e(url('admin/places/delete.php')) ?>" style="display:inline" onsubmit="return confirm('Delete place?');">
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
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Places';
$activeNav = 'places';
require dirname(__DIR__) . '/_layout.php';
