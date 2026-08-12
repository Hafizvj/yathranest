<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Weekend Getaways | YathraNest';
$metaDescription = 'Weekend getaways and short escapes with YathraNest.';
$enquiryType = 'general';
$enquiryInterest = 'Weekend getaway';
$enquirySource = 'pages/weekend-getaways.php';

$items = [];
try {
    $items = catalog_list('getaways');
} catch (Throwable $e) {
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero page-hero--media" style="background-image:url('../assets/images/friends-travel.jpg')">
    <div class="container page-hero__inner">
      <h1>Weekend Getaways</h1>
      <p>Short escapes designed for spontaneous weekends — enquire for upcoming options.</p>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="package-grid">
        <?php foreach ($items as $item):
          $img = strpos($item['image'], 'uploads/') === 0 ? '../' . $item['image'] : '../assets/images/' . ltrim($item['image'] ?: 'forest.jpg', '/');
          ?>
          <article class="card">
            <div class="card__media"><img src="<?= e($img) ?>" alt="<?= e($item['title']) ?>" loading="lazy" /></div>
            <div class="card__body">
              <p class="card__meta"><?= e($item['location']) ?> · <?= e($item['duration']) ?></p>
              <h3 class="card__title"><?= e($item['title']) ?></h3>
              <p class="card__text"><?= e($item['summary']) ?></p>
              <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($item['title']) ?>">I'm Interested</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
