<?php
/** Shared media library picker modal — included from admin/_layout.php */
$mediaPickerApi = url('admin/media/api.php');
?>
<div class="media-picker" id="media-picker" hidden data-media-picker data-api="<?= e($mediaPickerApi) ?>">
  <div class="media-picker__backdrop" data-media-picker-close></div>
  <div class="media-picker__dialog" role="dialog" aria-modal="true" aria-labelledby="media-picker-title">
    <header class="media-picker__head">
      <div>
        <h2 id="media-picker-title">Choose from library</h2>
        <p class="media-picker__hint" data-media-picker-hint>Select an image to use.</p>
      </div>
      <button class="icon-btn" type="button" data-media-picker-close aria-label="Close">&times;</button>
    </header>
    <div class="media-picker__toolbar">
      <input class="form-control" type="search" placeholder="Search library" data-media-picker-search />
      <label class="btn btn--secondary media-picker__upload-btn">
        <?= yn_icon('upload') ?> Upload
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple hidden data-media-picker-upload />
      </label>
    </div>
    <div class="media-picker__body">
      <p class="media-picker__empty" data-media-picker-empty hidden>No images found.</p>
      <div class="media-picker__grid" data-media-picker-grid></div>
      <div class="media-picker__more-wrap">
        <button class="btn btn--secondary btn--sm" type="button" data-media-picker-more hidden>Load more images</button>
      </div>
    </div>
    <footer class="media-picker__foot">
      <span data-media-picker-status></span>
      <div class="media-picker__actions">
        <button class="btn btn--secondary" type="button" data-media-picker-close>Cancel</button>
        <button class="btn btn--primary" type="button" data-media-picker-confirm disabled>Use selected</button>
      </div>
    </footer>
  </div>
</div>
<script type="application/json" id="media-picker-csrf"><?= json_encode(['token' => csrf_token()], JSON_UNESCAPED_UNICODE) ?></script>
