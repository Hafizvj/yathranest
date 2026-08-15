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
$navActive = 'faq';

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-head">
    <div class="container page-head__inner">
      <?= yn_crumbs(['Home' => '../index.php', 'FAQ' => null]) ?>
      <div class="page-head__body">
        <p class="page-head__eyebrow">Help centre</p>
        <h1><?= e($content['title'] ?? 'Frequently asked questions') ?></h1>
        <?php if (!empty($sections['intro'])): ?>
          <p class="page-head__lead"><?= e($sections['intro']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="article-layout">
        <div>
          <?php if (!$faqs): ?>
            <div class="empty-state">
              <div class="empty-state__icon"><?= yn_icon('chat') ?></div>
              <h2>Questions? Just ask</h2>
              <p>We're happy to explain how packages, stays and pricing work at YathraNest.</p>
              <div class="btn-group" style="justify-content:center">
                <a class="btn btn--primary" href="#enquiry" data-open-modal="enquiry-modal">Ask a question</a>
              </div>
            </div>
          <?php else: ?>
            <div class="accordion" data-accordion="single">
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
          <?php endif; ?>

          <?php if (!empty($sections['body'])): ?>
            <div class="prose" style="margin-top:2.5rem"><?= $sections['body'] ?></div>
          <?php endif; ?>
        </div>

        <aside class="article-layout__aside">
          <div class="quote-card">
            <p class="quote-card__eyebrow">Still unsure?</p>
            <h3>Talk to a planner</h3>
            <p>Anything not covered here — ask us directly and we'll walk you through it.</p>
            <div class="btn-group">
              <a class="btn btn--light" href="#enquiry" data-open-modal="enquiry-modal">Ask a question</a>
              <a class="btn btn--secondary quote-card__ghost" href="contact.php">Contact Us</a>
            </div>
            <p class="quote-card__note"><?= yn_icon('headset') ?>Usually replies same day</p>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
