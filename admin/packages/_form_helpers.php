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

function format_package_duration(int $days, int $nights): string
{
    return $days . 'D ' . $nights . 'N';
}

/** Reads "4D 3N", "4 Days 3 Nights" or "4/3" into days and nights. */
function parse_package_duration(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    if (preg_match('/(\d+)\s*[dD](?:ays?)?[^\d]*(\d+)\s*[nN](?:ights?)?/', $text, $m)) {
        return ['days' => max(1, (int) $m[1]), 'nights' => max(0, (int) $m[2])];
    }
    if (preg_match('/^(\d+)\s*[\/\-]\s*(\d+)$/', $text, $m)) {
        return ['days' => max(1, (int) $m[1]), 'nights' => max(0, (int) $m[2])];
    }
    return null;
}

/**
 * Days and nights come from two number inputs; the older "4D 3N" text field
 * is still accepted so saved links and scripts keep working.
 */
function package_days_nights_from_post(?array $existing = null): array
{
    if (post('days') !== '' || post('nights') !== '') {
        return [
            'days' => max(1, (int) post('days', '1')),
            'nights' => max(0, (int) post('nights', '0')),
        ];
    }
    $parsed = parse_package_duration(post('duration'));
    if ($parsed) {
        return $parsed;
    }
    $days = max(1, (int) ($existing['days'] ?? 1));
    return ['days' => $days, 'nights' => max(0, (int) ($existing['nights'] ?? max(0, $days - 1)))];
}

/**
 * Values from a chip field: the chips themselves, plus whatever is still
 * sitting in the entry box. Falls back to the older one-per-line textarea.
 */
function chips_from_post(string $name): array
{
    $out = [];
    if (isset($_POST[$name]) && is_array($_POST[$name])) {
        foreach ($_POST[$name] as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $out, true)) {
                $out[] = $value;
            }
        }
    } else {
        $out = lines_to_array(post($name));
    }
    foreach (lines_to_array(post($name . '_extra')) as $value) {
        if (!in_array($value, $out, true)) {
            $out[] = $value;
        }
    }
    return $out;
}

function package_highlights_from_post(): array
{
    return chips_from_post('highlights');
}

function place_tags_from_post(): array
{
    $tags = chips_from_post('tags');
    // Tags used to be one comma-separated field.
    if (count($tags) === 1 && strpos($tags[0], ',') !== false) {
        $tags = array_values(array_filter(array_map('trim', explode(',', $tags[0]))));
    }
    return $tags;
}

/** One itinerary entry per day row; empty rows are dropped. */
function package_itinerary_from_post(): array
{
    if (!isset($_POST['itinerary_title']) || !is_array($_POST['itinerary_title'])) {
        return textarea_to_itinerary(post('itinerary'));
    }
    $titles = array_values($_POST['itinerary_title']);
    $texts = isset($_POST['itinerary_text']) && is_array($_POST['itinerary_text'])
        ? array_values($_POST['itinerary_text'])
        : [];
    $out = [];
    foreach ($titles as $i => $title) {
        $title = trim((string) $title);
        $text = trim((string) ($texts[$i] ?? ''));
        if ($title === '' && $text === '') {
            continue;
        }
        $out[] = ['day' => count($out) + 1, 'title' => $title, 'text' => $text];
    }
    return $out;
}

function package_duration_bucket(int $days): string
{
    if ($days <= 4) {
        return '2-4';
    }
    if ($days <= 7) {
        return '5-7';
    }
    return '8-10';
}

/** Appends -2, -3 ... until the slug is free in the given table. */
function unique_slug(string $table, string $base, string $fallback, int $excludeId = 0): string
{
    $slug = $base !== '' ? $base : $fallback;
    $check = db()->prepare('SELECT id FROM ' . $table . ' WHERE slug = ? AND id <> ? LIMIT 1');
    $candidate = $slug;
    $i = 2;
    while (true) {
        $check->execute([$candidate, $excludeId]);
        if (!$check->fetch()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i;
        $i++;
    }
}

function unique_package_slug(string $base, int $excludeId = 0): string
{
    return unique_slug('packages', $base, 'package', $excludeId);
}

function unique_place_slug(string $base, int $excludeId = 0): string
{
    return unique_slug('places', $base, 'place', $excludeId);
}

function package_selected_types(?array $row = null): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $types = isset($_POST['types']) && is_array($_POST['types']) ? array_values($_POST['types']) : [];
    } elseif ($row) {
        $types = json_decode_array($row['types_json'] ?? null);
        if (!$types && !empty($row['type'])) {
            $types = [(string) $row['type']];
        }
    } else {
        $types = [];
    }
    $out = [];
    foreach ($types as $type) {
        $type = strtolower(trim((string) $type));
        if ($type !== '' && !in_array($type, $out, true)) {
            $out[] = $type;
        }
    }
    return $out;
}

