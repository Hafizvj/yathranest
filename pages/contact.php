<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$pageTitle = 'Contact | YathraNest';
$metaDescription = 'Contact YathraNest.';
$enquiryType = 'contact';
$enquiryInterest = 'General contact';
$enquirySource = 'pages/contact.php';
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$whatsapp = preg_replace('/\D/', '', setting('whatsapp', '919876543210'));

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <p class="section-eyebrow">Contact</p>
      <h1>Contact YathraNest</h1>
      <p>Reach us by phone, email or WhatsApp — or send an enquiry form below.</p>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="grid-2" style="align-items:start">
        <div>
          <h2 class="mb-2">Talk to us</h2>
          <div class="footer-contact" style="color:var(--text);gap:0.75rem">
            <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= e($phone) ?></a>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
            <a href="https://wa.me/<?= e($whatsapp) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
          </div>
          <?php if (setting('address')): ?>
            <p class="mt-2"><?= e(setting('address')) ?></p>
          <?php endif; ?>
        </div>
        <div class="form-panel">
          <h2 style="margin-bottom:1rem">Send an enquiry</h2>
          <form data-enquiry-form data-success-modal="success-modal" action="../handlers/enquiry.php" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="contact" />
            <input type="hidden" name="source_page" value="pages/contact.php" />
            <input type="hidden" name="interest" value="General contact" />
            <div class="form-grid">
              <div class="form-group"><label for="c-name">Full name</label><input class="form-control" id="c-name" name="name" type="text" required /><span class="field-error"></span></div>
              <div class="form-group"><label for="c-phone">Phone</label><input class="form-control" id="c-phone" name="phone" type="tel" required data-validate="phone" /><span class="field-error"></span></div>
              <div class="form-group"><label for="c-email">Email</label><input class="form-control" id="c-email" name="email" type="email" required /><span class="field-error"></span></div>
              <div class="form-group"><label for="c-msg">Message</label><textarea class="form-control" id="c-msg" name="message" rows="4" required></textarea><span class="field-error"></span></div>
            </div>
            <div class="btn-group"><button class="btn btn--primary" type="submit">Send Message</button></div>
          </form>
        </div>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
