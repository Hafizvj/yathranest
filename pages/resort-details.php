<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$slug = get_query('resort');
$resort = null;
$others = [];
try {
    if ($slug !== '') {
        $resort = catalog_by_slug('resorts', $slug);
    }
    foreach (catalog_list('resorts') as $row) {
        if (($row['slug'] ?? '') !== $slug) {
            $others[] = $row;
        }
    }
} catch (Throwable $e) {
}
$others = array_slice($others, 0, 3);

$pageTitle = $resort ? ($resort['title'] . ' | YathraNest') : 'Resort Details | YathraNest';
$metaDescription = $resort['summary'] ?? 'Resort details with YathraNest.';
$enquiryType = 'resort';
$enquiryInterest = $resort['title'] ?? 'Resort stay';
$enquirySource = 'pages/resort-details.php?resort=' . rawurlencode($slug);
$navActive = 'resorts';
$whatsapp = preg_replace('/\D/', '', setting('whatsapp', '919876543210'));

$resortImg = static function (string $file): string {
    return media_src($file, '../', 'resort.jpg');
};
$img = $resortImg((string) ($resort['image'] ?? ''));

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <div class="crumbs-bar">
    <div class="container">
      <?= yn_crumbs([
          'Home' => '../index.php',
          'Resort Stays' => 'resort-booking.php',
          ($resort['title'] ?? 'Resort') => null,
      ]) ?>
    </div>
  </div>

  <section class="section section--tight">
    <div class="container">
      <?php if (!$resort): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('bed') ?></div>
          <h2>Resort not found</h2>
          <p>This property isn't in our current collection. Browse all stays, or tell us what you're looking for.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="resort-booking.php">Browse resorts</a>
            <a class="btn btn--secondary" href="#enquiry" data-open-modal="enquiry-modal">Request a stay</a>
          </div>
        </div>
      <?php else: ?>
        <div class="detail-head">
          <div class="detail-head__media">
            <img src="<?= e($img) ?>" alt="<?= e($resort['title']) ?>" width="1200" height="800" />
            <?php if (!empty($resort['category'])): ?>
              <span class="detail-head__tag"><?= e($resort['category']) ?></span>
            <?php endif; ?>
          </div>
          <div>
            <h1><?= e($resort['title']) ?></h1>
            <p class="detail-head__lead"><?= e($resort['summary']) ?></p>
            <div class="spec-grid">
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('pin') ?></span>
                <span>
                  <span class="spec__label">Location</span>
                  <span class="spec__value"><?= e($resort['location'] ?: 'On request') ?></span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('bed') ?></span>
                <span>
                  <span class="spec__label">Property</span>
                  <span class="spec__value"><?= e($resort['category'] ?: 'Resort') ?></span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('sparkle') ?></span>
                <span>
                  <span class="spec__label">Amenities</span>
                  <span class="spec__value"><?= count($resort['amenities'] ?? []) ?: '—' ?></span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('tag') ?></span>
                <span>
                  <span class="spec__label">Rates</span>
                  <span class="spec__value">On enquiry</span>
                </span>
              </div>
            </div>
            <div class="btn-group">
              <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($resort['title']) ?>">Check Availability</a>
              <a class="btn btn--secondary" href="resort-booking.php">All resorts</a>
            </div>
          </div>
        </div>

        <div class="detail-layout">
          <div class="detail-body">
            <?php if (!empty($resort['body'])): ?>
              <div class="content-block">
                <h2>About this stay</h2>
                <div class="prose"><p><?= nl2br(e($resort['body'])) ?></p></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($resort['amenities'])): ?>
              <div class="content-block">
                <h2>Amenities</h2>
                <div class="amenity-grid">
                  <?php foreach ($resort['amenities'] as $a): ?>
                    <span class="amenity"><?= yn_icon('check') ?><?= e($a) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($resort['gallery'])): ?>
              <div class="content-block">
                <h2>Gallery</h2>
                <div class="gallery-grid" data-gallery>
                  <?php foreach ($resort['gallery'] as $file):
                    $src = $resortImg((string) $file);
                    ?>
                    <button type="button" data-gallery-item data-full="<?= e($src) ?>">
                      <img src="<?= e($src) ?>" alt="<?= e($resort['title']) ?>" loading="lazy" />
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="content-block">
              <h2>How booking works</h2>
              <div class="timeline">
                <div class="timeline__item">
                  <span class="timeline__day" aria-hidden="true">1</span>
                  <div class="timeline__card">
                    <h3 class="timeline__title">Send your dates</h3>
                    <p class="timeline__text">Share check-in and check-out dates, guest count and room preference.</p>
                  </div>
                </div>
                <div class="timeline__item">
                  <span class="timeline__day" aria-hidden="true">2</span>
                  <div class="timeline__card">
                    <h3 class="timeline__title">We confirm availability</h3>
                    <p class="timeline__text">Our team checks the property directly and shares room options with pricing.</p>
                  </div>
                </div>
                <div class="timeline__item">
                  <span class="timeline__day" aria-hidden="true">3</span>
                  <div class="timeline__card">
                    <h3 class="timeline__title">Confirm personally</h3>
                    <p class="timeline__text">No online payment — the booking is confirmed with our team over call or WhatsApp.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <aside class="detail-aside">
            <div class="quote-card">
              <p class="quote-card__eyebrow">No online rates</p>
              <h3>Check availability</h3>
              <p>Tell us your dates and we'll confirm rooms and share pricing for this property.</p>
              <div class="btn-group">
                <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($resort['title']) ?>">Request Availability</a>
                <a class="btn btn--secondary quote-card__ghost" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
              </div>
              <p class="quote-card__note"><?= yn_icon('shield') ?>Verified partner property</p>
            </div>
          </aside>
        </div>

        <?php if ($others): ?>
          <div class="section-head" style="margin-top:3.5rem">
            <div>
              <p class="section-head__eyebrow">More stays</p>
              <h2>Other resorts you may like</h2>
            </div>
            <a class="btn btn--secondary btn--sm" href="resort-booking.php">View all</a>
          </div>
          <div class="resort-grid">
            <?php foreach ($others as $other):
              $otherHref = 'resort-details.php?resort=' . rawurlencode($other['slug']);
              ?>
              <article class="card">
                <div class="card__media">
                  <img src="<?= e($resortImg((string) ($other['image'] ?? ''))) ?>" alt="<?= e($other['title']) ?>" loading="lazy" />
                  <?php if (!empty($other['category'])): ?>
                    <span class="card__badge"><?= e($other['category']) ?></span>
                  <?php endif; ?>
                </div>
                <div class="card__body">
                  <p class="card__meta"><?= e($other['location']) ?></p>
                  <h3 class="card__title"><a href="<?= e($otherHref) ?>"><?= e($other['title']) ?></a></h3>
                  <p class="card__text"><?= e($other['summary']) ?></p>
                  <a class="link-arrow" href="<?= e($otherHref) ?>">View Resort</a>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
