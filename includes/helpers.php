<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(?string $key = null, $default = null)
{
    static $cfg;
    if ($cfg === null) {
        $path = dirname(__DIR__) . '/config/config.php';
        if (!is_file($path)) {
            $path = dirname(__DIR__) . '/config/config.example.php';
        }
        $cfg = require $path;
    }
    if ($key === null) {
        return $cfg;
    }
    $parts = explode('.', $key);
    $val = $cfg;
    foreach ($parts as $p) {
        if (!is_array($val) || !array_key_exists($p, $val)) {
            return $default;
        }
        $val = $val[$p];
    }
    return $val;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) config('base_url', ''), '/');
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base === '' ? '' : $base;
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function url(string $path = ''): string
{
    return base_url($path);
}

function asset_url(string $path): string
{
    return base_url(ltrim($path, '/'));
}

function image_url(string $path, string $prefix = 'assets/images/'): string
{
    $path = ltrim($path, '/');
    if ($path === '') {
        return asset_url($prefix . 'beach.jpg');
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    if (strpos($path, 'uploads/') === 0) {
        return asset_url($path);
    }
    return asset_url($prefix . $path);
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function json_decode_array($value): array
{
    if (is_array($value)) {
        return $value;
    }
    if ($value === null || $value === '') {
        return [];
    }
    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : [];
}

function flash_set(string $key, $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key, $default = null)
{
    if (!isset($_SESSION['_flash'][$key])) {
        return $default;
    }
    $v = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $v;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function request_is_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return stripos($accept, 'application/json') !== false
        || strcasecmp($xhr, 'XMLHttpRequest') === 0;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Turns a stored enquiry into a wa.me link carrying a pre-written message.
 * The message is phrased from the visitor's side, since they are the sender.
 */
function enquiry_whatsapp_url(array $enquiry): string
{
    $number = preg_replace('/\D/', '', setting('whatsapp', ''));
    if ($number === '') {
        return '';
    }

    $lines = [];
    $add = static function (string $emoji, string $label, ?string $value) use (&$lines): void {
        $value = trim((string) $value);
        if ($value !== '') {
            $lines[] = $emoji . ' *' . $label . ':* ' . $value;
        }
    };

    $intro = [
        'taxi' => "\u{1F44B} Hi YathraNest! I'd like to book a taxi.",
        'resort' => "\u{1F44B} Hi YathraNest! I'd like to enquire about a resort stay.",
        'gift' => "\u{1F44B} Hi YathraNest! I'd like to know more about gift cards.",
        'investment' => "\u{1F44B} Hi YathraNest! I'd like details on your investment plans.",
        'contact' => "\u{1F44B} Hi YathraNest! I have an enquiry.",
    ];
    $lines[] = $intro[$enquiry['type'] ?? ''] ?? "\u{1F44B} Hi YathraNest! I'd like pricing for a trip.";
    $lines[] = '';

    $add("\u{1F9ED}", 'Interest', $enquiry['interest'] ?? '');
    $add("\u{1F4C5}", 'Travel from', enquiry_date_label($enquiry['travel_date'] ?? ''));
    $add("\u{1F464}", 'Name', $enquiry['name'] ?? '');
    $add("\u{1F4DE}", 'Phone', $enquiry['phone'] ?? '');
    $add("\u{2709}\u{FE0F}", 'Email', $enquiry['email'] ?? '');

    $extraLabels = [
        'pickup' => ["\u{1F4CD}", 'Pickup'],
        'drop' => ["\u{1F3C1}", 'Drop'],
        'date' => ["\u{1F4C5}", 'Date'],
        'time' => ["\u{23F0}", 'Time'],
        'tripType' => ["\u{1F504}", 'Trip type'],
        'vehicle' => ["\u{1F697}", 'Vehicle'],
        'passengers' => ["\u{1F465}", 'Passengers'],
        'notes' => ["\u{1F4DD}", 'Notes'],
    ];
    foreach ($extraLabels as $key => [$emoji, $label]) {
        $value = $enquiry['extra'][$key] ?? '';
        $add($emoji, $label, $key === 'date' ? enquiry_date_label($value) : $value);
    }

    $add("\u{1F4AC}", 'Message', $enquiry['message'] ?? '');

    if (!empty($enquiry['id'])) {
        $lines[] = '';
        $lines[] = "\u{1F516} Ref #" . (int) $enquiry['id'];
    }

    // api.whatsapp.com rather than wa.me: the wa.me redirect re-encodes the query
    // as latin-1 and turns every emoji into a replacement character.
    return 'https://api.whatsapp.com/send?phone=' . $number . '&text=' . rawurlencode(implode("\n", $lines));
}

/** Formats a Y-m-d date for humans, leaving anything unparseable untouched. */
function enquiry_date_label(?string $date): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return '';
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $parsed ? $parsed->format('D, j M Y') : $date;
}

function post(string $key, $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function get_query(string $key, $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}
