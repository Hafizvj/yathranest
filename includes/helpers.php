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

function post(string $key, $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function get_query(string $key, $default = ''): string
{
    return isset($_GET[$key]) ? trim((string) $_GET[$key]) : $default;
}
