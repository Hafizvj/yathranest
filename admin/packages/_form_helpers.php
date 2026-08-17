<?php

function lines_to_array(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function csv_to_array(string $text): array
{
    $parts = array_map('trim', explode(',', $text));
    return array_values(array_filter($parts, static fn($p) => $p !== ''));
}

function itinerary_to_textarea(array $items): string
{
    $lines = [];
    foreach ($items as $item) {
        $lines[] = ($item['day'] ?? '') . ' | ' . ($item['title'] ?? '') . ' | ' . ($item['text'] ?? '');
    }
    return implode("\n", $lines);
}

function textarea_to_itinerary(string $text): array
{
    $out = [];
    foreach (lines_to_array($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 3));
        $out[] = [
            'day' => (int) ($parts[0] ?? 1),
            'title' => $parts[1] ?? '',
            'text' => $parts[2] ?? '',
        ];
    }
    return $out;
}

function package_form_data_from_post(): array
{
    $pages = isset($_POST['pages']) && is_array($_POST['pages']) ? array_values($_POST['pages']) : [];
    $destinations = csv_to_array(post('destinations'));
    $highlights = lines_to_array(post('highlights'));
    $gallery = admin_collect_media_paths('gallery_keep', 'gallery_paths');
    $itinerary = textarea_to_itinerary(post('itinerary'));
    $slug = post('slug') !== '' ? post('slug') : slugify(post('title'));
    $days = max(1, (int) post('days', '1'));
    $nights = max(0, (int) post('nights', (string) max(0, $days - 1)));
    $duration = post('duration_bucket');
    if ($duration === '') {
        if ($days <= 4) {
            $duration = '2-4';
        } elseif ($days <= 7) {
            $duration = '5-7';
        } else {
            $duration = '8-10';
        }
    }
    return [
        'slug' => $slug,
        'sheet' => post('sheet'),
        'group_name' => post('group_name'),
        'pickup' => post('pickup'),
        'drop_point' => post('drop_point'),
        'pickup_slug' => post('pickup_slug') !== '' ? post('pickup_slug') : slugify(post('pickup')),
        'days' => $days,
        'nights' => $nights,
        'stay_split' => post('stay_split'),
        'stay_summary' => post('stay_summary'),
        'destinations_json' => json_encode($destinations, JSON_UNESCAPED_UNICODE),
        'dest_line' => post('dest_line'),
        'pages_json' => json_encode($pages, JSON_UNESCAPED_UNICODE),
        'type' => post('type', 'leisure'),
        'state' => post('state'),
        'duration_bucket' => $duration,
        'title' => post('title'),
        'overview' => post('overview'),
        'card_text' => post('card_text'),
        'highlights_json' => json_encode($highlights, JSON_UNESCAPED_UNICODE),
        'itinerary_json' => json_encode($itinerary, JSON_UNESCAPED_UNICODE),
        'image' => post('image'),
        'gallery_json' => json_encode($gallery, JSON_UNESCAPED_UNICODE),
        'has_houseboat' => isset($_POST['has_houseboat']) ? 1 : 0,
        'accommodation' => post('accommodation'),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'sort_order' => (int) post('sort_order', '0'),
    ];
}

function package_fields(): array
{
    return [
        'slug', 'sheet', 'group_name', 'pickup', 'drop_point', 'pickup_slug',
        'days', 'nights', 'stay_split', 'stay_summary', 'destinations_json', 'dest_line',
        'pages_json', 'type', 'state', 'duration_bucket', 'title', 'overview', 'card_text',
        'highlights_json', 'itinerary_json', 'image', 'gallery_json', 'has_houseboat',
        'accommodation', 'is_published', 'sort_order',
    ];
}

function admin_package_insert(array $data): int
{
    $fields = package_fields();
    $cols = implode(', ', $fields);
    $ph = implode(', ', array_fill(0, count($fields), '?'));
    $stmt = db()->prepare("INSERT INTO packages ({$cols}) VALUES ({$ph})");
    $vals = [];
    foreach ($fields as $f) {
        $vals[] = $data[$f];
    }
    $stmt->execute($vals);
    return (int) db()->lastInsertId();
}

function admin_package_update(int $id, array $data): void
{
    $fields = package_fields();
    $sets = implode(', ', array_map(static fn($f) => "$f = ?", $fields));
    $stmt = db()->prepare("UPDATE packages SET {$sets} WHERE id = ?");
    $vals = [];
    foreach ($fields as $f) {
        $vals[] = $data[$f];
    }
    $vals[] = $id;
    $stmt->execute($vals);
}

function admin_upload_max_bytes(): int
{
    return 5 * 1024 * 1024;
}

function admin_upload_last_error(?string $set = null): ?string
{
    static $error = null;
    if ($set !== null) {
        $error = $set === '' ? null : $set;
    }
    return $error;
}

function admin_upload_error_message(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Image is too large. Maximum size is 5 MB.';
        case UPLOAD_ERR_PARTIAL:
            return 'Image upload was incomplete. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'No image was selected.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            return 'Server could not store the uploaded image.';
        default:
            return 'Image upload failed.';
    }
}

function admin_is_upload_path(?string $path): bool
{
    $path = ltrim((string) $path, '/');
    return $path !== '' && strpos($path, 'uploads/') === 0 && strpos($path, '..') === false;
}

