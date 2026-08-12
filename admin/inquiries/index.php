<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();

$type = get_query('type');
$status = get_query('status');
$sql = 'SELECT * FROM inquiries WHERE 1=1';
$params = [];
if ($type !== '') {
    $sql .= ' AND type = ?';
    $params[] = $type;
}
if ($status !== '') {
    $sql .= ' AND status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

ob_start();
?>
<div class="admin-panel">
  <form method="get" class="admin-toolbar">
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap">
      <select class="form-control" name="type" style="width:auto">
        <option value="">All types</option>
        <?php foreach (['general','contact','taxi','resort','gift','investment'] as $t): ?>
          <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" name="status" style="width:auto">
        <option value="">All statuses</option>
        <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>new</option>
        <option value="handled" <?= $status === 'handled' ? 'selected' : '' ?>>handled</option>
      </select>
      <button class="btn btn--secondary" type="submit">Filter</button>
    </div>
  </form>
  <table class="admin-table">
    <thead><tr><th>When</th><th>Type</th><th>Name</th><th>Phone</th><th>Interest</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php if (!$rows): ?><tr><td colspan="7">No inquiries.</td></tr><?php endif; ?>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= e($row['created_at']) ?></td>
          <td><?= e($row['type']) ?></td>
          <td><?= e($row['name']) ?></td>
          <td><?= e($row['phone']) ?></td>
          <td><?= e($row['interest']) ?></td>
          <td><span class="badge badge--<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
          <td><a class="btn btn--secondary btn--sm" href="<?= e(url('admin/inquiries/view.php?id=' . (int) $row['id'])) ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Inquiries';
$activeNav = 'inquiries';
require dirname(__DIR__) . '/_layout.php';
