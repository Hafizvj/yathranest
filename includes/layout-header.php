<?php
/**
 * Public site header.
 * Expects bootstrap already loaded.
 * Optional: $pageTitle, $metaDescription, $bodyAttrs, $assetDepth ('', '../'), $navActive
 */
$assetDepth = $assetDepth ?? '';
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

/** Primary navigation, shared by the Explore menu and the mobile drawer. */
$navGroups = [
    'Packages' => [
        ['key' => 'kerala', 'label' => 'Kerala Packages', 'href' => 'kerala-packages.php'],
        ['key' => 'south', 'label' => 'South Indian Packages', 'href' => 'south-indian-packages.php'],
        ['key' => 'domestic', 'label' => 'Domestic Packages', 'href' => 'domestic-packages.php'],
        ['key' => 'international', 'label' => 'International Packages', 'href' => 'international-packages.php'],
    ],
    'Services' => [
        ['key' => 'resorts', 'label' => 'Resort Stays', 'href' => 'resort-booking.php'],
        ['key' => 'taxi', 'label' => 'Taxi Booking', 'href' => 'taxi-booking.php'],
        ['key' => 'getaways', 'label' => 'Weekend Getaways', 'href' => 'weekend-getaways.php'],
        ['key' => 'gift', 'label' => 'Gift Cards', 'href' => 'gift-cards.php'],
        ['key' => 'investment', 'label' => 'Investment Plans', 'href' => 'investment-plans.php'],
    ],
    'Company' => [
        ['key' => 'about', 'label' => 'About Us', 'href' => 'about.php'],
        ['key' => 'contact', 'label' => 'Contact', 'href' => 'contact.php'],
        ['key' => 'faq', 'label' => 'FAQ', 'href' => 'faq.php'],
    ],
];
$navCurrent = static function (string $key) use ($navActive): string {
    return $key === $navActive ? ' aria-current="page"' : '';
};
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
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/style.css?v=12" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/components.css?v=12" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/responsive.css?v=12" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/inner.css?v=12" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/motion.css?v=12" />
  <script>
    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      document.documentElement.classList.add("has-motion");
    }
  </script>
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body<?= $bodyAttrs !== '' ? ' ' . $bodyAttrs : '' ?>>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header site-header--modern">
    <div class="container container--wide site-header__inner">
      <a class="logo" href="<?= e($homeHref) ?>" aria-label="YathraNest home">
        <img class="logo__img" src="<?= e(media_src($logoWordmark, $assetDepth, 'assets/logo/logo-wordmark.png', 'assets/')) ?>" alt="YathraNest" width="293" height="98" />
      </a>
      <div class="header-actions">
        <div class="nav-explore">
          <button class="nav-explore__btn" type="button" aria-expanded="false" aria-controls="explore-menu" id="explore-btn">
            Explore
            <svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true" focusable="false">
              <path d="M2.5 4.5L6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <div class="nav-explore__menu" id="explore-menu" hidden>
            <?php foreach ($navGroups as $groupLabel => $links): ?>
              <p class="nav-explore__label"><?= e($groupLabel) ?></p>
              <?php foreach ($links as $link): ?>
                <a href="<?= e($pagesPrefix . $link['href']) ?>"<?= $navCurrent($link['key']) ?>><?= e($link['label']) ?></a>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <a class="btn btn--primary btn--header-cta" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
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
        <a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
        <a class="nav-drawer__phone" href="<?= e($phoneHref) ?>"><?= e($phone) ?></a>
      </div>
    </div>
  </div>
