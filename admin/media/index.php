<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/packages/_form_helpers.php';
require_once dirname(__DIR__) . '/_media.php';
require_admin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect('admin/media/index.php');
    }

    $action = post('action');
    if ($action === 'upload') {
        $files = $_FILES['files'] ?? null;
        $uploaded = 0;
        if ($files && is_array($files['name'] ?? null)) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $files['size'][$i] ?? 0,
                ];
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $file['name'] === '') {
                    continue;
                }
                $row = media_register_upload($file, 'library');
                if ($row) {
                    $uploaded++;
                } elseif (admin_upload_last_error()) {
                    $errors[] = admin_upload_last_error();
                    admin_upload_last_error('');
                }
            }
        }
        if ($uploaded > 0) {
            flash_set('success', $uploaded === 1 ? '1 image uploaded.' : $uploaded . ' images uploaded.');
        } elseif (!$errors) {
            flash_set('error', 'No images uploaded.');
        } else {
            flash_set('error', implode(' ', $errors));
        }
        redirect('admin/media/index.php');
    }

    if ($action === 'delete') {
        $id = (int) post('id', '0');
        if (media_delete_if_unused($id)) {
            flash_set('success', 'Image deleted.');
        } else {
            flash_set('error', admin_upload_last_error() ?: 'Could not delete image.');
            admin_upload_last_error('');
        }
        redirect('admin/media/index.php');
    }

    if ($action === 'import') {
        $n = media_import_referenced_uploads();
        flash_set('success', $n === 0 ? 'No new uploads to import.' : "Imported {$n} image(s) into the library.");
        redirect('admin/media/index.php');
    }
}

$q = trim(get_query('q'));
$page = max(1, (int) get_query('page', '1'));
$result = media_list($q, $page, 96);
$items = $result['items'];
$total = $result['total'];
$pages = max(1, (int) ceil($total / $result['per_page']));

ob_start();
?>
<div class="admin-toolbar">
  <p class="admin-toolbar__meta"><?= (int) $total ?> image<?= $total === 1 ? '' : 's' ?></p>
  <div class="admin-toolbar__actions">
    <form method="get" class="admin-media-search" action="">
      <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search images" />
      <button class="btn btn--secondary" type="submit">Search</button>
    </form>
    <form method="post" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="import" />
      <button class="btn btn--secondary" type="submit">Import from site</button>
    </form>
  </div>
</div>

<div class="admin-panel">
  <form class="media-library-upload" method="post" enctype="multipart/form-data" data-dropzone>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload" />
    <span class="media-drop__icon" aria-hidden="true"><?= yn_icon('upload') ?></span>
    <span class="media-drop__title">Upload to library</span>
    <span class="media-drop__hint">JPG, PNG, WEBP, GIF up to 5MB each — multiple files allowed</span>
    <input id="media_files" type="file" name="files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple />
    <button class="btn btn--primary" type="submit">Upload</button>
  </form>

  <?php if (!$items): ?>
    <p class="admin-empty">No images in the library yet. Upload above or import paths already used on the site.</p>
  <?php else: ?>
    <div class="media-library-grid">
      <?php foreach ($items as $item): ?>
        <article class="media-library-card">
          <div class="media-library-card__frame">
            <img src="<?= e($item['url']) ?>" alt="<?= e($item['name']) ?>" loading="lazy" />
          </div>
          <div class="media-library-card__meta">
            <strong title="<?= e($item['path']) ?>"><?= e($item['name']) ?></strong>
            <span><?= e($item['bytes_label']) ?><?= ($item['source'] ?? '') === 'asset' ? ' · Site asset' : '' ?></span>
          </div>
          <?php if (!empty($item['deletable'])): ?>
            <form method="post" action="" data-confirm="Delete this image from the library?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete" />
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
              <button class="btn btn--danger btn--sm" type="submit">Delete</button>
            </form>
          <?php else: ?>
            <p class="media-library-card__note">Built-in asset</p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($pages > 1): ?>
      <div class="admin-media-pager">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <?php if ($p === $page): ?>
            <span class="is-current"><?= $p ?></span>
          <?php else: ?>
            <a href="?<?= e(http_build_query(array_filter(['q' => $q, 'page' => $p]))) ?>"><?= $p ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php
$adminContent = ob_get_clean();
$pageTitle = 'Media';
$pageSubtitle = 'Reusable image library for packages, stays and site content.';
$activeNav = 'media';
require dirname(__DIR__) . '/_layout.php';
