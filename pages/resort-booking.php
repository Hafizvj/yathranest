<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Resort Booking | YathraNest';
$metaDescription = 'Discover resorts and stays with YathraNest. Check availability and request a quote — no online rates.';
$enquiryType = 'resort';
$enquiryInterest = 'Resort stay';
$enquirySource = 'pages/resort-booking.php';
$navActive = 'resorts';
$resortsOn = feature_enabled('resorts');
$bodyAttrs = $resortsOn ? '' : 'data-auto-enquiry="1"';

$resorts = [];
try {
    if ($resortsOn) {
        $resorts = catalog_list('resorts');
    }
} catch (Throwable $e) {
}

$locationOptions = [];
$categoryOptions = [];
foreach ($resorts as $r) {
    if (!empty($r['location'])) {
        $locationOptions[slugify($r['location'])] = $r['location'];
    }
    if (!empty($r['category'])) {
        $categoryOptions[strtolower($r['category'])] = $r['category'];
    }
}
asort($locationOptions);
asort($categoryOptions);

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/resort.jpg" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Resort Stays' => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Stays</p>
        <h1>Resort Stays</h1>
        <p class="page-head__lead">Hill, backwater and coastal properties we know personally — enquire for availability and personalised pricing.</p>
        <div class="page-head__chips">
          <?php if ($resortsOn): ?>
            <?= yn_chip('bed', count($resorts) . ' propert' . (count($resorts) === 1 ? 'y' : 'ies')) ?>
          <?php else: ?>
            <?= yn_chip('chat', 'Enquire for stays') ?>
          <?php endif; ?>
          <?= yn_chip('shield', 'Verified partners') ?>
          <?= yn_chip('tag', 'Rates on enquiry') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container" data-filter-root>
      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">Browse</p>
          <h2>Find your stay</h2>
          <p>Filter by location or property type, then request a quote for your dates.</p>
        </div>
      </div>

      <?php if (!$resortsOn): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('chat') ?></div>
          <h2>Request a stay</h2>
          <p>Resort listings are currently by enquiry. Tell us the destination and dates you have in mind and we'll share options that fit.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request a stay</a>
          </div>
        </div>
      <?php elseif (!$resorts): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('bed') ?></div>
          <h2>Stays are being updated</h2>
          <p>Tell us the destination and dates you have in mind and we'll share options that fit.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request a stay</a>
          </div>
        </div>
      <?php else: ?>
        <form class="filter-bar" data-filter-form>
          <div class="filter-bar__row">
            <div class="form-group">
              <label for="f-location">Location</label>
              <select class="form-control" id="f-location" data-filter="destination">
                <option value="all">All locations</option>
                <?php foreach ($locationOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="f-category">Property type</label>
              <select class="form-control" id="f-category" data-filter="type">
                <option value="all">All types</option>
                <?php foreach ($categoryOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="f-search">Search</label>
              <input class="form-control" id="f-search" type="search" placeholder="Search resorts" data-search />
            </div>
            <div class="filter-actions">
              <button class="btn btn--primary" type="submit">Apply</button>
              <button class="btn btn--secondary" type="button" data-filter-reset>Reset</button>
            </div>
          </div>
        </form>
        <div class="results-meta"><span data-filter-count></span></div>
        <div class="resort-grid" data-filter-list data-per-page="6">
          <?php foreach ($resorts as $r):
            $img = media_src((string) ($r['image'] ?? ''), '../', 'resort.jpg');
            $href = 'resort-details.php?resort=' . rawurlencode($r['slug']);
            ?>
            <article class="card" data-filter-item data-name="<?= e($r['title']) ?>" data-destination="<?= e(slugify($r['location'])) ?>" data-type="<?= e(strtolower((string) $r['category'])) ?>">
              <div class="card__media">
                <img src="<?= e($img) ?>" alt="<?= e($r['title']) ?>" loading="lazy" />
                <?php if (!empty($r['category'])): ?>
                  <span class="card__badge"><?= e($r['category']) ?></span>
                <?php endif; ?>
              </div>
              <div class="card__body">
                <p class="card__meta"><?= e($r['location']) ?></p>
                <h3 class="card__title"><a href="<?= e($href) ?>"><?= e($r['title']) ?></a></h3>
                <p class="card__text"><?= e($r['summary']) ?></p>
                <?php if (!empty($r['amenities'])): ?>
                  <ul class="highlight-list">
                    <?php foreach (array_slice($r['amenities'], 0, 3) as $a): ?><li><?= e($a) ?></li><?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                <div class="card__actions">
                  <a class="btn btn--secondary btn--sm" href="<?= e($href) ?>">View Resort</a>
                  <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($r['title']) ?>">Request Quote</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <div class="empty-state" data-filter-empty hidden>
          <div class="empty-state__icon"><?= yn_icon('search') ?></div>
          <h2>No stays match your filters</h2>
          <p>Try a different location or reset the filters — or ask us to find something that fits.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Ask us to find a stay</a>
          </div>
        </div>
        <div class="pagination" data-pagination></div>
      <?php endif; ?>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Stay planning</p>
        <h2>Not sure which stay suits you?</h2>
        <p>Tell us your destination, dates and budget range — we'll shortlist properties and share rates personally.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Request Availability
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
