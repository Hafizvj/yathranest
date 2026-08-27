<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$slug = get_query('package');
$pkg = null;
try {
    if ($slug !== '') {
        $pkg = package_by_slug($slug);
    }
} catch (Throwable $e) {
    $pkg = null;
}

$pageTitle = $pkg ? ($pkg['title'] . ' — Package Details | YathraNest') : 'Package Details | YathraNest';
$metaDescription = $pkg['overview'] ?? 'Package details — itinerary, inclusions and enquire with YathraNest.';
$bodyAttrs = 'data-package-details="true" data-asset-prefix="../assets/images/"';
$enquiryType = 'general';
$enquiryInterest = $pkg['title'] ?? 'Package enquiry';
$enquirySource = 'pages/package-details.php?package=' . rawurlencode($slug);
$packagesOn = feature_enabled('packages');
if (!$packagesOn) {
    $bodyAttrs .= ' data-auto-enquiry="1"';
}

$related = $pkg ? packages_related($pkg, 3) : [];

$pageKey = ($pkg['pages'][0] ?? 'kerala');
$listHref = $pageKey === 'south' ? 'south-indian-packages.php' : ($pageKey === 'domestic' ? 'domestic-packages.php' : ($pageKey === 'international' ? 'international-packages.php' : 'kerala-packages.php'));
$listLabel = $pageKey === 'south' ? 'South Indian Packages' : ($pageKey === 'domestic' ? 'Domestic Packages' : ($pageKey === 'international' ? 'International Packages' : 'Kerala Packages'));
$eyebrow = $pageKey === 'south' ? 'South Indian Package' : ($pageKey === 'domestic' ? 'Domestic Package' : ($pageKey === 'international' ? 'International Package' : 'Kerala Package'));
$navActive = $pageKey === 'south' ? 'south' : ($pageKey === 'domestic' ? 'domestic' : ($pageKey === 'international' ? 'international' : 'kerala'));

$heroSrc = media_src((string) ($pkg['image'] ?? ''), '../', 'beach.jpg');

$days = (int) ($pkg['days'] ?? 0);
$nights = (int) ($pkg['nights'] ?? 0);
$nightsLabel = $nights . ' Night' . ($nights === 1 ? '' : 's');

$typesLabel = $pkg ? (package_types_label($pkg) ?: 'All travellers') : 'All travellers';
// An uploaded brochure wins over the generated print view.
$hasItineraryPdf = !empty($pkg['itinerary_pdf']);
$itineraryHref = $hasItineraryPdf
    ? media_src((string) $pkg['itinerary_pdf'], '../')
    : 'itinerary-print.php?package=' . rawurlencode((string) ($pkg['slug'] ?? '')) . '&print=1';
$hasPriceSheet = !empty($pkg['price_chart_pdf']);
$priceSheetHref = $hasPriceSheet ? media_src((string) $pkg['price_chart_pdf'], '../') : '';

