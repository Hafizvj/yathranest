<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$keys = [
    'home' => 'Homepage',
    'about' => 'About',
    'faq' => 'FAQ',
    'terms' => 'Terms',
    'privacy' => 'Privacy',
];

$key = get_query('key', 'about');
if (!isset($keys[$key])) {
    $key = 'about';
}

$row = page_content($key);
$sections = $row['sections'] ?? ['intro' => '', 'body' => ''];
if (!is_array($sections)) {
    $sections = ['intro' => '', 'body' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/content/index.php?key=' . rawurlencode($key));
    }
    $title = post('title');
    $newSections = [
        'intro' => post('intro'),
        'body' => post('body'),
        'hero_image' => post('hero_image'),
    ];
    if ($key === 'faq') {
        $faqs = [];
        $qs = $_POST['faq_q'] ?? [];
        $as = $_POST['faq_a'] ?? [];
        if (is_array($qs)) {
            foreach ($qs as $i => $q) {
                $q = trim((string) $q);
                $a = trim((string) ($as[$i] ?? ''));
                if ($q !== '' || $a !== '') {
                    $faqs[] = ['q' => $q, 'a' => $a];
                }
            }
        }
        $newSections['faqs'] = $faqs;
    }
    if ($key === 'home') {
        $newSections['hero_title'] = post('hero_title');
        $newSections['hero_text'] = post('hero_text');
        $newSections['cta_text'] = post('cta_text');
    }
    page_content_save($key, $title, $newSections);
    flash_set('success', 'Content saved.');
    redirect('admin/content/index.php?key=' . rawurlencode($key));
}

ob_start();
?>
<div class="admin-toolbar">
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
    <?php foreach ($keys as $k => $label): ?>
      <a class="btn btn--<?= $k === $key ? 'primary' : 'secondary' ?> btn--sm" href="<?= e(url('admin/content/index.php?key=' . $k)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<div class="admin-panel">
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group full"><label>Page title</label><input class="form-control" name="title" value="<?= e($row['title'] ?? $keys[$key]) ?>" /></div>
      <?php if ($key === 'home'): ?>
        <div class="form-group full"><label>Hero title</label><input class="form-control" name="hero_title" value="<?= e($sections['hero_title'] ?? '') ?>" /></div>
        <div class="form-group full"><label>Hero text</label><textarea class="form-control" name="hero_text"><?= e($sections['hero_text'] ?? '') ?></textarea></div>
        <div class="form-group full"><label>CTA text</label><input class="form-control" name="cta_text" value="<?= e($sections['cta_text'] ?? '') ?>" /></div>
      <?php endif; ?>
      <div class="form-group full"><label>Intro</label><textarea class="form-control" name="intro"><?= e($sections['intro'] ?? '') ?></textarea></div>
      <div class="form-group full"><label>Body (HTML allowed)</label><textarea class="form-control" name="body" rows="10"><?= e($sections['body'] ?? '') ?></textarea></div>
      <div class="form-group full"><label>Hero image path</label><input class="form-control" name="hero_image" value="<?= e($sections['hero_image'] ?? '') ?>" /></div>
      <?php if ($key === 'faq'):
        $faqs = $sections['faqs'] ?? [['q' => '', 'a' => '']];
        if (!$faqs) {
            $faqs = [['q' => '', 'a' => '']];
        }
        ?>
        <div class="form-group full"><h3>FAQ items</h3></div>
        <?php foreach ($faqs as $i => $faq): ?>
          <div class="form-group full"><label>Question</label><input class="form-control" name="faq_q[]" value="<?= e($faq['q'] ?? '') ?>" /></div>
          <div class="form-group full"><label>Answer</label><textarea class="form-control" name="faq_a[]"><?= e($faq['a'] ?? '') ?></textarea></div>
        <?php endforeach; ?>
        <div class="form-group full"><label>Question (new)</label><input class="form-control" name="faq_q[]" value="" /></div>
        <div class="form-group full"><label>Answer (new)</label><textarea class="form-control" name="faq_a[]"></textarea></div>
      <?php endif; ?>
    </div>
    <button class="btn btn--primary" type="submit" style="margin-top:1rem">Save</button>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Page content — ' . $keys[$key];
$activeNav = 'content';
require dirname(__DIR__) . '/_layout.php';
