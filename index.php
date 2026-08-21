<?php
require_once __DIR__ . '/includes/bootstrap.php';
$assetDepth = '';
$headerHero = true;
$pageTitle = 'YathraNest — Your Journey Starts Here';
$metaDescription = 'YathraNest — curated travel packages, taxi services, resort stays, weekend getaways, gift cards and investment plans. Browse, explore and enquire for personalised pricing.';
$enquiryType = 'general';
$enquiryInterest = 'General travel enquiry';
$enquirySource = 'index.php';
$phone = '+91 98765 43210';
$email = 'hello@yathranest.com';
$whatsapp = '919876543210';
$home = null;
$sections = [];
$featured = [];
try {
    $phone = setting('phone', $phone);
    $email = setting('email', $email);
    $whatsapp = preg_replace('/\D/', '', setting('whatsapp', $whatsapp));
    $home = page_content('home');
    $sections = $home['sections'] ?? [];
    foreach (packages_for_page('') as $pkg) {
        if (!empty($pkg['is_featured'])) {
            $featured[] = $pkg;
        }
    }
    $featured = array_slice($featured, 0, 3);
} catch (Throwable $e) {
    // Site can still render without DB for static assets
}

$homeHeroVisual = (string) ($sections['hero_visual'] ?? $sections['hero_image'] ?? 'maldives.jpg');
if ($homeHeroVisual === '') {
    $homeHeroVisual = 'maldives.jpg';
}
$homeHeroVisualSrc = media_src($homeHeroVisual, '', 'maldives.jpg');

$homeHeroCardDefaults = [
    [
        'title' => 'Kerala Tour Package',
        'desc' => 'Experience God’s Own Country.',
        'href' => 'pages/kerala-packages.php',
        'image' => 'hills-mist.jpg',
        'icon' => yn_icon('leaf'),
    ],
    [
        'title' => 'South Indian Packages',
        'desc' => 'Temples, hills and coastal escapes.',
        'href' => 'pages/south-indian-packages.php',
        'image' => 'temple.jpg',
        'icon' => yn_icon('buildings'),
    ],
    [
        'title' => 'Domestic Packages',
        'desc' => 'Incredible places across India.',
        'href' => 'pages/domestic-packages.php',
        'image' => 'lake.jpg',
        'icon' => yn_icon('map'),
    ],
    [
        'title' => 'International Package',
        'desc' => 'Explore the world beyond borders.',
        'href' => 'pages/international-packages.php',
        'image' => 'dubai.jpg',
        'icon' => yn_icon('globe'),
    ],
    [
        'title' => 'Taxi Cab Booking',
        'desc' => 'Reliable rides, anytime, anywhere.',
        'href' => 'pages/taxi-booking.php',
        'image' => 'car-taxi.jpg',
        'icon' => yn_icon('car'),
    ],
    [
        'title' => 'Resort Stay Booking',
        'desc' => 'Stay. Relax. Rejuvenate.',
        'href' => 'pages/resort-booking.php',
        'image' => 'resort-pool.jpg',
        'icon' => yn_icon('bed'),
    ],
    [
        'title' => 'Weekend Getaways',
        'desc' => 'Travel. Connect. Create memories.',
        'href' => 'pages/weekend-getaways.php',
        'image' => 'friends-travel.jpg',
        'icon' => yn_icon('users'),
    ],
    [
        'title' => 'Gift Card',
        'desc' => 'Give the gift of travel.',
        'href' => 'pages/gift-cards.php',
        'image' => 'gift.jpg',
        'icon' => yn_icon('gift'),
    ],
    [
        'title' => 'Investment Plans',
        'desc' => 'Grow with YathraNest options.',
        'href' => 'pages/investment-plans.php',
        'image' => 'city.jpg',
        'icon' => yn_icon('chart'),
    ],
];
$homeHeroCards = $homeHeroCardDefaults;
$savedCards = $sections['hero_cards'] ?? [];
if (is_array($savedCards)) {
    foreach ($homeHeroCardDefaults as $i => $defaults) {
        if (!empty($savedCards[$i]) && is_array($savedCards[$i])) {
            $homeHeroCards[$i] = array_merge($defaults, $savedCards[$i]);
            // Keep Solar icons from defaults; admin does not store icon markup.
            $homeHeroCards[$i]['icon'] = $defaults['icon'];
        }
    }
}
$arrowSvg = yn_icon('arrow-right');

