<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/_feature_toggle.php';
require_admin();

feature_toggle_handle_post('packages', 'admin/packages/index.php');

$status = get_query('status');
$type = get_query('type');
$scope = get_query('scope');
$duration = get_query('duration');
$pickup = get_query('pickup');
$featured = get_query('featured');
$q = get_query('q');

$sql = 'SELECT id, slug, title, days, nights, type, types_json, image, is_published, is_featured, pages_json,
               pickup_slug, duration_bucket, pickup
        FROM packages WHERE 1=1';
$params = [];

if ($status === 'published') {
    $sql .= ' AND is_published = 1';
} elseif ($status === 'draft') {
    $sql .= ' AND is_published = 0';
}

if ($type !== '') {
    $sql .= ' AND (type = ? OR JSON_CONTAINS(COALESCE(types_json, JSON_ARRAY()), ?))';
    $params[] = $type;
    $params[] = json_encode($type);
}

if ($scope !== '') {
    $sql .= ' AND JSON_CONTAINS(COALESCE(pages_json, JSON_ARRAY()), ?)';
    $params[] = json_encode($scope);
}

if ($duration !== '') {
    $sql .= ' AND duration_bucket = ?';
    $params[] = $duration;
}

if ($pickup !== '') {
    $sql .= ' AND pickup_slug = ?';
    $params[] = $pickup;
}

if ($featured === '1') {
    $sql .= ' AND is_featured = 1';
}

if ($q !== '') {
    $sql .= ' AND (title LIKE ? OR slug LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql .= ' ORDER BY is_featured DESC, sort_order, title';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pickupLabels = ['calicut' => 'Calicut', 'kochi' => 'Kochi', 'coimbatore' => 'Coimbatore', 'mysore' => 'Mysore', 'trivandrum' => 'Trivandrum'];
$pickupOptions = [];
foreach (db()->query('SELECT DISTINCT pickup_slug, pickup FROM packages WHERE pickup_slug != "" ORDER BY pickup')->fetchAll() as $pickupRow) {
    $slug = (string) $pickupRow['pickup_slug'];
    $pickupOptions[$slug] = $pickupLabels[$slug] ?? ($pickupRow['pickup'] !== '' ? $pickupRow['pickup'] : $slug);
}

$hasFilters = $status !== '' || $type !== '' || $scope !== '' || $duration !== '' || $pickup !== '' || $featured !== '' || $q !== '';

ob_start();
?>
<div class="admin-toolbar">
  <p class="admin-toolbar__meta"><?= count($rows) ?> package<?= count($rows) === 1 ? '' : 's' ?><?= $hasFilters ? ' shown' : '' ?></p>
  <div class="admin-toolbar__actions">
    <?= feature_toggle_html('packages') ?>
    <a class="btn btn--secondary" href="<?= e(url('admin/packages/create-ai.php')) ?>">Add with AI</a>
    <a class="btn btn--primary" href="<?= e(url('admin/packages/edit.php')) ?>">Add package</a>
  </div>
</div>
<div class="admin-panel">
  <form method="get" class="admin-toolbar">
    <div class="admin-filters">
      <select class="form-control" name="status" aria-label="Status">
        <option value="">All statuses</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
      </select>
      <select class="form-control" name="type" aria-label="Travel type">
        <option value="">All types</option>
        <?php foreach (package_type_options() as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= $type === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" name="scope" aria-label="Listing page">
        <option value="">All pages</option>
        <?php foreach (catalog_scope_options() as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= $scope === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" name="duration" aria-label="Duration">
        <option value="">Any duration</option>
        <option value="2-4" <?= $duration === '2-4' ? 'selected' : '' ?>>2–4 days</option>
        <option value="5-7" <?= $duration === '5-7' ? 'selected' : '' ?>>5–7 days</option>
        <option value="8-10" <?= $duration === '8-10' ? 'selected' : '' ?>>8–10 days</option>
      </select>
      <select class="form-control" name="pickup" aria-label="Pickup">
        <option value="">Any pickup</option>
        <?php foreach ($pickupOptions as $val => $label): ?>
          <option value="<?= e($val) ?>" <?= $pickup === $val ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-control" name="featured" aria-label="Featured">
        <option value="">All packages</option>
        <option value="1" <?= $featured === '1' ? 'selected' : '' ?>>Featured only</option>
      </select>
      <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search title or slug" aria-label="Search" />
      <button class="btn btn--secondary" type="submit">Filter</button>
      <?php if ($hasFilters): ?>
        <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Clear</a>
      <?php endif; ?>
    </div>
  </form>
  <?php if (!$rows): ?>
    <p class="admin-empty"><?= $hasFilters ? 'No packages match these filters.' : 'No packages yet. Create your first package to get started.' ?></p>
  <?php else: ?>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th></th><th>Title</th><th>Duration</th><th>Type</th><th>Pages</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td>
              <?php if (!empty($row['image'])): ?>
                <img class="admin-thumb" src="<?= e(image_url($row['image'])) ?>" alt="" width="48" height="48" loading="lazy" />
              <?php endif; ?>
            </td>
            <td><strong><?= e($row['title']) ?></strong><br><small><?= e($row['slug']) ?></small></td>
            <td><?= (int) $row['days'] ?>D / <?= (int) $row['nights'] ?>N</td>
            <?php $types = json_decode_array($row['types_json']) ?: array_filter([(string) $row['type']]); ?>
            <td><?= e(package_types_label(['types' => $types])) ?></td>
            <td><?= e(implode(', ', json_decode_array($row['pages_json']))) ?></td>
            <td>
              <span class="badge badge--<?= (int) $row['is_published'] ? 'published' : 'draft' ?>">
                <?= (int) $row['is_published'] ? 'Published' : 'Draft' ?>
              </span>
              <?php if (!empty($row['is_featured'])): ?>
                <br><small>Featured</small>
              <?php endif; ?>
            </td>
            <td class="admin-row-actions">
              <a class="btn btn--secondary btn--sm" href="<?= e(url('admin/packages/edit.php?id=' . (int) $row['id'])) ?>">Edit</a>
              <form method="post" action="<?= e(url('admin/packages/delete.php')) ?>" data-confirm="Delete this package?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
                <button class="btn btn--danger btn--sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Packages';
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
