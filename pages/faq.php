<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$content = null;
try {
    $content = page_content('faq');
} catch (Throwable $e) {
}
$sections = $content['sections'] ?? [];
$faqs = $sections['faqs'] ?? [];
$pageTitle = ($content['title'] ?? 'FAQ') . ' | YathraNest';
$metaDescription = $sections['intro'] ?? 'Frequently asked questions.';
$enquiryType = 'general';
$enquiryInterest = 'FAQ enquiry';
$enquirySource = 'pages/faq.php';

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <h1><?= e($content['title'] ?? 'FAQ') ?></h1>
      <p><?= e($sections['intro'] ?? '') ?></p>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="accordion">
        <?php foreach ($faqs as $i => $faq): ?>
          <div class="accordion__item<?= $i === 0 ? ' is-open' : '' ?>">
            <button class="accordion__trigger" type="button" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>">
              <span><?= e($faq['q'] ?? '') ?></span>
              <span class="accordion__icon" aria-hidden="true">+</span>
            </button>
            <div class="accordion__panel"><p><?= e($faq['a'] ?? '') ?></p></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($sections['body'])): ?>
        <div class="prose" style="margin-top:2rem"><?= $sections['body'] ?></div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