function package_selected_destinations(?array $row = null): array
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST['destinations']) && is_array($_POST['destinations'])
            ? array_values(array_filter(array_map('strval', $_POST['destinations'])))
            : [];
    }
    return $row ? json_decode_array($row['destinations_json'] ?? null) : [];
}

/** Expands a legacy "2 wayanad 1 ooty" split into one slug per night. */
function parse_stay_split_to_nights(string $split, int $nights): array
{
    $split = strtolower(trim($split));
    if ($split === '' || $nights < 1) {
        return array_fill(0, max(0, $nights), '');
    }
    $out = [];
    if (preg_match_all('/(?:(\d+)\s+)?([a-z][a-z0-9-]*)/', $split, $matches, PREG_SET_ORDER)) {
        $hasCount = false;
        foreach ($matches as $m) {
            if (($m[1] ?? '') !== '') {
                $hasCount = true;
                break;
            }
        }
        foreach ($matches as $m) {
            $count = $hasCount ? (int) ($m[1] !== '' ? $m[1] : 1) : 1;
            for ($i = 0; $i < $count; $i++) {
                $out[] = $m[2];
            }
        }
    }
    if (count($out) < $nights) {
        $out = array_merge($out, array_fill(0, $nights - count($out), $out[0] ?? ''));
    }
    return array_slice($out, 0, $nights);
}

/**
 * One stay slug per night, taken from the submitted form, the stored stays,
 * a legacy stay split, or failing that the package's destinations.
 */
function package_stays_from_row(?array $row, int $nights): array
{
    if ($nights < 1) {
        return [];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stays']) && is_array($_POST['stays'])) {
        $stays = array_values(array_map('strval', $_POST['stays']));
        if (count($stays) < $nights) {
            $stays = array_merge($stays, array_fill(0, $nights - count($stays), ''));
        }
        return array_slice($stays, 0, $nights);
    }
    if ($row) {
        $stored = json_decode_array($row['stays_json'] ?? null);
        if ($stored) {
            if (count($stored) < $nights) {
                $stored = array_merge($stored, array_fill(0, $nights - count($stored), (string) end($stored)));
            }
            return array_slice(array_map('strval', $stored), 0, $nights);
        }
        $fromSplit = parse_stay_split_to_nights((string) ($row['stay_split'] ?? ''), $nights);
        if (array_filter($fromSplit)) {
            return $fromSplit;
        }
        $dests = json_decode_array($row['destinations_json'] ?? null);
        if ($dests) {
            $out = [];
            for ($i = 0; $i < $nights; $i++) {
                $out[] = (string) ($dests[$i] ?? $dests[count($dests) - 1]);
            }
            return $out;
        }
    }
    return array_fill(0, $nights, '');
}

/** Collapses per-night slugs into the stored "2 wayanad 1 ooty" form. */
function stays_to_split(array $slugs): string
{
    $parts = [];
    foreach (stays_to_runs($slugs) as [$slug, $count]) {
        $parts[] = $count . ' ' . $slug;
    }
    return implode(' ', $parts);
}

/** Collapses per-night slugs into "2 nights Wayanad · 1 night Ooty". */
function stays_to_summary(array $slugs, array $places): string
{
    $parts = [];
    foreach (stays_to_runs($slugs) as [$slug, $count]) {
        $label = $places[$slug]['label'] ?? $slug;
        $parts[] = $count . ' night' . ($count === 1 ? '' : 's') . ' ' . $label;
    }
    return implode(' · ', $parts);
}

/**
 * Groups consecutive nights in the same place.
 * @return array<int, array{0:string,1:int}>
 */
function stays_to_runs(array $slugs): array
{
    $runs = [];
    $current = '';
    $count = 0;
    foreach ($slugs as $slug) {
        $slug = trim((string) $slug);
        if ($slug === '') {
            continue;
        }
        if ($slug === $current) {
            $count++;
            continue;
        }
        if ($current !== '') {
            $runs[] = [$current, $count];
        }
        $current = $slug;
        $count = 1;
    }
    if ($current !== '') {
        $runs[] = [$current, $count];
    }
    return $runs;
}

