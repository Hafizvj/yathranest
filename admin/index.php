<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$counts = [
    'packages' => (int) db()->query('SELECT COUNT(*) FROM packages')->fetchColumn(),
    'inquiries_new' => (int) db()->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn(),
    'resorts' => (int) db()->query('SELECT COUNT(*) FROM resorts')->fetchColumn(),
    'places' => (int) db()->query('SELECT COUNT(*) FROM places')->fetchColumn(),
];

$recent = db()->query('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 10')->fetchAll();

ob_start();
?>
<div class="admin-cards">
  <a class="admin-card" href="<?= e(url('admin/packages/index.php')) ?>" style="text-decoration:none">
    <strong><?= $counts['packages'] ?></strong><span>Packages</span>
  </a>
  <a class="admin-card" href="<?= e(url('admin/inquiries/index.php?status=new')) ?>" style="text-decoration:none">
    <strong><?= $counts['inquiries_new'] ?></strong><span>New inquiries</span>
  </a>
  <a class="admin-card" href="<?= e(url('admin/resorts/index.php')) ?>" style="text-decoration:none">
    <strong><?= $counts['resorts'] ?></strong><span>Resorts</span>
  </a>
  <a class="admin-card" href="<?= e(url('admin/places/index.php')) ?>" style="text-decoration:none">
    <strong><?= $counts['places'] ?></strong><span>Places</span>
  </a>
</div>

<div class="admin-panel">
  <div class="admin-toolbar">
    <h2>Recent inquiries</h2>
    <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/inquiries/index.php')) ?>">View all</a>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr><th>When</th><th>Type</th><th>Name</th><th>Interest</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$recent): ?>
          <tr><td class="admin-empty" colspan="6">No inquiries yet.</td></tr>
        <?php else: foreach ($recent as $row): ?>
          <tr>
            <td><?= e($row['created_at']) ?></td>
            <td><?= e($row['type']) ?></td>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['interest']) ?></td>
            <td><span class="badge badge--<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
            <td class="admin-row-actions">
              <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/inquiries/view.php?id=' . (int) $row['id'])) ?>">Open</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/_layout.php';
