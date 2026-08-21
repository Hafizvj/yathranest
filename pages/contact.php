<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Contact | YathraNest';
$metaDescription = 'Contact YathraNest — call, email or WhatsApp our travel desk for packages, stays and taxi bookings.';
$enquiryType = 'contact';
$enquiryInterest = 'General contact';
$enquirySource = 'pages/contact.php';
$navActive = 'contact';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = preg_replace('/\D/', '', setting('whatsapp', '919876543210'));
$address = setting('address', '');

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head">
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Contact' => null]) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Contact</p>
        <h1>Let's plan your next journey</h1>
        <p class="page-head__lead">Call, email or WhatsApp our travel desk — or send the form below and we'll get back with options and pricing.</p>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="tile-grid tile-grid--3" style="margin-bottom:2.5rem">
        <a class="tile" href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>" data-reveal>
          <span class="tile__icon"><?= yn_icon('phone') ?></span>
          <h3>Call us</h3>
          <p><?= e($phone) ?></p>
          <span class="link-arrow">Start a call</span>
        </a>
        <a class="tile" href="mailto:<?= e($email) ?>" data-reveal>
          <span class="tile__icon"><?= yn_icon('mail') ?></span>
          <h3>Email us</h3>
          <p><?= e($email) ?></p>
          <span class="link-arrow">Write to us</span>
        </a>
        <a class="tile" href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer" data-reveal>
          <span class="tile__icon"><?= yn_icon('chat') ?></span>
          <h3>WhatsApp</h3>
          <p>Quickest way to reach our travel desk.</p>
          <span class="link-arrow">Open chat</span>
        </a>
      </div>

      <div class="article-layout">
        <div class="form-card">
          <div class="form-card__head">
            <h2>Send an enquiry</h2>
            <p>Tell us what you're planning. We reply with options and personalised pricing — no online payment.</p>
          </div>
          <div class="form-card__body">
            <form data-enquiry-form data-success-modal="success-modal" action="../handlers/enquiry.php" method="post" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="type" value="contact" />
              <input type="hidden" name="source_page" value="pages/contact.php" />
              <input type="hidden" name="interest" value="General contact" />
              <div class="form-grid form-grid--2">
                <div class="form-group">
                  <label for="c-name">Full name</label>
                  <input class="form-control" id="c-name" name="name" type="text" required autocomplete="name" />
                  <span class="field-error"></span>
                </div>
                <div class="form-group">
                  <label for="c-phone">Phone</label>
                  <input class="form-control" id="c-phone" name="phone" type="tel" required data-validate="phone" autocomplete="tel" />
                  <span class="field-error"></span>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                  <label for="c-email">Email</label>
                  <input class="form-control" id="c-email" name="email" type="email" required autocomplete="email" />
                  <span class="field-error"></span>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                  <label for="c-msg">Message</label>
                  <textarea class="form-control" id="c-msg" name="message" rows="5" required placeholder="Destination, dates, travellers, preferences..."></textarea>
                  <span class="field-error"></span>
                </div>
              </div>
              <p class="form-note"><?= yn_icon('info') ?>We use your details only to respond to this enquiry.</p>
              <div class="btn-group">
                <button class="btn btn--primary" type="submit">Send Message</button>
              </div>
            </form>
          </div>
        </div>

        <aside class="article-layout__aside">
          <div class="panel">
            <h3 style="margin-bottom:0.5rem">Travel desk</h3>
            <div class="info-list">
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('clock') ?></span>
                <span>
                  <span class="info-row__label">Hours</span>
                  <span class="info-row__value">9:00 – 21:00, all week</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('headset') ?></span>
                <span>
                  <span class="info-row__label">On-trip support</span>
                  <span class="info-row__value">Available 24/7</span>
                </span>
              </div>
              <?php if ($address !== ''): ?>
                <div class="info-row">
                  <span class="info-row__icon"><?= yn_icon('pin') ?></span>
                  <span>
                    <span class="info-row__label">Office</span>
                    <span class="info-row__value"><?= e($address) ?></span>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="quote-card" style="margin-top:1rem">
            <p class="quote-card__eyebrow">Prefer to talk?</p>
            <h3>Get a call back</h3>
            <p>Send an enquiry with your number and a good time — we'll call you.</p>
            <div class="btn-group">
              <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Enquire Now</a>
            </div>
            <p class="quote-card__note"><?= yn_icon('shield') ?>Your details stay private</p>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
