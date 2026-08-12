<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$assetDepth = '../';
$content = null;
try {
    $content = page_content('about');
} catch (Throwable $e) {
}
$sections = $content['sections'] ?? [];
$pageTitle = ($content['title'] ?? 'About') . ' | YathraNest';
$metaDescription = $sections['intro'] ?? 'About YathraNest.';
$enquiryType = 'general';
$enquiryInterest = 'About enquiry';
$enquirySource = 'pages/about.php';
$hero = $sections['hero_image'] ?? 'friends-travel.jpg';

require dirname(__DIR__) . '/includes/layout-header.php';
?>
<main id="main">
  <section class="page-hero page-hero--media" style="background-image:url('../assets/images/<?= e($hero) ?>')">
    <div class="container page-hero__inner">
      <h1><?= e($content['title'] ?? 'About YathraNest') ?></h1>
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
