<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$key = 'terms';
$content = null;
try {
    $content = page_content($key);
} catch (Throwable $e) {
}
$sections = $content['sections'] ?? [];
$pageTitle = ($content['title'] ?? 'Terms') . ' | YathraNest';
$metaDescription = $sections['intro'] ?? 'Terms and conditions.';
$enquiryType = 'general';
$enquiryInterest = 'Terms';
$enquirySource = 'pages/terms.php';
$updated = $content['updated_at'] ?? '';

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head">
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Terms & Conditions' => null]) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Legal</p>
        <h1><?= e($content['title'] ?? 'Terms & Conditions') ?></h1>
        <?php if (!empty($sections['intro'])): ?>
          <p class="page-head__lead"><?= e($sections['intro']) ?></p>
        <?php endif; ?>
        <?php if ($updated !== ''): ?>
          <div class="page-head__chips"><?= yn_chip('calendar', 'Updated ' . date('j M Y', strtotime((string) $updated))) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="article-layout">
        <div class="panel">
          <div class="prose">
            <?= $sections['body'] ?? '<p>Our terms are being updated. Please contact us for any clarification in the meantime.</p>' ?>
          </div>
        </div>
        <aside class="article-layout__aside">
          <div class="panel">
            <h3 style="margin-bottom:0.5rem">Questions on these terms?</h3>
            <p class="text-muted" style="font-size:0.9375rem">Our team can clarify anything about bookings, changes or cancellations.</p>
            <div class="btn-group">
              <a class="btn btn--secondary btn--block" href="contact.php">Contact Us</a>
            </div>
          </div>
          <div class="panel" style="margin-top:1rem">
            <div class="info-list">
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('shield') ?></span>
                <span>
                  <span class="info-row__label">Related</span>
                  <span class="info-row__value"><a href="privacy.php">Privacy Policy</a></span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('info') ?></span>
                <span>
                  <span class="info-row__label">Related</span>
                  <span class="info-row__value"><a href="faq.php">FAQ</a></span>
                </span>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
