<?php
/** @var string $pageTitle */
/** @var string $activeNav */
/** @var string $adminContent */
$admin = admin_user();
$pageTitle = $pageTitle ?? 'Admin';
$pageSubtitle = $pageSubtitle ?? '';
$adminScripts = $adminScripts ?? [];
$activeNav = $activeNav ?? '';
$logoMark = setting('logo_mark', 'assets/logo/logo-mark.png');
$favicon = setting('favicon', 'assets/logo/favicon-32.png');
$appleTouch = setting('apple_touch_icon', 'assets/logo/apple-touch-icon.png');
$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'href' => 'admin/index.php'],
    'packages' => ['label' => 'Packages', 'href' => 'admin/packages/index.php'],
    'places' => ['label' => 'Places', 'href' => 'admin/places/index.php'],
    'resorts' => ['label' => 'Resorts', 'href' => 'admin/resorts/index.php'],
    'getaways' => ['label' => 'Getaways', 'href' => 'admin/getaways/index.php'],
    'gift-cards' => ['label' => 'Gift Cards', 'href' => 'admin/gift-cards/index.php'],
    'investment' => ['label' => 'Investment', 'href' => 'admin/investment/index.php'],
    'media' => ['label' => 'Media', 'href' => 'admin/media/index.php'],
    'inquiries' => ['label' => 'Inquiries', 'href' => 'admin/inquiries/index.php'],
    'content' => ['label' => 'Page content', 'href' => 'admin/content/index.php'],
    'settings' => ['label' => 'Settings', 'href' => 'admin/settings/index.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle) ?> | YathraNest Admin</title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(image_url($favicon, '')) ?>" />
  <link rel="icon" type="image/png" sizes="150x150" href="<?= e(image_url($logoMark, '')) ?>" />
  <link rel="apple-touch-icon" href="<?= e(image_url($appleTouch, '')) ?>" />
  <meta name="theme-color" content="#346356" />
  <link rel="stylesheet" href="<?= e(url('admin/assets/admin.css')) ?>?v=8" />
  <script src="https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js" defer></script>
</head>
<body class="admin">
  <a class="skip-link" href="#admin-main">Skip to content</a>
  <div class="admin-backdrop" data-admin-backdrop hidden></div>
  <aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('admin/index.php')) ?>">
      <img src="<?= e(image_url($logoMark, '')) ?>" alt="" width="150" height="150" />
      <span>YathraNest Admin</span>
    </a>
    <nav class="admin-nav" aria-label="Admin">
      <?php foreach ($navItems as $key => $item): ?>
        <a
          class="<?= $activeNav === $key ? 'is-active' : '' ?>"
          href="<?= e(url($item['href'])) ?>"
          <?= $activeNav === $key ? 'aria-current="page"' : '' ?>
        ><?= e($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar__foot">
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View site</a>
      <a href="<?= e(url('admin/logout.php')) ?>">Log out</a>
    </div>
  </aside>
  <div class="admin-main">
    <header class="admin-top">
      <div class="admin-top__left">
        <button class="admin-menu-btn" type="button" data-admin-menu aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open menu">
          <span></span>
        </button>
        <div class="admin-top__titles">
          <h1><?= e($pageTitle) ?></h1>
          <?php if ($pageSubtitle !== ''): ?>
            <p class="admin-top__subtitle"><?= e($pageSubtitle) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($admin): ?>
        <?php $adminName = (string) ($admin['name'] ?? $admin['email']); ?>
        <p class="admin-user">
          <span class="admin-user__avatar" aria-hidden="true"><?= e(strtoupper(substr($adminName, 0, 1))) ?></span>
          <span><?= e($adminName) ?></span>
        </p>
      <?php endif; ?>
    </header>
    <?php if ($msg = flash_get('success')): ?>
      <div class="admin-alert admin-alert--ok" role="status">
        <span><?= e($msg) ?></span>
        <button type="button" class="admin-alert__close" data-alert-dismiss aria-label="Dismiss">&times;</button>
      </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
      <div class="admin-alert admin-alert--err" role="alert">
        <span><?= e($msg) ?></span>
        <button type="button" class="admin-alert__close" data-alert-dismiss aria-label="Dismiss">&times;</button>
      </div>
    <?php endif; ?>
    <div class="admin-content" id="admin-main">
      <?= $adminContent ?? '' ?>
    </div>
  </div>
  <?php require __DIR__ . '/_media_picker.php'; ?>
  <script src="<?= e(url('admin/assets/admin.js')) ?>?v=5" defer></script>
  <script src="<?= e(url('admin/assets/admin-media.js')) ?>?v=1" defer></script>
  <?php foreach ($adminScripts as $script): ?>
    <script src="<?= e(url($script)) ?>?v=5" defer></script>
  <?php endforeach; ?>
</body>
</html>
