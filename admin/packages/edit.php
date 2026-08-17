<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin();
require_once __DIR__ . '/_form_helpers.php';

$id = (int) get_query('id', '0');
$row = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch() ?: null;
    if (!$row) {
        flash_set('error', 'Package not found.');
        redirect('admin/packages/index.php');
    }
}

$places = places_all();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf(post('_csrf'))) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $oldImage = (string) ($row['image'] ?? '');
        $oldGallery = json_decode_array($row['gallery_json'] ?? null);

        $data = package_form_data_from_post($row);
        $duration = parse_package_duration(post('duration'));
        $errors = array_merge($errors, package_form_validate($data, $duration));

        $takeUploadError = static function () use (&$errors): void {
            $message = admin_upload_last_error();
            if ($message) {
                $errors[] = $message;
                admin_upload_last_error('');
            }
        };

        $newHero = !empty($_FILES['image_file']['name']);
        $removeHero = !empty($_POST['remove_image']);
        if (!$newHero && ($removeHero || $oldImage === '')) {
            $errors[] = 'A cover image is required.';
        }

        // Uploads only run once the rest of the form is valid, so a rejected
        // save does not leave orphaned files behind.
        $gallery = $oldGallery;
        if (!$errors) {
            $gallery = admin_collect_media_paths('gallery_keep', '', $_FILES['gallery_files'] ?? null, 'packages');
            $takeUploadError();
            $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

            if ($newHero) {
                $uploaded = admin_apply_image_upload($_FILES['image_file'], 'packages', $oldImage);
                $takeUploadError();
                if ($uploaded) {
                    $data['image'] = $uploaded;
                }
            } elseif ($removeHero) {
                admin_delete_upload($oldImage);
                $data['image'] = '';
            }

            $data['itinerary_pdf'] = admin_apply_pdf_field(
                'itinerary_pdf_file',
                'remove_itinerary_pdf',
                (string) ($row['itinerary_pdf'] ?? ''),
                'packages'
            );
            $takeUploadError();
            $data['price_chart_pdf'] = admin_apply_pdf_field(
                'price_chart_pdf_file',
                'remove_price_chart_pdf',
                (string) ($row['price_chart_pdf'] ?? ''),
                'packages'
            );
            $takeUploadError();
        }

        if (!$errors) {
            if ($id) {
                admin_package_update($id, $data);
                admin_remove_missing_uploads($oldGallery, $gallery);
                flash_set('success', 'Package updated.');
            } else {
                admin_package_insert($data);
                flash_set('success', 'Package created.');
            }
            redirect('admin/packages/index.php');
        }
        $row = array_merge($row ?: [], $data);
    }
}

$selectedTypes = package_selected_types($row);
$typeOptions = package_type_options();
foreach ($selectedTypes as $type) {
    if (!isset($typeOptions[$type])) {
        $typeOptions[$type] = ucwords(str_replace('-', ' ', $type));
    }
}
$selectedDestinations = package_selected_destinations($row);
$highlights = implode("\n", json_decode_array($row['highlights_json'] ?? null));
$itineraryText = itinerary_to_textarea(json_decode_array($row['itinerary_json'] ?? null));
$galleryPaths = json_decode_array($row['gallery_json'] ?? null);

$durationValue = post('duration') !== ''
    ? post('duration')
    : format_package_duration((int) ($row['days'] ?? 4), (int) ($row['nights'] ?? 3));
$nights = (int) (parse_package_duration($durationValue)['nights'] ?? 0);
$stays = package_stays_from_row($row, $nights);

$placeOptions = [];
foreach ($places as $place) {
    $placeOptions[] = ['slug' => $place['slug'], 'label' => $place['label']];
}