function admin_delete_upload(?string $path): void
{
    if (!admin_is_upload_path($path)) {
        return;
    }
    $relative = ltrim((string) $path, '/');
    $full = rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . substr($relative, strlen('uploads/'));
    $full = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full);
    $root = realpath((string) config('upload_dir'));
    $real = realpath($full);
    if ($root && $real && strpos($real, $root) === 0 && is_file($real)) {
        @unlink($real);
    }
}

/**
 * Store an uploaded image under uploads/{subdir}/.
 * Returns the relative path on success, or null on failure (see admin_upload_last_error()).
 */
function admin_store_upload(array $file, string $subdir): ?string
{
    admin_upload_last_error('');
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        admin_upload_last_error(admin_upload_error_message($error));
        return null;
    }

    $subdir = trim(str_replace(['..', '\\'], '', $subdir), '/');
    if ($subdir === '') {
        admin_upload_last_error('Invalid upload folder.');
        return null;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        admin_upload_last_error('Invalid upload.');
        return null;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > admin_upload_max_bytes()) {
        admin_upload_last_error('Image must be between 1 byte and 5 MB.');
        return null;
    }

    $original = (string) ($file['name'] ?? '');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowedExt, true)) {
        admin_upload_last_error('Allowed image types: JPG, PNG, WEBP, GIF.');
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $allowedMime = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
        'image/gif' => ['gif'],
    ];
    if (!isset($allowedMime[$mime]) || !in_array($ext, $allowedMime[$mime], true)) {
        admin_upload_last_error('File is not a valid image.');
        return null;
    }

    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        admin_upload_last_error('File is not a valid image.');
        return null;
    }

    if ($ext === 'jpeg') {
        $ext = 'jpg';
    }

    $dir = rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        admin_upload_last_error('Could not create upload folder.');
        return null;
    }

    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        admin_upload_last_error('Could not save uploaded image.');
        return null;
    }
    @chmod($dest, 0644);

    return 'uploads/' . $subdir . '/' . $name;
}

/**
 * Apply a single file upload onto a stored path, optionally deleting the previous upload.
 */
function admin_apply_image_upload(array $file, string $subdir, string $currentPath = ''): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return null;
    }
    $uploaded = admin_store_upload($file, $subdir);
    if ($uploaded) {
        if ($currentPath !== '' && $currentPath !== $uploaded) {
            admin_delete_upload($currentPath);
        }
        return $uploaded;
    }
    return null;
}

/**
 * Build a media path list from keep[] checkboxes, optional textarea paths, and new uploads.
 *
 * @param array|null $files typically $_FILES['gallery_files']
 */
function admin_collect_media_paths(string $keepKey, string $textareaKey = '', ?array $files = null, string $subdir = ''): array
{
    $keep = [];
    if (isset($_POST[$keepKey]) && is_array($_POST[$keepKey])) {
        foreach ($_POST[$keepKey] as $path) {
            $path = trim((string) $path);
            if ($path !== '' && strpos($path, '..') === false) {
                $keep[] = $path;
            }
        }
    }
    if ($textareaKey !== '') {
        $keep = array_merge($keep, lines_to_array(post($textareaKey)));
    }
    if ($files && $subdir !== '') {
        $keep = array_merge($keep, admin_store_uploads_many($files, $subdir));
    }

    $out = [];
    foreach ($keep as $path) {
        $path = trim((string) $path);
        if ($path === '' || strpos($path, '..') !== false) {
            continue;
        }
        if (!in_array($path, $out, true)) {
            $out[] = $path;
        }
    }
    return $out;
}

function admin_store_uploads_many(array $files, string $subdir): array
{
    $out = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        if (!empty($files['name'])) {
            $one = admin_store_upload($files, $subdir);
            if ($one) {
                $out[] = $one;
            }
        }
        return $out;
    }

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
        $path = admin_store_upload($file, $subdir);
        if ($path) {
            $out[] = $path;
        }
    }
    return $out;
}

function admin_remove_missing_uploads(array $before, array $after): void
{
    foreach ($before as $path) {
        if (!in_array($path, $after, true)) {
            admin_delete_upload((string) $path);
        }
    }
}

function admin_media_preview_items(array $paths, string $keepName = 'gallery_keep', string $prefix = 'assets/images/'): string
{
    if (!$paths) {
        return '<p class="help-text">No images yet.</p>';
    }
    $html = '<div class="media-preview">';
    foreach ($paths as $i => $path) {
        $path = (string) $path;
        if ($path === '') {
            continue;
        }
        $id = $keepName . '_' . $i;
        $html .= '<div class="media-preview__item">'
            . '<img src="' . e(image_url($path, $prefix)) . '" alt="" />'
            . '<label for="' . e($id) . '">'
            . '<input type="checkbox" id="' . e($id) . '" name="' . e($keepName) . '[]" value="' . e($path) . '" checked /> Keep'
            . '</label>'
            . '</div>';
    }
    $html .= '</div>';
    return $html;
}

function admin_hero_preview(?string $path, string $prefix = 'assets/images/'): string
{
    if (!$path) {
        return '<p class="help-text">No hero image set.</p>';
    }
    return '<div class="media-preview">'
        . '<div class="media-preview__item media-preview__item--hero">'
        . '<img src="' . e(image_url($path, $prefix)) . '" alt="Current image" />'
        . '</div></div>';
}
