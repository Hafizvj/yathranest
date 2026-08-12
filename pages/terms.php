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

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero">
    <div class="container page-hero__inner">
      <h1><?= e($content['title'] ?? 'Terms & Conditions') ?></h1>
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
