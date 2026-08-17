<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$id = (int) get_query('id', '0');
$stmt = db()->prepare('SELECT * FROM inquiries WHERE id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    flash_set('error', 'Inquiry not found.');
    redirect('admin/inquiries/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/inquiries/view.php?id=' . $id);
    }
    $status = post('status') === 'handled' ? 'handled' : 'new';
    db()->prepare('UPDATE inquiries SET status = ? WHERE id = ?')->execute([$status, $id]);
    flash_set('success', 'Status updated.');
    redirect('admin/inquiries/view.php?id=' . $id);
}

$extra = json_decode_array($row['extra_json'] ?? null);
$waUrl = enquiry_whatsapp_url(array_merge($row, ['extra' => $extra]));

ob_start();
?>
<div class="admin-panel">
  <dl class="admin-dl">
    <dt>Type</dt>
    <dd><?= e($row['type']) ?> · <span class="badge badge--<?= e($row['status']) ?>"><?= e($row['status']) ?></span></dd>
    <dt>When</dt>
    <dd><?= e($row['created_at']) ?></dd>
    <dt>Name</dt>
    <dd><?= e($row['name']) ?></dd>
    <dt>Phone</dt>
    <dd><a href="tel:<?= e(preg_replace('/\s+/', '', $row['phone'])) ?>"><?= e($row['phone']) ?></a></dd>
    <dt>Email</dt>
    <dd><?php if ($row['email'] !== ''): ?><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a><?php else: ?>—<?php endif; ?></dd>
    <dt>Interest</dt>
    <dd><?= e($row['interest'] ?: '—') ?></dd>
    <?php if (!empty($row['travel_date'])): ?>
      <dt>Travel from</dt>
      <dd><?= e(enquiry_date_label($row['travel_date'])) ?></dd>
    <?php endif; ?>
    <dt>Source</dt>
    <dd><?= e($row['source_page'] ?: '—') ?></dd>
    <?php if ($row['package_slug']): ?>
      <dt>Package</dt>
      <dd><?= e($row['package_slug']) ?></dd>
    <?php endif; ?>
    <?php if (trim((string) ($row['message'] ?? '')) !== ''): ?>
      <dt>Message</dt>
      <dd><?= nl2br(e($row['message'])) ?></dd>
    <?php endif; ?>
  </dl>

  <?php if ($extra): ?>
    <h3 class="form-section__title" style="margin-top:1.25rem">Extra fields</h3>
    <dl class="admin-dl">
      <?php foreach ($extra as $k => $v): ?>
        <dt><?= e((string) $k) ?></dt>
        <dd><?= e(is_scalar($v) ? (string) $v : json_encode($v)) ?></dd>
      <?php endforeach; ?>
    </dl>
  <?php endif; ?>

  <form method="post">
    <?= csrf_field() ?>
    <div class="form-section">
      <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
          <option value="new" <?= $row['status'] === 'new' ? 'selected' : '' ?>>new</option>
          <option value="handled" <?= $row['status'] === 'handled' ? 'selected' : '' ?>>handled</option>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Update</button>
      <?php if ($waUrl !== ''): ?>
        <a class="btn btn--secondary" href="<?= e($waUrl) ?>" target="_blank" rel="noopener">WhatsApp (prefilled)</a>
      <?php elseif ($row['phone'] !== ''): ?>
        <a class="btn btn--secondary" href="https://wa.me/<?= e(preg_replace('/\D/', '', $row['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <?php endif; ?>
      <a class="btn btn--secondary" href="<?= e(url('admin/inquiries/index.php')) ?>">Back</a>
    </div>
  </form>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Inquiry #' . $id;
$activeNav = 'inquiries';
require dirname(__DIR__) . '/_layout.php';
