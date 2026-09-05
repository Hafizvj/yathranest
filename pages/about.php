<?php



require_once dirname(__DIR__) . '/includes/bootstrap.php';



$assetDepth = '../';

$content = null;

try {

    $content = page_content('about');

} catch (Throwable $e) {

}

$sections = $content['sections'] ?? [];

$pageTitle = ($content['title'] ?? 'About') . ' | YathraNest';

$metaDescription = $sections['intro'] ?? 'About YathraNest.';

$enquiryType = 'general';

$enquiryInterest = 'About enquiry';

$enquirySource = 'pages/about.php';

$navActive = 'about';

$hero = $sections['hero_image'] ?? 'friends-travel.jpg';

$heroSrc = media_src($hero, $assetDepth, 'friends-travel.jpg');

$storySrc = media_src('hills-mist.jpg', $assetDepth, 'hills-mist.jpg');



$services = [

    ['icon' => 'leaf', 'title' => 'Travel packages', 'text' => 'Kerala, South India, domestic and international collections.', 'href' => 'kerala-packages.php'],

    ['icon' => 'car', 'title' => 'Taxi booking', 'text' => 'Airport, local and outstation rides with personal quotes.', 'href' => 'taxi-booking.php'],

    ['icon' => 'bed', 'title' => 'Resort stays', 'text' => 'Discover stays and check availability for your dates.', 'href' => 'resort-booking.php'],

    ['icon' => 'sun', 'title' => 'Weekend getaways', 'text' => 'Short escapes, group trips and stranger journeys.', 'href' => 'weekend-getaways.php'],

    ['icon' => 'gift', 'title' => 'Gift cards', 'text' => 'Give the gift of travel — packages, stays or getaways.', 'href' => 'gift-cards.php'],

    ['icon' => 'wallet', 'title' => 'Investment plans', 'text' => 'Partner programmes — details shared on enquiry.', 'href' => 'investment-plans.php'],

];



$steps = [

    ['title' => 'Browse', 'text' => 'Explore packages, stays, taxis and getaways on the site.'],

    ['title' => 'Explore', 'text' => 'Share your dates, group size and preferences with our team.'],

    ['title' => 'Enquire', 'text' => 'Receive personalised pricing — no online payment on this site.'],

];



$benefits = [

    ['title' => 'Curated Travel Experiences', 'text' => 'Itineraries shaped around destinations, pacing and what matters to you.'],

    ['title' => 'Trusted Travel Partners', 'text' => 'Stays, transport and experiences coordinated with reliable local partners.'],

    ['title' => 'Personalized Planning', 'text' => 'Share your dates and preferences — we tailor the plan and pricing for you.'],

    ['title' => 'Flexible Travel Options', 'text' => 'Packages, taxis, resorts, getaways and gifts — one place to explore it all.'],

    ['title' => 'Dedicated Support', 'text' => 'From enquiry to return, our team stays reachable when you need us.'],

];



require dirname(__DIR__) . '/includes/layout-header.php';

?>

