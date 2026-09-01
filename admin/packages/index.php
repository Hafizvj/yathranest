<?php



require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_once dirname(__DIR__) . '/_feature_toggle.php';
require_once __DIR__ . '/_form_helpers.php';

require_admin();



feature_toggle_handle_post('packages', 'admin/packages/index.php');



$destination = get_query('destination');

$duration = get_query('duration');

$scope = get_query('scope');



$sql = 'SELECT id, slug, title, days, nights, type, types_json, image, is_published, is_featured, pages_json

        FROM packages WHERE 1=1';

$params = [];



if ($destination !== '') {

    $sql .= ' AND JSON_CONTAINS(COALESCE(destinations_json, JSON_ARRAY()), ?)';

    $params[] = json_encode($destination);

}



if ($duration !== '') {

    $sql .= ' AND duration_bucket = ?';

    $params[] = $duration;

}



if ($scope !== '') {

    $sql .= ' AND JSON_CONTAINS(COALESCE(pages_json, JSON_ARRAY()), ?)';

    $params[] = json_encode($scope);

}



$sql .= ' ORDER BY is_featured DESC, sort_order, title';



$stmt = db()->prepare($sql);

$stmt->execute($params);

$rows = $stmt->fetchAll();



$places = places_all();

$hasFilters = $destination !== '' || $duration !== '' || $scope !== '';



ob_start();

?>

<div class="admin-toolbar">

  <p class="admin-toolbar__meta"><?= count($rows) ?> package<?= count($rows) === 1 ? '' : 's' ?><?= $hasFilters ? ' shown' : '' ?></p>

  <div class="admin-toolbar__actions">

    <?= feature_toggle_html('packages') ?>

    <a class="btn btn--secondary" href="<?= e(url('admin/packages/create-from-pdf.php')) ?>">Add from PDF</a>

    <a class="btn btn--secondary" href="<?= e(url('admin/packages/create-ai.php')) ?>">Add with AI</a>

    <a class="btn btn--primary" href="<?= e(url('admin/packages/edit.php')) ?>">Add package</a>

  </div>

</div>

<div class="admin-panel">

  <form method="get" class="admin-toolbar">

    <div class="admin-filters">

      <select class="form-control" name="destination" aria-label="Destination">

        <option value="">All destinations</option>

        <?php foreach ($places as $place): ?>

          <option value="<?= e($place['slug']) ?>" <?= $destination === $place['slug'] ? 'selected' : '' ?>><?= e($place['label']) ?></option>

        <?php endforeach; ?>

      </select>

      <select class="form-control" name="duration" aria-label="Duration">

        <option value="">Any duration</option>

        <option value="2-4" <?= $duration === '2-4' ? 'selected' : '' ?>>2–4 days</option>

        <option value="5-7" <?= $duration === '5-7' ? 'selected' : '' ?>>5–7 days</option>

        <option value="8-10" <?= $duration === '8-10' ? 'selected' : '' ?>>8–10 days</option>

      </select>

      <select class="form-control" name="scope" aria-label="Listing page">

        <option value="">All pages</option>

        <?php foreach (catalog_scope_options() as $val => $label): ?>

          <option value="<?= e($val) ?>" <?= $scope === $val ? 'selected' : '' ?>><?= e($label) ?></option>

        <?php endforeach; ?>

      </select>

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

              <a class="btn btn--secondary btn--sm" href="<?= e(package_public_url((string) $row['slug'], empty($row['is_published']))) ?>" target="_blank" rel="noopener"><?= empty($row['is_published']) ? 'Preview' : 'View' ?></a>

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

