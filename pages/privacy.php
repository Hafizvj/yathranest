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

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <h1><?= e($content['title'] ?? 'Privacy Policy') ?></h1>
      <p><?= e($sections['intro'] ?? '') ?></p>
    </div>
  </section>
  <section class="section">
    <div class="container prose">
      <?= $sections['body'] ?? '' ?>
    </div>
  </section>
</main>
<?php require dirname(__DIR__) . '/includes/layout-footer.php'; ?>
