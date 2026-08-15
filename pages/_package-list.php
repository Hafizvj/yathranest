<?php
/**
 * Shared package listing page.
 * Set before include: $packagePage, $pageHeading, $pageLead, $heroImage, $pageTitle, $metaDescription
 * Optional: $extraCardsHtml (for domestic hardcoded cards after DB cards)
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$packagePage = $packagePage ?? 'kerala';
$pageHeading = $pageHeading ?? 'Packages';
$pageLead = $pageLead ?? '';
$heroImage = $heroImage ?? 'kerala-backwaters.jpg';
$pageTitle = $pageTitle ?? ($pageHeading . ' | YathraNest');
$metaDescription = $metaDescription ?? $pageLead;
$bodyAttrs = 'data-package-page="' . e($packagePage) . '" data-asset-prefix="../assets/images/" data-details-prefix="package-details.php"';
$enquiryType = 'general';
$enquiryInterest = $pageHeading;
$enquirySource = 'pages/' . basename($_SERVER['SCRIPT_NAME'] ?? '');
$navActive = $packagePage;

$packages = [];
$places = [];
$dbOk = true;
try {
    $packages = packages_for_page($packagePage);
    $places = places_all();
} catch (Throwable $e) {
    $dbOk = false;
}

$destOptions = [];
$pickupOptions = [];
$pickupLabels = ['calicut' => 'Calicut', 'kochi' => 'Kochi', 'coimbatore' => 'Coimbatore', 'mysore' => 'Mysore', 'trivandrum' => 'Trivandrum'];
foreach ($packages as $pkg) {
    foreach ($pkg['destinations'] as $d) {
        $destOptions[$d] = $places[$d]['label'] ?? $d;
    }
    if (!empty($pkg['pickup_slug'])) {
        $pickupOptions[$pkg['pickup_slug']] = $pickupLabels[$pkg['pickup_slug']] ?? $pkg['pickup_slug'];
    }
}
ksort($destOptions);
ksort($pickupOptions);

$packageCount = count($packages);
$shortestTrip = null;
foreach ($packages as $pkg) {
    $d = (int) ($pkg['days'] ?? 0);
    if ($d > 0 && ($shortestTrip === null || $d < $shortestTrip)) {
        $shortestTrip = $d;
    }
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="../assets/images/<?= e($heroImage) ?>" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', $pageHeading => null], true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Tour packages</p>
        <h1><?= e($pageHeading) ?></h1>
        <p class="page-head__lead"><?= e($pageLead) ?></p>
        <div class="page-head__chips">
          <?php if ($packageCount): ?>
            <?= yn_chip('compass', $packageCount . ' itinerar' . ($packageCount === 1 ? 'y' : 'ies')) ?>
          <?php endif; ?>
          <?php if ($shortestTrip): ?>
            <?= yn_chip('clock', 'From ' . $shortestTrip . ' days') ?>
          <?php endif; ?>
          <?= yn_chip('tag', 'Pricing on enquiry') ?>
          <?= yn_chip('headset', 'Support 24/7') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container" data-filter-root>
      <?= yn_package_subnav($packagePage) ?>

      <div class="section-head">
        <div>
          <p class="section-head__eyebrow">Browse</p>
          <h2><?= e($sectionTitle ?? 'Explore') ?></h2>
          <p><?= e($sectionLead ?? 'Filter and enquire — pricing is shared after you enquire.') ?></p>
        </div>
      </div>

      <?php if (!$dbOk): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('info') ?></div>
          <h2>Catalog temporarily unavailable</h2>
          <p>Please try again shortly, or send us an enquiry and we'll share options directly.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
            <a class="btn btn--secondary" href="contact.php">Contact Us</a>
          </div>
        </div>
      <?php else: ?>
        <form class="filter-bar" data-filter-form>
          <div class="filter-bar__row">
            <div class="form-group">
              <label for="f-dest">Destination</label>
              <select class="form-control" id="f-dest" data-filter="destination">
                <option value="all">All destinations</option>
                <?php foreach ($destOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="f-pickup">Pickup</label>
              <select class="form-control" id="f-pickup" data-filter="pickup">
                <option value="all">Any pickup</option>
                <?php foreach ($pickupOptions as $val => $label): ?>
                  <option value="<?= e($val) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="f-duration">Duration</label>
              <select class="form-control" id="f-duration" data-filter="duration">
                <option value="all">Any duration</option>
                <option value="2-4">2–4 days</option>
                <option value="5-7">5–7 days</option>
                <option value="8-10">8–10 days</option>
              </select>
            </div>
            <div class="form-group">
              <label for="f-type">Travel type</label>
              <select class="form-control" id="f-type" data-filter="type">
                <option value="all">All types</option>
                <option value="family">Family</option>
                <option value="couple">Couple</option>
                <option value="adventure">Adventure</option>
                <option value="leisure">Leisure</option>
                <option value="heritage">Heritage</option>
              </select>
            </div>
            <div class="form-group">
              <label for="f-search">Search</label>
              <input class="form-control" id="f-search" type="search" placeholder="Search packages" data-search />
            </div>
            <div class="filter-actions">
              <button class="btn btn--primary" type="submit">Apply</button>
              <button class="btn btn--secondary" type="button" data-filter-reset>Reset</button>
            </div>
          </div>
        </form>
        <div class="results-meta"><span data-filter-count></span></div>
        <div class="package-grid" data-filter-list data-per-page="9">
          <?php
          if ($packagePage === 'domestic' && !empty($extraCardsHtml)) {
              echo $extraCardsHtml;
          }
          foreach ($packages as $pkg) {
              echo package_card_html($pkg, '../assets/images/');
          }
          ?>
        </div>
        <div class="empty-state" data-filter-empty hidden>
          <div class="empty-state__icon"><?= yn_icon('search') ?></div>
          <h2>No packages match your filters</h2>
          <p>Try resetting the filters, or tell us what you have in mind and we'll build a custom plan.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request a custom plan</a>
          </div>
        </div>
        <div class="pagination" data-pagination></div>
      <?php endif; ?>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Tailor-made</p>
        <h2>Looking for a custom itinerary?</h2>
        <p>Share your dates, group size and preferences — we'll craft a plan and send pricing personally.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Request Pricing
            <span class="btn__icon" aria-hidden="true">→</span>
          </a>
          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
</main>
<?php
require dirname(__DIR__) . '/includes/layout-footer.php';