require __DIR__ . '/includes/layout-header.php';
?>
  <main id="main">
    <section class="hero-v2" aria-labelledby="hero-heading">
      <div class="hero-v2__visual" aria-hidden="true">
        <img src="<?= e($homeHeroVisualSrc) ?>" alt="" width="1400" height="900" />
        <svg class="hero-v2__edge" viewBox="0 0 280 520" preserveAspectRatio="none">
          <path fill="var(--hero-wash)" d="M280 0C210 36 188 78 168 124c-22 50-48 72-42 128 6 58 44 78 22 132-18 44-70 62-108 86-20 12-40 28-40 50V0h280z"/>
        </svg>
        <svg class="hero-v2__topo" viewBox="0 0 320 480" preserveAspectRatio="none">
          <path d="M40 20c70 40 90 90 70 150s-10 90 40 140 20 90-10 150" fill="none" stroke="#346356" stroke-width="1.2"/>
          <path d="M18 8c80 48 104 100 82 164s-6 96 48 148 16 96-18 160" fill="none" stroke="#346356" stroke-width="1.1"/>
          <path d="M8 0c88 56 112 112 90 178s2 100 54 154 10 100-24 148" fill="none" stroke="#346356" stroke-width="1"/>
          <path d="M70 36c60 36 78 82 60 136s-8 84 32 128 18 84-8 140" fill="none" stroke="#346356" stroke-width="1"/>
        </svg>
      </div>

      <div class="container container--wide hero-v2__inner">
        <div class="hero-v2__copy">
          <p class="hero-v2__eyebrow">Journeys designed for you</p>
          <h1 id="hero-heading">
            <?php if (!empty($sections['hero_title'])): ?>
              <span class="hero-line"><span><?= e($sections['hero_title']) ?></span></span>
            <?php else: ?>
              <span class="hero-line"><span>Traveled more.</span></span>
              <span class="hero-line"><span>Worry less.</span></span>
            <?php endif; ?>
          </h1>
          <p class="hero-v2__lead"><?= e($sections['hero_text'] ?? 'Handpicked experiences, comfortable stays, and seamless journeys — all in one place.') ?></p>
        </div>

        <h2 id="services-heading" class="sr-only">Our services</h2>
        <div class="hero-v2__grid">
          <?php foreach ($homeHeroCards as $i => $card):
            $cardImg = media_src((string) ($card['image'] ?? ''), '', (string) ($homeHeroCardDefaults[$i]['image'] ?? 'beach.jpg'));
            ?>
            <a class="hero-card" href="<?= e($card['href']) ?>" style="--i:<?= (int) $i ?>">
              <img src="<?= e($cardImg) ?>" alt="" width="640" height="360" />
              <span class="hero-card__icon" aria-hidden="true">
                <?= $card['icon'] ?? '' ?>
              </span>
              <span class="hero-card__text">
                <span class="hero-card__title"><?= e($card['title']) ?></span>
                <span class="hero-card__desc"><?= e($card['desc']) ?></span>
              </span>
              <span class="hero-card__arrow" aria-hidden="true">
                <?= $arrowSvg ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="hero-trust" role="list">
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <?= yn_icon('shield') ?>
            </span>
            <span>
              <strong>Trusted &amp; Safe</strong>
              <span>Your safety is our priority</span>
            </span>
          </div>
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <?= yn_icon('headset') ?>
            </span>
            <span>
              <strong>24/7 Support</strong>
              <span>We’re here anytime you need</span>
            </span>
          </div>
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <?= yn_icon('tag') ?>
            </span>
            <span>
              <strong>Best Price Promise</strong>
              <span>Get the best value always</span>
            </span>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Packages -->
    <section class="section section--alt" aria-labelledby="featured-heading">
      <div class="container">
        <div class="section-header section-header__row">
          <div>
            <p class="section-eyebrow">Featured</p>
            <h2 id="featured-heading">Featured Packages</h2>
            <p class="section-lead">Handpicked itineraries designed for comfort, discovery and memorable moments.</p>
          </div>
          <a class="btn btn--secondary" href="pages/kerala-packages.php">View All Packages</a>
        </div>

        <div class="package-grid">
          <?php if ($featured): ?>
            <?php foreach ($featured as $pkg):
              $href = 'pages/package-details.php?package=' . rawurlencode((string) $pkg['slug']);
              $nights = (int) $pkg['nights'];
              ?>
              <article class="card">
                <div class="card__media">
                  <img src="<?= e(media_src((string) ($pkg['image'] ?? ''), '', 'beach.jpg')) ?>" alt="<?= e($pkg['title']) ?>" width="800" height="500" loading="lazy" />
                </div>
                <div class="card__body">
                  <p class="card__meta"><?= e($pkg['dest_line']) ?></p>
                  <h3 class="card__title"><a href="<?= e($href) ?>"><?= e($pkg['title']) ?></a></h3>
                  <p class="meta-row"><span><strong><?= (int) $pkg['days'] ?> Days</strong> / <?= $nights ?> Night<?= $nights === 1 ? '' : 's' ?></span></p>
                  <p class="card__text"><?= e($pkg['card_text'] ?? '') ?></p>
                  <ul class="highlight-list">
                    <?php foreach (array_slice($pkg['highlights'], 0, 3) as $highlight): ?>
                      <li><?= e($highlight) ?></li>
                    <?php endforeach; ?>
                  </ul>
                  <div class="card__actions">
                    <a class="btn btn--secondary btn--sm" href="<?= e($href) ?>">View Package</a>
                    <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Enquire Now</a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          <?php else: ?>
          <article class="card">
            <div class="card__media">
              <img src="./assets/images/packages/clt-wayanad-4d.jpg" alt="Wayanad forests and plantations" width="800" height="500" loading="lazy" />
            </div>
            <div class="card__body">
              <p class="card__meta">Wayanad</p>
              <h3 class="card__title"><a href="pages/package-details.php?package=clt-wayanad-4d">Wayanad · 4 Days</a></h3>
              <p class="meta-row"><span><strong>4 Days</strong> / 3 Nights</span></p>
              <p class="card__text">Forest trails, caves and plantation stays, pickup and drop at Calicut.</p>
              <ul class="highlight-list">
                <li>Edakkal Caves</li>
                <li>Waterfalls</li>
                <li>Plantations</li>
              </ul>
              <div class="card__actions">
                <a class="btn btn--secondary btn--sm" href="pages/package-details.php?package=clt-wayanad-4d">View Package</a>
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
              </div>
            </div>
          </article>

          <article class="card">
            <div class="card__media">
              <img src="./assets/images/packages/allk-kochi-mta-5d.jpg" alt="Kerala hills and backwaters circuit" width="800" height="500" loading="lazy" />
            </div>
            <div class="card__body">
              <p class="card__meta">Munnar · Thekkady · Alleppey</p>
              <h3 class="card__title"><a href="pages/package-details.php?package=allk-kochi-mta-5d">Munnar, Thekkady &amp; Alleppey · 5 Days</a></h3>
              <p class="meta-row"><span><strong>5 Days</strong> / 4 Nights</span></p>
              <p class="card__text">The classic Kerala circuit from Kochi — tea hills, wildlife and a houseboat night.</p>
              <ul class="highlight-list">
                <li>Tea gardens</li>
                <li>Houseboat</li>
                <li>Wildlife</li>
              </ul>
              <div class="card__actions">
                <a class="btn btn--secondary btn--sm" href="pages/package-details.php?package=allk-kochi-mta-5d">View Package</a>
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
              </div>
            </div>
          </article>

          <article class="card">
            <div class="card__media">
              <img src="./assets/images/packages/tn-ooty-3d.jpg" alt="Nilgiri hills around Ooty" width="800" height="500" loading="lazy" />
            </div>
            <div class="card__body">
              <p class="card__meta">Ooty</p>
              <h3 class="card__title"><a href="pages/package-details.php?package=tn-ooty-3d">Ooty · 3 Days</a></h3>
              <p class="meta-row"><span><strong>3 Days</strong> / 2 Nights</span></p>
              <p class="card__text">A cool Nilgiri break from Coimbatore — gardens, lake and tea estates.</p>
              <ul class="highlight-list">
                <li>Nilgiri hills</li>
                <li>Botanical garden</li>
                <li>Tea estates</li>
              </ul>
              <div class="card__actions">
                <a class="btn btn--secondary btn--sm" href="pages/package-details.php?package=tn-ooty-3d">View Package</a>
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
              </div>
            </div>
          </article>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Popular Destinations -->
    <section class="section" aria-labelledby="destinations-heading">
      <div class="container">
        <div class="section-header section-header--center">
          <p class="section-eyebrow">Destinations</p>
          <h2 id="destinations-heading">Popular Destinations</h2>
          <p class="section-lead">Places travellers ask us about most — explore and enquire for a tailored plan.</p>
        </div>
        <div class="destinations-grid">
          <a class="destination-card" href="pages/kerala-packages.php">
            <img src="./assets/images/tea-plantation.jpg" alt="Tea plantations in Munnar" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Munnar</h3><span>Kerala</span></div>
          </a>
          <a class="destination-card" href="pages/kerala-packages.php">
            <img src="./assets/images/kerala-backwaters.jpg" alt="Houseboat on Alleppey backwaters" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Alleppey</h3><span>Kerala</span></div>
          </a>
          <a class="destination-card" href="pages/south-indian-packages.php">
            <img src="./assets/images/waterfall.jpg" alt="Lush hills of Coorg" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Coorg</h3><span>Karnataka</span></div>
          </a>
          <a class="destination-card" href="pages/south-indian-packages.php">
            <img src="./assets/images/temple.jpg" alt="Scenic view of Ooty" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Ooty</h3><span>Tamil Nadu</span></div>
          </a>
          <a class="destination-card" href="pages/domestic-packages.php">
            <img src="./assets/images/goa-beach.jpg" alt="Beach in Goa" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Goa</h3><span>West India</span></div>
          </a>
          <a class="destination-card" href="pages/kerala-packages.php">
            <img src="./assets/images/forest.jpg" alt="Misty forests of Wayanad" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Wayanad</h3><span>Kerala</span></div>
          </a>
          <a class="destination-card" href="pages/domestic-packages.php">
            <img src="./assets/images/hills-mist.jpg" alt="Mountains near Manali" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Manali</h3><span>Himachal</span></div>
          </a>
          <a class="destination-card" href="pages/domestic-packages.php">
            <img src="./assets/images/lake.jpg" alt="Dal Lake Kashmir" width="600" height="800" loading="lazy" />
            <div class="destination-card__overlay"><h3>Kashmir</h3><span>North India</span></div>
          </a>
        </div>
      </div>
    </section>

    <!-- Weekend Getaways -->
    <section class="section section--soft" aria-labelledby="getaways-heading">
      <div class="container">
        <div class="promo-split">
          <div class="promo-split__media">
            <img src="./assets/images/friends-travel.jpg" alt="Friends on a weekend trip" width="900" height="620" loading="lazy" />
          </div>
          <div>
            <p class="section-eyebrow">Short escapes</p>
            <h2 id="getaways-heading">Weekend Getaways &amp; Stranger Trips</h2>
            <p class="section-lead" style="margin-bottom:1rem">Break the routine with short getaways, group escapes and curated stranger trips — planned for comfort and connection.</p>
            <ul class="check-list" style="margin-bottom:1.25rem">
              <li>2–4 day weekend itineraries</li>
              <li>Group and stranger trip options</li>
              <li>Transport and stay coordination</li>
            </ul>
            <a class="btn btn--primary" href="pages/weekend-getaways.php">Explore Getaways</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Why YathraNest -->
    <section class="section" aria-labelledby="why-heading">
      <div class="container">
        <div class="section-header section-header--center">
          <p class="section-eyebrow">Why us</p>
          <h2 id="why-heading">Why YathraNest</h2>
          <p class="section-lead">Thoughtful planning, trusted partners and personal support at every step.</p>
        </div>
        <div class="benefit-grid">
          <div class="benefit-item">
            <p class="benefit-item__num">01</p>
            <h3>Curated Travel Experiences</h3>
            <p>Itineraries shaped around destinations, pacing and what matters to you.</p>
          </div>
          <div class="benefit-item">
            <p class="benefit-item__num">02</p>
            <h3>Trusted Travel Partners</h3>
            <p>Stays, transport and experiences coordinated with reliable local partners.</p>
          </div>
          <div class="benefit-item">
            <p class="benefit-item__num">03</p>
            <h3>Personalized Planning</h3>
            <p>Share your dates and preferences — we tailor the plan and pricing for you.</p>
          </div>
          <div class="benefit-item">
            <p class="benefit-item__num">04</p>
            <h3>Flexible Travel Options</h3>
            <p>Packages, taxis, resorts, getaways and gifts — one place to explore it all.</p>
          </div>
          <div class="benefit-item">
            <p class="benefit-item__num">05</p>
            <h3>Dedicated Support</h3>
            <p>From enquiry to return, our team stays reachable when you need us.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Gift Cards -->
    <section class="section section--alt" aria-labelledby="gifts-heading">
      <div class="container">
        <div class="promo-split promo-split--reverse">
          <div class="promo-split__media">
            <img src="./assets/images/gift.jpg" alt="Gift wrapped for a special occasion" width="900" height="620" loading="lazy" />
          </div>
          <div>
            <p class="section-eyebrow">Gifting</p>
            <h2 id="gifts-heading">Give the Gift of Travel</h2>
            <p class="section-lead" style="margin-bottom:1.25rem">YathraNest gift cards let loved ones choose the journey that fits them — packages, stays or getaways.</p>
            <a class="btn btn--primary" href="pages/gift-cards.php">Explore Gift Cards</a>
          </div>
        </div>
      </div>
    </section>

    <!-- Investment Plans -->
    <section class="section" aria-labelledby="invest-heading">
      <div class="container">
        <div class="investment-banner">
          <p class="section-eyebrow" style="color:var(--accent)">Investment</p>
          <h2 id="invest-heading">Investment Plans</h2>
          <p>Interested in learning about YathraNest investment options? Request information and our team will share the right details for you.</p>
          <a class="btn btn--accent" href="pages/investment-plans.php">Learn More</a>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="section section--tight" id="enquiry" aria-labelledby="final-cta-heading">
      <div class="container">
        <div class="cta-band">
          <h2 id="final-cta-heading">Planning your next journey?</h2>
          <p>Tell us where you want to go and we’ll help you plan it. Pricing is shared personally after your enquiry.</p>
          <div class="btn-group">
            <a class="btn btn--teal" href="#enquiry" data-open-modal="enquiry-modal">
              Enquire Now
              <span class="btn__icon" aria-hidden="true">→</span>
            </a>
            <a class="btn btn--outline" href="pages/contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
          </div>
        </div>
      </div>
    </section>
  </main>

<?php
require __DIR__ . '/includes/layout-footer.php';
