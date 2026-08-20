<?php

/**
 * Small presentational helpers shared by the public pages.
 */

/**
 * UI icons via Solar (Iconify). Names map to solar:*-linear ids.
 * @see https://icon-sets.iconify.design/solar/
 */
function yn_icon(string $name): string
{
    static $icons = [
        'clock' => 'clock-circle-linear',
        'calendar' => 'calendar-linear',
        'pin' => 'map-point-linear',
        'route' => 'routing-2-linear',
        'car' => 'wheel-linear',
        'bed' => 'bed-linear',
        'users' => 'users-group-rounded-linear',
        'gift' => 'gift-linear',
        'phone' => 'phone-linear',
        'mail' => 'letter-linear',
        'chat' => 'chat-round-dots-linear',
        'check' => 'check-circle-linear',
        'shield' => 'shield-check-linear',
        'sparkle' => 'star-fall-minimalistic-2-linear',
        'info' => 'info-circle-linear',
        'download' => 'download-minimalistic-linear',
        'search' => 'magnifer-linear',
        'tag' => 'tag-price-linear',
        'plane' => 'plain-2-linear',
        'leaf' => 'leaf-linear',
        'wallet' => 'wallet-linear',
        'headset' => 'headphones-round-sound-linear',
        'arrow-right' => 'alt-arrow-right-linear',
        'compass' => 'compass-linear',
        'sun' => 'sun-linear',
        'moon' => 'moon-linear',
        'upload' => 'upload-minimalistic-linear',
        'image' => 'gallery-linear',
        'file-text' => 'document-text-linear',
        'star' => 'star-linear',
        'list' => 'hamburger-menu-linear',
        'plus' => 'add-circle-linear',
        'trash' => 'trash-bin-trash-linear',
        'copy' => 'copy-linear',
        'chevron-down' => 'alt-arrow-down-linear',
        'buildings' => 'buildings-2-linear',
        'globe' => 'global-linear',
        'map' => 'map-linear',
        'chart' => 'chart-2-linear',
    ];

    $id = $icons[$name] ?? $icons['sparkle'];

    return '<iconify-icon icon="solar:' . $id . '" width="1.15em" height="1.15em" aria-hidden="true"></iconify-icon>';
}

/**
 * Breadcrumb trail. Pass [label => href, ...]; a null href marks the current page.
 */
function yn_crumbs(array $items, bool $onMedia = false): string
{
    $out = '<nav class="crumbs' . ($onMedia ? ' crumbs--onmedia' : '') . '" aria-label="Breadcrumb"><ol>';
    foreach ($items as $label => $href) {
        $out .= '<li>' . ($href === null
            ? '<span aria-current="page">' . e((string) $label) . '</span>'
            : '<a href="' . e((string) $href) . '">' . e((string) $label) . '</a>') . '</li>';
    }
    return $out . '</ol></nav>';
}

/**
 * Pill switcher across the four package catalogues.
 */
function yn_package_subnav(string $active = ''): string
{
    $links = [
        'kerala' => ['Kerala', 'kerala-packages.php'],
        'south' => ['South India', 'south-indian-packages.php'],
        'domestic' => ['Domestic', 'domestic-packages.php'],
        'international' => ['International', 'international-packages.php'],
        'getaways' => ['Weekend Getaways', 'weekend-getaways.php'],
    ];
    $out = '<nav class="subnav" aria-label="Package categories">';
    foreach ($links as $key => [$label, $href]) {
        $out .= '<a href="' . e($href) . '"' . ($key === $active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    return $out . '</nav>';
}

/**
 * Meta chip with an icon, used in page heads and detail summaries.
 */
function yn_chip(string $icon, string $text): string
{
    return '<span class="chip">' . yn_icon($icon) . e($text) . '</span>';
}

/**
 * Shorten long copy for hero leads, cutting at a sentence end when one is
 * available so the full text can still be shown lower on the page.
 */
function yn_lead(string $text, int $limit = 170): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    if ($text === '' || mb_strlen($text) <= $limit) {
        return $text;
    }

    $window = mb_substr($text, 0, $limit);
    $sentenceEnd = max(
        (int) mb_strrpos($window . ' ', '. '),
        (int) mb_strrpos($window . ' ', '! '),
        (int) mb_strrpos($window . ' ', '? ')
    );
    if ($sentenceEnd > $limit * 0.5) {
        return mb_substr($text, 0, $sentenceEnd + 1);
    }

    $wordEnd = mb_strrpos($window, ' ');
    return rtrim(mb_substr($window, 0, $wordEnd ?: $limit), " ,;:-") . '…';
}
