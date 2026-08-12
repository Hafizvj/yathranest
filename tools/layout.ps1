# Generates shared partials for YathraNest pages
# Usage from project root: . .\tools\layout.ps1

function Get-CssLinks([string]$Prefix) {
@"
  <link rel="stylesheet" href="$Prefix/css/style.css" />
  <link rel="stylesheet" href="$Prefix/css/components.css" />
  <link rel="stylesheet" href="$Prefix/css/responsive.css" />
"@
}

function Get-JsScripts([string]$Prefix) {
@"
  <script src="$Prefix/js/navigation.js" defer></script>
  <script src="$Prefix/js/filters.js" defer></script>
  <script src="$Prefix/js/forms.js" defer></script>
  <script src="$Prefix/js/gallery.js" defer></script>
  <script src="$Prefix/js/main.js" defer></script>
"@
}

function Get-Header([string]$Prefix, [string]$EnquireHref) {
@"
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="site-header">
    <div class="container site-header__inner">
      <a class="logo" href="$Prefix/index.html" aria-label="YathraNest home">
        <span class="logo__mark" aria-hidden="true">YN</span>
        <span class="logo__text">Yathra<span>Nest</span></span>
      </a>
      <nav class="nav-desktop" aria-label="Primary">
        <a href="$Prefix/pages/kerala-packages.html" data-nav="packages">Packages</a>
        <a href="$Prefix/pages/taxi-booking.html" data-nav>Taxi</a>
        <a href="$Prefix/pages/resort-booking.html" data-nav>Resorts</a>
        <a href="$Prefix/pages/weekend-getaways.html" data-nav>Getaways</a>
        <a href="$Prefix/pages/gift-cards.html" data-nav>Gift Cards</a>
        <a href="$Prefix/pages/investment-plans.html" data-nav>Investment Plans</a>
        <a href="$Prefix/pages/about.html" data-nav>About</a>
        <a href="$Prefix/pages/contact.html" data-nav>Contact</a>
      </nav>
      <div class="header-actions">
        <a class="btn btn--primary btn--header-cta" href="$EnquireHref">Request Pricing</a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-drawer" aria-label="Open menu">
          <span class="nav-toggle__bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
      </div>
    </div>
  </header>
  <div class="nav-drawer" id="nav-drawer" aria-hidden="true">
    <div class="nav-drawer__backdrop"></div>
    <div class="nav-drawer__panel" role="dialog" aria-label="Mobile navigation">
      <div class="nav-drawer__head">
        <a class="logo" href="$Prefix/index.html">
          <span class="logo__mark" aria-hidden="true">YN</span>
          <span class="logo__text">Yathra<span>Nest</span></span>
        </a>
        <button class="nav-drawer__close" type="button" aria-label="Close menu">&times;</button>
      </div>
      <nav class="nav-drawer__links" aria-label="Mobile">
        <a href="$Prefix/pages/kerala-packages.html">Packages</a>
        <a href="$Prefix/pages/taxi-booking.html">Taxi</a>
        <a href="$Prefix/pages/resort-booking.html">Resorts</a>
        <a href="$Prefix/pages/weekend-getaways.html">Getaways</a>
        <a href="$Prefix/pages/gift-cards.html">Gift Cards</a>
        <a href="$Prefix/pages/investment-plans.html">Investment Plans</a>
        <a href="$Prefix/pages/about.html">About</a>
        <a href="$Prefix/pages/contact.html">Contact</a>
      </nav>
      <div class="nav-drawer__cta">
        <a class="btn btn--primary btn--block" href="$EnquireHref">Request Pricing</a>
      </div>
    </div>
  </div>
"@
}

function Get-Footer([string]$Prefix) {
@"
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="logo" href="$Prefix/index.html">
            <span class="logo__mark" aria-hidden="true">YN</span>
            <span class="logo__text">Yathra<span>Nest</span></span>
          </a>
          <p>Curated travel packages, stays, taxi services and unique experiences across India and beyond.</p>
          <div class="footer-contact">
            <a href="tel:+919876543210">+91 98765 43210</a>
            <a href="mailto:hello@yathranest.com">hello@yathranest.com</a>
            <a href="https://wa.me/919876543210" rel="noopener noreferrer" target="_blank">WhatsApp</a>
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
            <li><a href="$Prefix/pages/kerala-packages.html">Kerala Packages</a></li>
            <li><a href="$Prefix/pages/south-indian-packages.html">South Indian Packages</a></li>
            <li><a href="$Prefix/pages/domestic-packages.html">Domestic Packages</a></li>
            <li><a href="$Prefix/pages/international-packages.html">International Packages</a></li>
            <li><a href="$Prefix/pages/weekend-getaways.html">Weekend Getaways</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="$Prefix/pages/taxi-booking.html">Taxi Booking</a></li>
            <li><a href="$Prefix/pages/resort-booking.html">Resort Stays</a></li>
            <li><a href="$Prefix/pages/gift-cards.html">Gift Cards</a></li>
            <li><a href="$Prefix/pages/investment-plans.html">Investment Plans</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="$Prefix/pages/about.html">About Us</a></li>
            <li><a href="$Prefix/pages/contact.html">Contact</a></li>
            <li><a href="$Prefix/pages/faq.html">FAQ</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Legal</h4>
          <ul>
            <li><a href="$Prefix/pages/terms.html">Terms &amp; Conditions</a></li>
            <li><a href="$Prefix/pages/privacy.html">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span data-year>2026</span> YathraNest. All rights reserved.</p>
        <p>Browse · Explore · Enquire — pricing shared personally.</p>
      </div>
    </div>
  </footer>
"@
}

function Get-SuccessModal {
@"
  <div class="modal" id="success-modal" role="dialog" aria-modal="true" aria-labelledby="success-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <div class="modal__icon" aria-hidden="true">✓</div>
      <h2 id="success-title">Thank you!</h2>
      <p>Your enquiry has been submitted. Our team will contact you shortly with availability and pricing.</p>
      <button class="btn btn--primary" type="button" data-close-modal>Close</button>
    </div>
  </div>
"@
}

function Get-EnquiryModal([string]$DefaultInterest = "General enquiry") {
@"
  <div class="modal" id="enquiry-modal" role="dialog" aria-modal="true" aria-labelledby="enquiry-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog modal__dialog--lg">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <h2 id="enquiry-title">Request Pricing</h2>
      <p>Tell us what you're planning. We'll get back with a personalised quote — no online payment required.</p>
      <form data-enquiry-form data-success-modal="success-modal" novalidate>
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
            <label for="enq-interest">Interest</label>
            <input class="form-control" id="enq-interest" name="interest" type="text" value="$DefaultInterest" data-prefill="interest" />
          </div>
          <div class="form-group" style="grid-column:1/-1">
            <label for="enq-message">Tell us more</label>
            <textarea class="form-control" id="enq-message" name="message" rows="4" placeholder="Destination, dates, travellers, preferences..."></textarea>
          </div>
        </div>
        <div class="btn-group">
          <button class="btn btn--primary" type="submit">Submit Enquiry</button>
          <button class="btn btn--secondary" type="button" data-close-modal>Cancel</button>
        </div>
      </form>
    </div>
  </div>
"@
}
