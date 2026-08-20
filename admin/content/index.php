<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
require_admin();

$keys = [
    'home' => [
        'label' => 'Homepage',
        'desc' => 'Hero visual, service cards and homepage copy',
        'view' => 'index.php',
    ],
    'about' => [
        'label' => 'About',
        'desc' => 'About page text and hero image',
        'view' => 'pages/about.php',
    ],
    'faq' => [
        'label' => 'FAQ',
        'desc' => 'Questions and answers shown on the FAQ page',
        'view' => 'pages/faq.php',
    ],
    'terms' => [
        'label' => 'Terms',
        'desc' => 'Terms of use page content',
        'view' => 'pages/terms.php',
    ],
    'privacy' => [
        'label' => 'Privacy',
        'desc' => 'Privacy policy page content',
        'view' => 'pages/privacy.php',
    ],
];

$homeHeroCardDefaults = [
    [
        'title' => 'Kerala Tour Package',
        'desc' => 'Experience God’s Own Country.',
        'href' => 'pages/kerala-packages.php',
        'image' => 'hills-mist.jpg',
    ],
    [
        'title' => 'South Indian Packages',
        'desc' => 'Temples, hills and coastal escapes.',
        'href' => 'pages/south-indian-packages.php',
        'image' => 'temple.jpg',
    ],
    [
        'title' => 'Domestic Packages',
        'desc' => 'Incredible places across India.',
        'href' => 'pages/domestic-packages.php',
        'image' => 'lake.jpg',
    ],
    [
        'title' => 'International Package',
        'desc' => 'Explore the world beyond borders.',
        'href' => 'pages/international-packages.php',
        'image' => 'dubai.jpg',
    ],
    [
        'title' => 'Taxi Cab Booking',
        'desc' => 'Reliable rides, anytime, anywhere.',
        'href' => 'pages/taxi-booking.php',
        'image' => 'car-taxi.jpg',
    ],
    [
        'title' => 'Resort Stay Booking',
        'desc' => 'Stay. Relax. Rejuvenate.',
        'href' => 'pages/resort-booking.php',
        'image' => 'resort-pool.jpg',
    ],
    [
        'title' => 'Weekend Getaways',
        'desc' => 'Travel. Connect. Create memories.',
        'href' => 'pages/weekend-getaways.php',
        'image' => 'friends-travel.jpg',
    ],
    [
        'title' => 'Gift Card',
        'desc' => 'Give the gift of travel.',
        'href' => 'pages/gift-cards.php',
        'image' => 'gift.jpg',
    ],
    [
        'title' => 'Investment Plans',
        'desc' => 'Grow with YathraNest options.',
        'href' => 'pages/investment-plans.php',
        'image' => 'city.jpg',
    ],
];

$key = get_query('key', 'home');
if (!isset($keys[$key])) {
    $key = 'home';
}
$pageMeta = $keys[$key];

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

        $oldVisual = (string) ($sections['hero_visual'] ?? $sections['hero_image'] ?? '');
        $heroVisual = post('hero_visual');
        if (!empty($_FILES['hero_visual_file']['name'])) {
            $uploaded = admin_apply_image_upload($_FILES['hero_visual_file'], 'home', $oldVisual);
            if ($uploaded) {
                $heroVisual = $uploaded;
            } elseif (admin_upload_last_error()) {
                $errors[] = admin_upload_last_error();
            }
        } elseif (isset($_POST['remove_hero_visual']) && $_POST['remove_hero_visual'] === '1') {
            admin_delete_upload($oldVisual);
            $heroVisual = '';
        }
        $newSections['hero_visual'] = $heroVisual !== '' ? $heroVisual : 'maldives.jpg';
        $newSections['hero_image'] = $newSections['hero_visual'];

        $existingCards = $sections['hero_cards'] ?? [];
        if (!is_array($existingCards)) {
            $existingCards = [];
        }
        $cardTitles = $_POST['hero_card_title'] ?? [];
        $cardDescs = $_POST['hero_card_desc'] ?? [];
        $cardHrefs = $_POST['hero_card_href'] ?? [];
        $cardImages = $_POST['hero_card_image'] ?? [];
        $cardFiles = $_FILES['hero_card_file'] ?? null;
        $heroCards = [];
        foreach ($homeHeroCardDefaults as $i => $defaults) {
            $oldImage = (string) ($existingCards[$i]['image'] ?? $defaults['image']);
            $image = trim((string) ($cardImages[$i] ?? $oldImage));
            if ($image === '') {
                $image = $defaults['image'];
            }
            if (is_array($cardFiles) && !empty($cardFiles['name'][$i])) {
                $file = [
                    'name' => $cardFiles['name'][$i] ?? '',
                    'type' => $cardFiles['type'][$i] ?? '',
                    'tmp_name' => $cardFiles['tmp_name'][$i] ?? '',
                    'error' => $cardFiles['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $cardFiles['size'][$i] ?? 0,
                ];
                $uploaded = admin_apply_image_upload($file, 'home', $oldImage);
                if ($uploaded) {
                    $image = $uploaded;
                } elseif (admin_upload_last_error()) {
                    $errors[] = 'Hero card ' . ($i + 1) . ': ' . admin_upload_last_error();
                }
            } elseif (isset($_POST['remove_hero_card'][$i]) && $_POST['remove_hero_card'][$i] === '1') {
                admin_delete_upload($oldImage);
                $image = $defaults['image'];
            }
            $heroCards[] = [
                'title' => trim((string) ($cardTitles[$i] ?? $defaults['title'])) ?: $defaults['title'],
                'desc' => trim((string) ($cardDescs[$i] ?? $defaults['desc'])) ?: $defaults['desc'],
                'href' => trim((string) ($cardHrefs[$i] ?? $defaults['href'])) ?: $defaults['href'],
                'image' => $image,
            ];
        }
        $newSections['hero_cards'] = $heroCards;
    }

    if (!$errors) {
        page_content_save($key, $title, $newSections);
        flash_set('success', 'Content saved.');
        redirect('admin/content/index.php?key=' . rawurlencode($key));
    }

    $sections = $newSections;
    $row['title'] = $title;
}

