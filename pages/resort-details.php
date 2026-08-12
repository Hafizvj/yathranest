<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$slug = get_query('resort');
$resort = null;
try {
    if ($slug !== '') {
        $resort = catalog_by_slug('resorts', $slug);
    }
} catch (Throwable $e) {
}

$pageTitle = $resort ? ($resort['title'] . ' | YathraNest') : 'Resort Details | YathraNest';
$metaDescription = $resort['summary'] ?? 'Resort details with YathraNest.';
$enquiryType = 'resort';
$enquiryInterest = $resort['title'] ?? 'Resort stay';
$enquirySource = 'pages/resort-details.php?resort=' . rawurlencode($slug);

$img = '../assets/images/resort.jpg';
if ($resort) {
    $img = strpos($resort['image'], 'uploads/') === 0 ? '../' . $resort['image'] : '../assets/images/' . ltrim($resort['image'] ?: 'resort.jpg', '/');
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <div class="container">
      <ol>
        <li><a href="../index.php">Home</a></li>
        <li><a href="resort-booking.php">Resorts</a></li>
        <li><span aria-current="page"><?= e($resort['title'] ?? 'Resort') ?></span></li>
      </ol>
    </div>
  </nav>
  <section class="section">
    <div class="container">
      <?php if (!$resort): ?>
        <div class="empty-state">
          <h2>Resort not found</h2>
          <p><a class="btn btn--primary" href="resort-booking.php">Browse resorts</a></p>
        </div>
      <?php else: ?>
        <div class="package-detail">
          <h1><?= e($resort['title']) ?></h1>
          <p class="meta-row"><span><?= e($resort['location']) ?></span><span><?= e($resort['category']) ?></span></p>
          <p><?= e($resort['summary']) ?></p>
          <div class="package-detail__hero"><img src="<?= e($img) ?>" alt="<?= e($resort['title']) ?>" /></div>
          <p><?= nl2br(e($resort['body'])) ?></p>
          <?php if (!empty($resort['amenities'])): ?>
            <h2>Amenities</h2>
            <ul class="highlight-list">
              <?php foreach ($resort['amenities'] as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="btn-group" style="margin-top:1.5rem">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($resort['title']) ?>">Check Availability</a>
            <a class="btn btn--secondary" href="resort-booking.php">All resorts</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
