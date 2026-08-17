<?php
require_once __DIR__ . '/includes/bootstrap.php';
$assetDepth = '';
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
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="YathraNest — curated travel packages, taxi services, resort stays, weekend getaways, gift cards and investment plans. Browse, explore and enquire for personalised pricing." />
  <title>YathraNest — Your Journey Starts Here</title>
  <link rel="icon" type="image/png" sizes="32x32" href="assets/logo/favicon-32.png" />
  <link rel="icon" type="image/png" sizes="150x150" href="assets/logo/logo-mark.png" />
  <link rel="apple-touch-icon" href="assets/logo/apple-touch-icon.png" />
  <meta name="theme-color" content="#346356" />
  <link rel="stylesheet" href="css/style.css?v=12" />
  <link rel="stylesheet" href="css/components.css?v=12" />
  <link rel="stylesheet" href="css/responsive.css?v=12" />
  <link rel="stylesheet" href="css/motion.css?v=12" />
  <script>
    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      document.documentElement.classList.add("has-motion");
    }
  </script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header site-header--modern site-header--hero">
    <div class="container container--wide site-header__inner">
      <a class="logo" href="index.php" aria-label="YathraNest home">
        <img class="logo__img" src="assets/logo/logo-wordmark.png" alt="YathraNest" width="293" height="98" />
      </a>
      <div class="header-actions">
        <div class="nav-explore">
          <button class="nav-explore__btn" type="button" aria-expanded="false" aria-controls="explore-menu" id="explore-btn">
            Explore
            <svg viewBox="0 0 12 12" width="12" height="12" aria-hidden="true" focusable="false">
              <path d="M2.5 4.5L6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <div class="nav-explore__menu" id="explore-menu" hidden>
            <a href="pages/kerala-packages.php">Kerala Packages</a>
            <a href="pages/domestic-packages.php">Domestic Packages</a>
            <a href="pages/international-packages.php">International</a>
            <a href="pages/resort-booking.php">Resort Booking</a>
            <a href="pages/taxi-booking.php">Taxi Booking</a>
            <a href="pages/weekend-getaways.php">Weekend Getaways</a>
            <a href="pages/south-indian-packages.php">South Indian Packages</a>
            <a href="pages/gift-cards.php">Gift Cards</a>
            <a href="pages/about.php">About</a>
            <a href="pages/contact.php">Contact</a>
          </div>
        </div>
        <button class="nav-toggle nav-toggle--circle" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu">
          <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
      </div>
    </div>
  </header>

  <div class="nav-drawer" id="nav-drawer">
    <div class="nav-drawer__backdrop"></div>
    <div class="nav-drawer__panel" role="dialog" aria-label="Mobile navigation">
      <div class="nav-drawer__head">
        <a class="logo" href="index.php" aria-label="YathraNest home">
          <img class="logo__img" src="assets/logo/logo-wordmark.png" alt="YathraNest" width="293" height="98" />
        </a>
        <button class="nav-drawer__close" type="button" aria-label="Close menu">&times;</button>
      </div>
      <nav class="nav-drawer__links" aria-label="Mobile">
        <a href="pages/kerala-packages.php">Packages</a>
        <a href="pages/taxi-booking.php">Taxi</a>
        <a href="pages/resort-booking.php">Resorts</a>
        <a href="pages/weekend-getaways.php">Getaways</a>
        <a href="pages/gift-cards.php">Gift Cards</a>
        <a href="pages/investment-plans.php">Investment Plans</a>
        <a href="pages/about.php">About</a>
        <a href="pages/contact.php">Contact</a>
      </nav>
      <div class="nav-drawer__cta">
        <a class="btn btn--primary btn--block" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
      </div>
    </div>
  </div>

  <main id="main">
    <section class="hero-v2" aria-labelledby="hero-heading">
      <div class="hero-v2__visual" aria-hidden="true">
        <img src="./assets/images/maldives.jpg" alt="" width="1400" height="900" />
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
            <span class="hero-line"><span>Traveled more.</span></span>
            <span class="hero-line"><span>Worry less.</span></span>
          </h1>
          <p class="hero-v2__lead">Handpicked experiences, comfortable stays, and seamless journeys — all in one place.</p>
        </div>

        <h2 id="services-heading" class="sr-only">Our services</h2>
        <div class="hero-v2__grid">
          <a class="hero-card" href="pages/kerala-packages.php" style="--i:0">
            <img src="./assets/images/hills-mist.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 14c-3.2-.8-6-3.4-7-5.6 2.6.2 5.1 1.8 7 5.6 1.9-3.8 4.4-5.4 7-5.6-1 2.2-3.8 4.8-7 5.6z"/><path d="M12 13.6c-1.4-2.8-.9-5.5.3-7.2-2.1 1-3.2 3.8-.3 7.2 2.9-3.4 1.8-6.2-.3-7.2 1.2 1.7 1.7 4.4.3 7.2z"/><path d="M11.4 13.4h1.2v6.2a.9.9 0 0 1-.9.9h.6a.9.9 0 0 1-.9-.9v-6.2z"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">Kerala Tour Package</span>
              <span class="hero-card__desc">Experience God’s Own Country.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>

          <a class="hero-card" href="pages/domestic-packages.php" style="--i:1">
            <img src="./assets/images/lake.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M9.2 3.2 12 4.4l2.2.6 1.4 2.4-.2 1.5 1.7 1.2.3 2.2-1.5 1.3.5 2.3-1.3 1.7.3 2.1-1.7 1.5-1.4-1.1-1.2 1.7-1.8-.9-1.2 1.1-1.6-2.1-1.8.3-1.2-2.1.8-1.7-1.5-1.5.2-2.1 1.5-1.2-.4-2.1 1.4-1.5.2-1.7 1.7-1.2z"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">Domestic Tour Pack</span>
              <span class="hero-card__desc">Incredible places across India.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>

          <a class="hero-card" href="pages/international-packages.php" style="--i:2">
            <img src="./assets/images/dubai.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="8"/><path d="M4 12h16M12 4c2.4 2.6 3.6 5.4 3.6 8s-1.2 5.4-3.6 8c-2.4-2.6-3.6-5.4-3.6-8s1.2-5.4 3.6-8z"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">International</span>
              <span class="hero-card__desc">Explore the world beyond borders.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>

          <a class="hero-card" href="pages/resort-booking.php" style="--i:3">
            <img src="./assets/images/resort-pool.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 18v-5.5A2.5 2.5 0 0 1 6.5 10H18a2 2 0 0 1 2 2v6"/><path d="M4 15h16"/><path d="M7 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">Resort Booking</span>
              <span class="hero-card__desc">Stay. Relax. Rejuvenate.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>

          <a class="hero-card" href="pages/taxi-booking.php" style="--i:4">
            <img src="./assets/images/car-taxi.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15v2.5A1.5 1.5 0 0 0 5.5 19h.7"/><path d="M17.8 19h.7A1.5 1.5 0 0 0 20 17.5V15"/><path d="M4 15 6.2 9.8A2 2 0 0 1 8 8.5h8a2 2 0 0 1 1.8 1.3L20 15"/><path d="M7 15h10"/><circle cx="7.5" cy="18.5" r="1.5"/><circle cx="16.5" cy="18.5" r="1.5"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">Taxi Booking</span>
              <span class="hero-card__desc">Reliable rides, anytime, anywhere.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>

          <a class="hero-card" href="pages/weekend-getaways.php" style="--i:5">
            <img src="./assets/images/friends-travel.jpg" alt="" width="640" height="360" />
            <span class="hero-card__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="8.2" cy="8" r="2.1"/><circle cx="15.8" cy="8.4" r="2.1"/><circle cx="12" cy="9.2" r="2.2"/><path d="M4.6 18c.4-2.6 2.2-4 4.2-4 1.2 0 2.2.4 3 .1.8.4 1.9.1 3.2.1 2.1 0 3.9 1.4 4.4 4"/></svg>
            </span>
            <span class="hero-card__text">
              <span class="hero-card__title">Weekend Strangers Trip</span>
              <span class="hero-card__desc">Travel. Connect. Create memories.</span>
            </span>
            <span class="hero-card__arrow" aria-hidden="true">
              <svg viewBox="0 0 16 16"><path d="M3 8h9M8.5 4.5L12.5 8l-4 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
          </a>
        </div>

        <div class="hero-trust" role="list">
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 5.5 6.2v5.2c0 4.1 2.8 7.8 6.5 8.9 3.7-1.1 6.5-4.8 6.5-8.9V6.2L12 3.5z"/><path d="m8.8 12.1 2.1 2.1 4.3-4.4"/></svg>
            </span>
            <span>
              <strong>Trusted &amp; Safe</strong>
              <span>Your safety is our priority</span>
            </span>
          </div>
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12v-1a7 7 0 0 1 14 0v1"/><path d="M5 12h2a1.5 1.5 0 0 1 1.5 1.5V16A1.5 1.5 0 0 1 7 17.5H5.8A1.8 1.8 0 0 1 4 15.7V13.8A1.8 1.8 0 0 1 5.8 12H5z"/><path d="M19 12h-2a1.5 1.5 0 0 0-1.5 1.5V16a1.5 1.5 0 0 0 1.5 1.5h1.2A1.8 1.8 0 0 0 20 15.7v-1.9A1.8 1.8 0 0 0 18.2 12H19z"/><path d="M12 17.5v1.2a2.3 2.3 0 0 0 2.3 2.3H15"/></svg>
            </span>
            <span>
              <strong>24/7 Support</strong>
              <span>We’re here anytime you need</span>
            </span>
          </div>
          <div class="hero-trust__item" role="listitem">
            <span class="hero-trust__icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12.8 4.4 19.6 8v3.2L8.2 20.4 4 16.2 12.8 4.4z"/><path d="M14.2 8.2 16 10"/><circle cx="15.2" cy="9.2" r=".7" fill="currentColor" stroke="none"/></svg>
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
                    <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="<?= e($pkg['title']) ?>">Request Pricing</a>
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
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
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
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
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
                <a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal">Request Pricing</a>
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
              Request Pricing
              <span class="btn__icon" aria-hidden="true">→</span>
            </a>
            <a class="btn btn--outline" href="pages/contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="logo" href="index.php" aria-label="YathraNest home">
            <img class="logo__badge" src="assets/logo/logo-mark.png" alt="" width="150" height="150" />
            <img class="logo__img logo__img--text" src="assets/logo/logo-text-light.png" alt="YathraNest" width="207" height="98" />
          </a>
          <p>Curated travel packages, stays, taxi services and unique experiences across India and beyond.</p>
          <div class="footer-contact">
            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" rel="noopener noreferrer" target="_blank">WhatsApp</a>
          </div>
          <div class="social-links" aria-label="Social media">
            <a href="#" aria-label="Instagram">IG</a>
            <a href="#" aria-label="Facebook">FB</a>
            <a href="#" aria-label="YouTube">YT</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Explore</h4>
          <ul>
            <li><a href="pages/kerala-packages.php">Kerala Packages</a></li>
            <li><a href="pages/south-indian-packages.php">South Indian Packages</a></li>
            <li><a href="pages/domestic-packages.php">Domestic Packages</a></li>
            <li><a href="pages/international-packages.php">International Packages</a></li>
            <li><a href="pages/weekend-getaways.php">Weekend Getaways</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="pages/taxi-booking.php">Taxi Booking</a></li>
            <li><a href="pages/resort-booking.php">Resort Stays</a></li>
            <li><a href="pages/gift-cards.php">Gift Cards</a></li>
            <li><a href="pages/investment-plans.php">Investment Plans</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="pages/about.php">About Us</a></li>
            <li><a href="pages/contact.php">Contact</a></li>
            <li><a href="pages/faq.php">FAQ</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Legal</h4>
          <ul>
            <li><a href="pages/terms.php">Terms &amp; Conditions</a></li>
            <li><a href="pages/privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span data-year>2026</span> YathraNest. All rights reserved.</p>
        <p>Browse · Explore · Enquire — pricing shared personally.</p>
      </div>
    </div>
  </footer>

  <!-- Enquiry Modal -->
  <div class="modal" id="enquiry-modal" role="dialog" aria-modal="true" aria-labelledby="enquiry-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog modal__dialog--lg">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <h2 id="enquiry-title">Request Pricing</h2>
      <p>Share a few details and we’ll continue the conversation on WhatsApp with a personalised quote — no online payment required.</p>
      <form data-enquiry-form data-success-modal="success-modal" novalidate action="handlers/enquiry.php" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="type" value="general" />
        <input type="hidden" name="source_page" value="index.php" />
        <div class="form-grid form-grid--2">
          <div class="form-group">
            <label for="enq-name">Full name</label>
            <input class="form-control" id="enq-name" name="name" type="text" required autocomplete="name" />
            <span class="field-error"></span>
          </div>
          <div class="form-group">
            <label for="enq-phone">Phone</label>
            <input class="form-control" id="enq-phone" name="phone" type="tel" required data-validate="phone" autocomplete="tel" />
            <span class="field-error"></span>
          </div>
          <div class="form-group">
            <label for="enq-email">Email</label>
            <input class="form-control" id="enq-email" name="email" type="email" required autocomplete="email" />
            <span class="field-error"></span>
          </div>
          <div class="form-group">
            <label for="enq-date">Travel from</label>
            <input class="form-control" id="enq-date" name="travel_date" type="date" required min="<?= e(date('Y-m-d')) ?>" />
            <span class="field-error"></span>
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label for="enq-interest">Interest</label>
            <input class="form-control" id="enq-interest" name="interest" type="text" value="General travel enquiry" data-prefill="interest" />
          </div>
        </div>
        <div class="btn-group">
          <button class="btn btn--primary" type="submit">Continue on WhatsApp</button>
          <button class="btn btn--secondary" type="button" data-close-modal>Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal" id="success-modal" role="dialog" aria-modal="true" aria-labelledby="success-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <div class="modal__icon" aria-hidden="true">✓</div>
      <h2 id="success-title">Thank you!</h2>
      <p data-success-note>Your enquiry has been submitted. Our team will contact you shortly with availability and pricing.</p>
      <div class="btn-group">
        <a class="btn btn--primary" data-whatsapp-link href="#" target="_blank" rel="noopener" hidden>Open WhatsApp</a>
        <button class="btn btn--secondary" type="button" data-close-modal>Close</button>
      </div>
    </div>
  </div>

  <script src="js/navigation.js?v=11" defer></script>
  <script src="js/filters.js?v=11" defer></script>
  <script src="js/forms.js?v=11" defer></script>
  <script src="js/gallery.js?v=11" defer></script>
  <script src="js/main.js?v=11" defer></script>
  <script src="js/motion.js?v=11" defer></script>
</body>
</html>
