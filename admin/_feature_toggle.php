<?php

/**
 * Shared "Show on site" toggle for catalog list pages.
 * Allowed keys: packages, resorts, getaways, gift_cards, investments
 */

function feature_toggle_allowed(string $key): bool
{
    return in_array($key, ['packages', 'resorts', 'getaways', 'gift_cards', 'investments'], true);
}

/**
 * Handle POST from the toolbar toggle. Call before any output.
 * Returns true if a toggle request was handled (and redirected).
 */
function feature_toggle_handle_post(string $featureKey, string $redirectUrl): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return false;
    }
    if (post('action') !== 'feature_toggle') {
        return false;
    }
    if (!feature_toggle_allowed($featureKey)) {
        flash_set('error', 'Invalid feature.');
        redirect($redirectUrl);
        return true;
    }
    if (!verify_csrf(post('_csrf'))) {
        flash_set('error', 'Invalid CSRF.');
        redirect($redirectUrl);
        return true;
    }

    $enabled = post('feature_enabled') === '1' ? '1' : '0';
    settings_save(['feature_' . $featureKey => $enabled]);
    flash_set(
        'success',
        $enabled === '1'
            ? 'Listings are now visible on the site.'
            : 'Listings are hidden — visitors will see the enquiry form instead.'
    );
    redirect($redirectUrl);
    return true;
}

/**
 * Render toolbar switch HTML.
 */
function feature_toggle_html(string $featureKey, string $label = 'Show on site'): string
{
    if (!feature_toggle_allowed($featureKey)) {
        return '';
    }
    $on = feature_enabled($featureKey);
    $checked = $on ? ' checked' : '';
    $state = $on ? 'is-on' : 'is-off';

    return '<form class="admin-feature-toggle ' . e($state) . '" method="post" action="">'
        . csrf_field()
        . '<input type="hidden" name="action" value="feature_toggle" />'
        . '<input type="hidden" name="feature_enabled" value="0" />'
        . '<label class="admin-feature-toggle__label" title="' . e($on ? 'Visible on the front site' : 'Hidden — enquiry form only') . '">'
        . '<span class="admin-feature-toggle__text">' . e($label) . '</span>'
        . '<span class="admin-feature-toggle__switch">'
        . '<input type="checkbox" name="feature_enabled" value="1"' . $checked
        . ' onchange="this.form.submit()" aria-label="' . e($label) . '" />'
        . '<span class="admin-feature-toggle__track" aria-hidden="true"></span>'
        . '</span>'
        . '</label>'
        . '</form>';
}
