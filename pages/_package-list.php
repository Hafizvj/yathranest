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

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <div class="container">
      <ol>
        <li><a href="../index.php">Home</a></li>
        <li><span aria-current="page"><?= e($pageHeading) ?></span></li>
      </ol>
    </div>
  </nav>
  <section class="page-hero page-hero--media" style="background-image:url('../assets/images/<?= e($heroImage) ?>')">
    <div class="container page-hero__inner">
      <h1><?= e($pageHeading) ?></h1>
      <p><?= e($pageLead) ?></p>
    </div>
  </section>
  <section class="section">
    <div class="container" data-filter-root>
      <div class="section-header">
        <h2><?= e($sectionTitle ?? 'Explore') ?></h2>
        <p class="section-lead"><?= e($sectionLead ?? 'Filter and enquire — pricing is shared after you enquire.') ?></p>
      </div>
      <?php if (!$dbOk): ?>
        <div class="empty-state"><p>Catalog temporarily unavailable. Please try again later or contact us.</p></div>
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
      <div class="empty-state" data-filter-empty hidden>No packages match your filters. Try resetting or enquire for a custom plan.</div>
      <div class="pagination" data-pagination></div>
      <?php endif; ?>
      <div class="cta-band mt-3" style="margin-top:3rem">
        <h2>Looking for a custom itinerary?</h2>
        <p>Share your dates and preferences — we'll craft a plan and send pricing.</p>
        <div class="btn-group"><a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a></div>
      </div>
    </div>
  </section>
</main>
<?php
require dirname(__DIR__) . '/includes/layout-footer.php';
