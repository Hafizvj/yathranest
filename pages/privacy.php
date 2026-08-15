<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$content = null;
try {
    $content = page_content('privacy');
} catch (Throwable $e) {
}
$sections = $content['sections'] ?? [];
$pageTitle = ($content['title'] ?? 'Privacy') . ' | YathraNest';
$metaDescription = $sections['intro'] ?? 'Privacy policy.';
$enquiryType = 'general';
$enquiryInterest = 'Privacy';
$enquirySource = 'pages/privacy.php';
$updated = $content['updated_at'] ?? '';

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head">
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'Privacy Policy' => null]) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Legal</p>
        <h1><?= e($content['title'] ?? 'Privacy Policy') ?></h1>
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
            <?= $sections['body'] ?? '<p>Our privacy policy is being updated. Contact us if you have any questions about how we handle your details.</p>' ?>
          </div>
        </div>
        <aside class="article-layout__aside">
          <div class="panel">
            <h3 style="margin-bottom:0.5rem">How we use your details</h3>
            <div class="info-list">
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('mail') ?></span>
                <span>
                  <span class="info-row__label">Enquiries only</span>
                  <span class="info-row__value">To respond with quotes</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('shield') ?></span>
                <span>
                  <span class="info-row__label">Never sold</span>
                  <span class="info-row__value">No third-party marketing</span>
                </span>
              </div>
              <div class="info-row">
                <span class="info-row__icon"><?= yn_icon('wallet') ?></span>
                <span>
                  <span class="info-row__label">No card data</span>
                  <span class="info-row__value">We take no online payments</span>
                </span>
              </div>
            </div>
            <div class="btn-group">
              <a class="btn btn--secondary btn--block" href="contact.php">Contact Us</a>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
