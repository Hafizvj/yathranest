<?php
/** @var string $pageTitle */
/** @var string $activeNav */
/** @var string $adminContent */
$admin = admin_user();
$pageTitle = $pageTitle ?? 'Admin';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle) ?> | YathraNest Admin</title>
  <link rel="icon" href="<?= e(url('assets/logo/logo.svg')) ?>" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>" />
</head>
<body class="admin">
  <aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('admin/index.php')) ?>">YN Admin</a>
    <nav class="admin-nav">
      <a class="<?= $activeNav === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
      <a class="<?= $activeNav === 'packages' ? 'is-active' : '' ?>" href="<?= e(url('admin/packages/index.php')) ?>">Packages</a>
      <a class="<?= $activeNav === 'places' ? 'is-active' : '' ?>" href="<?= e(url('admin/places/index.php')) ?>">Places</a>
      <a class="<?= $activeNav === 'resorts' ? 'is-active' : '' ?>" href="<?= e(url('admin/resorts/index.php')) ?>">Resorts</a>
      <a class="<?= $activeNav === 'getaways' ? 'is-active' : '' ?>" href="<?= e(url('admin/getaways/index.php')) ?>">Getaways</a>
      <a class="<?= $activeNav === 'gift-cards' ? 'is-active' : '' ?>" href="<?= e(url('admin/gift-cards/index.php')) ?>">Gift Cards</a>
      <a class="<?= $activeNav === 'investment' ? 'is-active' : '' ?>" href="<?= e(url('admin/investment/index.php')) ?>">Investment</a>
      <a class="<?= $activeNav === 'inquiries' ? 'is-active' : '' ?>" href="<?= e(url('admin/inquiries/index.php')) ?>">Inquiries</a>
      <a class="<?= $activeNav === 'content' ? 'is-active' : '' ?>" href="<?= e(url('admin/content/index.php')) ?>">Page content</a>
      <a class="<?= $activeNav === 'settings' ? 'is-active' : '' ?>" href="<?= e(url('admin/settings/index.php')) ?>">Settings</a>
    </nav>
    <div class="admin-sidebar__foot">
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
      <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <h1><?= e($pageTitle) ?></h1>
      <?php if ($admin): ?>
        <p class="admin-user"><?= e($admin['name'] ?? $admin['email']) ?></p>
      <?php endif; ?>
    </header>
    <?php if ($msg = flash_get('success')): ?>
      <div class="admin-alert admin-alert--ok"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
      <div class="admin-alert admin-alert--err"><?= e($msg) ?></div>
    <?php endif; ?>
    <div class="admin-content">
      <?= $adminContent ?? '' ?>
    </div>
  </div>
</body>
</html>
