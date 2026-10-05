<?php

/**
 * Generic catalog CRUD helpers for resorts / getaways / gift_cards / investment_plans
 */

function catalog_config(string $key): ?array
{
    $map = [
        'resorts' => [
            'table' => 'resorts',
            'nav' => 'resorts',
            'label' => 'Resorts',
            'singular' => 'Resort',
            'public_path' => 'pages/resort-details.php?resort=',
            'feature_key' => 'resorts',
            'fields' => ['slug','title','location','category','summary','body','image','gallery_json','amenities_json','is_published','sort_order'],
            'has_gallery' => true,
            'has_amenities' => true,
            'has_features' => false,
            'has_duration' => false,
            'has_category' => true,
        ],
        'getaways' => [
            'table' => 'getaways',
            'nav' => 'getaways',
            'label' => 'Getaways',
            'singular' => 'Getaway',
            'feature_key' => 'getaways',
            'fields' => ['slug','title','location','duration','summary','body','image','is_published','sort_order'],
            'has_gallery' => false,
            'has_amenities' => false,
            'has_features' => false,
            'has_duration' => true,
            'has_category' => false,
        ],
        'gift-cards' => [
            'table' => 'gift_cards',
            'nav' => 'gift-cards',
            'label' => 'Gift Cards',
            'singular' => 'Gift Card',
            'feature_key' => 'gift_cards',
            'fields' => ['slug','title','blurb','features_json','resorts_json','image','is_published','sort_order'],
            'has_gallery' => false,
            'has_amenities' => false,
            'has_features' => true,
            'has_duration' => false,
            'has_category' => false,
            'has_resorts' => true,
            'blurb_field' => true,
        ],
        'investment' => [
            'table' => 'investment_plans',
            'nav' => 'investment',
            'label' => 'Investment Plans',
            'singular' => 'Investment Plan',
            'feature_key' => 'investments',
            'fields' => ['slug','title','blurb','features_json','image','is_published','sort_order'],
            'has_gallery' => false,
            'has_amenities' => false,
            'has_features' => true,
            'has_duration' => false,
            'has_category' => false,
            'blurb_field' => true,
        ],
    ];
    return $map[$key] ?? null;
}

function catalog_lines_to_json(string $text): string
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return json_encode($out, JSON_UNESCAPED_UNICODE);
}

/** Unique positive resort ids from the posted resorts[] checkboxes, in submitted order. */
function catalog_resort_ids_from_post(): array
{
    $ids = [];
    foreach ((array) ($_POST['resorts'] ?? []) as $value) {
        $id = (int) $value;
        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/** All resorts (published or not) for the gift card resort picker. */
function catalog_resort_options(): array
{
    try {
        return db()->query(
            'SELECT id, slug, title, location, category, image, is_published FROM resorts ORDER BY sort_order ASC, title ASC'
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/** Distinct non-empty values of a text column, for reuse suggestions. */
function catalog_distinct_values(string $table, string $column): array
{
    try {
        $rows = db()->query(
            "SELECT DISTINCT {$column} AS v FROM {$table} WHERE {$column} IS NOT NULL AND {$column} <> '' ORDER BY {$column} ASC"
        )->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
    return array_values(array_filter(array_map(static fn($r) => trim((string) $r['v']), $rows)));
}

/** Unique entries across a JSON list column (amenities, features), for chip suggestions. */
function catalog_json_list_suggestions(string $table, string $column): array
{
    $out = [];
    try {
        $rows = db()->query("SELECT {$column} AS v FROM {$table} WHERE {$column} IS NOT NULL")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
    foreach ($rows as $row) {
        foreach (json_decode_array($row['v'] ?? null) as $item) {
            $item = trim((string) $item);
            if ($item !== '' && !isset($out[mb_strtolower($item)])) {
                $out[mb_strtolower($item)] = $item;
            }
        }
    }
    $list = array_values($out);
    natcasesort($list);
    return array_values($list);
}
