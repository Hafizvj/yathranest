<?php

/** Travel types offered on the package form. */
function package_type_options(): array
{
    return [
        'family' => 'Family',
        'couple' => 'Couple',
        'bachelors' => 'Bachelors',
    ];
}

/** Listing categories a place can belong to, used to derive a package's pages. */
function catalog_scope_options(): array
{
    return [
        'kerala' => 'Kerala',
        'south' => 'South India',
        'domestic' => 'Domestic',
        'international' => 'International',
    ];
}

/** Every category a place row belongs to, falling back to its single scope. */
function place_catalog_scopes(?array $row): array
{
    $scopes = json_decode_array($row['catalog_scopes_json'] ?? null);
    if (!$scopes) {
        $scopes = [(string) ($row['catalog_scope'] ?? '')];
    }
    $out = [];
    foreach ($scopes as $scope) {
        $scope = strtolower(trim((string) $scope));
        if (isset(catalog_scope_options()[$scope]) && !in_array($scope, $out, true)) {
            $out[] = $scope;
        }
    }
    if (!$out) {
        $out = [place_default_catalog_scope((string) ($row['slug'] ?? ''))];
    }
    return $out;
}

/** "Kerala · South India" for listings. */
function catalog_scopes_label(array $scopes): string
{
    $options = catalog_scope_options();
    $labels = [];
    foreach ($scopes as $scope) {
        $labels[] = $options[$scope] ?? ucfirst((string) $scope);
    }
    return implode(' · ', $labels);
}

function place_default_catalog_scope(string $slug): string
{
    static $south = ['mysore', 'ooty', 'coorg', 'chikmagalur', 'valparai', 'kodaikanal'];
    static $domestic = ['lakshadweep', 'goa', 'andaman'];
    if (in_array($slug, $south, true)) {
        return 'south';
    }
    if (in_array($slug, $domestic, true)) {
        return 'domestic';
    }
    return 'kerala';
}

function package_map_row(array $row): array
{
    $row['pages'] = json_decode_array($row['pages_json'] ?? null);
    $row['destinations'] = json_decode_array($row['destinations_json'] ?? null);
    $row['highlights'] = json_decode_array($row['highlights_json'] ?? null);
    $row['itinerary'] = json_decode_array($row['itinerary_json'] ?? null);
    $row['gallery'] = json_decode_array($row['gallery_json'] ?? null);
    $row['stays'] = json_decode_array($row['stays_json'] ?? null);
    $row['types'] = json_decode_array($row['types_json'] ?? null);
    if (!$row['types'] && !empty($row['type'])) {
        $row['types'] = [(string) $row['type']];
    }
    $row['id'] = $row['slug'];
    $row['group'] = $row['group_name'];
    $row['drop'] = $row['drop_point'];
    $row['duration'] = $row['duration_bucket'];
    $row['destLine'] = $row['dest_line'];
    $row['cardText'] = $row['card_text'];
    $row['hasHouseboat'] = (bool) $row['has_houseboat'];
    $row['isFeatured'] = !empty($row['is_featured']);
    $row['staySummary'] = $row['stay_summary'];
    $row['nights'] = (int) $row['nights'];
    $row['days'] = (int) $row['days'];
    return $row;
}

/** Human label for a package's travel types, e.g. "Family, Couple". */
function package_types_label(array $pkg): string
{
    $options = package_type_options();
    $labels = [];
    foreach ($pkg['types'] ?? [] as $type) {
        $type = strtolower(trim((string) $type));
        if ($type === '') {
            continue;
        }
        $labels[] = $options[$type] ?? ucwords(str_replace('-', ' ', $type));
    }
    return implode(', ', $labels);
}

