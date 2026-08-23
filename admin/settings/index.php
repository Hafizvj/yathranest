<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/settings/index.php');
    }

    $payload = [
        'phone' => post('phone'),
        'email' => post('email'),
        'whatsapp' => post('whatsapp'),
        'address' => post('address'),
        'social_instagram' => post('social_instagram'),
        'social_facebook' => post('social_facebook'),
        'social_youtube' => post('social_youtube'),
        'logo_wordmark' => post('logo_wordmark'),
        'logo_mark' => post('logo_mark'),
        'favicon' => post('favicon'),
        'apple_touch_icon' => post('apple_touch_icon'),
    ];

    $brandFields = [
        'logo_wordmark_file' => ['key' => 'logo_wordmark', 'subdir' => 'brand'],
        'logo_mark_file' => ['key' => 'logo_mark', 'subdir' => 'brand'],
        'favicon_file' => ['key' => 'favicon', 'subdir' => 'brand'],
        'apple_touch_icon_file' => ['key' => 'apple_touch_icon', 'subdir' => 'brand'],
    ];
    foreach ($brandFields as $fileKey => $meta) {
        $current = setting($meta['key'], '');
        if (!empty($_FILES[$fileKey]['name'])) {
            $uploaded = admin_apply_image_upload($_FILES[$fileKey], $meta['subdir'], $current);
            if ($uploaded) {
                $payload[$meta['key']] = $uploaded;
            } elseif (admin_upload_last_error()) {
                $errors[] = admin_upload_last_error();
            }
        } elseif (isset($_POST['remove_' . $meta['key']]) && $_POST['remove_' . $meta['key']] === '1') {
            $payload[$meta['key']] = '';
        }
    }

    if (!$errors) {
        settings_save($payload);
        flash_set('success', 'Settings saved.');
        redirect('admin/settings/index.php');
    }
}

$logoWordmark = setting('logo_wordmark', 'assets/logo/logo-wordmark.png');
$logoMark = setting('logo_mark', 'assets/logo/logo-mark.png');
$favicon = setting('favicon', 'assets/logo/favicon-32.png');
$appleTouch = setting('apple_touch_icon', 'assets/logo/apple-touch-icon.png');

