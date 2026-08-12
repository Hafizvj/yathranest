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
    $gallery = lines_to_array(post('gallery'));
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

function admin_store_upload(array $file, string $subdir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    $dir = rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . $subdir;
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return 'uploads/' . $subdir . '/' . $name;
}