<main id="main">

  <section class="page-head page-head--media">

    <div class="page-head__media" aria-hidden="true">

      <img src="<?= e($heroSrc) ?>" alt="" width="1600" height="900" />

    </div>

    <div class="container page-head__inner">

      <?= yn_crumbs(['Home' => '../index.php', 'About' => null], true) ?>

      <div class="page-head__body">

        <p class="page-head__eyebrow">Who we are</p>

        <h1><?= e($content['title'] ?? 'About YathraNest') ?></h1>

        <?php if (!empty($sections['intro'])): ?>

          <p class="page-head__lead"><?= e($sections['intro']) ?></p>

        <?php endif; ?>

        <div class="page-head__chips">

          <?= yn_chip('compass', 'Curated journeys') ?>

          <?= yn_chip('shield', 'Trusted partners') ?>

          <?= yn_chip('chat', 'Personal pricing') ?>

        </div>

      </div>

    </div>

  </section>



  <div class="container hero-trust hero-trust--inner" role="list">

    <div class="hero-trust__item" role="listitem" data-reveal>

      <span class="hero-trust__icon" aria-hidden="true"><?= yn_icon('shield') ?></span>

      <span>

        <strong>Trusted &amp; Safe</strong>

        <span>Your safety is our priority</span>

      </span>

    </div>

    <div class="hero-trust__item" role="listitem" data-reveal>

      <span class="hero-trust__icon" aria-hidden="true"><?= yn_icon('headset') ?></span>

      <span>

        <strong>24/7 Support</strong>

        <span>We're here anytime you need</span>

      </span>

    </div>

    <div class="hero-trust__item" role="listitem" data-reveal>

      <span class="hero-trust__icon" aria-hidden="true"><?= yn_icon('tag') ?></span>

      <span>

        <strong>Best Price Promise</strong>

        <span>Get the best value always</span>

      </span>

    </div>

  </div>



  <section class="section">

    <div class="container">

      <div class="promo-split" data-reveal>

        <div class="promo-split__media">

          <img src="<?= e($storySrc) ?>" alt="Scenic hills and misty landscapes" width="900" height="620" loading="lazy" />

        </div>

        <div>

          <?php if (!empty($sections['body'])): ?>

            <div class="prose"><?= $sections['body'] ?></div>

          <?php else: ?>

            <p class="section-eyebrow">Our approach</p>

            <h2>A travel company built around clarity</h2>

            <div class="prose">

              <p>YathraNest brings together travel packages, taxi services, resort stays, weekend getaways, gift cards and investment conversations under one thoughtful brand experience.</p>

              <p>We believe travel planning should feel clear and human. Explore options on the website, then enquire — our team responds with availability and pricing tailored to your dates.</p>

            </div>

            <h3 style="margin-top:1.5rem;margin-bottom:0.75rem">What we offer</h3>

            <ul class="check-list">

              <li>Kerala, South Indian, domestic and international packages</li>

              <li>Taxi quotes for airport, local and outstation trips</li>

              <li>Resort discovery and availability checks</li>

              <li>Weekend getaways and stranger trips</li>

              <li>Travel gift cards and investment information requests</li>

            </ul>

          <?php endif; ?>

        </div>

      </div>



      <div class="about-quote-wrap">

        <div class="quote-card">

          <p class="quote-card__eyebrow">Start planning</p>

          <h3>Tell us your plan</h3>

          <p>Share where you'd like to go and when — we'll design the trip and share pricing.</p>

          <div class="btn-group">

            <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>

            <a class="btn btn--secondary quote-card__ghost" href="contact.php">Contact Us</a>

          </div>

          <p class="quote-card__note"><?= yn_icon('shield') ?>Trusted &amp; safe travel</p>

        </div>

      </div>

    </div>

  </section>



  <section class="section section--soft">

    <div class="container">

      <div class="section-head">

        <div>

          <p class="section-head__eyebrow">What we do</p>

          <h2>Everything under one roof</h2>

          <p>Packages, stays, transport and more — explore each service and enquire when you're ready.</p>

        </div>

      </div>

      <div class="tile-grid tile-grid--3">

        <?php foreach ($services as $service): ?>

          <a class="tile" href="<?= e($service['href']) ?>" data-reveal>

            <span class="tile__icon"><?= yn_icon($service['icon']) ?></span>

            <h3><?= e($service['title']) ?></h3>

            <p><?= e($service['text']) ?></p>

            <span class="link-arrow">Explore</span>

          </a>

        <?php endforeach; ?>

      </div>

    </div>

  </section>



  <section class="section">

    <div class="container">

      <div class="section-head">

        <div>

          <p class="section-head__eyebrow">How it works</p>

          <h2>Browse · Explore · Enquire</h2>

          <p>Three simple steps from browsing ideas to receiving a personalised quote.</p>

        </div>

      </div>

      <div class="steps">

        <?php foreach ($steps as $i => $step): ?>

          <div class="step" data-reveal>

            <span class="step__num"><?= $i + 1 ?></span>

            <div>

              <h3><?= e($step['title']) ?></h3>

              <p><?= e($step['text']) ?></p>

            </div>

          </div>

        <?php endforeach; ?>

      </div>

    </div>

  </section>



  <section class="section section--soft">

    <div class="container">

      <div class="section-head section-head--center">

        <div>

          <p class="section-head__eyebrow">Why us</p>

          <h2>Why YathraNest</h2>

          <p>Thoughtful planning, trusted partners and personal support at every step.</p>

        </div>

      </div>

      <div class="benefit-grid">

        <?php foreach ($benefits as $i => $benefit): ?>

          <div class="benefit-item" data-reveal>

            <p class="benefit-item__num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></p>

            <h3><?= e($benefit['title']) ?></h3>

            <p><?= e($benefit['text']) ?></p>

          </div>

        <?php endforeach; ?>

      </div>

    </div>

  </section>



  <section class="section section--tight">

    <div class="container">

      <div class="cta-band" data-reveal>

        <p class="cta-band__eyebrow">Browse · Explore · Enquire</p>

        <h2>Ready when you are</h2>

        <p>Browse our collections, tell us what you have in mind, and we'll share pricing personally — no online payment needed.</p>

        <div class="btn-group">

          <a class="btn btn--teal" href="kerala-packages.php">

            Explore packages

            <span class="btn__icon" aria-hidden="true">→</span>

          </a>

          <a class="btn btn--outline" href="#enquiry" data-open-modal="enquiry-modal" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Enquire Now</a>

          <a class="btn btn--outline" href="contact.php" style="background:transparent;border-color:rgba(255,255,255,.35);color:#fff">Contact Us</a>

        </div>

      </div>

    </div>

  </section>

</main>

<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>

