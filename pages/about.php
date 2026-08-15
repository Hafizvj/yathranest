<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$content = null;
try {
    $content = page_content('about');
} catch (Throwable $e) {
}
$sections = $content['sections'] ?? [];
$pageTitle = ($content['title'] ?? 'About') . ' | YathraNest';
$metaDescription = $sections['intro'] ?? 'About YathraNest.';
$enquiryType = 'general';
$enquiryInterest = 'About enquiry';
$enquirySource = 'pages/about.php';
$navActive = 'about';
$hero = $sections['hero_image'] ?? 'friends-travel.jpg';

$values = [
    ['icon' => 'compass', 'title' => 'Curated, not generic', 'text' => 'Every itinerary is shaped around the destination, pacing and what matters to you.'],
    ['icon' => 'shield', 'title' => 'Trusted partners', 'text' => 'Stays, transport and experiences are coordinated with people we work with directly.'],
    ['icon' => 'chat', 'title' => 'Personal pricing', 'text' => 'We quote after understanding your plan — no inflated online rates or hidden extras.'],
    ['icon' => 'headset', 'title' => 'Support that answers', 'text' => 'From first enquiry to the trip home, our team stays reachable.'],
];

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/<?= e($hero) ?>" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'About' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Who we are</p>
        <h1><?= e($content['title'] ?? 'About YathraNest') ?></h1>
        <?php if (!empty($sections['intro'])): ?>
          <p class="page-head__lead"><?= e($sections['intro']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="stat-strip" style="margin-bottom:3rem">
        <div class="stat-strip__item">
          <span class="stat-strip__num">4</span>
          <span class="stat-strip__label">Package collections</span>
        </div>
        <div class="stat-strip__item">
          <span class="stat-strip__num">24/7</span>
          <span class="stat-strip__label">Traveller support</span>
        </div>
        <div class="stat-strip__item">
          <span class="stat-strip__num">100%</span>
          <span class="stat-strip__label">Personalised quotes</span>
        </div>
        <div class="stat-strip__item">
          <span class="stat-strip__num">0</span>
          <span class="stat-strip__label">Online payments needed</span>
        </div>
      </div>

      <div class="article-layout">
        <div class="panel">
          <?php if (!empty($sections['body'])): ?>
            <div class="prose"><?= $sections['body'] ?></div>
          <?php else: ?>
            <div class="prose">
              <p>YathraNest plans curated travel across Kerala, South India, the rest of the country and short-haul international destinations — along with resort stays, taxi services, weekend getaways and travel gift cards.</p>
              <p>We keep pricing personal. Instead of publishing rates that change with season and availability, we understand your dates, group and preferences first, then share a quote that reflects the trip you actually want.</p>
            </div>
          <?php endif; ?>
        </div>

        <aside class="article-layout__aside">
          <div class="quote-card">
            <p class="quote-card__eyebrow">Start planning</p>
            <h3>Tell us your plan</h3>
            <p>Share where you'd like to go and when — we'll design the trip and share pricing.</p>
            <div class="btn-group">
              <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
              <a class="btn btn--secondary quote-card__ghost" href="contact.php">Contact Us</a>
            </div>
            <p class="quote-card__note"><?= yn_icon('shield') ?>Trusted &amp; safe travel</p>
          </div>
        </aside>
      </div>
    </div>
  </section>

  <section class="section section--soft">
    <div class="container">
      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">What we stand for</p>
          <h2>How we plan travel</h2>
          <p>A few principles that shape every itinerary we send out.</p>
        </div>
      </div>
      <div class="tile-grid">
        <?php foreach ($values as $value): ?>
          <div class="tile" data-reveal>
            <span class="tile__icon"><?= yn_icon($value['icon']) ?></span>
            <h3><?= e($value['title']) ?></h3>
            <p><?= e($value['text']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="section section--tight">
    <div class="container">
      <div class="cta-band">
        <p class="cta-band__eyebrow">Browse · Explore · Enquire</p>
        <h2>Ready when you are</h2>
        <p>Explore our collections or tell us what you have in mind — pricing is always shared personally.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="kerala-packages.php">
            Explore packages
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
