<?php
/**
 * Public site header.
 * Expects bootstrap already loaded.
 * Optional: $pageTitle, $metaDescription, $bodyAttrs, $assetDepth ('', '../'),
 *           $navActive, $headerHero (bool — home over hero)
 */
$assetDepth = $assetDepth ?? '';
$headerHero = !empty($headerHero);
$homeHref = $assetDepth === '' ? 'index.php' : '../index.php';
$pagesPrefix = $assetDepth === '' ? 'pages/' : '';
$pageTitle = $pageTitle ?? 'YathraNest';
$metaDescription = $metaDescription ?? 'Curated travel packages, stays and experiences with YathraNest.';
$bodyAttrs = $bodyAttrs ?? '';
$navActive = $navActive ?? '';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = setting('whatsapp', '919876543210');
$phoneHref = 'tel:' . preg_replace('/\s+/', '', $phone);
$logoWordmark = setting('logo_wordmark', 'assets/logo/logo-wordmark.png');
$logoMark = setting('logo_mark', 'assets/logo/logo-mark.png');
$favicon = setting('favicon', 'assets/logo/favicon-32.png');
$appleTouch = setting('apple_touch_icon', 'assets/logo/apple-touch-icon.png');

$navPackages = [
    ['key' => 'kerala', 'label' => 'Kerala Packages', 'href' => 'kerala-packages.php'],
    ['key' => 'south', 'label' => 'South Indian Packages', 'href' => 'south-indian-packages.php'],
    ['key' => 'domestic', 'label' => 'Domestic Packages', 'href' => 'domestic-packages.php'],
    ['key' => 'international', 'label' => 'International Packages', 'href' => 'international-packages.php'],
];
$navServices = [
    ['key' => 'resorts', 'label' => 'Resort Stays', 'href' => 'resort-booking.php'],
    ['key' => 'taxi', 'label' => 'Taxi Booking', 'href' => 'taxi-booking.php'],
    ['key' => 'getaways', 'label' => 'Weekend Getaways', 'href' => 'weekend-getaways.php'],
    ['key' => 'gift', 'label' => 'Gift Cards', 'href' => 'gift-cards.php'],
    ['key' => 'investment', 'label' => 'Investment Plans', 'href' => 'investment-plans.php'],
];
$navGroups = [
    'Packages' => $navPackages,
    'Services' => $navServices,
    'Company' => [
        ['key' => 'about', 'label' => 'About Us', 'href' => 'about.php'],
        ['key' => 'contact', 'label' => 'Contact', 'href' => 'contact.php'],
        ['key' => 'faq', 'label' => 'FAQ', 'href' => 'faq.php'],
    ],
];

$packagesKeys = array_column($navPackages, 'key');
$servicesKeys = array_column($navServices, 'key');
$packagesActive = in_array($navActive, $packagesKeys, true);
$servicesActive = in_array($navActive, $servicesKeys, true);

$navCurrent = static function (string $key) use ($navActive): string {
    return $key === $navActive ? ' aria-current="page"' : '';
};

$headerClass = 'site-header site-header--modern';
if ($headerHero) {
    $headerClass .= ' site-header--hero';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= e($metaDescription) ?>" />
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(media_src($favicon, $assetDepth, 'assets/logo/favicon-32.png', 'assets/')) ?>" />
  <link rel="icon" type="image/png" sizes="150x150" href="<?= e(media_src($logoMark, $assetDepth, 'assets/logo/logo-mark.png', 'assets/')) ?>" />
  <link rel="apple-touch-icon" href="<?= e(media_src($appleTouch, $assetDepth, 'assets/logo/apple-touch-icon.png', 'assets/')) ?>" />
  <meta name="theme-color" content="#346356" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/style.css?v=14" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/components.css?v=12" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/responsive.css?v=14" />
  <?php if (!$headerHero): ?>
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/inner.css?v=14" />
  <?php endif; ?>
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/motion.css?v=14" />
  <script src="https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js" defer></script>
  <script>
    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      document.documentElement.classList.add("has-motion");
    }
  </script>
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body<?= $bodyAttrs !== '' ? ' ' . $bodyAttrs : '' ?>>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="<?= e($headerClass) ?>">
    <div class="container container--wide site-header__inner">
      <a class="logo" href="<?= e($homeHref) ?>" aria-label="YathraNest home">
        <img class="logo__img" src="<?= e(media_src($logoWordmark, $assetDepth, 'assets/logo/logo-wordmark.png', 'assets/')) ?>" alt="YathraNest" width="293" height="98" />
      </a>

      <nav class="nav-desktop" aria-label="Primary">
        <div class="nav-item<?= $packagesActive ? ' is-active' : '' ?>">
          <button class="nav-item__btn" type="button" aria-expanded="false" aria-controls="nav-menu-packages"<?= $packagesActive ? ' aria-current="true"' : '' ?>>
            Packages
            <?= yn_icon('chevron-down') ?>
          </button>
          <div class="nav-item__menu" id="nav-menu-packages" hidden>
            <?php foreach ($navPackages as $link): ?>
              <a href="<?= e($pagesPrefix . $link['href']) ?>"<?= $navCurrent($link['key']) ?>><?= e($link['label']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="nav-item<?= $servicesActive ? ' is-active' : '' ?>">
          <button class="nav-item__btn" type="button" aria-expanded="false" aria-controls="nav-menu-services"<?= $servicesActive ? ' aria-current="true"' : '' ?>>
            Services
            <?= yn_icon('chevron-down') ?>
          </button>
          <div class="nav-item__menu" id="nav-menu-services" hidden>
            <?php foreach ($navServices as $link): ?>
              <a href="<?= e($pagesPrefix . $link['href']) ?>"<?= $navCurrent($link['key']) ?>><?= e($link['label']) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <a href="<?= e($pagesPrefix) ?>about.php"<?= $navCurrent('about') ?>>About</a>
        <a href="<?= e($pagesPrefix) ?>contact.php"<?= $navCurrent('contact') ?>>Contact</a>
      </nav>

      <div class="header-actions">
        <a class="btn btn--primary btn--header-cta" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
        <button class="nav-toggle nav-toggle--circle" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu">
          <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
      </div>
    </div>
  </header>
  <div class="nav-drawer" id="nav-drawer">
    <div class="nav-drawer__backdrop"></div>
    <div class="nav-drawer__panel" role="dialog" aria-label="Mobile navigation">
      <div class="nav-drawer__head">
        <a class="logo" href="<?= e($homeHref) ?>" aria-label="YathraNest home">
          <img class="logo__img" src="<?= e(media_src($logoWordmark, $assetDepth, 'assets/logo/logo-wordmark.png', 'assets/')) ?>" alt="YathraNest" width="293" height="98" />
        </a>
        <button class="nav-drawer__close" type="button" aria-label="Close menu">&times;</button>
      </div>
      <nav class="nav-drawer__links" aria-label="Mobile">
        <?php foreach ($navGroups as $groupLabel => $links): ?>
          <p class="nav-drawer__label"><?= e($groupLabel) ?></p>
          <?php foreach ($links as $link): ?>
            <a href="<?= e($pagesPrefix . $link['href']) ?>"<?= $navCurrent($link['key']) ?>><?= e($link['label']) ?></a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
      <div class="nav-drawer__cta">
        <a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
        <a class="nav-drawer__phone" href="<?= e($phoneHref) ?>"><?= e($phone) ?></a>
      </div>
    </div>
  </div>
