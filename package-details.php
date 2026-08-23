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
$metaDescription = $pkg['overview'] ?? 'Package details — itinerary, inclusions and Enquire Now with YathraNest.';
$bodyAttrs = 'data-package-details="true" data-asset-prefix="../assets/images/"';
$enquiryType = 'general';
$enquiryInterest = $pkg['title'] ?? 'Package enquiry';
$enquirySource = 'pages/package-details.php?package=' . rawurlencode($slug);

$related = $pkg ? packages_related($pkg, 3) : [];

$pageKey = ($pkg['pages'][0] ?? 'kerala');
$listHref = $pageKey === 'south' ? 'south-indian-packages.php' : ($pageKey === 'domestic' ? 'domestic-packages.php' : ($pageKey === 'international' ? 'international-packages.php' : 'kerala-packages.php'));
$listLabel = $pageKey === 'south' ? 'South Indian Packages' : ($pageKey === 'domestic' ? 'Domestic Packages' : ($pageKey === 'international' ? 'International Packages' : 'Kerala Packages'));
$eyebrow = ($pkg['sheet'] ?? '') === 'TN & KA PLANS' ? 'South Indian Package' : (($pkg['sheet'] ?? '') === 'Domestic' ? 'Domestic Package' : (($pkg['sheet'] ?? '') === 'International' ? 'International Package' : 'Kerala Package'));

$heroSrc = '../assets/images/' . ltrim($pkg['image'] ?? 'beach.jpg', '/');
if ($pkg && strpos($pkg['image'] ?? '', 'uploads/') === 0) {
    $heroSrc = '../' . $pkg['image'];
}

$days = (int) ($pkg['days'] ?? 0);
$nights = (int) ($pkg['nights'] ?? 0);
$nightsLabel = $nights . ' Night' . ($nights === 1 ? '' : 's');
$whatsapp = preg_replace('/\D/', '', setting('whatsapp', '919876543210'));

function pkg_img_src(string $file): string
{
    if ($file === '') {
        return '../assets/images/beach.jpg';
    }
    if (strpos($file, 'uploads/') === 0) {
        return '../' . $file;
    }
    return '../assets/images/' . ltrim($file, '/');
}

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <div class="container">
      <ol>
        <li><a href="../index.php">Home</a></li>
        <li><a href="<?= e($listHref) ?>"><?= e($listLabel) ?></a></li>
        <li><span aria-current="page"><?= e($pkg['title'] ?? 'Package') ?></span></li>
      </ol>
    </div>
  </nav>

  <section class="section section--tight">
    <div class="container">
      <?php if (!$pkg): ?>
        <div class="empty-state">
          <h2>Package not found</h2>
          <p>This itinerary is not in our current catalog. Browse Kerala or South Indian packages, or enquire for a custom plan.</p>
          <div class="btn-group">
            <a class="btn btn--primary" href="kerala-packages.php">Kerala Packages</a>
            <a class="btn btn--secondary" href="south-indian-packages.php">South Indian Packages</a>
          </div>
        </div>
      <?php else: ?>
        <div class="detail-hero">
          <div class="detail-hero__media">
            <img src="<?= e($heroSrc) ?>" alt="<?= e($pkg['title']) ?>" width="1200" height="750" />
          </div>
          <div class="detail-hero__content">
            <p class="section-eyebrow"><?= e($eyebrow) ?></p>
            <h1><?= e($pkg['title']) ?></h1>
            <p class="meta-row">
              <span><strong>Destination:</strong> <?= e($pkg['dest_line']) ?></span>
              <span><strong>Duration:</strong> <?= $days ?> Days / <?= e($nightsLabel) ?></span>
            </p>
            <p class="meta-row"><span>Pickup <?= e($pkg['pickup']) ?> · Drop <?= e($pkg['drop_point']) ?></span></p>
            <p class="text-muted"><?= e($pkg['overview']) ?></p>
            <div class="btn-group">
              <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
              <a class="btn btn--secondary" href="contact.php">Enquire Now</a>
            </div>
          </div>
        </div>

        <div class="detail-layout">
          <div>
            <div class="content-block">
              <h2>Overview</h2>
              <p><?= e($pkg['overview']) ?></p>
            </div>

            <div class="content-block">
              <h2>Trip Highlights</h2>
              <ul class="check-list">
                <?php foreach ($pkg['highlights'] as $h): ?>
                  <li><?= e($h) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>

            <div class="content-block">
              <h2>Detailed Itinerary</h2>
              <div class="accordion" data-accordion="single">
                <?php foreach ($pkg['itinerary'] as $i => $day): ?>
                  <div class="accordion__item<?= $i === 0 ? ' is-open' : '' ?>">
                    <button class="accordion__trigger" type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
                      <span>Day <?= (int) ($day['day'] ?? ($i + 1)) ?> — <?= e($day['title'] ?? '') ?></span>
                      <span class="accordion__icon" aria-hidden="true">+</span>
                    </button>
                    <div class="accordion__panel"><p><?= e($day['text'] ?? '') ?></p></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="grid-2 content-block">
              <div>
                <h2>Inclusions</h2>
                <ul class="check-list">
                  <li>Accommodation as per itinerary (<?= e($pkg['stay_summary']) ?>)</li>
                  <li><?= !empty($pkg['has_houseboat']) ? 'Daily breakfast and houseboat meals as applicable' : 'Daily breakfast' ?></li>
                  <li>Private transfers for sightseeing segments</li>
                  <li>Assistance at pickup and drop</li>
                </ul>
              </div>
              <div>
                <h2>Exclusions</h2>
                <ul class="check-list check-list--exclude">
                  <li>Flights / trains</li>
                  <li>Personal expenses &amp; optional activities</li>
                  <li>Entry fees not mentioned</li>
                  <li>Anything not listed in inclusions</li>
                </ul>
              </div>
            </div>

            <div class="content-block">
              <h2>Accommodation</h2>
              <p><?= e($pkg['accommodation']) ?></p>
            </div>

            <div class="content-block">
              <h2>Transportation</h2>
              <p>Private air-conditioned cab for transfers and sightseeing as outlined. Vehicle category can be adjusted based on group size when you enquire.</p>
            </div>

            <?php if (!empty($pkg['gallery'])): ?>
            <div class="content-block">
              <h2>Gallery</h2>
              <div class="gallery-grid" data-gallery>
                <?php foreach ($pkg['gallery'] as $file):
                  if (!media_exists((string) $file)) {
                      continue;
                  }
                  $src = pkg_img_src((string) $file);
                  ?>
                  <button type="button" data-gallery-item data-full="<?= e($src) ?>">
                    <img src="<?= e($src) ?>" alt="<?= e($pkg['title']) ?>" />
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

            <div class="content-block">
              <h2>FAQs</h2>
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
                  <div class="accordion__panel"><p>Yes. Houseboat nights, extra stays and activity add-ons can be discussed when you Enquire Now.</p></div>
                </div>
              </div>
            </div>
          </div>

          <aside class="detail-aside">
            <div class="aside-card">
              <h3>Enquire Now</h3>
              <p>Share your preferred dates and traveller count. Our team will respond with availability and a personalised quote.</p>
              <a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
              <a class="btn btn--secondary btn--block mt-1" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer">WhatsApp Us</a>
            </div>
          </aside>
        </div>

        <?php if ($related): ?>
        <div class="section-header" style="margin-top:3rem">
          <h2>Related packages</h2>
          <p class="section-lead">More itineraries you might like — enquire for personalised pricing.</p>
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
              <div class="card__media"><img src="<?= e($img) ?>" alt="<?= e($p['title']) ?>" loading="lazy" /></div>
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
