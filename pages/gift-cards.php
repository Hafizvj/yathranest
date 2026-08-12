<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Gift Cards | YathraNest';
$metaDescription = 'YathraNest travel gift cards — enquire for personalised options. No online pricing.';
$enquiryType = 'gift';
$enquiryInterest = 'Gift card';
$enquirySource = 'pages/gift-cards.php';

$cards = [];
try {
    $cards = catalog_list('gift_cards');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <p class="section-eyebrow">Gifting</p>
      <h1>Give the Gift of Travel</h1>
      <p>Gift cards for packages, stays or getaways. Enquire for personalised options — no online purchase.</p>
    </div>
  </section>
  <section class="section section--alt">
    <div class="container">
      <div class="section-header section-header--center">
        <h2>Gift card options</h2>
        <p class="section-lead">Details and fulfilment are shared after enquiry.</p>
      </div>
      <div class="grid-3">
        <?php foreach ($cards as $i => $card): ?>
          <article class="option-card<?= $i === 1 ? ' option-card--featured' : '' ?>">
            <h3><?= e($card['title']) ?></h3>
            <p><?= e($card['blurb']) ?></p>
            <?php if (!empty($card['features'])): ?>
              <ul class="highlight-list">
                <?php foreach ($card['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <a class="btn btn--<?= $i === 1 ? 'primary' : 'secondary' ?>" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($card['title']) ?> gift card">Request Information</a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
