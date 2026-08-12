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
            'fields' => ['slug','title','blurb','features_json','image','is_published','sort_order'],
            'has_gallery' => false,
            'has_amenities' => false,
            'has_features' => true,
            'has_duration' => false,
            'has_category' => false,
            'blurb_field' => true,
        ],
        'investment' => [
            'table' => 'investment_plans',
            'nav' => 'investment',
            'label' => 'Investment Plans',
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