/**
 * Builds a full package row from the minimal form. Everything the form no
 * longer asks for is derived here, or carried over from $existing.
 */
function package_form_data_from_post(?array $existing = null): array
{
    $title = post('title');
    $existingSlug = trim((string) ($existing['slug'] ?? ''));
    $slug = $existingSlug !== '' ? $existingSlug : unique_package_slug(slugify($title), (int) ($existing['id'] ?? 0));

    ['days' => $days, 'nights' => $nights] = package_days_nights_from_post($existing);

    $pickup = post('pickup');

    $types = package_selected_types($existing);
    $destSlugs = package_selected_destinations($existing);
    $places = places_all();

    $destLabels = [];
    $pages = [];
    foreach ($destSlugs as $destSlug) {
        $place = $places[$destSlug] ?? null;
        $destLabels[] = $place['label'] ?? $destSlug;
        $scopes = $place['catalog_scopes'] ?? [place_default_catalog_scope((string) $destSlug)];
        foreach ($scopes as $scope) {
            if ($scope !== '' && !in_array($scope, $pages, true)) {
                $pages[] = $scope;
            }
        }
    }
    if (!$pages) {
        $pages = json_decode_array($existing['pages_json'] ?? null) ?: ['kerala'];
    }

    $stays = package_stays_from_row($existing, $nights);
    $staySummary = stays_to_summary($stays, $places);

    return [
        'slug' => $slug,
        'sheet' => (string) ($existing['sheet'] ?? ''),
        'group_name' => $destLabels[0] ?? (string) ($existing['group_name'] ?? ''),
        'pickup' => $pickup,
        'drop_point' => $pickup,
        'pickup_slug' => $pickup !== '' ? slugify($pickup) : (string) ($existing['pickup_slug'] ?? ''),
        'days' => $days,
        'nights' => $nights,
        'stay_split' => stays_to_split($stays),
        'stay_summary' => $staySummary,
        'stays_json' => json_encode($stays, JSON_UNESCAPED_UNICODE),
        'destinations_json' => json_encode($destSlugs, JSON_UNESCAPED_UNICODE),
        'dest_line' => implode(' · ', $destLabels) ?: (string) ($existing['dest_line'] ?? ''),
        'pages_json' => json_encode($pages, JSON_UNESCAPED_UNICODE),
        'type' => $types[0] ?? (string) ($existing['type'] ?? 'family'),
        'types_json' => json_encode($types, JSON_UNESCAPED_UNICODE),
        'state' => (string) ($existing['state'] ?? ''),
        'duration_bucket' => package_duration_bucket($days),
        'title' => $title,
        'overview' => post('overview'),
        'card_text' => post('card_text'),
        'highlights_json' => json_encode(package_highlights_from_post(), JSON_UNESCAPED_UNICODE),
        'itinerary_json' => json_encode(package_itinerary_from_post(), JSON_UNESCAPED_UNICODE),
        'image' => (string) ($existing['image'] ?? ''),
        'gallery_json' => json_encode(json_decode_array($existing['gallery_json'] ?? null), JSON_UNESCAPED_UNICODE),
        'itinerary_pdf' => (string) ($existing['itinerary_pdf'] ?? ''),
        'price_chart_pdf' => (string) ($existing['price_chart_pdf'] ?? ''),
        'has_houseboat' => (int) ($existing['has_houseboat'] ?? 0),
        'accommodation' => $staySummary !== '' ? $staySummary : (string) ($existing['accommodation'] ?? ''),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_published' => $existing ? (int) ($existing['is_published'] ?? 1) : 1,
        'sort_order' => (int) post('sort_order', (string) ($existing['sort_order'] ?? '0')),
    ];
}

function package_form_validate(array $data): array
{
    $errors = [];
    if (($data['title'] ?? '') === '') {
        $errors[] = 'Title is required.';
    }
    if (trim((string) ($data['overview'] ?? '')) === '') {
        $errors[] = 'Overview is required.';
    }
    if ((int) ($data['days'] ?? 0) < 1) {
        $errors[] = 'Duration needs at least one day.';
    }
    if ((int) ($data['nights'] ?? 0) > (int) ($data['days'] ?? 0)) {
        $errors[] = 'Nights cannot be more than days.';
    }
    if (trim((string) ($data['pickup'] ?? '')) === '') {
        $errors[] = 'Pickup / drop is required.';
    }
    if (json_decode_array($data['types_json'] ?? null) === []) {
        $errors[] = 'Select at least one type.';
    }
    if (json_decode_array($data['destinations_json'] ?? null) === []) {
        $errors[] = 'Select at least one destination.';
    }
    $nights = (int) ($data['nights'] ?? 0);
    $stays = json_decode_array($data['stays_json'] ?? null);
    if ($nights > 0) {
        if (count($stays) !== $nights) {
            $errors[] = 'Choose a stay for each night.';
        } else {
            foreach ($stays as $i => $stay) {
                if (trim((string) $stay) === '') {
                    $errors[] = 'Choose a stay for night ' . ($i + 1) . '.';
                    break;
                }
            }
        }
    }
    return $errors;
}

