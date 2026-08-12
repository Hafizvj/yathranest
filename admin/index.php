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
  <div class="admin-card"><strong><?= $counts['packages'] ?></strong><span>Packages</span></div>
  <div class="admin-card"><strong><?= $counts['inquiries_new'] ?></strong><span>New inquiries</span></div>
  <div class="admin-card"><strong><?= $counts['resorts'] ?></strong><span>Resorts</span></div>
  <div class="admin-card"><strong><?= $counts['places'] ?></strong><span>Places</span></div>
</div>

<div class="admin-panel">
  <div class="admin-toolbar">
    <h2 style="margin:0;font-size:1.1rem">Recent inquiries</h2>
    <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/inquiries/index.php')) ?>">View all</a>
  </div>
  <table class="admin-table">
    <thead>
      <tr><th>When</th><th>Type</th><th>Name</th><th>Interest</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php if (!$recent): ?>
        <tr><td colspan="6">No inquiries yet.</td></tr>
      <?php else: foreach ($recent as $row): ?>
        <tr>
          <td><?= e($row['created_at']) ?></td>
          <td><?= e($row['type']) ?></td>
          <td><?= e($row['name']) ?></td>
          <td><?= e($row['interest']) ?></td>
          <td><span class="badge badge--<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
          <td><a class="btn btn--secondary btn--sm" href="<?= e(url('admin/inquiries/view.php?id=' . (int) $row['id'])) ?>">Open</a></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/_layout.php';