function packages_for_page(string $page, bool $publishedOnly = true): array
{
    $sql = 'SELECT * FROM packages';
    if ($publishedOnly) {
        $sql .= ' WHERE is_published = 1';
    }
    $sql .= ' ORDER BY is_featured DESC, sort_order ASC, days ASC, title ASC';
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

function packages_for_destination(string $page, string $destinationSlug, bool $publishedOnly = true): array
{
    $destinationSlug = strtolower(trim($destinationSlug));
    $out = [];
    foreach (packages_for_page($page, $publishedOnly) as $pkg) {
        $dests = array_map('strtolower', $pkg['destinations'] ?? []);
        if (in_array($destinationSlug, $dests, true)) {
            $out[] = $pkg;
        }
    }
    return $out;
}

/**
 * Destinations that appear in packages for a given page (e.g. kerala).
 * @return array<int, array{slug:string,label:string,count:int,image:string,tags:array}>
 */
function destinations_for_page(string $page): array
{
    $places = places_all();
    $counts = [];
    foreach (packages_for_page($page, true) as $pkg) {
        foreach ($pkg['destinations'] as $slug) {
            $slug = strtolower((string) $slug);
            if ($slug === '') {
                continue;
            }
            if (!isset($counts[$slug])) {
                $counts[$slug] = 0;
            }
            $counts[$slug]++;
        }
    }

    $out = [];
    foreach ($counts as $slug => $count) {
        $place = $places[$slug] ?? null;
        $images = $place['images'] ?? [];
        $image = $images[0] ?? 'beach.jpg';
        $out[] = [
            'slug' => $slug,
            'label' => $place['label'] ?? ucwords(str_replace('-', ' ', $slug)),
            'count' => $count,
            'image' => $image,
            'tags' => $place['tags'] ?? [],
        ];
    }

    usort($out, static fn($a, $b) => strcasecmp($a['label'], $b['label']));
    return $out;
}

function place_by_slug(string $slug): ?array
{
    $places = places_all();
    return $places[strtolower(trim($slug))] ?? null;
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
            'catalog_scope' => $row['catalog_scope'] ?? place_default_catalog_scope((string) $row['slug']),
            'catalog_scopes' => place_catalog_scopes($row),
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
    $depth = (strpos($assetPrefix, '../') === 0) ? '../' : '';
    $imgSrc = media_src((string) ($pkg['image'] ?? ''), $depth, 'beach.jpg');
    $href = 'package-details.php?package=' . rawurlencode($pkg['slug']);
    $destAttr = e(implode(' ', $pkg['destinations'] ?? []));
    $highlights = '';
    foreach (array_slice($pkg['highlights'] ?? [], 0, 3) as $h) {
        $highlights .= '<li>' . e($h) . '</li>';
    }
    $days = (int) ($pkg['days'] ?? 0);
    $nights = (int) ($pkg['nights'] ?? 0);
    $nightsLabel = $nights . ' Night' . ($nights === 1 ? '' : 's');
    $typeAttr = e(implode(' ', $pkg['types'] ?? array_filter([$pkg['type'] ?? ''])));
    return '<article class="card" data-filter-item data-name="' . e($pkg['title']) . '" data-destination="' . $destAttr . '" data-state="' . e($pkg['state'] ?? '') . '" data-duration="' . e($pkg['duration'] ?? '') . '" data-type="' . $typeAttr . '" data-pickup="' . e($pkg['pickup_slug'] ?? '') . '">'
        . '<div class="card__media"><img src="' . e($imgSrc) . '" alt="' . e($pkg['title']) . '" width="800" height="500" loading="lazy" /></div>'
        . '<div class="card__body">'
        . '<p class="card__meta">' . e($pkg['dest_line'] ?? '') . '</p>'
        . '<h3 class="card__title"><a href="' . e($href) . '">' . e($pkg['title']) . '</a></h3>'
        . '<p class="meta-row"><span><strong>' . $days . ' Days / ' . e($nightsLabel) . '</strong></span></p>'
        . '<p class="card__text">' . e($pkg['card_text'] ?? '') . '</p>'
        . '<ul class="highlight-list">' . $highlights . '</ul>'
        . '<div class="card__actions">'
        . '<a class="btn btn--secondary btn--sm" href="' . e($href) . '">View Details</a>'
        . '<a class="btn btn--primary btn--sm" href="#enquiry" data-open-modal="enquiry-modal" data-package-title="' . e($pkg['title']) . '">Request Pricing</a>'
        . '</div></div></article>';
}
