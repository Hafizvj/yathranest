<?php

function settings_all(bool $refresh = false): array
{
    static $cache = null;
    if ($refresh) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== ''
        ? (string) $all[$key]
        : $default;
}

function settings_save(array $pairs): void
{
    $stmt = db()->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($pairs as $key => $value) {
        $stmt->execute([(string) $key, (string) $value]);
    }
    settings_all(true);
}

function page_content(string $key): ?array
{
    $stmt = db()->prepare('SELECT * FROM page_content WHERE page_key = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $row['sections'] = json_decode_array($row['sections_json'] ?? null);
    return $row;
}

function page_content_save(string $key, string $title, array $sections): void
{
    $stmt = db()->prepare(
        'INSERT INTO page_content (page_key, title, sections_json) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE title = VALUES(title), sections_json = VALUES(sections_json)'
    );
    $stmt->execute([$key, $title, json_encode($sections, JSON_UNESCAPED_UNICODE)]);
}
