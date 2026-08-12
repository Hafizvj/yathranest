<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$slug = get_query('package');
$pkg = null;
$dbOk = true;
try {
    if ($slug !== '') {
        $pkg = package_by_slug($slug);
    }
} catch (Throwable $e) {
    $dbOk = false;
}

$pageTitle = $pkg ? ($pkg['title'] . ' — Package Details | YathraNest') : 'Package Details | YathraNest';
$metaDescription = $pkg['overview'] ?? 'Package details — itinerary, inclusions and request pricing with YathraNest.';
$bodyAttrs = 'data-package-details="true" data-asset-prefix="../assets/images/"';
$enquiryType = 'general';
$enquiryInterest = $pkg['title'] ?? 'Package enquiry';
$enquirySource = 'pages/package-details.php?package=' . rawurlencode($slug);

$related = $pkg ? packages_related($pkg, 3) : [];

$pageKey = ($pkg['pages'][0] ?? 'kerala');
$listHref = $pageKey === 'south' ? 'south-indian-packages.php' : ($pageKey === 'domestic' ? 'domestic-packages.php' : ($pageKey === 'international' ? 'international-packages.php' : 'kerala-packages.php'));
$listLabel = $pageKey === 'south' ? 'South Indian Packages' : ($pageKey === 'domestic' ? 'Domestic Packages' : ($pageKey === 'international' ? 'International Packages' : 'Kerala Packages'));
$eyebrow = ($pkg['sheet'] ?? '') === 'TN & KA PLANS' ? 'South Indian Package' : (($pkg['sheet'] ?? '') === 'Domestic' ? 'Domestic Package' : (($pkg['sheet'] ?? '') === 'International' ? 'International Package' : 'Kerala Package'));

$heroSrc = '../assets/images/' . ltrim($pkg['image'] ?? 'beach.jpg', '/');
if ($pkg && strpos($pkg['image'] ?? '', 'uploads/') === 0) {
    $heroSrc = '../' . $pkg['image'];
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <div class="container">
      <ol>
        <li><a href="../index.php">Home</a></li>
        <li><a href="<?= e($listHref) ?>"><?= e($listLabel) ?></a></li>
        <li><span aria-current="page"><?= e($pkg['title'] ?? 'Package') ?></span></li>
      </ol>
    </div>
  </nav>

  <section class="section section--tight">
    <div class="container">
      <?php if (!$pkg): ?>
        <div class="empty-state">
          <h2>Package not found</h2>
          <p>This itinerary is not in our current catalog. Browse Kerala or South Indian packages, or enquire for a custom plan.</p>
          <div class="btn-group">
            <a class="btn btn--primary" href="kerala-packages.php">Kerala Packages</a>
            <a class="btn btn--secondary" href="south-indian-packages.php">South Indian Packages</a>
          </div>
        </div>
      <?php else: ?>
        <div class="package-detail">
          <p class="eyebrow"><?= e($eyebrow) ?></p>
          <h1><?= e($pkg['title']) ?></h1>
          <p class="meta-row">
            <span><?= e($pkg['dest_line']) ?></span>
            <span><strong><?= (int) $pkg['days'] ?> Days / <?= (int) $pkg['nights'] ?> Nights</strong></span>
            <span>Pickup <?= e($pkg['pickup']) ?> · Drop <?= e($pkg['drop_point']) ?></span>
          </p>
          <p><?= e($pkg['overview']) ?></p>
          <div class="package-detail__hero">
            <img src="<?= e($heroSrc) ?>" alt="<?= e($pkg['title']) ?>" />
          </div>
          <div class="package-detail__grid">
            <div>
              <h2>Overview</h2>
              <p><?= e($pkg['overview']) ?></p>
              <h2>Highlights</h2>
              <ul class="highlight-list">
                <?php foreach ($pkg['highlights'] as $h): ?>
                  <li><?= e($h) ?></li>
                <?php endforeach; ?>
              </ul>
              <h2>Itinerary</h2>
              <div class="accordion">
                <?php foreach ($pkg['itinerary'] as $i => $day): ?>
                  <div class="accordion__item<?= $i === 0 ? ' is-open' : '' ?>">
                    <button class="accordion__trigger" type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                      <span>Day <?= (int) $day['day'] ?> — <?= e($day['title'] ?? '') ?></span>
                      <span class="accordion__icon" aria-hidden="true">+</span>
                    </button>
                    <div class="accordion__panel"><p><?= e($day['text'] ?? '') ?></p></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <h2>Accommodation</h2>
              <p><?= e($pkg['accommodation']) ?></p>
              <h2>Inclusions</h2>
              <ul>
                <li>Accommodation as per itinerary (<?= e($pkg['stay_summary']) ?>)</li>
                <li><?= !empty($pkg['has_houseboat']) ? 'Daily breakfast and houseboat meals as applicable' : 'Daily breakfast' ?></li>
                <li>Private transfers for sightseeing segments</li>
                <li>Assistance at pickup and drop</li>
              </ul>
            </div>
            <aside>
              <div class="cta-band">
                <h2>Request pricing</h2>
                <p>Share your dates — we will confirm stays and send a personalised quote.</p>
                <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Request Pricing</a>
              </div>
              <?php if ($pkg['gallery']): ?>
                <h3>Gallery</h3>
                <div class="gallery-grid" data-bind="gallery">
                  <?php foreach ($pkg['gallery'] as $file):
                    $src = strpos($file, 'uploads/') === 0 ? '../' . $file : '../assets/images/' . ltrim($file, '/');
                    ?>
                    <button type="button" data-gallery-item data-full="<?= e($src) ?>">
                      <img src="<?= e($src) ?>" alt="<?= e($pkg['title']) ?>" />
                    </button>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </aside>
          </div>
          <?php if ($related): ?>
            <h2>Related packages</h2>
            <div class="package-grid">
              <?php foreach ($related as $p):
                $img = strpos($p['image'], 'uploads/') === 0 ? '../' . $p['image'] : '../assets/images/' . ltrim($p['image'], '/');
                $href = 'package-details.php?package=' . rawurlencode($p['slug']);
                ?>
                <article class="card">
                  <div class="card__media"><img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" /></div>
                  <div class="card__body">
                    <h3 class="card__title"><a href="<?= e($href) ?>"><?= e($p['title']) ?></a></h3>
                    <p class="card__text"><?= e($p['days'] . ' Days · ' . $p['dest_line']) ?></p>
                    <a class="link-arrow" href="<?= e($href) ?>">View Package</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
require dirname(__DIR__) . '/includes/layout-footer.php';
