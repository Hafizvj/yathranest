<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Investment Plans | YathraNest';
$metaDescription = 'Learn about YathraNest partner and investment programmes — enquire for information.';
$enquiryType = 'investment';
$enquiryInterest = 'Investment plans';
$enquirySource = 'pages/investment-plans.php';

$plans = [];
try {
    $plans = catalog_list('investment_plans');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <p class="section-eyebrow">Partners</p>
      <h1>Investment Plans</h1>
      <p>Explore partnership opportunities — information shared after enquiry. No online rates.</p>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="grid-2">
        <?php foreach ($plans as $plan): ?>
          <article class="option-card">
            <h3><?= e($plan['title']) ?></h3>
            <p><?= e($plan['blurb']) ?></p>
            <?php if (!empty($plan['features'])): ?>
              <ul class="highlight-list">
                <?php foreach ($plan['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($plan['title']) ?>">Request Information</a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
