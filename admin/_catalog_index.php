<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once __DIR__ . '/_catalog.php';
require_once __DIR__ . '/_feature_toggle.php';
require_admin();

$cfg = catalog_config($catalogKey ?? '');
if (!$cfg) {
    http_response_code(404);
    echo 'Unknown catalog';
    exit;
}

$featureKey = (string) ($cfg['feature_key'] ?? '');
if ($featureKey !== '') {
    feature_toggle_handle_post($featureKey, 'admin/' . $cfg['nav'] . '/index.php');
}

$table = $cfg['table'];
$rows = db()->query("SELECT * FROM {$table} ORDER BY sort_order, title")->fetchAll();
$singular = $cfg['singular'] ?? $cfg['label'];
$editBase = 'admin/' . $cfg['nav'] . '/edit.php';

$publishedCount = count(array_filter($rows, static fn($r) => !empty($r['is_published'])));
$draftCount = count($rows) - $publishedCount;

$resortIds = [];
if (!empty($cfg['has_resorts'])) {
    $resortIds = array_map('intval', array_column(catalog_resort_options(), 'id'));
}

/** Secondary line under the title, depending on the catalog. */
$rowMeta = static function (array $row) use ($cfg, $resortIds): string {
    if (!empty($cfg['has_resorts'])) {
        $n = count(array_intersect(array_map('intval', json_decode_array($row['resorts_json'] ?? null)), $resortIds));
        return $n ? 'Valid at ' . $n . ' resort' . ($n === 1 ? '' : 's') : 'No resorts linked';
    }
    if (!empty($cfg['blurb_field'])) {
        $features = json_decode_array($row['features_json'] ?? null);
        return $features ? (string) $features[0] . (count($features) > 1 ? ' +' . (count($features) - 1) . ' more' : '') : '';
    }
    $second = !empty($cfg['has_duration']) ? ($row['duration'] ?? '') : ($row['category'] ?? '');
    return implode(' · ', array_filter([(string) ($row['location'] ?? ''), (string) $second]));
};

ob_start();
?>
<div class="catalog-toolbar">
  <?php if ($rows): ?>
    <div class="catalog-toolbar__filters">
      <span class="input-icon catalog-toolbar__search">
        <?= yn_icon('search') ?>
        <input class="form-control" type="search" placeholder="Search <?= e(strtolower($cfg['label'])) ?>" aria-label="Search <?= e(strtolower($cfg['label'])) ?>" data-catalog-search />
      </span>
      <div class="catalog-pills" role="group" aria-label="Filter by status">
        <button class="catalog-pill is-active" type="button" data-catalog-filter="all" aria-pressed="true">All <span><?= count($rows) ?></span></button>
        <button class="catalog-pill" type="button" data-catalog-filter="published" aria-pressed="false">Published <span><?= $publishedCount ?></span></button>
        <button class="catalog-pill" type="button" data-catalog-filter="draft" aria-pressed="false">Draft <span><?= $draftCount ?></span></button>
      </div>
    </div>
  <?php endif; ?>
  <div class="catalog-toolbar__actions">
    <?php if ($featureKey !== ''): ?>
      <?= feature_toggle_html($featureKey) ?>
    <?php endif; ?>
    <a class="btn btn--primary" href="<?= e(url($editBase)) ?>"><?= yn_icon('plus') ?>Add <?= e(strtolower($singular)) ?></a>
  </div>
</div>

<?php if (!$rows): ?>
  <div class="catalog-empty">
    <span class="catalog-empty__icon"><?= yn_icon(['resorts' => 'bed', 'getaways' => 'compass', 'gift-cards' => 'gift', 'investment' => 'chart'][$cfg['nav']] ?? 'list') ?></span>
    <h2>No <?= e(strtolower($cfg['label'])) ?> yet</h2>
    <p>Create your first <?= e(strtolower($singular)) ?> to show it on the website.</p>
    <a class="btn btn--primary" href="<?= e(url($editBase)) ?>"><?= yn_icon('plus') ?>Add <?= e(strtolower($singular)) ?></a>
  </div>
<?php else: ?>
  <div class="catalog-list" data-catalog-list>
    <div class="catalog-list__head" aria-hidden="true">
      <span><?= e($singular) ?></span>
      <span>Status</span>
      <span>Order</span>
      <span></span>
    </div>
    <?php foreach ($rows as $row):
      $rid = (int) $row['id'];
      $isPub = !empty($row['is_published']);
      $editUrl = url($editBase . '?id=' . $rid);
      $meta = $rowMeta($row);
      $publicUrl = ($isPub && !empty($cfg['public_path'])) ? url($cfg['public_path'] . rawurlencode((string) $row['slug'])) : '';
      ?>
      <div class="catalog-row" data-catalog-row data-href="<?= e($editUrl) ?>" data-status="<?= $isPub ? 'published' : 'draft' ?>" data-search="<?= e(strtolower($row['title'] . ' ' . $row['slug'] . ' ' . $meta)) ?>">
        <div class="catalog-row__main">
          <span class="catalog-row__thumb">
            <?php if (!empty($row['image'])): ?>
              <img src="<?= e(image_url($row['image'])) ?>" alt="" loading="lazy" />
            <?php else: ?>
              <?= yn_icon('image') ?>
            <?php endif; ?>
          </span>
          <span class="catalog-row__text">
            <a class="catalog-row__title" href="<?= e($editUrl) ?>"><?= e($row['title']) ?></a>
            <span class="catalog-row__meta">
              <?php if ($meta !== ''): ?><?= e($meta) ?><span class="catalog-row__dot" aria-hidden="true">·</span><?php endif; ?>
              <span class="catalog-row__slug">/<?= e($row['slug']) ?></span>
            </span>
          </span>
        </div>
        <span class="catalog-status catalog-status--<?= $isPub ? 'published' : 'draft' ?>"><?= $isPub ? 'Published' : 'Draft' ?></span>
        <span class="catalog-row__order" title="Display order"><?= (int) ($row['sort_order'] ?? 0) ?></span>
        <div class="catalog-row__actions">
          <?php if ($publicUrl !== ''): ?>
            <a class="icon-btn" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener" aria-label="View <?= e($row['title']) ?> on site" title="View on site"><?= yn_icon('eye') ?></a>
          <?php endif; ?>
          <a class="icon-btn" href="<?= e($editUrl) ?>" aria-label="Edit <?= e($row['title']) ?>" title="Edit"><?= yn_icon('pencil') ?></a>
          <form method="post" action="<?= e(url('admin/' . $cfg['nav'] . '/delete.php')) ?>" data-confirm="Delete &ldquo;<?= e($row['title']) ?>&rdquo;? This cannot be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $rid ?>" />
            <button class="icon-btn icon-btn--danger" type="submit" aria-label="Delete <?= e($row['title']) ?>" title="Delete"><?= yn_icon('trash') ?></button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
    <p class="catalog-list__none" data-catalog-none hidden>No <?= e(strtolower($cfg['label'])) ?> match your search.</p>
  </div>
<?php endif; ?>
<?php
$adminContent = ob_get_clean();
$pageTitle = $cfg['label'];
$pageSubtitle = $rows
    ? count($rows) . ' total · ' . $publishedCount . ' published · ' . $draftCount . ' draft'
    : 'Nothing here yet.';
$activeNav = $cfg['nav'];
$adminScripts = ['admin/assets/admin-catalog-index.js'];
require __DIR__ . '/_layout.php';
