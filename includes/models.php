<?php

function package_map_row(array $row): array
{
    $row['pages'] = json_decode_array($row['pages_json'] ?? null);
    $row['destinations'] = json_decode_array($row['destinations_json'] ?? null);
    $row['highlights'] = json_decode_array($row['highlights_json'] ?? null);
    $row['itinerary'] = json_decode_array($row['itinerary_json'] ?? null);
    $row['gallery'] = json_decode_array($row['gallery_json'] ?? null);
    $row['id'] = $row['slug'];
    $row['group'] = $row['group_name'];
    $row['drop'] = $row['drop_point'];
    $row['duration'] = $row['duration_bucket'];
    $row['destLine'] = $row['dest_line'];
    $row['cardText'] = $row['card_text'];
    $row['hasHouseboat'] = (bool) $row['has_houseboat'];
    $row['staySummary'] = $row['stay_summary'];
    $row['nights'] = (int) $row['nights'];
    $row['days'] = (int) $row['days'];
    return $row;
}

function packages_for_page(string $page, bool $publishedOnly = true): array
{
    $sql = 'SELECT * FROM packages';
    $params = [];
    if ($publishedOnly) {
        $sql .= ' WHERE is_published = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, days ASC, title ASC';
    $rows = db()->query($sql)->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $pkg = package_map_row($row);
        if ($page === '' || in_array($page, $pkg['pages'], true)) {
            $out[] = $pkg;
        }
    }
    return $out;
}

function package_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    $sql = 'SELECT * FROM packages WHERE slug = ?';
    if ($publishedOnly) {
        $sql .= ' AND is_published = 1';
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? package_map_row($row) : null;
}

function packages_related(array $pkg, int $n = 3): array
{
    $all = packages_for_page('', true);
    $scored = [];
    foreach ($all as $p) {
        if ($p['slug'] === $pkg['slug']) {
            continue;
        }
        $score = 0;
        if (($p['group_name'] ?? '') === ($pkg['group_name'] ?? '')) {
            $score += 5;
        }
        foreach ($pkg['destinations'] as $d) {
            if (in_array($d, $p['destinations'], true)) {
                $score += 2;
            }
        }
        if (($p['pickup_slug'] ?? '') === ($pkg['pickup_slug'] ?? '')) {
            $score += 1;
        }
        $scored[] = ['p' => $p, 'score' => $score];
    }
    usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);
    return array_map(static fn($x) => $x['p'], array_slice($scored, 0, $n));
}

function places_all(): array
{
    $rows = db()->query('SELECT * FROM places ORDER BY sort_order ASC, label ASC')->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $out[$row['slug']] = [
            'slug' => $row['slug'],
            'label' => $row['label'],
            'tags' => json_decode_array($row['tags_json'] ?? null),
            'arrive' => $row['arrive_text'],
            'sightseeing' => $row['sightseeing_text'],
            'images' => json_decode_array($row['images_json'] ?? null),
        ];
    }
    return $out;
}

function map_catalog_row(array $row, array $jsonFields = ['gallery', 'amenities', 'features']): array
{
    foreach ($jsonFields as $field) {
        $col = $field . '_json';
        if (array_key_exists($col, $row)) {
            $row[$field] = json_decode_array($row[$col]);
        }
    }
    return $row;
}

function catalog_list(string $table, bool $publishedOnly = true): array
{
    $allowed = ['resorts', 'getaways', 'gift_cards', 'investment_plans'];
    if (!in_array($table, $allowed, true)) {
        return [];
    }
    $sql = "SELECT * FROM {$table}";
    if ($publishedOnly) {
        $sql .= ' WHERE is_published = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, title ASC';
    $rows = db()->query($sql)->fetchAll();
    $json = $table === 'resorts' ? ['gallery', 'amenities'] : ['features'];
    if ($table === 'getaways') {
        $json = [];
    }
    return array_map(static fn($r) => map_catalog_row($r, $json), $rows);
}

function catalog_by_slug(string $table, string $slug, bool $publishedOnly = true): ?array
{
    $allowed = ['resorts', 'getaways', 'gift_cards', 'investment_plans'];
    if (!in_array($table, $allowed, true)) {
        return null;
    }
    $sql = "SELECT * FROM {$table} WHERE slug = ?";
    if ($publishedOnly) {
        $sql .= ' AND is_published = 1';
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $json = $table === 'resorts' ? ['gallery', 'amenities'] : ['features'];
    if ($table === 'getaways') {
        $json = [];
    }
    return map_catalog_row($row, $json);
}

function package_card_html(array $pkg, string $assetPrefix = '../assets/images/'): string
{
    $img = image_url($pkg['image'] ?? '', ltrim($assetPrefix, './'));
    // Prefer relative asset path for existing CSS site structure
    $imgSrc = $pkg['image'] ?? '';
    if ($imgSrc !== '' && strpos($imgSrc, 'uploads/') !== 0 && strpos($imgSrc, 'http') !== 0) {
        $imgSrc = rtrim($assetPrefix, '/') . '/' . ltrim($imgSrc, '/');
    } elseif (strpos($imgSrc, 'uploads/') === 0) {
        $imgSrc = '../' . $imgSrc;
    } else {
        $imgSrc = $img;
    }
    $href = 'package-details.php?package=' . rawurlencode($pkg['slug']);
    $destAttr = e(implode(' ', $pkg['destinations'] ?? []));
    $highlights = '';
    foreach (array_slice($pkg['highlights'] ?? [], 0, 3) as $h) {
        $highlights .= '<li>' . e($h) . '</li>';
    }
    return '<article class="card" data-filter-item data-name="' . e($pkg['title']) . '" data-destination="' . $destAttr . '" data-state="' . e($pkg['state'] ?? '') . '" data-duration="' . e($pkg['duration'] ?? '') . '" data-type="' . e($pkg['type'] ?? '') . '" data-pickup="' . e($pkg['pickup_slug'] ?? '') . '">'
        . '<div class="card__media"><img src="' . e($imgSrc) . '" alt="' . e($pkg['title']) . '" width="800" height="500" loading="lazy" /></div>'
        . '<div class="card__body">'
        . '<p class="card__meta">' . e($pkg['dest_line'] ?? '') . '</p>'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($pkg['title']) . '</a></h3>'
        . '<p class="meta-row"><span><strong>' . (int) $pkg['days'] . ' Days / ' . (int) $pkg['nights'] . ' Nights</strong></span></p>'
        . '<p class="card__text">' . e($pkg['card_text'] ?? '') . '</p>'
        . '<ul class="highlight-list">' . $highlights . '</ul>'
        . '<div class="card__actions">'
        . '<a class="btn btn--secondary btn--sm" href="' . e($href) . '">View Details</a>'
        . '<a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="' . e($pkg['title']) . '">Request Pricing</a>'
        . '</div></div></article>';
}