function package_fields(): array
{
    return [
        'slug', 'sheet', 'group_name', 'pickup', 'drop_point', 'pickup_slug',
        'days', 'nights', 'stay_split', 'stay_summary', 'stays_json', 'destinations_json', 'dest_line',
        'pages_json', 'type', 'types_json', 'state', 'duration_bucket', 'title', 'overview', 'card_text',
        'highlights_json', 'itinerary_json', 'image', 'gallery_json', 'itinerary_pdf', 'price_chart_pdf',
        'has_houseboat', 'accommodation', 'is_featured', 'is_published', 'sort_order',
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
 * Apply a single file upload onto a stored path.
 * Does not delete the previous file (shared media library). Registers the new path in media.
 */
function admin_apply_image_upload(array $file, string $subdir, string $currentPath = ''): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return null;
    }
    $uploaded = admin_store_upload($file, $subdir);
    if ($uploaded) {
        if (function_exists('media_register_stored_path')) {
            media_register_stored_path($uploaded, (string) ($file['name'] ?? ''));
        }
        return $uploaded;
    }
    return null;
}

function admin_pdf_max_bytes(): int
{
    return 10 * 1024 * 1024;
}

/**
 * Store an uploaded PDF under uploads/{subdir}/ and return its relative path,
 * or null on failure (see admin_upload_last_error()).
 */
function admin_store_pdf(array $file, string $subdir): ?string
{
    admin_upload_last_error('');
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        admin_upload_last_error('PDF upload failed.');
        return null;
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        admin_upload_last_error('Invalid upload.');
        return null;
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > admin_pdf_max_bytes()) {
        admin_upload_last_error('PDF must be between 1 byte and 10 MB.');
        return null;
    }

    if (strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'pdf') {
        admin_upload_last_error('Only PDF files are allowed here.');
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ((string) $finfo->file($tmp) !== 'application/pdf') {
        admin_upload_last_error('File is not a valid PDF.');
        return null;
    }

    $subdir = trim(str_replace(['..', '\\'], '', $subdir), '/');
    $dir = rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        admin_upload_last_error('Could not create upload folder.');
        return null;
    }

    $name = bin2hex(random_bytes(8)) . '.pdf';
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        admin_upload_last_error('Could not save uploaded PDF.');
        return null;
    }
    @chmod($dest, 0644);

    return 'uploads/' . $subdir . '/' . $name;
}

/**
 * Resolve one PDF field: a new upload replaces the stored file, a remove
 * checkbox clears it, otherwise the current path stays.
 */
function admin_apply_pdf_field(string $fileKey, string $removeKey, string $current, string $subdir): string
{
    if (!empty($_FILES[$fileKey]['name'])) {
        $uploaded = admin_store_pdf($_FILES[$fileKey], $subdir);
        if ($uploaded) {
            if ($current !== '' && $current !== $uploaded) {
                admin_delete_upload($current);
            }
            return $uploaded;
        }
        return $current;
    }
    if (!empty($_POST[$removeKey])) {
        admin_delete_upload($current);
        return '';
    }
    return $current;
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
                if (function_exists('media_register_stored_path')) {
                    media_register_stored_path($one, (string) ($files['name'] ?? ''));
                }
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
            if (function_exists('media_register_stored_path')) {
                media_register_stored_path($path, (string) $file['name']);
            }
            $out[] = $path;
        }
    }
    return $out;
}

/**
 * Previously deleted files dropped from galleries. With the shared media library,
 * entity forms only update path lists — physical deletes happen from Media admin.
 */
function admin_remove_missing_uploads(array $before, array $after): void
{
    // Intentionally no-op: reused images must not be unlinked when one entity drops them.
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

// Media library helpers (media_register_*, media_list, …)
require_once dirname(__DIR__) . '/_media.php';