function pkg_img_src(string $file): string
{
    return media_src($file, '../', 'beach.jpg');
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <div class="crumbs-bar">
    <div class="container">
      <?= yn_crumbs([
          'Home' => '../index.php',
          $listLabel => $listHref,
          ($pkg['title'] ?? 'Package') => null,
      ]) ?>
    </div>
  </div>

  <section class="section section--tight">
    <div class="container">
      <?php if (!$pkg): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('compass') ?></div>
          <h2>Package not found</h2>
          <p>This itinerary isn't in our current catalogue. Browse our collections, or tell us what you're planning and we'll build it.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="kerala-packages.php">Kerala Packages</a>
            <a class="btn btn--secondary" href="south-indian-packages.php">South Indian Packages</a>
          </div>
        </div>
      <?php elseif (!$packagesOn): ?>
        <div class="empty-state">
          <div class="empty-state__icon"><?= yn_icon('chat') ?></div>
          <h2><?= e($pkg['title']) ?></h2>
          <p>Full itinerary details are shared on enquiry. Tell us your dates and we'll send the plan and pricing personally.</p>
          <div class="btn-group" style="justify-content:center">
            <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
            <a class="btn btn--secondary" href="<?= e($listHref) ?>"><?= e($listLabel) ?></a>
          </div>
        </div>
      <?php else: ?>
        <div class="detail-head">
          <div class="detail-head__media">
            <img src="<?= e($heroSrc) ?>" alt="<?= e($pkg['title']) ?>" width="1200" height="750" />
            <span class="detail-head__tag"><?= e($eyebrow) ?></span>
          </div>
          <div>
            <h1><?= e($pkg['title']) ?></h1>
            <p class="detail-head__lead"><?= e(yn_lead($pkg['overview'])) ?></p>
            <div class="spec-grid">
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('clock') ?></span>
                <span>
                  <span class="spec__label">Duration</span>
                  <span class="spec__value"><?= $days ?>D / <?= (int) $nights ?>N</span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('pin') ?></span>
                <span>
                  <span class="spec__label">Destinations</span>
                  <span class="spec__value"><?= e($pkg['dest_line']) ?></span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('car') ?></span>
                <span>
                  <span class="spec__label">Pickup &amp; drop</span>
                  <span class="spec__value"><?= e($pkg['pickup']) ?></span>
                </span>
              </div>
              <div class="spec">
                <span class="spec__icon"><?= yn_icon('users') ?></span>
                <span>
                  <span class="spec__label">Best for</span>
                  <span class="spec__value"><?= e($typesLabel) ?></span>
                </span>
              </div>
            </div>
            <div class="btn-group">
              <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
              <a class="btn btn--secondary" href="<?= e($itineraryHref) ?>" target="_blank" rel="noopener">Download Itinerary</a>
              <?php if ($hasPriceSheet): ?>
                <a class="btn btn--secondary" href="<?= e($priceSheetHref) ?>" target="_blank" rel="noopener">View Price Sheet</a>
              <?php else: ?>
                <a class="btn btn--secondary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title'] . ' — Price sheet') ?>">View Price Sheet</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="detail-layout">
          <div class="detail-body">
            <div class="content-block">
              <h2>Overview</h2>
              <p class="text-muted"><?= e($pkg['overview']) ?></p>
            </div>

            <div class="content-block">
              <h2>Trip highlights</h2>
              <div class="amenity-grid">
                <?php foreach ($pkg['highlights'] as $h): ?>
                  <span class="amenity"><?= yn_icon('sparkle') ?><?= e($h) ?></span>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="content-block">
              <h2>Detailed itinerary</h2>
              <div class="timeline">
                <?php foreach ($pkg['itinerary'] as $i => $day): ?>
                  <div class="timeline__item" data-reveal>
                    <span class="timeline__day" aria-hidden="true">D<?= (int) ($day['day'] ?? ($i + 1)) ?></span>
                    <div class="timeline__card">
                      <h3 class="timeline__title">
                        <span class="sr-only">Day <?= (int) ($day['day'] ?? ($i + 1)) ?> — </span><?= e($day['title'] ?? '') ?>
                      </h3>
                      <p class="timeline__text"><?= e($day['text'] ?? '') ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="content-block">
              <h2>What's included</h2>
              <div class="incl-grid">
                <div class="incl-card incl-card--yes">
                  <h3><?= yn_icon('check') ?>Inclusions</h3>
                  <ul class="check-list">
                    <li>Accommodation as per itinerary (<?= e($pkg['stay_summary']) ?>)</li>
                    <li><?= !empty($pkg['has_houseboat']) ? 'Daily breakfast and houseboat meals as applicable' : 'Daily breakfast' ?></li>
                    <li>Private transfers for sightseeing segments</li>
                    <li>Assistance at pickup and drop</li>
                  </ul>
                </div>
                <div class="incl-card incl-card--no">
                  <h3><?= yn_icon('info') ?>Exclusions</h3>
                  <ul class="check-list check-list--exclude">
                    <li>Flights / trains</li>
                    <li>Personal expenses &amp; optional activities</li>
                    <li>Entry fees not mentioned</li>
                    <li>Anything not listed in inclusions</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="content-block">
              <h2>Stay &amp; travel</h2>
              <div class="tile-grid">
                <div class="tile">
                  <span class="tile__icon"><?= yn_icon('bed') ?></span>
                  <h3>Accommodation</h3>
                  <p><?= e($pkg['accommodation']) ?></p>
                </div>
                <div class="tile">
                  <span class="tile__icon"><?= yn_icon('car') ?></span>
                  <h3>Transportation</h3>
                  <p>Private air-conditioned cab for transfers and sightseeing as outlined. Vehicle category can be adjusted based on group size when you enquire.</p>
                </div>
              </div>
            </div>

            <?php if (!empty($pkg['gallery'])): ?>
            <div class="content-block">
              <h2>Gallery</h2>
              <div class="gallery-grid" data-gallery>
                <?php foreach ($pkg['gallery'] as $file):
                  $file = trim((string) $file);
                  if ($file === '') {
                      continue;
                  }
                  $src = pkg_img_src($file);
                  ?>
                  <button type="button" data-gallery-item data-full="<?= e($src) ?>">
                    <img src="<?= e($src) ?>" alt="<?= e($pkg['title']) ?>" loading="lazy" />
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <div class="content-block">
              <h2>Good to know</h2>
              <div class="accordion" data-accordion="single">
                <div class="accordion__item">
                  <button class="accordion__trigger" type="button" aria-expanded="false"><span>Do you show package prices online?</span><span class="accordion__icon" aria-hidden="true">+</span></button>
                  <div class="accordion__panel"><p>No. YathraNest shares pricing after understanding your dates, group size and preferences.</p></div>
                </div>
                <div class="accordion__item">
                  <button class="accordion__trigger" type="button" aria-expanded="false"><span>Can this itinerary be customised?</span><span class="accordion__icon" aria-hidden="true">+</span></button>
                  <div class="accordion__panel"><p>Yes. We can adjust nights, add destinations or change stay categories based on your enquiry.</p></div>
                </div>
                <div class="accordion__item">
                  <button class="accordion__trigger" type="button" aria-expanded="false"><span>Can we add a houseboat or extra night?</span><span class="accordion__icon" aria-hidden="true">+</span></button>
                  <div class="accordion__panel"><p>Yes. Houseboat nights, extra stays and activity add-ons can be discussed when you enquire.</p></div>
                </div>
              </div>
            </div>
          </div>

          <aside class="detail-aside">
            <div class="quote-card">
              <h3><?= e($pkg['title']) ?></h3>
              <div class="btn-group">
                <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
                <a class="btn btn--secondary quote-card__ghost" href="<?= e($itineraryHref) ?>" target="_blank" rel="noopener">Download Itinerary</a>
                <?php if ($hasPriceSheet): ?>
                  <a class="btn btn--secondary quote-card__ghost" href="<?= e($priceSheetHref) ?>" target="_blank" rel="noopener">View Price Sheet</a>
                <?php else: ?>
                  <a class="btn btn--secondary quote-card__ghost" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title'] . ' — Price sheet') ?>">View Price Sheet</a>
                <?php endif; ?>
              </div>
              <p class="quote-card__note"><?= yn_icon('shield') ?>Trusted partners</p>
            </div>
            <div class="panel">
              <div class="info-list">
                <div class="info-row">
                  <span class="info-row__icon"><?= yn_icon('clock') ?></span>
                  <span>
                    <span class="info-row__label">Duration</span>
                    <span class="info-row__value"><?= $days ?> Days / <?= e($nightsLabel) ?></span>
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-row__icon"><?= yn_icon('bed') ?></span>
                  <span>
                    <span class="info-row__label">Stay</span>
                    <span class="info-row__value"><?= e($pkg['stay_summary']) ?></span>
                  </span>
                </div>
                <div class="info-row">
                  <span class="info-row__icon"><?= yn_icon('users') ?></span>
                  <span>
                    <span class="info-row__label">Best for</span>
                    <span class="info-row__value"><?= e($typesLabel) ?></span>
                  </span>
                </div>
              </div>
            </div>
          </aside>
        </div>

        <?php if ($related): ?>
        <div class="section-head" style="margin-top:3.5rem">
          <div>
            <p class="section-head__eyebrow">You may also like</p>
            <h2>Related packages</h2>
            <p>More itineraries in the same region — enquire for personalised pricing.</p>
          </div>
          <a class="btn btn--secondary btn--sm" href="<?= e($listHref) ?>">View all</a>
        </div>
        <div class="package-grid">
          <?php foreach ($related as $p):
            $href = 'package-details.php?package=' . rawurlencode($p['slug']);
            $img = pkg_img_src((string) ($p['image'] ?? ''));
            $rd = (int) $p['days'];
            $rn = (int) $p['nights'];
            $rnLabel = $rn . ' Night' . ($rn === 1 ? '' : 's');
            ?>
            <article class="card">
              <div class="card__media">
                <img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" />
                <span class="card__badge"><?= $rd ?>D / <?= $rn ?>N</span>
              </div>
              <div class="card__body">
                <p class="card__meta"><?= e($p['dest_line']) ?></p>
                <h3 class="card__title"><a href="<?= e($href) ?>"><?= e($p['title']) ?></a></h3>
                <p class="card__text"><?= e($rd . ' Days / ' . $rnLabel . ' · ' . $p['dest_line']) ?></p>
                <a class="link-arrow" href="<?= e($href) ?>">View Package</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
require dirname(__DIR__) . '/includes/layout-footer.php';
