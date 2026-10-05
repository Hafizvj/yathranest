<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/_feature_toggle.php';
require_once __DIR__ . '/_form_helpers.php';

require_admin();

feature_toggle_handle_post('packages', 'admin/packages/index.php');

$destination = get_query('destination');
$duration = get_query('duration');
$scope = get_query('scope');

$sql = 'SELECT id, slug, title, days, nights, type, types_json, image, is_published, is_featured, pages_json, destinations_json
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
$placeLabels = array_column($places, 'label', 'slug');
$scopeLabels = catalog_scope_options();
$durationOptions = ['2-4' => '2–4 days', '5-7' => '5–7 days', '8-10' => '8–10 days'];
$hasFilters = $destination !== '' || $duration !== '' || $scope !== '';

$publishedCount = count(array_filter($rows, static fn($r) => !empty($r['is_published'])));
$draftCount = count($rows) - $publishedCount;
$featuredCount = count(array_filter($rows, static fn($r) => !empty($r['is_featured'])));

ob_start();
?>
<div class="catalog-toolbar">
  <div class="catalog-toolbar__filters">
    <?php if ($rows): ?>
      <span class="input-icon catalog-toolbar__search">
        <?= yn_icon('search') ?>
        <input class="form-control" type="search" placeholder="Search packages" aria-label="Search packages" data-catalog-search />
      </span>
      <div class="catalog-pills" role="group" aria-label="Filter by status">
        <button class="catalog-pill is-active" type="button" data-catalog-filter="all" aria-pressed="true">All <span><?= count($rows) ?></span></button>
        <button class="catalog-pill" type="button" data-catalog-filter="published" aria-pressed="false">Published <span><?= $publishedCount ?></span></button>
        <button class="catalog-pill" type="button" data-catalog-filter="draft" aria-pressed="false">Draft <span><?= $draftCount ?></span></button>
        <?php if ($featuredCount): ?>
          <button class="catalog-pill" type="button" data-catalog-filter="featured" aria-pressed="false">Featured <span><?= $featuredCount ?></span></button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="catalog-toolbar__actions">
    <?= feature_toggle_html('packages') ?>
    <a class="btn btn--secondary" href="<?= e(url('admin/packages/create-from-pdf.php')) ?>"><?= yn_icon('file-text') ?>From PDF</a>
    <a class="btn btn--secondary" href="<?= e(url('admin/packages/create-ai.php')) ?>"><?= yn_icon('sparkle') ?>With AI</a>
    <a class="btn btn--primary" href="<?= e(url('admin/packages/edit.php')) ?>"><?= yn_icon('plus') ?>Add package</a>
  </div>
</div>

