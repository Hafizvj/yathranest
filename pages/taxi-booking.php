<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$csrf = csrf_token();
$phone = '+91 98765 43210';
$email = 'hello@yathranest.com';
$whatsapp = '919876543210';
try {
    $phone = setting('phone', $phone);
    $email = setting('email', $email);
    $whatsapp = preg_replace('/\D/', '', setting('whatsapp', $whatsapp));
} catch (Throwable $e) {
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Request a taxi quote with YathraNest — airport transfers, outstation and local trips. No online payment." />
  <title>Taxi Cab Booking | YathraNest</title>
  <link rel="icon" href="../assets/logo/logo.svg" type="image/svg+xml" />
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <link rel="stylesheet" href="../css/responsive.css" />
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="container site-header__inner">
      <a class="logo" href="../index.php" aria-label="YathraNest home"><span class="logo__mark" aria-hidden="true">YN</span><span class="logo__text">Yathra<span>Nest</span></span></a>
      <nav class="nav-desktop" aria-label="Primary">
        <a href="kerala-packages.php" data-nav="packages">Packages</a>
        <a href="taxi-booking.php" data-nav>Taxi</a>
        <a href="resort-booking.php" data-nav>Resorts</a>
        <a href="weekend-getaways.php" data-nav>Getaways</a>
        <a href="gift-cards.php" data-nav>Gift Cards</a>
        <a href="investment-plans.php" data-nav>Investment Plans</a>
        <a href="about.php" data-nav>About</a>
        <a href="contact.php" data-nav>Contact</a>
      </nav>
      <div class="header-actions">
        <a class="btn btn--primary btn--header-cta" href="#taxi-form">Get a Quote</a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu"><span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span></button>
      </div>
    </div>
  </header>
  <div class="nav-drawer" id="nav-drawer">
    <div class="nav-drawer__backdrop"></div>
    <div class="nav-drawer__panel" role="dialog" aria-label="Mobile navigation">
      <div class="nav-drawer__head">
        <a class="logo" href="../index.php"><span class="logo__mark" aria-hidden="true">YN</span><span class="logo__text">Yathra<span>Nest</span></span></a>
        <button class="nav-drawer__close" type="button" aria-label="Close menu">&times;</button>
      </div>
      <nav class="nav-drawer__links" aria-label="Mobile">
        <a href="kerala-packages.php">Packages</a>
        <a href="taxi-booking.php">Taxi</a>
        <a href="resort-booking.php">Resorts</a>
        <a href="weekend-getaways.php">Getaways</a>
        <a href="gift-cards.php">Gift Cards</a>
        <a href="investment-plans.php">Investment Plans</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
      </nav>
      <div class="nav-drawer__cta"><a class="btn btn--primary btn--block" href="#taxi-form">Get a Quote</a></div>
    </div>
  </div>

  <main id="main">
    <section class="page-hero page-hero--media" style="background-image:url('../assets/images/car-taxi.jpg')">
      <div class="container page-hero__inner">
        <h1>Taxi Cab Booking</h1>
        <p>Airport transfers, outstation trips and local travel. Submit an enquiry — we’ll confirm availability and pricing.</p>
      </div>
    </section>

    <section class="section">
      <div class="container" style="max-width:860px">
        <div class="form-panel" id="taxi-form">
          <h2 style="margin-bottom:0.5rem">Get a Quote</h2>
          <p class="text-muted mb-2">This is an enquiry form, not online booking or payment. Our team will contact you with options.</p>

          <form data-enquiry-form data-success-modal="taxi-success-modal" novalidate action="../handlers/enquiry.php" method="post">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />
        <input type="hidden" name="type" value="taxi" />
        <input type="hidden" name="source_page" value="pages/taxi-booking.php" />
        <input type="hidden" name="interest" value="Taxi booking" />
            <h3 class="mb-2">Trip details</h3>
            <div class="form-grid form-grid--2">
              <div class="form-group">
                <label for="pickup">Pickup location</label>
                <input class="form-control" id="pickup" name="pickup" type="text" required placeholder="City / airport / address" />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="drop">Drop location</label>
                <input class="form-control" id="drop" name="drop" type="text" required placeholder="Destination" />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="date">Date</label>
                <input class="form-control" id="date" name="date" type="date" required />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="time">Time</label>
                <input class="form-control" id="time" name="time" type="time" required />
                <span class="field-error"></span>
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label for="trip-type">Trip type</label>
                <select class="form-control" id="trip-type" name="tripType" required>
                  <option value="">Select trip type</option>
                  <option>Airport transfer</option>
                  <option>Local (8 hrs / 80 km)</option>
                  <option>Outstation one-way</option>
                  <option>Outstation round trip</option>
                </select>
                <span class="field-error"></span>
              </div>
            </div>

            <h3 class="mt-3 mb-2">Vehicle options</h3>
            <div class="form-group" data-required-group="vehicle">
              <div class="radio-group">
                <label class="choice-card"><input type="radio" name="vehicle" value="Sedan" required /><strong>Sedan</strong><span>Comfortable for 3–4 travellers</span></label>
                <label class="choice-card"><input type="radio" name="vehicle" value="SUV" /><strong>SUV</strong><span>Extra space for luggage</span></label>
                <label class="choice-card"><input type="radio" name="vehicle" value="Innova / Crysta" /><strong>Innova / Crysta</strong><span>Ideal for families</span></label>
                <label class="choice-card"><input type="radio" name="vehicle" value="Tempo Traveller" /><strong>Tempo Traveller</strong><span>Groups &amp; larger parties</span></label>
              </div>
              <span class="field-error"></span>
            </div>

            <h3 class="mt-3 mb-2">Traveller &amp; contact details</h3>
            <div class="form-grid form-grid--2">
              <div class="form-group">
                <label for="passengers">Number of passengers</label>
                <input class="form-control" id="passengers" name="passengers" type="number" min="1" max="20" required value="2" />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="name">Full name</label>
                <input class="form-control" id="name" name="name" type="text" required autocomplete="name" />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" type="tel" required data-validate="phone" autocomplete="tel" />
                <span class="field-error"></span>
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" required autocomplete="email" />
                <span class="field-error"></span>
              </div>
              <div class="form-group" style="grid-column:1/-1">
                <label for="notes">Additional requirements</label>
                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="Child seat, extra stops, flight number..."></textarea>
              </div>
            </div>

            <div class="btn-group">
              <button class="btn btn--primary" type="submit">Get a Quote</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="logo" href="../index.php"><span class="logo__mark" aria-hidden="true">YN</span><span class="logo__text">Yathra<span>Nest</span></span></a>
          <p>Curated travel packages, stays, taxi services and unique experiences across India and beyond.</p>
          <div class="footer-contact">
            <a href="tel:+919876543210"><?= e($phone) ?></a>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" rel="noopener noreferrer" target="_blank">WhatsApp</a>
          </div>
          <div class="social-links"><a href="#" aria-label="Instagram">IG</a><a href="#" aria-label="Facebook">FB</a><a href="#" aria-label="YouTube">YT</a></div>
        </div>
        <div class="footer-col"><h4>Explore</h4><ul>
          <li><a href="kerala-packages.php">Kerala Packages</a></li>
          <li><a href="south-indian-packages.php">South Indian Packages</a></li>
          <li><a href="domestic-packages.php">Domestic Packages</a></li>
          <li><a href="international-packages.php">International Packages</a></li>
          <li><a href="weekend-getaways.php">Weekend Getaways</a></li>
        </ul></div>
        <div class="footer-col"><h4>Services</h4><ul>
          <li><a href="taxi-booking.php">Taxi Booking</a></li>
          <li><a href="resort-booking.php">Resort Stays</a></li>
          <li><a href="gift-cards.php">Gift Cards</a></li>
          <li><a href="investment-plans.php">Investment Plans</a></li>
        </ul></div>
        <div class="footer-col"><h4>Company</h4><ul>
          <li><a href="about.php">About Us</a></li>
          <li><a href="contact.php">Contact</a></li>
          <li><a href="faq.php">FAQ</a></li>
        </ul></div>
        <div class="footer-col"><h4>Legal</h4><ul>
          <li><a href="terms.php">Terms &amp; Conditions</a></li>
          <li><a href="privacy.php">Privacy Policy</a></li>
        </ul></div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span data-year>2026</span> YathraNest. All rights reserved.</p>
        <p>Browse · Explore · Enquire — pricing shared personally.</p>
      </div>
    </div>
  </footer>

  <div class="modal" id="taxi-success-modal" role="dialog" aria-modal="true" aria-labelledby="taxi-success-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <div class="modal__icon" aria-hidden="true">✓</div>
      <h2 id="taxi-success-title">Thank you!</h2>
      <p>Your taxi enquiry has been submitted. Our team will contact you with availability and pricing.</p>
      <button class="btn btn--primary" type="button" data-close-modal>Close</button>
    </div>
  </div>

  <script src="../js/navigation.js" defer></script>
  <script src="../js/filters.js" defer></script>
  <script src="../js/forms.js" defer></script>
  <script src="../js/gallery.js" defer></script>
  <script src="../js/main.js" defer></script>
</body>
</html>
