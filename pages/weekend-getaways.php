<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Weekend Getaways | YathraNest';
$metaDescription = 'Weekend getaways and short escapes with YathraNest.';
$enquiryType = 'general';
$enquiryInterest = 'Weekend getaway';
$enquirySource = 'pages/weekend-getaways.php';
$navActive = 'getaways';

$items = [];
try {
    $items = catalog_list('getaways');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/friends-travel.jpg" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Weekend Getaways' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Short escapes</p>
        <h1>Weekend Getaways</h1>
        <p class="page-head__lead">Two to four day escapes and stranger trips — planned for comfort, connection and zero logistics on your side.</p>
        <div class="page-head__chips">
          <?= yn_chip('calendar', count($items) . ' upcoming trip' . (count($items) === 1 ? '' : 's')) ?>
          <?= yn_chip('users', 'Solo & group friendly') ?>
          <?= yn_chip('route', 'Transport included') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <?= yn_package_subnav('getaways') ?>

      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">Browse</p>
          <h2>Upcoming getaways</h2>
          <p>Pick a trip that fits your weekend — we'll share the plan, stay details and pricing on enquiry.</p>
        </div>
      </div>

      <?php if (!$items): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('calendar') ?></div>
          <h2>New trips are being planned</h2>
          <p>Tell us the kind of weekend you're after and we'll let you know as soon as dates open up.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Notify me</a>
          </div>
        </div>
      <?php else: ?>
        <div class="package-grid">
          <?php foreach ($items as $item):
            $img = media_src((string) ($item['image'] ?? ''), '../', 'forest.jpg');
            ?>
            <article class="card">
              <div class="card__media">
                <img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy" />
                <?php if (!empty($item['duration'])): ?>
                  <span class="card__badge"><?= e($item['duration']) ?></span>
                <?php endif; ?>
              </div>
              <div class="card__body">
                <p class="card__meta"><?= e($item['location']) ?></p>
                <h3 class="card__title"><?= e($item['title']) ?></h3>
                <p class="card__text"><?= e($item['summary']) ?></p>
                <div class="card__actions">
                  <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($item['title']) ?>">I'm Interested</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="section-head" style="margin-top:3.5rem">
        <div>
          <p class="section-head__eyebrow">How it works</p>
          <h2>Join in three steps</h2>
        </div>
      </div>
      <div class="tile-grid tile-grid--3">
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('compass') ?></span>
          <h3>Pick a trip</h3>
          <p>Choose a getaway and send an enquiry with your preferred dates.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('users') ?></span>
          <h3>Meet your group</h3>
          <p>We confirm your seat and share the itinerary, stay and group details.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('sparkle') ?></span>
          <h3>Just show up</h3>
          <p>Transport, stays and activities are coordinated — you only pack a bag.</p>
        </div>
      </div>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Private groups</p>
        <h2>Planning a getaway with your own group?</h2>
        <p>Tell us your group size, dates and vibe — we'll design a private weekend around it.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Plan a private trip
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