<form method="get" class="package-filters<?= $hasFilters ? ' is-filtered' : '' ?>" data-auto-submit>
  <span class="package-filters__label"><?= yn_icon('list') ?>Filter</span>
  <label class="package-filters__field">
    <?= yn_icon('pin') ?>
    <select class="form-control" name="destination" aria-label="Destination">
      <option value="">All destinations</option>
      <?php foreach ($places as $place): ?>
        <option value="<?= e($place['slug']) ?>" <?= $destination === $place['slug'] ? 'selected' : '' ?>><?= e($place['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="package-filters__field">
    <?= yn_icon('clock') ?>
    <select class="form-control" name="duration" aria-label="Duration">
      <option value="">Any duration</option>
      <?php foreach ($durationOptions as $val => $label): ?>
        <option value="<?= e($val) ?>" <?= $duration === $val ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label class="package-filters__field">
    <?= yn_icon('globe') ?>
    <select class="form-control" name="scope" aria-label="Listing page">
      <option value="">All listing pages</option>
      <?php foreach ($scopeLabels as $val => $label): ?>
        <option value="<?= e($val) ?>" <?= $scope === $val ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn btn--secondary btn--sm" type="submit" data-auto-submit-btn>Apply</button>
  <?php if ($hasFilters): ?>
    <a class="package-filters__clear" href="<?= e(url('admin/packages/index.php')) ?>">Clear filters</a>
  <?php endif; ?>
</form>

<?php if (!$rows): ?>
  <div class="catalog-empty">
    <span class="catalog-empty__icon"><?= yn_icon($hasFilters ? 'search' : 'route') ?></span>
    <?php if ($hasFilters): ?>
      <h2>No packages match these filters</h2>
      <p>Try a different destination, duration or listing page.</p>
      <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Clear filters</a>
    <?php else: ?>
      <h2>No packages yet</h2>
      <p>Create your first package by hand, from a PDF itinerary, or with AI.</p>
      <a class="btn btn--primary" href="<?= e(url('admin/packages/edit.php')) ?>"><?= yn_icon('plus') ?>Add package</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="catalog-list catalog-list--packages" data-catalog-list>
    <div class="catalog-list__head" aria-hidden="true">
      <span>Package</span>
      <span>Duration</span>
      <span>Listing pages</span>
      <span>Status</span>
      <span></span>
    </div>
    <?php foreach ($rows as $row):
      $pid = (int) $row['id'];
      $isPub = !empty($row['is_published']);
      $isFeatured = !empty($row['is_featured']);
      $editUrl = url('admin/packages/edit.php?id=' . $pid);
      $types = json_decode_array($row['types_json']) ?: array_filter([(string) $row['type']]);
      $typeLabel = package_types_label(['types' => $types]);
      $destNames = array_map(static fn($slug) => $placeLabels[$slug] ?? ucwords(str_replace('-', ' ', (string) $slug)), json_decode_array($row['destinations_json']));
      $destLabel = implode(', ', array_slice($destNames, 0, 3)) . (count($destNames) > 3 ? ' +' . (count($destNames) - 3) : '');
      $pages = json_decode_array($row['pages_json']);
      $searchText = strtolower(implode(' ', [$row['title'], $row['slug'], $typeLabel, implode(' ', $destNames)]));
      ?>
      <div class="catalog-row" data-catalog-row data-href="<?= e($editUrl) ?>" data-status="<?= $isPub ? 'published' : 'draft' ?>" data-featured="<?= $isFeatured ? '1' : '0' ?>" data-search="<?= e($searchText) ?>">
        <div class="catalog-row__main">
          <span class="catalog-row__thumb">
            <?php if (!empty($row['image'])): ?>
              <img src="<?= e(image_url($row['image'])) ?>" alt="" loading="lazy" />
            <?php else: ?>
              <?= yn_icon('image') ?>
            <?php endif; ?>
            <?php if ($isFeatured): ?>
              <span class="catalog-row__star" title="Featured"><?= yn_icon('star') ?></span>
            <?php endif; ?>
          </span>
          <span class="catalog-row__text">
            <a class="catalog-row__title" href="<?= e($editUrl) ?>"><?= e($row['title']) ?></a>
            <span class="catalog-row__meta">
              <?php if ($typeLabel !== ''): ?><span class="catalog-row__type"><?= e($typeLabel) ?></span><?php endif; ?>
              <?php if ($destLabel !== ''): ?><?= yn_icon('pin') ?><?= e($destLabel) ?><?php else: ?><span class="catalog-row__slug">/<?= e($row['slug']) ?></span><?php endif; ?>
            </span>
          </span>
        </div>
        <span class="catalog-row__duration"><strong><?= (int) $row['days'] ?>D</strong> / <?= (int) $row['nights'] ?>N</span>
        <span class="catalog-row__chips">
          <?php if ($pages): ?>
            <?php foreach ($pages as $page): ?>
              <span class="catalog-chip"><?= e($scopeLabels[$page] ?? ucfirst((string) $page)) ?></span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="catalog-row__none">Not listed</span>
          <?php endif; ?>
        </span>
        <span class="catalog-row__status">
          <span class="catalog-status catalog-status--<?= $isPub ? 'published' : 'draft' ?>"><?= $isPub ? 'Published' : 'Draft' ?></span>
          <?php if ($isFeatured): ?><span class="catalog-row__featured">Featured</span><?php endif; ?>
        </span>
        <div class="catalog-row__actions">
          <a class="icon-btn" href="<?= e(package_public_url((string) $row['slug'], !$isPub)) ?>" target="_blank" rel="noopener" aria-label="<?= $isPub ? 'View' : 'Preview' ?> <?= e($row['title']) ?> on site" title="<?= $isPub ? 'View on site' : 'Preview draft' ?>"><?= yn_icon('eye') ?></a>
          <a class="icon-btn" href="<?= e($editUrl) ?>" aria-label="Edit <?= e($row['title']) ?>" title="Edit"><?= yn_icon('pencil') ?></a>
          <form method="post" action="<?= e(url('admin/packages/delete.php')) ?>" data-confirm="Delete &ldquo;<?= e($row['title']) ?>&rdquo;? This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $pid ?>" />
            <button class="icon-btn icon-btn--danger" type="submit" aria-label="Delete <?= e($row['title']) ?>" title="Delete"><?= yn_icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <p class="catalog-list__none" data-catalog-none hidden>No packages match your search.</p>
  </div>
<?php endif; ?>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Packages';
$pageSubtitle = $hasFilters
    ? count($rows) . ' matching package' . (count($rows) === 1 ? '' : 's')
    : ($rows ? count($rows) . ' total · ' . $publishedCount . ' published · ' . $draftCount . ' draft' : 'Nothing here yet.');
$activeNav = 'packages';
$adminScripts = ['admin/assets/admin-catalog-index.js'];
require dirname(__DIR__) . '/_layout.php';
