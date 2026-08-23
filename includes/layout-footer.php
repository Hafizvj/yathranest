<?php
$assetDepth = $assetDepth ?? '';
$homeHref = $assetDepth === '' ? 'index.php' : '../index.php';
$pagesPrefix = $assetDepth === '' ? 'pages/' : '';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = setting('whatsapp', '919876543210');
$phoneTel = 'tel:' . preg_replace('/[^\d+]/', '', $phone);
$enquiryType = $enquiryType ?? 'general';
$enquiryInterest = $enquiryInterest ?? '';
$enquirySource = $enquirySource ?? '';
$handlerPath = ($assetDepth === '' ? '' : '../') . 'handlers/enquiry.php';
?>
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="logo" href="<?= e($homeHref) ?>" aria-label="YathraNest home">
            <img class="logo__badge" src="<?= e($assetDepth) ?>assets/logo/logo-mark.png" alt="" width="150" height="150" />
            <img class="logo__img logo__img--text" src="<?= e($assetDepth) ?>assets/logo/logo-text-light.png" alt="YathraNest" width="207" height="98" />
          </a>
          <p>Curated travel packages, stays, taxi services and unique experiences across India and beyond.</p>
          <div class="footer-contact">
            <a href="<?= e($phoneTel) ?>"><?= e($phone) ?></a>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <a href="https://wa.me/<?= e(preg_replace('/\D/', '', $whatsapp)) ?>" rel="noopener noreferrer" target="_blank">WhatsApp</a>
          </div>
          <div class="social-links" aria-label="Social media">
            <a href="<?= e(setting('social_instagram', '#')) ?>" aria-label="Instagram">IG</a>
            <a href="<?= e(setting('social_facebook', '#')) ?>" aria-label="Facebook">FB</a>
            <a href="<?= e(setting('social_youtube', '#')) ?>" aria-label="YouTube">YT</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Explore</h4>
          <ul>
            <li><a href="<?= e($pagesPrefix) ?>kerala-packages.php">Kerala Packages</a></li>
            <li><a href="<?= e($pagesPrefix) ?>south-indian-packages.php">South Indian Packages</a></li>
            <li><a href="<?= e($pagesPrefix) ?>domestic-packages.php">Domestic Packages</a></li>
            <li><a href="<?= e($pagesPrefix) ?>international-packages.php">International Packages</a></li>
            <li><a href="<?= e($pagesPrefix) ?>weekend-getaways.php">Weekend Getaways</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Services</h4>
          <ul>
            <li><a href="<?= e($pagesPrefix) ?>taxi-booking.php">Taxi Booking</a></li>
            <li><a href="<?= e($pagesPrefix) ?>resort-booking.php">Resort Stays</a></li>
            <li><a href="<?= e($pagesPrefix) ?>gift-cards.php">Gift Cards</a></li>
            <li><a href="<?= e($pagesPrefix) ?>investment-plans.php">Investment Plans</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Company</h4>
          <ul>
            <li><a href="<?= e($pagesPrefix) ?>about.php">About Us</a></li>
            <li><a href="<?= e($pagesPrefix) ?>contact.php">Contact</a></li>
            <li><a href="<?= e($pagesPrefix) ?>faq.php">FAQ</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>Legal</h4>
          <ul>
            <li><a href="<?= e($pagesPrefix) ?>terms.php">Terms &amp; Conditions</a></li>
            <li><a href="<?= e($pagesPrefix) ?>privacy.php">Privacy Policy</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; <span data-year><?= date('Y') ?></span> YathraNest. All rights reserved.</p>
        <p>Browse · Explore · Enquire — pricing shared personally.</p>
        <p class="footer-credit">Icons by <a href="https://icon-sets.iconify.design/solar/" rel="noopener noreferrer" target="_blank">Solar / 480 Design</a> via Iconify.</p>
      </div>
    </div>
  </footer>

  <div class="modal" id="enquiry-modal" role="dialog" aria-modal="true" aria-labelledby="enquiry-title">
    <div class="modal__backdrop"></div>
    <div class="modal__dialog modal__dialog--lg">
      <button class="modal__close" type="button" data-close-modal aria-label="Close">&times;</button>
      <h2 id="enquiry-title">Enquire Now</h2>
      <p>Share a few details and we'll continue the conversation on WhatsApp with a personalised quote — no online payment required.</p>
      <form data-enquiry-form data-success-modal="success-modal" action="<?= e($handlerPath) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= e($enquiryType) ?>" />
        <input type="hidden" name="source_page" value="<?= e($enquirySource !== '' ? $enquirySource : ($_SERVER['REQUEST_URI'] ?? '')) ?>" />
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
            <input class="form-control" id="enq-interest" name="interest" type="text" value="<?= e($enquiryInterest) ?>" data-prefill="interest" />
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
  <script src="<?= e($assetDepth) ?>js/navigation.js?v=14" defer></script>
  <script src="<?= e($assetDepth) ?>js/filters.js?v=11" defer></script>
  <script src="<?= e($assetDepth) ?>js/forms.js?v=12" defer></script>
  <script src="<?= e($assetDepth) ?>js/gallery.js?v=11" defer></script>
  <script src="<?= e($assetDepth) ?>js/main.js?v=11" defer></script>
  <script src="<?= e($assetDepth) ?>js/motion.js?v=14" defer></script>
  <?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
