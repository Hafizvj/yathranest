<?php

/**
 * Small presentational helpers shared by the public pages.
 */

/**
 * Inline icon set. Stroke-based, inherits currentColor, 24x24 viewBox.
 */
function yn_icon(string $name): string
{
    static $paths = [
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 9.5h17M8.5 3v4M15.5 3v4"/>',
        'pin' => '<path d="M12 21s6.5-6.1 6.5-11a6.5 6.5 0 1 0-13 0C5.5 14.9 12 21 12 21z"/><circle cx="12" cy="10" r="2.4"/>',
        'route' => '<circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="6" r="2.5"/><path d="M8.5 18h5a3.5 3.5 0 0 0 0-7h-3a3.5 3.5 0 0 1 0-7h5"/>',
        'car' => '<path d="M4 15l2.2-5.2A2 2 0 0 1 8 8.5h8a2 2 0 0 1 1.8 1.3L20 15v3a1 1 0 0 1-1 1h-1.5"/><path d="M6.5 19H5a1 1 0 0 1-1-1v-3M7 15h10"/><circle cx="7.5" cy="18.5" r="1.6"/><circle cx="16.5" cy="18.5" r="1.6"/>',
        'bed' => '<path d="M4 18v-6.5A2.5 2.5 0 0 1 6.5 9H18a2 2 0 0 1 2 2v7"/><path d="M4 14.5h16"/><path d="M7.5 9V7a1.8 1.8 0 0 1 1.8-1.8h4.4A1.8 1.8 0 0 1 15.5 7v2"/>',
        'users' => '<circle cx="9.5" cy="8.5" r="3"/><path d="M4 19.5c.6-3.2 2.8-5 5.5-5s4.9 1.8 5.5 5"/><path d="M16 6.2a3 3 0 0 1 0 5.6M17.5 14.8c1.6.8 2.7 2.5 3 4.7"/>',
        'gift' => '<rect x="3.5" y="9" width="17" height="11.5" rx="2"/><path d="M3.5 13.5h17M12 9v11.5"/><path d="M12 9S10.6 4.5 8.4 4.5a2 2 0 0 0 0 4.5H12zM12 9s1.4-4.5 3.6-4.5a2 2 0 0 1 0 4.5H12z"/>',
        'phone' => '<path d="M6.5 4h2.2l1.6 3.8-2 1.3a10.5 10.5 0 0 0 5.3 5.3l1.3-2L19 14v2.2a2.2 2.2 0 0 1-2.5 2.2A13.8 13.8 0 0 1 5.3 6.5 2.2 2.2 0 0 1 6.5 4z"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m4.5 8 6.4 4.5a2 2 0 0 0 2.2 0L19.5 8"/>',
        'chat' => '<path d="M20 11.5c0 4-3.6 7.2-8 7.2a9 9 0 0 1-2.6-.4L5 20l1.3-3.4A6.9 6.9 0 0 1 4 11.5c0-4 3.6-7.2 8-7.2s8 3.2 8 7.2z"/>',
        'check' => '<path d="M5 12.8 9.5 17 19 7.5"/>',
        'shield' => '<path d="M12 3.5 5.5 6.2v5.2c0 4.1 2.8 7.8 6.5 8.9 3.7-1.1 6.5-4.8 6.5-8.9V6.2L12 3.5z"/><path d="m8.8 12.1 2.1 2.1 4.3-4.4"/>',
        'sparkle' => '<path d="M12 3.5l1.7 4.6 4.6 1.7-4.6 1.7L12 16.1l-1.7-4.6-4.6-1.7 4.6-1.7L12 3.5z"/><path d="M18.5 15.5l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.4"/>',
        'download' => '<path d="M12 4v11M7.5 10.5 12 15l4.5-4.5"/><path d="M5 19h14"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/>',
        'tag' => '<path d="M12.8 4.4 19.6 8v3.2L8.2 20.4 4 16.2 12.8 4.4z"/><circle cx="15.2" cy="9.2" r=".9"/>',
        'plane' => '<path d="M10.5 19.5l1.4-4.6 7.3-2.1a1.6 1.6 0 0 0 .3-3l-2-1-9 2.6-3.1-1.9a1.3 1.3 0 0 0-1.6.2l-.4.4 3.6 3.5-1.2 4 1.5 1.6z"/>',
        'leaf' => '<path d="M5 19c8.5-.5 13-5 14-14-9 1-13.5 5.5-14 14z"/><path d="M5 19c3-3.5 6.5-6 10-7.5"/>',
        'wallet' => '<rect x="3.5" y="6" width="17" height="12.5" rx="2.5"/><path d="M3.5 10.5h17M15.5 14.5h2"/>',
        'headset' => '<path d="M5 13v-1a7 7 0 0 1 14 0v1"/><path d="M5 13h1.6A1.4 1.4 0 0 1 8 14.4v2.2A1.4 1.4 0 0 1 6.6 18H6a2 2 0 0 1-2-2v-1.5A1.5 1.5 0 0 1 5.5 13H5z"/><path d="M19 13h-1.6a1.4 1.4 0 0 0-1.4 1.4v2.2a1.4 1.4 0 0 0 1.4 1.4H18a2 2 0 0 0 2-2v-1.5A1.5 1.5 0 0 0 18.5 13H19z"/><path d="M12 18v.8a2.2 2.2 0 0 0 2.2 2.2H15"/>',
        'arrow-right' => '<path d="M4.5 12h14M13.5 6.5 19 12l-5.5 5.5"/>',
        'compass' => '<circle cx="12" cy="12" r="8.5"/><path d="m15 9-1.6 4.4L9 15l1.6-4.4L15 9z"/>',
    ];

    $body = $paths[$name] ?? $paths['sparkle'];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
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
