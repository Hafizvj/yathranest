<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Investment Plans | YathraNest';
$metaDescription = 'Learn about YathraNest partner and investment programmes — enquire for information.';
$enquiryType = 'investment';
$enquiryInterest = 'Investment plans';
$enquirySource = 'pages/investment-plans.php';
$navActive = 'investment';

$plans = [];
try {
    $plans = catalog_list('investment_plans');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head">
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Investment Plans' => null]) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Partners</p>
        <h1>Investment Plans</h1>
        <p class="page-head__lead">Partner with a growing travel brand. We share programme details, terms and returns personally after your enquiry — no figures are published online.</p>
        <div class="page-head__chips">
          <?= yn_chip('users', 'Partner programmes') ?>
          <?= yn_chip('shield', 'Transparent terms') ?>
          <?= yn_chip('chat', 'One-to-one briefing') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="article-layout">
        <div>
          <div class="section-head">
            <div>
              <p class="section-head__eyebrow">Programmes</p>
              <h2>Ways to partner with us</h2>
              <p>Each programme suits a different level of involvement. Request information and we'll walk you through the fit.</p>
            </div>
          </div>

          <?php if (!$plans): ?>
            <div class="empty-state">
              <div class="empty-state__icon"><?= yn_icon('wallet') ?></div>
              <h2>Programme details on request</h2>
              <p>Tell us a little about your interest and our team will share the current options.</p>
              <div class="btn-group" style="justify-content:center">
                <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
              </div>
            </div>
          <?php else: ?>
            <div class="tile-grid">
              <?php foreach ($plans as $plan): ?>
                <article class="tile" data-reveal>
                  <span class="tile__icon"><?= yn_icon('wallet') ?></span>
                  <h3><?= e($plan['title']) ?></h3>
                  <p><?= e($plan['blurb']) ?></p>
                  <?php if (!empty($plan['features'])): ?>
                    <ul class="check-list">
                      <?php foreach ($plan['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                  <div class="tile__foot">
                    <a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($plan['title']) ?>">Request Information</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="section-head" style="margin-top:3rem">
            <div>
              <p class="section-head__eyebrow">Process</p>
              <h2>How the conversation goes</h2>
            </div>
          </div>
          <div class="timeline">
            <div class="timeline__item" data-reveal>
              <span class="timeline__day" aria-hidden="true">1</span>
              <div class="timeline__card">
                <h3 class="timeline__title">Send an enquiry</h3>
                <p class="timeline__text">Share your interest and the best way to reach you.</p>
              </div>
            </div>
            <div class="timeline__item" data-reveal>
              <span class="timeline__day" aria-hidden="true">2</span>
              <div class="timeline__card">
                <h3 class="timeline__title">Briefing call</h3>
                <p class="timeline__text">We explain the programme, commitments, timelines and expected outcomes.</p>
              </div>
            </div>
            <div class="timeline__item" data-reveal>
              <span class="timeline__day" aria-hidden="true">3</span>
              <div class="timeline__card">
                <h3 class="timeline__title">Documentation</h3>
                <p class="timeline__text">If it's a fit, terms are documented formally before anything proceeds.</p>
              </div>
            </div>
          </div>

          <p class="disclaimer">Information on this page is for general awareness only and is not an offer, solicitation or guarantee of returns. Programme details are shared individually and are subject to formal documentation.</p>
        </div>

        <aside class="article-layout__aside">
          <div class="quote-card">
            <p class="quote-card__eyebrow">No figures online</p>
            <h3>Request information</h3>
            <p>Our team shares programme details, terms and next steps directly with interested partners.</p>
            <div class="btn-group">
              <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Request Information</a>
              <a class="btn btn--secondary quote-card__ghost" href="contact.php">Contact Us</a>
            </div>
            <p class="quote-card__note"><?= yn_icon('shield') ?>Handled confidentially</p>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
