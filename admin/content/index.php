<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
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

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/content/index.php?key=' . rawurlencode($key));
    }
    $title = post('title');
    $oldHero = (string) ($sections['hero_image'] ?? '');
    $heroImage = post('hero_image');
    if (!empty($_FILES['hero_image_file']['name'])) {
        $uploaded = admin_apply_image_upload($_FILES['hero_image_file'], 'content', $oldHero);
        if ($uploaded) {
            $heroImage = $uploaded;
        } elseif (admin_upload_last_error()) {
            $errors[] = admin_upload_last_error();
        }
    } elseif (isset($_POST['remove_hero_image']) && $_POST['remove_hero_image'] === '1') {
        admin_delete_upload($oldHero);
        $heroImage = '';
    }

    $newSections = [
        'intro' => post('intro'),
        'body' => post('body'),
        'hero_image' => $heroImage,
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

    if (!$errors) {
        page_content_save($key, $title, $newSections);
        flash_set('success', 'Content saved.');
        redirect('admin/content/index.php?key=' . rawurlencode($key));
    }

    $sections = $newSections;
    $row['title'] = $title;
}

ob_start();
?>
<div class="admin-toolbar">
  <div class="admin-tabs">
    <?php foreach ($keys as $k => $label): ?>
      <a class="btn btn--<?= $k === $key ? 'primary' : 'secondary' ?> btn--sm" href="<?= e(url('admin/content/index.php?key=' . $k)) ?>" <?= $k === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <div class="form-grid">
      <section class="form-section">
        <h2 class="form-section__title">Page copy</h2>
        <div class="form-group full">
          <label for="title">Page title</label>
          <input class="form-control" id="title" name="title" value="<?= e($row['title'] ?? $keys[$key]) ?>" />
        </div>
        <?php if ($key === 'home'): ?>
          <div class="form-group full">
            <label for="hero_title">Hero title</label>
            <input class="form-control" id="hero_title" name="hero_title" value="<?= e($sections['hero_title'] ?? '') ?>" />
          </div>
          <div class="form-group full">
            <label for="hero_text">Hero text</label>
            <textarea class="form-control" id="hero_text" name="hero_text"><?= e($sections['hero_text'] ?? '') ?></textarea>
          </div>
          <div class="form-group full">
            <label for="cta_text">CTA text</label>
            <input class="form-control" id="cta_text" name="cta_text" value="<?= e($sections['cta_text'] ?? '') ?>" />
          </div>
        <?php endif; ?>
        <div class="form-group full">
          <label for="intro">Intro</label>
          <textarea class="form-control" id="intro" name="intro"><?= e($sections['intro'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label for="body">Body (HTML allowed)</label>
          <textarea class="form-control" id="body" name="body" rows="10"><?= e($sections['body'] ?? '') ?></textarea>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Hero image</h2>
        <p class="form-section__hint">Used on pages that show a CMS hero (for example About). Upload JPG, PNG, WEBP or GIF up to 5 MB.</p>
        <div class="form-group full media-field">
          <?= admin_hero_preview($sections['hero_image'] ?? null) ?>
          <div class="media-live-preview" hidden>
            <div class="media-preview">
              <div class="media-preview__item media-preview__item--hero">
                <img id="content-hero-preview" alt="New hero preview" hidden />
              </div>
            </div>
          </div>
          <div class="media-drop">
            <label for="hero_image_file">Upload hero image</label>
            <input class="form-control" id="hero_image_file" type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#content-hero-preview" />
          </div>
          <label for="hero_image">Or image path</label>
          <input class="form-control" id="hero_image" name="hero_image" value="<?= e($sections['hero_image'] ?? '') ?>" placeholder="friends-travel.jpg" />
          <?php if (!empty($sections['hero_image'])): ?>
            <label class="checks"><input type="checkbox" name="remove_hero_image" value="1" /> Remove current hero image</label>
          <?php endif; ?>
        </div>
      </section>

      <?php if ($key === 'faq'):
        $faqs = $sections['faqs'] ?? [['q' => '', 'a' => '']];
        if (!$faqs) {
            $faqs = [['q' => '', 'a' => '']];
        }
        ?>
        <section class="form-section">
          <h2 class="form-section__title">FAQ items</h2>
          <?php foreach ($faqs as $i => $faq): ?>
            <div class="form-group full">
              <label for="faq_q_<?= $i ?>">Question</label>
              <input class="form-control" id="faq_q_<?= $i ?>" name="faq_q[]" value="<?= e($faq['q'] ?? '') ?>" />
            </div>
            <div class="form-group full">
              <label for="faq_a_<?= $i ?>">Answer</label>
              <textarea class="form-control" id="faq_a_<?= $i ?>" name="faq_a[]"><?= e($faq['a'] ?? '') ?></textarea>
            </div>
          <?php endforeach; ?>
          <div class="form-group full">
            <label for="faq_q_new">Question (new)</label>
            <input class="form-control" id="faq_q_new" name="faq_q[]" value="" />
          </div>
          <div class="form-group full">
            <label for="faq_a_new">Answer (new)</label>
            <textarea class="form-control" id="faq_a_new" name="faq_a[]"></textarea>
          </div>
        </section>
      <?php endif; ?>
    </div>
    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save content</button>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Page content — ' . $keys[$key];
$activeNav = 'content';
require dirname(__DIR__) . '/_layout.php';
