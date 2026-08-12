<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/settings/index.php');
    }
    settings_save([
        'phone' => post('phone'),
        'email' => post('email'),
        'whatsapp' => post('whatsapp'),
        'address' => post('address'),
        'social_instagram' => post('social_instagram'),
        'social_facebook' => post('social_facebook'),
        'social_youtube' => post('social_youtube'),
    ]);
    // clear settings cache by restarting request
    flash_set('success', 'Settings saved.');
    redirect('admin/settings/index.php');
}

ob_start();
?>
<div class="admin-panel">
  <form method="post">
    <?= csrf_field() ?>
    <div class="form-grid">
      <div class="form-group"><label>Phone</label><input class="form-control" name="phone" value="<?= e(setting('phone', '+91 98765 43210')) ?>" /></div>
      <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?= e(setting('email', 'hello@yathranest.com')) ?>" /></div>
      <div class="form-group"><label>WhatsApp (digits)</label><input class="form-control" name="whatsapp" value="<?= e(setting('whatsapp', '919876543210')) ?>" /></div>
      <div class="form-group"><label>Address</label><input class="form-control" name="address" value="<?= e(setting('address')) ?>" /></div>
      <div class="form-group"><label>Instagram URL</label><input class="form-control" name="social_instagram" value="<?= e(setting('social_instagram', '#')) ?>" /></div>
      <div class="form-group"><label>Facebook URL</label><input class="form-control" name="social_facebook" value="<?= e(setting('social_facebook', '#')) ?>" /></div>
      <div class="form-group full"><label>YouTube URL</label><input class="form-control" name="social_youtube" value="<?= e(setting('social_youtube', '#')) ?>" /></div>
    </div>
    <button class="btn btn--primary" type="submit" style="margin-top:1rem">Save settings</button>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Settings';
$activeNav = 'settings';
require dirname(__DIR__) . '/_layout.php';