ob_start();
?>
<div class="admin-panel">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($errors): ?>
      <div class="admin-alert admin-alert--err"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <div class="form-grid">
      <section class="form-section">
        <h2 class="form-section__title">Package</h2>
        <div class="form-group full">
          <label for="title">Title</label>
          <input class="form-control" id="title" name="title" required value="<?= e($row['title'] ?? '') ?>" placeholder="Wayanad 4 Days 3 Nights" />
        </div>
        <div class="form-group full">
          <label for="card_text">Card text</label>
          <textarea class="form-control" id="card_text" name="card_text" rows="3"><?= e($row['card_text'] ?? '') ?></textarea>
          <p class="help-text">Short line shown on listing cards.</p>
        </div>
        <div class="form-group full">
          <label for="overview">Overview</label>
          <textarea class="form-control" id="overview" name="overview" rows="5"><?= e($row['overview'] ?? '') ?></textarea>
        </div>
        <div class="form-group full">
          <label for="highlights">Highlights (one per line)</label>
          <textarea class="form-control" id="highlights" name="highlights" rows="4"><?= e($highlights) ?></textarea>
        </div>
        <div class="form-group full">
          <label>Type</label>
          <div class="checks">
            <?php foreach ($typeOptions as $value => $label): ?>
              <label><input type="checkbox" name="types[]" value="<?= e($value) ?>" <?= in_array($value, $selectedTypes, true) ? 'checked' : '' ?> /> <?= e($label) ?></label>
            <?php endforeach; ?>
          </div>
          <p class="help-text">Pick every group this trip suits.</p>
        </div>
        <div class="form-group full">
          <label>Destinations</label>
          <?php if (!$places): ?>
            <p class="help-text">Add places first so they can be selected here.</p>
          <?php else: ?>
            <div class="checks">
              <?php foreach ($places as $place): ?>
                <label><input type="checkbox" name="destinations[]" value="<?= e($place['slug']) ?>" <?= in_array($place['slug'], $selectedDestinations, true) ? 'checked' : '' ?> /> <?= e($place['label']) ?></label>
              <?php endforeach; ?>
            </div>
            <p class="help-text">Listing pages are set from these places.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Trip</h2>
        <div class="form-group">
          <label for="duration">Duration</label>
          <input class="form-control" id="duration" name="duration" required value="<?= e($durationValue) ?>" placeholder="4D 3N" />
        </div>
        <div class="form-group">
          <label for="pickup">Pickup / Drop</label>
          <input class="form-control" id="pickup" name="pickup" required value="<?= e($row['pickup'] ?? '') ?>" placeholder="Calicut" />
          <p class="help-text">Pickup and drop are the same place.</p>
        </div>
        <div class="form-group full" id="stays-field" data-places="<?= e(json_encode($placeOptions, JSON_UNESCAPED_UNICODE)) ?>">
          <label>Stays</label>
          <div class="stay-fields" data-stay-fields>
            <?php if ($nights < 1): ?>
              <p class="help-text">No overnight stays for this duration.</p>
            <?php else: ?>
              <?php for ($i = 0; $i < $nights; $i++): ?>
                <div class="stay-fields__item">
                  <label for="stay-<?= $i ?>">Night <?= $i + 1 ?></label>
                  <select class="form-control" id="stay-<?= $i ?>" name="stays[]">
                    <option value="">Select a place</option>
                    <?php foreach ($places as $place): ?>
                      <option value="<?= e($place['slug']) ?>" <?= (($stays[$i] ?? '') === $place['slug']) ? 'selected' : '' ?>><?= e($place['label']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endfor; ?>
            <?php endif; ?>
          </div>
          <p class="help-text">One stay per night, from the duration above.</p>
        </div>
        <div class="form-group full">
          <label for="itinerary">Itinerary (Day N | Title | Text — one day per line)</label>
          <textarea class="form-control" id="itinerary" name="itinerary" rows="8" placeholder="1 | Arrival | Drive to Wayanad and check in"><?= e($itineraryText) ?></textarea>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Media</h2>
        <p class="form-section__hint">Images up to 5 MB (JPG, PNG, WEBP, GIF). Uncheck Keep to remove a gallery image.</p>
        <div class="form-group full media-field">
          <label>Cover image</label>
          <?= admin_hero_preview($row['image'] ?? null) ?>
          <div class="media-live-preview" hidden>
            <div class="media-preview">
              <div class="media-preview__item media-preview__item--hero">
                <img id="package-hero-preview" alt="New cover preview" hidden />
              </div>
            </div>
          </div>
          <div class="media-drop">
            <label for="image_file">Upload cover image</label>
            <input class="form-control" id="image_file" type="file" name="image_file" accept="image/jpeg,image/png,image/webp,image/gif" data-preview-target="#package-hero-preview" <?= empty($row['image']) ? 'required' : '' ?> />
          </div>
          <?php if (!empty($row['image'])): ?>
            <label class="checks"><input type="checkbox" name="remove_image" value="1" /> Remove current cover image</label>
          <?php endif; ?>
        </div>
        <div class="form-group full media-field">
          <label>Gallery</label>
          <?= admin_media_preview_items($galleryPaths, 'gallery_keep') ?>
          <div class="media-drop">
            <label for="gallery_files">Upload gallery images</label>
            <input class="form-control" id="gallery_files" type="file" name="gallery_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-preview-list="#package-gallery-new" />
            <div id="package-gallery-new" class="media-preview" style="margin-top:0.75rem"></div>
          </div>
        </div>
      </section>

      <section class="form-section">
        <h2 class="form-section__title">Downloads &amp; publishing</h2>
        <p class="form-section__hint">PDFs up to 10 MB. Visitors can download whichever you upload.</p>
        <div class="form-group">
          <label for="itinerary_pdf_file">Itinerary PDF</label>
          <?php if (!empty($row['itinerary_pdf'])): ?>
            <p class="help-text"><a href="<?= e(asset_url((string) $row['itinerary_pdf'])) ?>" target="_blank" rel="noopener">Current itinerary PDF</a></p>
            <label class="checks"><input type="checkbox" name="remove_itinerary_pdf" value="1" /> Remove</label>
          <?php endif; ?>
          <input class="form-control" id="itinerary_pdf_file" type="file" name="itinerary_pdf_file" accept="application/pdf,.pdf" />
        </div>
        <div class="form-group">
          <label for="price_chart_pdf_file">Price chart PDF</label>
          <?php if (!empty($row['price_chart_pdf'])): ?>
            <p class="help-text"><a href="<?= e(asset_url((string) $row['price_chart_pdf'])) ?>" target="_blank" rel="noopener">Current price chart PDF</a></p>
            <label class="checks"><input type="checkbox" name="remove_price_chart_pdf" value="1" /> Remove</label>
          <?php endif; ?>
          <input class="form-control" id="price_chart_pdf_file" type="file" name="price_chart_pdf_file" accept="application/pdf,.pdf" />
        </div>
        <div class="form-group">
          <label for="sort_order">Order</label>
          <input class="form-control" id="sort_order" type="number" name="sort_order" value="<?= e((string) ($row['sort_order'] ?? '0')) ?>" />
        </div>
        <div class="form-group">
          <label>Featured</label>
          <div class="checks">
            <label><input type="checkbox" name="is_featured" value="1" <?= !empty($row['is_featured']) ? 'checked' : '' ?> /> Featured</label>
          </div>
          <p class="help-text">Featured packages rank first in listings and show on the homepage.</p>
        </div>
      </section>
    </div>

    <div class="form-actions">
      <button class="btn btn--primary" type="submit">Save package</button>
      <a class="btn btn--secondary" href="<?= e(url('admin/packages/index.php')) ?>">Cancel</a>
    </div>
  </form>
</div>
<script>
(function () {
  var duration = document.getElementById("duration");
  var root = document.getElementById("stays-field");
  if (!duration || !root) return;
  var fields = root.querySelector("[data-stay-fields]");
  var places = [];
  try {
    places = JSON.parse(root.getAttribute("data-places") || "[]");
  } catch (err) {
    places = [];
  }

  function nightsFromDuration(value) {
    var text = String(value || "");
    var match = text.match(/(\d+)\s*[dD](?:ays?)?[^\d]*(\d+)\s*[nN](?:ights?)?/) || text.match(/^(\d+)\s*[\/\-]\s*(\d+)$/);
    return match ? Math.max(0, parseInt(match[2], 10)) : 0;
  }

  function currentValues() {
    return Array.prototype.map.call(fields.querySelectorAll("select"), function (el) {
      return el.value;
    });
  }

  function escapeHtml(value) {
    return String(value).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
  }

  function render(nights, values) {
    if (nights < 1) {
      fields.innerHTML = '<p class="help-text">No overnight stays for this duration.</p>';
      return;
    }
    var html = "";
    for (var i = 0; i < nights; i++) {
      html += '<div class="stay-fields__item"><label for="stay-' + i + '">Night ' + (i + 1) + "</label>";
      html += '<select class="form-control" id="stay-' + i + '" name="stays[]"><option value="">Select a place</option>';
      places.forEach(function (place) {
        var selected = place.slug === values[i] ? " selected" : "";
        html += '<option value="' + escapeHtml(place.slug) + '"' + selected + ">" + escapeHtml(place.label) + "</option>";
      });
      html += "</select></div>";
    }
    fields.innerHTML = html;
  }

  function update() {
    render(nightsFromDuration(duration.value), currentValues());
  }

  duration.addEventListener("input", update);
  duration.addEventListener("change", update);
})();
</script>
<?php
$adminContent = ob_get_clean();
$pageTitle = $id ? 'Edit package' : 'Add package';
$activeNav = 'packages';
require dirname(__DIR__) . '/_layout.php';
