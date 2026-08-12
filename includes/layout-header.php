<?php
/**
 * Public site header.
 * Expects bootstrap already loaded.
 * Optional: $pageTitle, $metaDescription, $bodyAttrs, $assetDepth ('', '../')
 */
$assetDepth = $assetDepth ?? '';
$homeHref = $assetDepth === '' ? 'index.php' : '../index.php';
$pagesPrefix = $assetDepth === '' ? 'pages/' : '';
$pageTitle = $pageTitle ?? 'YathraNest';
$metaDescription = $metaDescription ?? 'Curated travel packages, stays and experiences with YathraNest.';
$bodyAttrs = $bodyAttrs ?? '';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = setting('whatsapp', '919876543210');
$phoneHref = 'tel:' . preg_replace('/\s+/', '', $phone);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="<?= e($metaDescription) ?>" />
  <title><?= e($pageTitle) ?></title>
  <link rel="icon" href="<?= e($assetDepth) ?>assets/logo/logo.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/style.css" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/components.css" />
  <link rel="stylesheet" href="<?= e($assetDepth) ?>css/responsive.css" />
  <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body<?= $bodyAttrs !== '' ? ' ' . $bodyAttrs : '' ?>>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="container site-header__inner">
      <a class="logo" href="<?= e($homeHref) ?>" aria-label="YathraNest home">
        <span class="logo__mark" aria-hidden="true">YN</span>
        <span class="logo__text">Yathra<span>Nest</span></span>
      </a>
      <nav class="nav-desktop" aria-label="Primary">
        <a href="<?= e($pagesPrefix) ?>kerala-packages.php" data-nav="packages">Packages</a>
        <a href="<?= e($pagesPrefix) ?>taxi-booking.php" data-nav>Taxi</a>
        <a href="<?= e($pagesPrefix) ?>resort-booking.php" data-nav>Resorts</a>
        <a href="<?= e($pagesPrefix) ?>weekend-getaways.php" data-nav>Getaways</a>
        <a href="<?= e($pagesPrefix) ?>gift-cards.php" data-nav>Gift Cards</a>
        <a href="<?= e($pagesPrefix) ?>investment-plans.php" data-nav>Investment Plans</a>
        <a href="<?= e($pagesPrefix) ?>about.php" data-nav>About</a>
        <a href="<?= e($pagesPrefix) ?>contact.php" data-nav>Contact</a>
      </nav>
      <div class="header-actions">
        <a class="btn btn--primary btn--header-cta" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu">
          <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
      </div>
    </div>
  </header>
  <div class="nav-drawer" id="nav-drawer">
    <div class="nav-drawer__backdrop"></div>
    <div class="nav-drawer__panel" role="dialog" aria-label="Mobile navigation">
      <div class="nav-drawer__head">
        <a class="logo" href="<?= e($homeHref) ?>"><span class="logo__mark" aria-hidden="true">YN</span><span class="logo__text">Yathra<span>Nest</span></span></a>
        <button class="nav-drawer__close" type="button" aria-label="Close menu">&times;</button>
      </div>
      <nav class="nav-drawer__links" aria-label="Mobile">
        <a href="<?= e($pagesPrefix) ?>kerala-packages.php">Packages</a>
        <a href="<?= e($pagesPrefix) ?>taxi-booking.php">Taxi</a>
        <a href="<?= e($pagesPrefix) ?>resort-booking.php">Resorts</a>
        <a href="<?= e($pagesPrefix) ?>weekend-getaways.php">Getaways</a>
        <a href="<?= e($pagesPrefix) ?>gift-cards.php">Gift Cards</a>
        <a href="<?= e($pagesPrefix) ?>investment-plans.php">Investment Plans</a>
        <a href="<?= e($pagesPrefix) ?>about.php">About</a>
        <a href="<?= e($pagesPrefix) ?>contact.php">Contact</a>
      </nav>
      <div class="nav-drawer__cta"><a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a></div>
    </div>
  </div>
