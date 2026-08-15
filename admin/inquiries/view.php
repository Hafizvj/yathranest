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

ob_start();
?>
<div class="admin-panel">
  <p><strong>Type:</strong> <?= e($row['type']) ?> · <span class="badge badge--<?= e($row['status']) ?>"><?= e($row['status']) ?></span></p>
  <p><strong>When:</strong> <?= e($row['created_at']) ?></p>
  <p><strong>Name:</strong> <?= e($row['name']) ?></p>
  <p><strong>Phone:</strong> <a href="tel:<?= e(preg_replace('/\s+/', '', $row['phone'])) ?>"><?= e($row['phone']) ?></a></p>
  <p><strong>Email:</strong> <a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></p>
  <p><strong>Interest:</strong> <?= e($row['interest']) ?></p>
  <?php if (!empty($row['travel_date'])): ?><p><strong>Travel from:</strong> <?= e(enquiry_date_label($row['travel_date'])) ?></p><?php endif; ?>
  <p><strong>Source:</strong> <?= e($row['source_page']) ?></p>
  <?php if ($row['package_slug']): ?><p><strong>Package:</strong> <?= e($row['package_slug']) ?></p><?php endif; ?>
  <?php if (trim((string) ($row['message'] ?? '')) !== ''): ?>
    <p><strong>Message:</strong></p>
    <p><?= nl2br(e($row['message'])) ?></p>
  <?php endif; ?>
  <?php if ($extra): ?>
    <h3>Extra fields</h3>
    <ul>
      <?php foreach ($extra as $k => $v): ?>
        <li><strong><?= e((string) $k) ?>:</strong> <?= e(is_scalar($v) ? (string) $v : json_encode($v)) ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post" style="margin-top:1rem">
    <?= csrf_field() ?>
    <div class="form-group" style="max-width:220px">
      <label>Status</label>
      <select class="form-control" name="status">
        <option value="new" <?= $row['status'] === 'new' ? 'selected' : '' ?>>new</option>
        <option value="handled" <?= $row['status'] === 'handled' ? 'selected' : '' ?>>handled</option>
      </select>
    </div>
    <div class="btn-group" style="margin-top:0.75rem">
      <button class="btn btn--primary" type="submit">Update</button>
      <?php if ($row['phone'] !== ''): ?>
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
