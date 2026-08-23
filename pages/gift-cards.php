<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Gift Cards | YathraNest';
$metaDescription = 'YathraNest travel gift cards — enquire for personalised options. No online pricing.';
$enquiryType = 'gift';
$enquiryInterest = 'Gift card';
$enquirySource = 'pages/gift-cards.php';
$navActive = 'gift';
$giftsOn = feature_enabled('gift_cards');
$bodyAttrs = $giftsOn ? '' : 'data-auto-enquiry="1"';

$cards = [];
try {
    if ($giftsOn) {
        $cards = catalog_list('gift_cards');
    }
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/gift.jpg" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Gift Cards' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Gifting</p>
        <h1>Give the Gift of Travel</h1>
        <p class="page-head__lead">Let someone choose the journey that suits them — packages, stays or weekend escapes, wrapped in a YathraNest gift card.</p>
        <div class="page-head__chips">
          <?= yn_chip('gift', 'Any occasion') ?>
          <?= yn_chip('calendar', 'Flexible redemption') ?>
          <?= yn_chip('tag', 'Value on enquiry') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">Options</p>
          <h2>Gift card options</h2>
          <p>Choose a style of gift card — value, delivery and fulfilment details are shared after your enquiry.</p>
        </div>
      </div>

      <?php if (!$giftsOn): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('chat') ?></div>
          <h2>Gift cards on request</h2>
          <p>Tell us the occasion and who it's for — we'll put together the right gift.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
          </div>
        </div>
      <?php elseif (!$cards): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('gift') ?></div>
          <h2>Gift cards on request</h2>
          <p>Tell us the occasion and who it's for — we'll put together the right gift.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
          </div>
        </div>
      <?php else: ?>
        <div class="tile-grid tile-grid--3">
          <?php foreach ($cards as $i => $card): ?>
            <article class="tile<?= $i === 1 ? ' tile--featured' : '' ?>" data-reveal>
              <?php if ($i === 1): ?><span class="tile__ribbon">Most gifted</span><?php endif; ?>
              <span class="tile__icon"><?= yn_icon('gift') ?></span>
              <h3><?= e($card['title']) ?></h3>
              <p><?= e($card['blurb']) ?></p>
              <?php if (!empty($card['features'])): ?>
                <ul class="check-list">
                  <?php foreach ($card['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                </ul>
              <?php endif; ?>
              <div class="tile__foot">
                <a class="btn btn--<?= $i === 1 ? 'primary' : 'secondary' ?> btn--block" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($card['title']) ?> gift card">Request Information</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="section-head" style="margin-top:3.5rem">
        <div>
          <p class="section-head__eyebrow">How it works</p>
          <h2>Gifting made simple</h2>
        </div>
      </div>
      <div class="tile-grid tile-grid--3">
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('chat') ?></span>
          <h3>1 · Tell us the occasion</h3>
          <p>Share who it's for, the occasion and the value you have in mind.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('mail') ?></span>
          <h3>2 · We prepare the card</h3>
          <p>We personalise the card and send it digitally or as a printed keepsake.</p>
        </div>
        <div class="tile" data-reveal>
          <span class="tile__icon"><?= yn_icon('plane') ?></span>
          <h3>3 · They travel</h3>
          <p>The recipient redeems it against any package, stay or getaway with us.</p>
        </div>
      </div>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Corporate gifting</p>
        <h2>Gifting for a team or event?</h2>
        <p>We arrange bulk travel gift cards for teams, weddings and celebrations — tell us the details.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Request Information
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
