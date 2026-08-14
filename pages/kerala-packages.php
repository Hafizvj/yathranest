<?php
/**
 * Kerala packages:
 * - No ?location= → show location cards (Wayanad, Munnar, …)
 * - ?location=wayanad → packages that include that destination
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$location = strtolower(trim(get_query('location')));
$packagePage = 'kerala';

$pageHeading = 'Kerala Packages';
$pageLead = 'Curated journeys through backwaters, tea hills, wildlife sanctuaries and coastal towns — request pricing for your dates.';
$heroImage = 'kerala-backwaters.jpg';
$pageTitle = 'Kerala Packages | YathraNest';
$metaDescription = $pageLead;
$bodyAttrs = 'data-package-page="kerala" data-asset-prefix="../assets/images/" data-details-prefix="package-details.php"';
$enquiryType = 'general';
$enquiryInterest = 'Kerala Packages';
$enquirySource = 'pages/kerala-packages.php' . ($location !== '' ? '?location=' . rawurlencode($location) : '');

$places = [];
$destinations = [];
$packages = [];
$activePlace = null;
$dbOk = true;

try {
    $places = places_all();
    $destinations = destinations_for_page('kerala');
    if ($location !== '') {
        $packages = packages_for_destination('kerala', $location);
        $activePlace = place_by_slug($location);
        if ($activePlace) {
            $pageHeading = $activePlace['label'] . ' Packages';
            $pageLead = 'Packages that include ' . $activePlace['label'] . ' — request pricing for your dates.';
            $pageTitle = $activePlace['label'] . ' Packages | YathraNest';
            $enquiryInterest = $activePlace['label'] . ' Packages';
            $imgs = $activePlace['images'] ?? [];
            if (!empty($imgs[0])) {
                $heroImage = $imgs[0];
            }
        } elseif (!$packages) {
            $location = '';
        }
    }
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
        <?php if ($location !== ''): ?>
          <li><a href="kerala-packages.php">Kerala Packages</a></li>
          <li><span aria-current="page"><?= e($activePlace['label'] ?? ucwords($location)) ?></span></li>
        <?php else: ?>
          <li><span aria-current="page">Kerala Packages</span></li>
        <?php endif; ?>
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
    <div class="container">
      <?php if (!$dbOk): ?>
        <div class="empty-state"><p>Catalog temporarily unavailable. Please try again later or contact us.</p></div>

      <?php elseif ($location === ''): ?>
        <div class="section-header">
          <h2>Choose a location</h2>
          <p class="section-lead">Pick a destination to see Kerala packages that include that place.</p>
        </div>
        <?php if (!$destinations): ?>
          <div class="empty-state"><p>No Kerala locations found yet.</p></div>
        <?php else: ?>
          <div class="destinations-grid">
            <?php foreach ($destinations as $dest):
              $img = '../assets/images/' . ltrim($dest['image'], '/');
              $href = 'kerala-packages.php?location=' . rawurlencode($dest['slug']);
              $countLabel = $dest['count'] . ' package' . ($dest['count'] === 1 ? '' : 's');
              ?>
              <a class="destination-card" href="<?= e($href) ?>">
                <img src="<?= e($img) ?>" alt="<?= e($dest['label']) ?>" loading="lazy" />
                <div class="destination-card__overlay">
                  <h3><?= e($dest['label']) ?></h3>
                  <span><?= e($countLabel) ?></span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <div class="section-header">
          <h2><?= e($activePlace['label'] ?? 'Packages') ?></h2>
          <p class="section-lead">
            Showing packages that include this location.
            <a href="kerala-packages.php">← All Kerala locations</a>
          </p>
        </div>

        <div data-filter-root>
          <form class="filter-bar" data-filter-form>
            <div class="filter-bar__row">
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
            <?php foreach ($packages as $pkg) {
                echo package_card_html($pkg, '../assets/images/');
            } ?>
          </div>
          <div class="empty-state" data-filter-empty <?= $packages ? 'hidden' : '' ?>>
            No packages for this location yet. <a href="kerala-packages.php">Browse other locations</a> or enquire for a custom plan.
          </div>
          <div class="pagination" data-pagination></div>
        </div>
      <?php endif; ?>

      <div class="cta-band mt-3" style="margin-top:3rem">
        <h2>Looking for a custom Kerala itinerary?</h2>
        <p>Share your dates and preferences — we'll craft a plan and send pricing.</p>
        <div class="btn-group"><a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a></div>
      </div>
    </div>
  </section>
</main>
<?php
require dirname(__DIR__) . '/includes/layout-footer.php';