ob_start();
?>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>
    <div class="form-grid">
      <section class="form-section">
        <h2 class="form-section__title">Contact</h2>
        <div class="form-group">
          <label for="phone">Phone</label>
          <input class="form-control" id="phone" name="phone" value="<?= e(setting('phone', '+91 98765 43210')) ?>" />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input class="form-control" id="email" name="email" value="<?= e(setting('email', 'hello@yathranest.com')) ?>" />
        </div>
        <div class="form-group">
          <label for="whatsapp">WhatsApp (digits)</label>
          <input class="form-control" id="whatsapp" name="whatsapp" value="<?= e(setting('whatsapp', '919876543210')) ?>" />
        </div>
        <div class="form-group">
          <label for="address">Address</label>
          <input class="form-control" id="address" name="address" value="<?= e(setting('address')) ?>" />
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Social</h2>
        <div class="form-group">
          <label for="social_instagram">Instagram URL</label>
          <input class="form-control" id="social_instagram" name="social_instagram" value="<?= e(setting('social_instagram', '#')) ?>" />
        </div>
        <div class="form-group">
          <label for="social_facebook">Facebook URL</label>
          <input class="form-control" id="social_facebook" name="social_facebook" value="<?= e(setting('social_facebook', '#')) ?>" />
        </div>
        <div class="form-group full">
          <label for="social_youtube">YouTube URL</label>
          <input class="form-control" id="social_youtube" name="social_youtube" value="<?= e(setting('social_youtube', '#')) ?>" />
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Brand images</h2>
        <p class="form-section__hint">Upload logo and favicon files (PNG recommended). Leave path fields blank to fall back to the default assets.</p>

        <div class="form-group full media-field">
          <label>Wordmark logo</label>
          <?= admin_hero_preview($logoWordmark, '') ?>
          <div class="media-drop">
            <label for="logo_wordmark_file">Upload wordmark</label>
            <input class="form-control" id="logo_wordmark_file" type="file" name="logo_wordmark_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#logo-wordmark-preview" />
          </div>
          <div class="media-live-preview" hidden>
            <div class="media-preview"><div class="media-preview__item media-preview__item--hero"><img id="logo-wordmark-preview" alt="" hidden /></div></div>
          </div>
          <button class="btn btn--secondary btn--sm media-library-browse" type="button" data-open-media-picker data-media-mode="single" data-media-target="#logo_wordmark" data-media-preview="#logo-wordmark-preview">
            <?= yn_icon('image') ?> Choose from library
          </button>
          <label for="logo_wordmark">Or path</label>
          <input class="form-control" id="logo_wordmark" name="logo_wordmark" value="<?= e($logoWordmark) ?>" />
          <?php if ($logoWordmark !== '' && $logoWordmark !== 'assets/logo/logo-wordmark.png'): ?>
            <label class="checks"><input type="checkbox" name="remove_logo_wordmark" value="1" /> Remove custom wordmark</label>
          <?php endif; ?>
        </div>

        <div class="form-group media-field">
          <label>Logo mark</label>
          <?= admin_hero_preview($logoMark, '') ?>
          <div class="media-drop">
            <label for="logo_mark_file">Upload mark</label>
            <input class="form-control" id="logo_mark_file" type="file" name="logo_mark_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#logo-mark-preview" />
          </div>
          <div class="media-live-preview" hidden>
            <div class="media-preview"><div class="media-preview__item media-preview__item--hero"><img id="logo-mark-preview" alt="" hidden /></div></div>
          </div>
          <button class="btn btn--secondary btn--sm media-library-browse" type="button" data-open-media-picker data-media-mode="single" data-media-target="#logo_mark" data-media-preview="#logo-mark-preview">
            <?= yn_icon('image') ?> Choose from library
          </button>
          <label for="logo_mark">Or path</label>
          <input class="form-control" id="logo_mark" name="logo_mark" value="<?= e($logoMark) ?>" />
          <?php if ($logoMark !== '' && $logoMark !== 'assets/logo/logo-mark.png'): ?>
            <label class="checks"><input type="checkbox" name="remove_logo_mark" value="1" /> Remove custom mark</label>
          <?php endif; ?>
        </div>

        <div class="form-group media-field">
          <label>Favicon</label>
          <?= admin_hero_preview($favicon, '') ?>
          <div class="media-drop">
            <label for="favicon_file">Upload favicon</label>
            <input class="form-control" id="favicon_file" type="file" name="favicon_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#favicon-preview" />
          </div>
          <div class="media-live-preview" hidden>
            <div class="media-preview"><div class="media-preview__item media-preview__item--hero"><img id="favicon-preview" alt="" hidden /></div></div>
          </div>
          <button class="btn btn--secondary btn--sm media-library-browse" type="button" data-open-media-picker data-media-mode="single" data-media-target="#favicon" data-media-preview="#favicon-preview">
            <?= yn_icon('image') ?> Choose from library
          </button>
          <label for="favicon">Or path</label>
          <input class="form-control" id="favicon" name="favicon" value="<?= e($favicon) ?>" />
          <?php if ($favicon !== '' && $favicon !== 'assets/logo/favicon-32.png'): ?>
            <label class="checks"><input type="checkbox" name="remove_favicon" value="1" /> Remove custom favicon</label>
          <?php endif; ?>
        </div>

        <div class="form-group full media-field">
          <label>Apple touch icon</label>
          <?= admin_hero_preview($appleTouch, '') ?>
          <div class="media-drop">
            <label for="apple_touch_icon_file">Upload apple touch icon</label>
            <input class="form-control" id="apple_touch_icon_file" type="file" name="apple_touch_icon_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#apple-touch-preview" />
          </div>
          <div class="media-live-preview" hidden>
            <div class="media-preview"><div class="media-preview__item media-preview__item--hero"><img id="apple-touch-preview" alt="" hidden /></div></div>
          </div>
          <button class="btn btn--secondary btn--sm media-library-browse" type="button" data-open-media-picker data-media-mode="single" data-media-target="#apple_touch_icon" data-media-preview="#apple-touch-preview">
            <?= yn_icon('image') ?> Choose from library
          </button>
          <label for="apple_touch_icon">Or path</label>
          <input class="form-control" id="apple_touch_icon" name="apple_touch_icon" value="<?= e($appleTouch) ?>" />
          <?php if ($appleTouch !== '' && $appleTouch !== 'assets/logo/apple-touch-icon.png'): ?>
            <label class="checks"><input type="checkbox" name="remove_apple_touch_icon" value="1" /> Remove custom apple icon</label>
          <?php endif; ?>
        </div>
      </section>
    </div>
    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save settings</button>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Settings';
$activeNav = 'settings';
require dirname(__DIR__) . '/_layout.php';