$homeHeroCards = $homeHeroCardDefaults;
if ($key === 'home') {
    $savedCards = $sections['hero_cards'] ?? [];
    if (is_array($savedCards)) {
        foreach ($homeHeroCardDefaults as $i => $defaults) {
            if (!empty($savedCards[$i]) && is_array($savedCards[$i])) {
                $homeHeroCards[$i] = array_merge($defaults, $savedCards[$i]);
            }
        }
    }
}
$homeHeroVisual = (string) ($sections['hero_visual'] ?? $sections['hero_image'] ?? 'maldives.jpg');
if ($homeHeroVisual === '') {
    $homeHeroVisual = 'maldives.jpg';
}

$faqs = $sections['faqs'] ?? [['q' => '', 'a' => '']];
if ($key === 'faq' && !$faqs) {
    $faqs = [['q' => '', 'a' => '']];
}

ob_start();
?>
<div class="content-shell">
  <header class="content-switcher admin-panel">
    <div class="content-switcher__intro">
      <p class="content-switcher__eyebrow">Site pages</p>
      <h2 class="content-switcher__title"><?= e($pageMeta['label']) ?></h2>
      <p class="content-switcher__desc"><?= e($pageMeta['desc']) ?></p>
    </div>
    <nav class="content-switcher__nav" aria-label="Choose page to edit">
      <?php foreach ($keys as $k => $meta): ?>
        <a
          class="content-switcher__tab<?= $k === $key ? ' is-active' : '' ?>"
          href="<?= e(url('admin/content/index.php?key=' . $k)) ?>"
          <?= $k === $key ? 'aria-current="page"' : '' ?>
        >
          <strong><?= e($meta['label']) ?></strong>
          <span><?= e($meta['desc']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="content-switcher__actions">
      <a class="btn btn--secondary btn--sm" href="<?= e(url($pageMeta['view'])) ?>" target="_blank" rel="noopener">View live page</a>
    </div>
  </header>

  <?php if ($key === 'home'): ?>
    <nav class="content-jump" aria-label="Jump to homepage section">
      <a href="#content-basics">Copy</a>
      <a href="#content-hero-visual">Hero visual</a>
      <a href="#content-hero-cards">Hero cards</a>
    </nav>
  <?php elseif ($key === 'faq'): ?>
    <nav class="content-jump" aria-label="Jump to FAQ section">
      <a href="#content-basics">Page copy</a>
      <a href="#content-faq">FAQ items</a>
    </nav>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="content-form">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <section class="admin-panel content-panel" id="content-basics">
      <div class="content-panel__head">
        <div>
          <h2 class="content-panel__title">Page copy</h2>
          <p class="content-panel__hint">Text shown on this page. Leave unused fields blank if not needed.</p>
        </div>
      </div>
      <div class="form-grid">
        <div class="form-group full">
          <label for="title">Page title</label>
          <input class="form-control" id="title" name="title" value="<?= e($row['title'] ?? $pageMeta['label']) ?>" />
        </div>
        <?php if ($key === 'home'): ?>
          <div class="form-group full">
            <label for="hero_title">Hero headline override</label>
            <input class="form-control" id="hero_title" name="hero_title" value="<?= e($sections['hero_title'] ?? '') ?>" placeholder="Leave blank to keep the default two-line headline" />
          </div>
          <div class="form-group full">
            <label for="hero_text">Hero supporting text</label>
            <textarea class="form-control" id="hero_text" name="hero_text" rows="3"><?= e($sections['hero_text'] ?? '') ?></textarea>
          </div>
          <div class="form-group full">
            <label for="cta_text">CTA text</label>
            <input class="form-control" id="cta_text" name="cta_text" value="<?= e($sections['cta_text'] ?? '') ?>" />
          </div>
        <?php endif; ?>
        <?php if ($key !== 'home'): ?>
          <div class="form-group full">
            <label for="intro">Intro</label>
            <textarea class="form-control" id="intro" name="intro" rows="3"><?= e($sections['intro'] ?? '') ?></textarea>
          </div>
          <div class="form-group full">
            <label for="body">Body<?= $key === 'terms' || $key === 'privacy' || $key === 'about' ? ' (HTML allowed)' : '' ?></label>
            <textarea class="form-control" id="body" name="body" rows="<?= $key === 'faq' ? '4' : '10' ?>"><?= e($sections['body'] ?? '') ?></textarea>
          </div>
        <?php else: ?>
          <input type="hidden" name="intro" value="<?= e($sections['intro'] ?? '') ?>" />
          <input type="hidden" name="body" value="<?= e($sections['body'] ?? '') ?>" />
        <?php endif; ?>
      </div>
    </section>

    <?php if ($key === 'home'): ?>
      <section class="admin-panel content-panel" id="content-hero-visual">
        <div class="content-panel__head">
          <div>
            <h2 class="content-panel__title">Hero visual</h2>
            <p class="content-panel__hint">Large image behind the homepage hero (<code>hero-v2__visual</code>). JPG, PNG, WEBP or GIF up to 5 MB.</p>
          </div>
        </div>
        <div class="content-media">
          <div class="content-media__preview">
            <?= admin_hero_preview($homeHeroVisual) ?>
            <div class="media-live-preview" hidden>
              <div class="media-preview">
                <div class="media-preview__item media-preview__item--hero">
                  <img id="home-visual-preview" alt="New visual preview" hidden />
                </div>
              </div>
            </div>
          </div>
          <div class="content-media__controls">
            <div class="media-drop">
              <label for="hero_visual_file">Upload new image</label>
              <input class="form-control" id="hero_visual_file" type="file" name="hero_visual_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#home-visual-preview" />
            </div>
            <details class="content-advanced">
              <summary>Advanced path</summary>
              <label for="hero_visual">Stored path</label>
              <input class="form-control" id="hero_visual" name="hero_visual" value="<?= e($homeHeroVisual) ?>" placeholder="maldives.jpg" />
            </details>
            <?php if ($homeHeroVisual !== '' && $homeHeroVisual !== 'maldives.jpg'): ?>
              <label class="checks"><input type="checkbox" name="remove_hero_visual" value="1" /> Reset to default Maldives image</label>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="admin-panel content-panel" id="content-hero-cards">
        <div class="content-panel__head">
          <div>
            <h2 class="content-panel__title">Hero cards</h2>
            <p class="content-panel__hint">Six service cards in the homepage hero grid. Upload an image or edit title, description and link for each.</p>
          </div>
          <span class="badge badge--published"><?= count($homeHeroCards) ?> cards</span>
        </div>
        <div class="content-card-grid">
          <?php foreach ($homeHeroCards as $i => $card): ?>
            <article class="content-card-editor">
              <header class="content-card-editor__head">
                <span class="content-card-editor__index"><?= $i + 1 ?></span>
                <h3><?= e($card['title']) ?></h3>
              </header>
              <div class="content-card-editor__body">
                <div class="content-card-editor__preview">
                  <?= admin_hero_preview($card['image'] ?? null) ?>
                  <div class="media-live-preview" hidden>
                    <div class="media-preview">
                      <div class="media-preview__item media-preview__item--hero">
                        <img id="home-card-preview-<?= $i ?>" alt="" hidden />
                      </div>
                    </div>
                  </div>
                  <div class="media-drop">
                    <label for="hero_card_file_<?= $i ?>">Replace image</label>
                    <input class="form-control" id="hero_card_file_<?= $i ?>" type="file" name="hero_card_file[<?= $i ?>]" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#home-card-preview-<?= $i ?>" />
                  </div>
                  <?php if (!empty($card['image']) && $card['image'] !== ($homeHeroCardDefaults[$i]['image'] ?? '')): ?>
                    <label class="checks"><input type="checkbox" name="remove_hero_card[<?= $i ?>]" value="1" /> Reset image</label>
                  <?php endif; ?>
                </div>
                <div class="content-card-editor__fields">
                  <div class="form-group">
                    <label for="hero_card_title_<?= $i ?>">Title</label>
                    <input class="form-control" id="hero_card_title_<?= $i ?>" name="hero_card_title[<?= $i ?>]" value="<?= e($card['title']) ?>" />
                  </div>
                  <div class="form-group">
                    <label for="hero_card_desc_<?= $i ?>">Description</label>
                    <input class="form-control" id="hero_card_desc_<?= $i ?>" name="hero_card_desc[<?= $i ?>]" value="<?= e($card['desc']) ?>" />
                  </div>
                  <div class="form-group">
                    <label for="hero_card_href_<?= $i ?>">Link</label>
                    <input class="form-control" id="hero_card_href_<?= $i ?>" name="hero_card_href[<?= $i ?>]" value="<?= e($card['href']) ?>" />
                  </div>
                  <details class="content-advanced">
                    <summary>Advanced path</summary>
                    <label for="hero_card_image_<?= $i ?>">Stored path</label>
                    <input class="form-control" id="hero_card_image_<?= $i ?>" name="hero_card_image[<?= $i ?>]" value="<?= e($card['image']) ?>" />
                  </details>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php elseif ($key !== 'faq'): ?>
      <section class="admin-panel content-panel" id="content-hero">
        <div class="content-panel__head">
          <div>
            <h2 class="content-panel__title">Hero image</h2>
            <p class="content-panel__hint">Optional banner image for this page. JPG, PNG, WEBP or GIF up to 5 MB.</p>
          </div>
        </div>
        <div class="content-media">
          <div class="content-media__preview">
            <?= admin_hero_preview($sections['hero_image'] ?? null) ?>
            <div class="media-live-preview" hidden>
              <div class="media-preview">
                <div class="media-preview__item media-preview__item--hero">
                  <img id="content-hero-preview" alt="New hero preview" hidden />
                </div>
              </div>
            </div>
          </div>
          <div class="content-media__controls">
            <div class="media-drop">
              <label for="hero_image_file">Upload hero image</label>
              <input class="form-control" id="hero_image_file" type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#content-hero-preview" />
            </div>
            <details class="content-advanced">
              <summary>Advanced path</summary>
              <label for="hero_image">Stored path</label>
              <input class="form-control" id="hero_image" name="hero_image" value="<?= e($sections['hero_image'] ?? '') ?>" placeholder="friends-travel.jpg" />
            </details>
            <?php if (!empty($sections['hero_image'])): ?>
              <label class="checks"><input type="checkbox" name="remove_hero_image" value="1" /> Remove current hero image</label>
            <?php endif; ?>
          </div>
        </div>
      </section>
    <?php else: ?>
      <input type="hidden" name="hero_image" value="<?= e($sections['hero_image'] ?? '') ?>" />
    <?php endif; ?>

    <?php if ($key === 'faq'): ?>
      <section class="admin-panel content-panel" id="content-faq">
        <div class="content-panel__head">
          <div>
            <h2 class="content-panel__title">FAQ items</h2>
            <p class="content-panel__hint">Each pair is one question and answer. Use the empty row at the bottom to add another.</p>
          </div>
          <span class="badge badge--published"><?= count(array_filter($faqs, static fn($f) => trim((string) ($f['q'] ?? '')) !== '' || trim((string) ($f['a'] ?? '')) !== '')) ?> items</span>
        </div>
        <div class="content-faq-list">
          <?php foreach ($faqs as $i => $faq): ?>
            <article class="content-faq-item">
              <span class="content-faq-item__index">Q<?= $i + 1 ?></span>
              <div class="form-grid">
                <div class="form-group full">
                  <label for="faq_q_<?= $i ?>">Question</label>
                  <input class="form-control" id="faq_q_<?= $i ?>" name="faq_q[]" value="<?= e($faq['q'] ?? '') ?>" />
                </div>
                <div class="form-group full">
                  <label for="faq_a_<?= $i ?>">Answer</label>
                  <textarea class="form-control" id="faq_a_<?= $i ?>" name="faq_a[]" rows="3"><?= e($faq['a'] ?? '') ?></textarea>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
          <article class="content-faq-item content-faq-item--new">
            <span class="content-faq-item__index">New</span>
            <div class="form-grid">
              <div class="form-group full">
                <label for="faq_q_new">Question</label>
                <input class="form-control" id="faq_q_new" name="faq_q[]" value="" placeholder="Add another question" />
              </div>
              <div class="form-group full">
                <label for="faq_a_new">Answer</label>
                <textarea class="form-control" id="faq_a_new" name="faq_a[]" rows="3" placeholder="Add the answer"></textarea>
              </div>
            </div>
          </article>
        </div>
      </section>
    <?php endif; ?>

    <div class="form-actions content-form__actions">
      <button class="btn btn--primary" type="submit">Save <?= e(strtolower($pageMeta['label'])) ?></button>
      <a class="btn btn--secondary" href="<?= e(url($pageMeta['view'])) ?>" target="_blank" rel="noopener">Preview</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Page content — ' . $pageMeta['label'];
$activeNav = 'content';
require dirname(__DIR__) . '/_layout.php';
