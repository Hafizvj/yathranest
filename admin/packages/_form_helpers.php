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

/** Unique highlight strings from all packages, for reuse suggestions. */
function package_highlight_suggestions(): array
{
    $out = [];
    try {
        $rows = db()->query('SELECT highlights_json FROM packages WHERE highlights_json IS NOT NULL')->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
    foreach ($rows as $row) {
        foreach (json_decode_array($row['highlights_json'] ?? null) as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $key = mb_strtolower($item);
            if (!isset($out[$key])) {
                $out[$key] = $item;
            }
        }
    }
    $list = array_values($out);
    natcasesort($list);
    return array_values($list);
}

/** Unique pickup labels from all packages, for reuse suggestions. */
function package_pickup_suggestions(): array
{
    $out = [];
    try {
        $rows = db()->query(
            "SELECT DISTINCT pickup FROM packages WHERE pickup IS NOT NULL AND pickup <> '' ORDER BY pickup ASC"
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
    foreach ($rows as $row) {
        $pickup = trim((string) ($row['pickup'] ?? ''));
        if ($pickup === '') {
            continue;
        }
        $key = mb_strtolower($pickup);
        if (!isset($out[$key])) {
            $out[$key] = $pickup;
        }
    }
    $list = array_values($out);
    natcasesort($list);
    return array_values($list);
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
function package_save_mode_from_post(): string
{
    $mode = trim(post('save_mode', 'publish'));

    return $mode === 'draft' ? 'draft' : 'publish';
}

function package_draft_title(string $title): string
{
    $title = trim($title);

    return $title !== '' ? $title : 'Untitled package';
}

function package_is_published_from_mode(string $mode): int
{
    return $mode === 'publish' ? 1 : 0;
}

function package_public_url(string $slug, bool $preview = false): string
{
    $path = 'pages/package-details.php?package=' . rawurlencode($slug);
    if ($preview) {
        $path .= '&preview=1';
    }

    return url($path);
}

function admin_package_by_id(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM packages WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

function package_form_data_from_post(?array $existing = null): array
{
    $saveMode = package_save_mode_from_post();
    $title = package_draft_title(post('title'));
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
        'is_published' => package_is_published_from_mode($saveMode),
        'sort_order' => (int) post('sort_order', (string) ($existing['sort_order'] ?? '0')),
    ];
}

function package_form_validate(array $data, string $mode = 'publish'): array
{
    $errors = [];
    if ($mode === 'publish') {
        if (trim((string) ($data['title'] ?? '')) === '' || ($data['title'] ?? '') === 'Untitled package') {
            $errors[] = 'Title is required.';
        }
        if (trim((string) ($data['overview'] ?? '')) === '') {
            $errors[] = 'Overview is required.';
        }
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

function admin_pdf_temp_dir(): string
{
    return rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'pdf-import';
}

function admin_cleanup_old_temp_pdfs(): void
{
    $dir = admin_pdf_temp_dir();
    if (!is_dir($dir)) {
        return;
    }
    $cutoff = time() - 86400;
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.pdf') ?: [] as $file) {
        if (@filemtime($file) !== false && filemtime($file) < $cutoff) {
            @unlink($file);
        }
    }
}

/**
 * Store an uploaded PDF in a temp folder for later finalize.
 *
 * @return array{token:string,filename:string}|null
 */
function admin_store_temp_pdf(array $file): ?array
{
    admin_upload_last_error('');
    admin_cleanup_old_temp_pdfs();

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE || ($file['name'] ?? '') === '') {
        admin_upload_last_error('No PDF was selected.');
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
        admin_upload_last_error('Only PDF files are allowed.');
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ((string) $finfo->file($tmp) !== 'application/pdf') {
        admin_upload_last_error('File is not a valid PDF.');
        return null;
    }

    $dir = admin_pdf_temp_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        admin_upload_last_error('Could not create temp folder.');
        return null;
    }

    $token = bin2hex(random_bytes(16));
    $dest = $dir . DIRECTORY_SEPARATOR . $token . '.pdf';
    if (!move_uploaded_file($tmp, $dest)) {
        admin_upload_last_error('Could not save uploaded PDF.');
        return null;
    }
    @chmod($dest, 0644);

    return [
        'token' => $token,
        'filename' => (string) ($file['name'] ?? 'package.pdf'),
    ];
}

function admin_temp_pdf_full_path(string $token): ?string
{
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        return null;
    }
    $path = admin_pdf_temp_dir() . DIRECTORY_SEPARATOR . $token . '.pdf';
    return is_file($path) ? $path : null;
}

/**
 * Move a temp PDF into uploads/packages/ and return the relative path.
 */
function admin_finalize_temp_pdf(string $token, string $subdir = 'packages'): ?string
{
    $full = admin_temp_pdf_full_path($token);
    if ($full === null) {
        admin_upload_last_error('Uploaded PDF session expired. Parse the PDF again.');
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
    if (!rename($full, $dest)) {
        admin_upload_last_error('Could not store itinerary PDF.');
        return null;
    }
    @chmod($dest, 0644);

    return 'uploads/' . $subdir . '/' . $name;
}

function package_resolve_place_slug(string $input, array $places): ?string
{
    $input = trim($input);
    if ($input === '') {
        return null;
    }
    if (isset($places[$input])) {
        return $input;
    }

    $needle = mb_strtolower($input);
    foreach ($places as $slug => $place) {
        if (mb_strtolower((string) $slug) === $needle) {
            return (string) $slug;
        }
        $label = mb_strtolower((string) ($place['label'] ?? ''));
        if ($label === $needle) {
            return (string) $slug;
        }
    }

    foreach ($places as $slug => $place) {
        $label = mb_strtolower((string) ($place['label'] ?? ''));
        if ($label !== '' && (str_contains($label, $needle) || str_contains($needle, $label))) {
            return (string) $slug;
        }
    }

    return null;
}

/**
 * @param list<string> $inputs
 * @return array{resolved:list<string>,unmatched:list<string>}
 */
function package_resolve_place_slugs(array $inputs, array $places): array
{
    $resolved = [];
    $unmatched = [];
    foreach ($inputs as $input) {
        $input = trim((string) $input);
        if ($input === '') {
            continue;
        }
        $slug = package_resolve_place_slug($input, $places);
        if ($slug !== null) {
            if (!in_array($slug, $resolved, true)) {
                $resolved[] = $slug;
            }
        } elseif (!in_array($input, $unmatched, true)) {
            $unmatched[] = $input;
        }
    }
    return ['resolved' => $resolved, 'unmatched' => $unmatched];
}

/**
 * @param list<string> $reserved
 */
function unique_package_slug_in_batch(string $base, array &$reserved, int $excludeId = 0): string
{
    $slug = unique_package_slug($base, $excludeId);
    $candidate = $slug;
    $i = 2;
    while (in_array($candidate, $reserved, true)) {
        $candidate = $slug . '-' . $i;
        $i++;
    }
    $reserved[] = $candidate;
    return $candidate;
}

/** @return list<string> */
function package_types_from_array(array $types): array
{
    $valid = array_keys(package_type_options());
    $out = [];
    foreach ($types as $type) {
        $type = strtolower(trim((string) $type));
        if ($type !== '' && in_array($type, $valid, true) && !in_array($type, $out, true)) {
            $out[] = $type;
        }
    }
    return $out;
}

/** @return list<array{day:int,title:string,text:string}> */
function package_itinerary_from_plan_array(array $plan): array
{
    $titles = isset($plan['itinerary_title']) && is_array($plan['itinerary_title'])
        ? array_values($plan['itinerary_title'])
        : [];
    $texts = isset($plan['itinerary_text']) && is_array($plan['itinerary_text'])
        ? array_values($plan['itinerary_text'])
        : [];

    if ($titles === [] && isset($plan['itinerary']) && is_array($plan['itinerary'])) {
        $out = [];
        foreach ($plan['itinerary'] as $day) {
            if (!is_array($day)) {
                continue;
            }
            $title = trim((string) ($day['title'] ?? ''));
            $text = trim((string) ($day['text'] ?? ''));
            if ($title === '' && $text === '') {
                continue;
            }
            $out[] = ['day' => count($out) + 1, 'title' => $title, 'text' => $text];
        }
        return $out;
    }

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

/**
 * Build package row data from a nested plan POST array.
 *
 * @param array<string,mixed> $plan
 * @param array{
 *   image?:string,
 *   gallery_json?:string,
 *   itinerary_pdf?:string,
 *   price_chart_pdf?:string,
 *   sort_order?:int,
 *   is_featured?:int
 * } $shared
 * @param list<string> $reservedSlugs
 */
function package_form_data_from_plan_post(array $plan, array $shared = [], array &$reservedSlugs = []): array
{
    $saveMode = (string) ($shared['save_mode'] ?? 'publish');
    $packageId = (int) ($plan['package_id'] ?? 0);
    $existing = $packageId > 0 ? admin_package_by_id($packageId) : null;

    $title = package_draft_title(trim((string) ($plan['title'] ?? '')));
    $planKey = trim((string) ($plan['plan_key'] ?? ''));
    if ($existing) {
        $slug = (string) $existing['slug'];
    } else {
        $slugBase = slugify($title);
        if ($slugBase === '' || $slugBase === 'untitled-package') {
            $slugBase = slugify((string) ($plan['plan_label'] ?? 'package'));
        }
        if ($planKey !== '' && !str_ends_with($slugBase, $planKey)) {
            $slugBase .= '-' . $planKey;
        }
        $slug = unique_package_slug_in_batch($slugBase, $reservedSlugs);
    }

    $days = max(1, (int) ($plan['days'] ?? 1));
    $nights = max(0, min($days, (int) ($plan['nights'] ?? 0)));
    $pickup = trim((string) ($plan['pickup'] ?? ''));

    $places = places_all();
    $destSlugs = isset($plan['destinations']) && is_array($plan['destinations'])
        ? array_values(array_filter(array_map('strval', $plan['destinations'])))
        : [];
    $types = package_types_from_array(isset($plan['types']) && is_array($plan['types']) ? $plan['types'] : []);

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
        $pages = ['kerala'];
    }

    $stays = [];
    if (isset($plan['stays']) && is_array($plan['stays'])) {
        $stays = array_values(array_map('strval', $plan['stays']));
    }
    if (count($stays) < $nights) {
        $stays = array_merge($stays, array_fill(0, $nights - count($stays), $destSlugs[count($destSlugs) - 1] ?? ''));
    }
    $stays = array_slice($stays, 0, $nights);
    $staySummary = stays_to_summary($stays, $places);

    $highlights = [];
    if (isset($plan['highlights']) && is_array($plan['highlights'])) {
        foreach ($plan['highlights'] as $h) {
            $h = trim((string) $h);
            if ($h !== '' && !in_array($h, $highlights, true)) {
                $highlights[] = $h;
            }
        }
    }

    return [
        'slug' => $slug,
        'sheet' => '',
        'group_name' => $destLabels[0] ?? '',
        'pickup' => $pickup,
        'drop_point' => $pickup,
        'pickup_slug' => $pickup !== '' ? slugify($pickup) : '',
        'days' => $days,
        'nights' => $nights,
        'stay_split' => stays_to_split($stays),
        'stay_summary' => $staySummary,
        'stays_json' => json_encode($stays, JSON_UNESCAPED_UNICODE),
        'destinations_json' => json_encode($destSlugs, JSON_UNESCAPED_UNICODE),
        'dest_line' => implode(' · ', $destLabels),
        'pages_json' => json_encode($pages, JSON_UNESCAPED_UNICODE),
        'type' => $types[0] ?? 'family',
        'types_json' => json_encode($types, JSON_UNESCAPED_UNICODE),
        'state' => '',
        'duration_bucket' => package_duration_bucket($days),
        'title' => $title,
        'overview' => trim((string) ($plan['overview'] ?? '')),
        'card_text' => trim((string) ($plan['card_text'] ?? '')),
        'highlights_json' => json_encode($highlights, JSON_UNESCAPED_UNICODE),
        'itinerary_json' => json_encode(package_itinerary_from_plan_array($plan), JSON_UNESCAPED_UNICODE),
        'image' => (string) ($plan['image'] ?? $shared['image'] ?? ($existing['image'] ?? '')),
        'gallery_json' => (string) ($plan['gallery_json'] ?? $shared['gallery_json'] ?? ($existing['gallery_json'] ?? '[]')),
        'itinerary_pdf' => (string) ($shared['itinerary_pdf'] ?? ($existing['itinerary_pdf'] ?? '')),
        'price_chart_pdf' => (string) ($plan['price_chart_pdf'] ?? $shared['price_chart_pdf'] ?? ($existing['price_chart_pdf'] ?? '')),
        'has_houseboat' => 0,
        'accommodation' => $staySummary,
        'is_featured' => (int) ($plan['is_featured'] ?? $shared['is_featured'] ?? ($existing['is_featured'] ?? 0)),
        'is_published' => package_is_published_from_mode($saveMode),
        'sort_order' => (int) ($plan['sort_order'] ?? $shared['sort_order'] ?? ($existing['sort_order'] ?? 0)),
    ];
}

/**
 * Apply standard single-package media uploads from POST to a data row.
 *
 * @return list<string> validation/upload errors
 */
function admin_package_apply_media_uploads(array &$data, ?array $existing = null, bool $requireCover = true): array
{
    $errors = [];
    $existing = $existing ?? [];
    $oldImage = (string) ($existing['image'] ?? $data['image'] ?? '');
    $oldGallery = json_decode_array($existing['gallery_json'] ?? $data['gallery_json'] ?? null);

    $takeUploadError = static function () use (&$errors): void {
        $message = admin_upload_last_error();
        if ($message) {
            $errors[] = $message;
            admin_upload_last_error('');
        }
    };

    $newHero = !empty($_FILES['image_file']['name']);
    $libraryCover = trim(post('library_image'));
    $removeHero = post('remove_image') === '1';
    $hasCover = $newHero || ($libraryCover !== '' && !$removeHero) || (!$removeHero && $oldImage !== '');

    if ($requireCover && !$hasCover) {
        $errors[] = 'A cover image is required.';
    }

    if ($errors) {
        return $errors;
    }

    $gallery = admin_collect_media_paths('gallery_keep', '', $_FILES['gallery_files'] ?? null, 'packages');
    $takeUploadError();
    if (count($gallery) > 10) {
        $gallery = array_slice($gallery, 0, 10);
    }
    $data['gallery_json'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

    if ($newHero) {
        $uploaded = admin_apply_image_upload($_FILES['image_file'], 'packages', $removeHero ? '' : $oldImage);
        $takeUploadError();
        if ($uploaded) {
            $data['image'] = $uploaded;
        }
    } elseif ($libraryCover !== '' && !$removeHero) {
        $data['image'] = ltrim($libraryCover, '/');
        media_ensure_row($data['image']);
    } elseif ($removeHero) {
        $data['image'] = '';
    } else {
        $data['image'] = $oldImage;
    }

    $data['itinerary_pdf'] = admin_apply_pdf_field(
        'itinerary_pdf_file',
        'remove_itinerary_pdf',
        (string) ($existing['itinerary_pdf'] ?? $data['itinerary_pdf'] ?? ''),
        'packages'
    );
    $takeUploadError();
    $data['price_chart_pdf'] = admin_apply_pdf_field(
        'price_chart_pdf_file',
        'remove_price_chart_pdf',
        (string) ($existing['price_chart_pdf'] ?? $data['price_chart_pdf'] ?? ''),
        'packages'
    );
    $takeUploadError();

    return $errors;
}

/**
 * @return array<string,mixed>|null
 */
function admin_plan_file_from_post(int $index, string $field): ?array
{
    if (!isset($_FILES['plans']['name'][$index][$field]) || $_FILES['plans']['name'][$index][$field] === '') {
        return null;
    }

    return [
        'name' => $_FILES['plans']['name'][$index][$field],
        'type' => $_FILES['plans']['type'][$index][$field],
        'tmp_name' => $_FILES['plans']['tmp_name'][$index][$field],
        'error' => $_FILES['plans']['error'][$index][$field],
        'size' => $_FILES['plans']['size'][$index][$field],
    ];
}

/**
 * Apply per-plan media uploads for PDF wizard step 3.
 *
 * @return list<string>
 */
function admin_package_apply_plan_media_uploads(array &$data, int $planIndex, ?array $existing = null, bool $requireCover = true): array
{
    $errors = [];
    $existing = $existing ?? [];
    $oldImage = (string) ($existing['image'] ?? $data['image'] ?? '');
    $oldGallery = json_decode_array($existing['gallery_json'] ?? $data['gallery_json'] ?? null);

    $takeUploadError = static function () use (&$errors): void {
        $message = admin_upload_last_error();
        if ($message) {
            $errors[] = $message;
            admin_upload_last_error('');
        }
    };

    $prefix = 'plans';
    $newHeroFile = admin_plan_file_from_post($planIndex, 'image_file');
    $libraryCover = trim((string) (($_POST['plans'][$planIndex]['library_image'] ?? '') ?: ''));
    $removeHero = (($_POST['plans'][$planIndex]['remove_image'] ?? '0') === '1');
    $newHero = $newHeroFile !== null;
    $hasCover = $newHero || ($libraryCover !== '' && !$removeHero) || (!$removeHero && $oldImage !== '');

    if ($requireCover && !$hasCover) {
        $errors[] = 'A cover image is required.';
    }

    if ($errors) {
        return $errors;
    }

    $galleryFiles = null;
    if (isset($_FILES['plans']['name'][$planIndex]['gallery_files'])) {
        $names = $_FILES['plans']['name'][$planIndex]['gallery_files'];
        if (is_array($names) ? in_array(true, array_map(static fn($n) => $n !== '', $names), true) : $names !== '') {
            $galleryFiles = [
                'name' => $_FILES['plans']['name'][$planIndex]['gallery_files'],
                'type' => $_FILES['plans']['type'][$planIndex]['gallery_files'],
                'tmp_name' => $_FILES['plans']['tmp_name'][$planIndex]['gallery_files'],
                'error' => $_FILES['plans']['error'][$planIndex]['gallery_files'],
                'size' => $_FILES['plans']['size'][$planIndex]['gallery_files'],
            ];
        }
    }

    $keep = [];
    if (isset($_POST['plans'][$planIndex]['gallery_keep']) && is_array($_POST['plans'][$planIndex]['gallery_keep'])) {
        foreach ($_POST['plans'][$planIndex]['gallery_keep'] as $path) {
            $path = trim((string) $path);
            if ($path !== '' && strpos($path, '..') === false) {
                $keep[] = $path;
            }
        }
    }
    if ($galleryFiles) {
        $keep = array_merge($keep, admin_store_uploads_many($galleryFiles, 'packages'));
    }
    $takeUploadError();
    if (count($keep) > 10) {
        $keep = array_slice($keep, 0, 10);
    }
    $data['gallery_json'] = json_encode(array_values(array_unique($keep)), JSON_UNESCAPED_UNICODE);

    if ($newHero && $newHeroFile) {
        $uploaded = admin_apply_image_upload($newHeroFile, 'packages', $removeHero ? '' : $oldImage);
        $takeUploadError();
        if ($uploaded) {
            $data['image'] = $uploaded;
        }
    } elseif ($libraryCover !== '' && !$removeHero) {
        $data['image'] = ltrim($libraryCover, '/');
        media_ensure_row($data['image']);
    } elseif ($removeHero) {
        $data['image'] = '';
    } else {
        $data['image'] = $oldImage;
    }

    $priceFile = admin_plan_file_from_post($planIndex, 'price_chart_pdf_file');
    if ($priceFile) {
        $uploadedPdf = admin_store_pdf($priceFile, 'packages');
        $takeUploadError();
        if ($uploadedPdf) {
            $oldPdf = (string) ($existing['price_chart_pdf'] ?? '');
            if ($oldPdf !== '' && $oldPdf !== $uploadedPdf) {
                admin_delete_upload($oldPdf);
            }
            $data['price_chart_pdf'] = $uploadedPdf;
        }
    } elseif (($_POST['plans'][$planIndex]['remove_price_chart_pdf'] ?? '0') === '1') {
        $oldPdf = (string) ($existing['price_chart_pdf'] ?? '');
        if ($oldPdf !== '') {
            admin_delete_upload($oldPdf);
        }
        $data['price_chart_pdf'] = '';
    }

    return $errors;
}

/**
 * Upsert a single package row from form data.
 *
 * @return array{id:int,slug:string,preview_url:string}
 */
function admin_package_upsert(array $data, int $packageId = 0): array
{
    if ($packageId > 0) {
        admin_package_update($packageId, $data);
        $id = $packageId;
    } else {
        $id = admin_package_insert($data);
    }

    return [
        'id' => $id,
        'slug' => (string) $data['slug'],
        'preview_url' => package_public_url((string) $data['slug'], true),
    ];
}

/**
 * Normalize a parsed PDF plan from Gemini.
 *
 * @param array<string,mixed> $plan
 * @return array{plan:array<string,mixed>,warnings:list<string>}
 */
function package_normalize_pdf_plan(array $plan, array $places): array
{
    $warnings = [];
    $planLabel = trim((string) ($plan['plan_label'] ?? ''));
    if ($planLabel === '') {
        $planLabel = 'Standard Plan';
    }
    $planKey = trim((string) ($plan['plan_key'] ?? ''));
    if ($planKey === '') {
        $planKey = slugify($planLabel);
    }

    $days = max(1, min(60, (int) ($plan['days'] ?? 1)));
    $nights = max(0, min($days, (int) ($plan['nights'] ?? 0)));

    $destResult = package_resolve_place_slugs(
        isset($plan['destinations']) && is_array($plan['destinations']) ? $plan['destinations'] : [],
        $places
    );
    foreach ($destResult['unmatched'] as $u) {
        $warnings[] = ($planLabel !== '' ? $planLabel . ': ' : '') . 'Unknown destination "' . $u . '".';
    }

    $stayResult = package_resolve_place_slugs(
        isset($plan['stays']) && is_array($plan['stays']) ? $plan['stays'] : [],
        $places
    );
    $stays = $stayResult['resolved'];
    if ($nights > 0 && count($stays) < $nights && $destResult['resolved'] !== []) {
        $fill = $destResult['resolved'][count($destResult['resolved']) - 1];
        $stays = array_merge($stays, array_fill(0, $nights - count($stays), $fill));
    }
    foreach ($stayResult['unmatched'] as $u) {
        $warnings[] = ($planLabel !== '' ? $planLabel . ': ' : '') . 'Unknown stay place "' . $u . '".';
    }

    $types = package_types_from_array(isset($plan['types']) && is_array($plan['types']) ? $plan['types'] : []);
    if ($types === []) {
        $types = ['family'];
    }

    $highlights = [];
    if (isset($plan['highlights']) && is_array($plan['highlights'])) {
        foreach ($plan['highlights'] as $h) {
            $h = trim((string) $h);
            if ($h !== '' && !in_array($h, $highlights, true)) {
                $highlights[] = $h;
            }
        }
    }

    $itinerary = [];
    $rawItinerary = $plan['itinerary'] ?? [];
    if (is_array($rawItinerary)) {
        foreach (array_values($rawItinerary) as $day) {
            if (!is_array($day)) {
                continue;
            }
            $dayTitle = trim((string) ($day['title'] ?? ''));
            $dayText = trim((string) ($day['text'] ?? ''));
            if ($dayTitle === '' && $dayText === '') {
                continue;
            }
            if (mb_strlen($dayText) > 1000) {
                $dayText = rtrim(mb_substr($dayText, 0, 997)) . '…';
            }
            $itinerary[] = [
                'day' => count($itinerary) + 1,
                'title' => $dayTitle !== '' ? $dayTitle : ('Day ' . (count($itinerary) + 1)),
                'text' => $dayText,
            ];
            if (count($itinerary) >= $days) {
                break;
            }
        }
    }
    while (count($itinerary) < $days) {
        $n = count($itinerary) + 1;
        $itinerary[] = ['day' => $n, 'title' => 'Day ' . $n, 'text' => ''];
    }

    $title = trim((string) ($plan['title'] ?? ''));
    $cardText = trim((string) ($plan['card_text'] ?? ''));
    $overview = trim((string) ($plan['overview'] ?? ''));
    if (mb_strlen($cardText) > 90) {
        $cardText = rtrim(mb_substr($cardText, 0, 87)) . '…';
    }
    if (mb_strlen($overview) > 500) {
        $overview = rtrim(mb_substr($overview, 0, 497)) . '…';
    }

    return [
        'plan' => [
            'plan_key' => $planKey,
            'plan_label' => $planLabel,
            'days' => $days,
            'nights' => $nights,
            'destinations' => $destResult['resolved'],
            'unmatched_destinations' => $destResult['unmatched'],
            'types' => $types,
            'pickup' => trim((string) ($plan['pickup'] ?? '')),
            'stays' => array_slice($stays, 0, $nights),
            'unmatched_stays' => $stayResult['unmatched'],
            'highlights' => $highlights,
            'title' => $title,
            'card_text' => $cardText,
            'overview' => $overview,
            'itinerary' => $itinerary,
        ],
        'warnings' => $warnings,
    ];
}

/**
 * Create a minimal place row for quick-add from PDF wizard.
 *
 * @param list<string> $scopes
 * @return array{slug:string,label:string}
 */
function admin_quick_insert_place(string $label, array $scopes = ['kerala']): array
{
    $label = trim($label);
    if ($label === '') {
        throw new InvalidArgumentException('Place label is required.');
    }

    $validScopes = [];
    foreach ($scopes as $scope) {
        $scope = strtolower(trim((string) $scope));
        if (isset(catalog_scope_options()[$scope]) && !in_array($scope, $validScopes, true)) {
            $validScopes[] = $scope;
        }
    }
    if ($validScopes === []) {
        $validScopes = ['kerala'];
    }

    $slug = unique_place_slug(slugify($label));
    db()->prepare(
        'INSERT INTO places (slug, label, catalog_scope, catalog_scopes_json, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (?,?,?,?,?,?,?,?,?)'
    )->execute([
        $slug,
        $label,
        $validScopes[0],
        json_encode($validScopes, JSON_UNESCAPED_UNICODE),
        '[]',
        '',
        '',
        '[]',
        0,
    ]);

    return ['slug' => $slug, 'label' => $label];
}

/**
 * @param array<string,array<string,mixed>> $places
 * @param array<string,string> $typeOptions
 */
function package_pdf_parse_prompt(array $places, array $typeOptions): string
{
    $placeLines = [];
    foreach ($places as $slug => $place) {
        $placeLines[] = '- ' . $slug . ': ' . (string) ($place['label'] ?? $slug);
    }
    $placeCatalog = $placeLines !== [] ? implode("\n", $placeLines) : '(none)';

    $typeLines = [];
    foreach ($typeOptions as $slug => $label) {
        $typeLines[] = '- ' . $slug . ': ' . $label;
    }
    $typeCatalog = $typeLines !== [] ? implode("\n", $typeLines) : '- family: Family';

    return <<<PROMPT
You extract structured travel package data from YathraNest itinerary PDF brochures.

Return ONLY valid JSON matching the schema. Each distinct itinerary variant in the PDF must be a separate object in the "plans" array.

Plan naming rules:
- PDF labels like "PLAN A : Sightseeing Plan" → plan_label = "Sightseeing Plan" (drop PLAN A/B letter prefix)
- PDF labels like "PLAN B : Trekking Plan" → plan_label = "Trekking Plan"
- Use descriptive names such as "Sightseeing Plan", "Trekking Plan", "Pykara lake & Pykara waterfalls", etc.
- plan_key = slugified plan_label (e.g. sightseeing-plan, trekking-plan)
- Single-plan PDFs without A/B variants → plan_label = section heading or "Standard Plan"

For each plan extract:
- days, nights from duration text (e.g. "3 DAY 2 NIGHT" → days=3, nights=2)
- destinations: ONLY slugs from the catalog below
- stays: one slug per night from the catalog (infer from "Overnight stay in X" lines)
- types: ONLY slugs from the type catalog (infer from copy mentioning families, couples, etc.)
- pickup: the pickup/drop line text
- highlights: key activities or places (short strings)
- title: destination + duration + "Package" + plan name when multiple plans exist (e.g. "Wayanad 3 Days Package — Sightseeing Plan")
- card_text: one benefit line, max 90 chars
- overview: intro copy for this plan, max 500 chars (from page 1 or plan-specific intro)
- itinerary: one object per day; merge FORENOON and AFTERNOON bullets into text (max 1000 chars per day)

Ignore these PDF sections entirely:
- Budget/Classic/Signature/Elite/Platinum/Luxury package tier descriptions
- Private trip details / terms / contact pages
- How to Reach / train-bus info pages
- Honeymoon special promos without a full itinerary

Important:
- Template header art may show wrong destination names — trust itinerary content over decorative headers
- Use ONLY destination/stay slugs from the catalog
- If a place is not in the catalog, put the human name in destinations array anyway (we will flag it)

Destination catalog (slug: label):
{$placeCatalog}

Type catalog (slug: label):
{$typeCatalog}
PROMPT;
}

/** @return array<string,mixed> */
function package_pdf_parse_schema(): array
{
    $daySchema = [
        'type' => 'OBJECT',
        'properties' => [
            'day' => ['type' => 'INTEGER'],
            'title' => ['type' => 'STRING'],
            'text' => ['type' => 'STRING'],
        ],
        'required' => ['day', 'title', 'text'],
    ];

    $planSchema = [
        'type' => 'OBJECT',
        'properties' => [
            'plan_key' => ['type' => 'STRING'],
            'plan_label' => ['type' => 'STRING'],
            'days' => ['type' => 'INTEGER'],
            'nights' => ['type' => 'INTEGER'],
            'destinations' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'types' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'pickup' => ['type' => 'STRING'],
            'stays' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'highlights' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'title' => ['type' => 'STRING'],
            'card_text' => ['type' => 'STRING'],
            'overview' => ['type' => 'STRING'],
            'itinerary' => ['type' => 'ARRAY', 'items' => $daySchema],
        ],
        'required' => ['plan_label', 'days', 'nights', 'destinations', 'types', 'pickup', 'title', 'overview', 'itinerary'],
    ];

    return [
        'type' => 'OBJECT',
        'properties' => [
            'plans' => [
                'type' => 'ARRAY',
                'items' => $planSchema,
            ],
        ],
        'required' => ['plans'],
    ];
}

// Media library helpers (media_register_*, media_list, …)
require_once dirname(__DIR__) . '/_media.php';

