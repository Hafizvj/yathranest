<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Resort Booking | YathraNest';
$metaDescription = 'Discover resorts and stays with YathraNest. Check availability and request a quote — no online rates.';
$enquiryType = 'resort';
$enquiryInterest = 'Resort stay';
$enquirySource = 'pages/resort-booking.php';

$resorts = [];
try {
    $resorts = catalog_list('resorts');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero page-hero--media" style="background-image:url('../assets/images/resort.jpg')">
    <div class="container page-hero__inner">
      <h1>Resort Stays</h1>
      <p>Hill, backwater and coastal stays — enquire for availability and personalised pricing.</p>
    </div>
  </section>
  <section class="section">
    <div class="container" data-filter-root>
      <div class="section-header">
        <h2>Browse resorts</h2>
        <p class="section-lead">Select a property to explore, then request a quote for your dates.</p>
      </div>
      <div class="resort-grid" data-filter-list data-per-page="6">
        <?php foreach ($resorts as $r):
          $img = strpos($r['image'], 'uploads/') === 0 ? '../' . $r['image'] : '../assets/images/' . ltrim($r['image'], '/');
          $href = 'resort-details.php?resort=' . rawurlencode($r['slug']);
          ?>
          <article class="card" data-filter-item data-name="<?= e($r['title']) ?>" data-destination="<?= e(slugify($r['location'])) ?>" data-type="<?= e($r['category']) ?>">
            <div class="card__media"><img src="<?= e($img) ?>" alt="<?= e($r['title']) ?>" loading="lazy" /></div>
            <div class="card__body">
              <p class="card__meta"><?= e($r['location']) ?><?= $r['category'] ? ' · ' . e($r['category']) : '' ?></p>
              <h3 class="card__title"><a href="<?= e($href) ?>"><?= e($r['title']) ?></a></h3>
              <p class="card__text"><?= e($r['summary']) ?></p>
              <div class="card__actions">
                <a class="btn btn--secondary btn--sm" href="<?= e($href) ?>">View Resort</a>
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($r['title']) ?>">Request Quote</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="empty-state" data-filter-empty hidden>No resorts match your filters.</div>
      <div class="pagination" data-pagination></div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
