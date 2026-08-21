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
$pageLead = 'Curated journeys through backwaters, tea hills, wildlife sanctuaries and coastal towns — enquire for your dates.';
$heroImage = 'kerala-backwaters.jpg';
$pageTitle = 'Kerala Packages | YathraNest';
$metaDescription = $pageLead;
$bodyAttrs = 'data-package-page="kerala" data-asset-prefix="../assets/images/" data-details-prefix="package-details.php"';
$enquiryType = 'general';
$enquiryInterest = 'Kerala Packages';
$enquirySource = 'pages/kerala-packages.php' . ($location !== '' ? '?location=' . rawurlencode($location) : '');
$navActive = 'kerala';

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
            $pageLead = 'Packages that include ' . $activePlace['label'] . ' — enquire for your dates.';
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
$typeOptions = [];
$typeLabels = package_type_options();
$pickupLabels = ['calicut' => 'Calicut', 'kochi' => 'Kochi', 'coimbatore' => 'Coimbatore', 'mysore' => 'Mysore', 'trivandrum' => 'Trivandrum'];
foreach ($packages as $pkg) {
    foreach ($pkg['destinations'] as $d) {
        $destOptions[$d] = $places[$d]['label'] ?? $d;
    }
    foreach ($pkg['types'] ?? [] as $t) {
        $t = strtolower((string) $t);
        if ($t !== '') {
            $typeOptions[$t] = $typeLabels[$t] ?? ucwords(str_replace('-', ' ', $t));
        }
    }
    if (!empty($pkg['pickup_slug'])) {
        $pickupOptions[$pkg['pickup_slug']] = $pickupLabels[$pkg['pickup_slug']] ?? $pkg['pickup_slug'];
    }
}
ksort($destOptions);
ksort($pickupOptions);
ksort($typeOptions);

$crumbTrail = ['Home' => '../index.php'];
if ($location !== '') {
    $crumbTrail['Kerala Packages'] = 'kerala-packages.php';
    $crumbTrail[$activePlace['label'] ?? ucwords($location)] = null;
} else {
    $crumbTrail['Kerala Packages'] = null;
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head page-head--media">
    <div class="page-head__media" aria-hidden="true">
      <img src="<?= e(media_src($heroImage, '../', 'kerala-backwaters.jpg')) ?>" alt="" width="1600" height="900" />
    </div>
    <div class="container page-head__inner">
      <?= yn_crumbs($crumbTrail, true) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow"><?= $location !== '' ? 'Kerala · ' . e($activePlace['label'] ?? ucwords($location)) : 'God’s own country' ?></p>
        <h1><?= e($pageHeading) ?></h1>
        <p class="page-head__lead"><?= e($pageLead) ?></p>
        <div class="page-head__chips">
          <?php if ($location === ''): ?>
            <?= yn_chip('pin', count($destinations) . ' destinations') ?>
          <?php else: ?>
            <?= yn_chip('compass', count($packages) . ' itinerar' . (count($packages) === 1 ? 'y' : 'ies')) ?>
          <?php endif; ?>
          <?= yn_chip('leaf', 'Backwaters & hills') ?>
          <?= yn_chip('tag', 'Pricing on enquiry') ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <?= yn_package_subnav('kerala') ?>

      <?php if (!$dbOk): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('info') ?></div>
          <h2>Catalog temporarily unavailable</h2>
          <p>Please try again shortly, or send us an enquiry and we'll share options directly.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
          </div>
        </div>

      <?php elseif ($location === ''): ?>
        <div class="section-head">
          <div>
            <p class="section-head__eyebrow">Step 1</p>
            <h2>Choose a location</h2>
            <p>Pick a destination to see the Kerala packages that include that place.</p>
          </div>
        </div>
        <?php if (!$destinations): ?>
          <div class="empty-state">
            <div class="empty-state__icon"><?= yn_icon('pin') ?></div>
            <h2>No Kerala locations yet</h2>
            <p>Our catalogue is being updated. Tell us where you'd like to go and we'll plan it.</p>
            <div class="btn-group" style="justify-content:center">
              <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
            </div>
          </div>
        <?php else: ?>
          <div class="destinations-grid">
            <?php foreach ($destinations as $dest):
              $img = media_src((string) $dest['image'], '../', 'beach.jpg');
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
        <div data-filter-root>
          <div class="section-head">
            <div>
              <p class="section-head__eyebrow">Step 2</p>
              <h2><?= e($activePlace['label'] ?? 'Packages') ?></h2>
              <p>Showing packages that include this location.</p>
            </div>
            <a class="btn btn--secondary btn--sm" href="kerala-packages.php">All Kerala locations</a>
          </div>

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
                  <?php foreach ($typeOptions as $val => $label): ?>
                    <option value="<?= e($val) ?>"><?= e($label) ?></option>
                  <?php endforeach; ?>
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
            <div class="empty-state__icon"><?= yn_icon('search') ?></div>
            <h2>Nothing here yet</h2>
            <p>No packages for this location match your filters. <a href="kerala-packages.php">Browse other locations</a> or enquire for a custom plan.</p>
            <div class="btn-group" style="justify-content:center">
              <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Request a custom plan</a>
            </div>
          </div>
          <div class="pagination" data-pagination></div>
        </div>
      <?php endif; ?>

      <div class="cta-band" style="margin-top:3.5rem">
        <p class="cta-band__eyebrow">Tailor-made</p>
        <h2>Looking for a custom Kerala itinerary?</h2>
        <p>Share your dates and preferences — we'll craft a plan and send pricing personally.</p>
        <div class="btn-group">
          <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
            Enquire Now
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
