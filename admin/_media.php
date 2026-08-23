<?php

/**
 * Central media library helpers.
 * Requires bootstrap + packages/_form_helpers.php (for upload helpers).
 */

function media_format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024) . ' KB';
    }
    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}

function media_row_public(array $row): array
{
    $path = (string) ($row['path'] ?? '');
    $id = (int) ($row['id'] ?? 0);
    $source = (string) ($row['source'] ?? (strpos($path, 'assets/') === 0 ? 'asset' : 'upload'));
    return [
        'id' => $id,
        'path' => $path,
        'url' => image_url($path, ''),
        'name' => (string) ($row['original_name'] ?: basename(str_replace('\\', '/', $path))),
        'mime' => (string) ($row['mime'] ?? ''),
        'bytes' => (int) ($row['bytes'] ?? 0),
        'bytes_label' => media_format_bytes((int) ($row['bytes'] ?? 0)),
        'width' => isset($row['width']) ? (int) $row['width'] : null,
        'height' => isset($row['height']) ? (int) $row['height'] : null,
        'created_at' => (string) ($row['created_at'] ?? ''),
        'source' => $source,
        'deletable' => $id > 0 && $source !== 'asset',
    ];
}

/**
 * Scan assets/images (recursive) into virtual media rows.
 * @return array<int, array>
 */
function media_scan_asset_images(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $root = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';
    $realRoot = realpath($root);
    if (!$realRoot || !is_dir($realRoot)) {
        $cache = [];
        return $cache;
    }

    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];
    $items = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realRoot, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (!isset($allowed[$ext])) {
            continue;
        }
        $full = $file->getPathname();
        $relative = str_replace('\\', '/', substr($full, strlen($realRoot) + 1));
        $path = 'assets/images/' . ltrim($relative, '/');
        $bytes = (int) $file->getSize();
        $items[] = [
            'id' => 0,
            'path' => $path,
            'original_name' => basename($relative),
            'mime' => $allowed[$ext],
            'bytes' => $bytes,
            'width' => null,
            'height' => null,
            'created_at' => date('Y-m-d H:i:s', (int) $file->getMTime()),
            'source' => 'asset',
        ];
    }

    usort($items, static function ($a, $b) {
        return strcasecmp((string) $a['original_name'], (string) $b['original_name']);
    });

    $cache = $items;
    return $cache;
}

/**
 * @return array{items: array, total: int, page: int, per_page: int}
 */
function media_list(string $q = '', int $page = 1, int $perPage = 48): array
{
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $offset = ($page - 1) * $perPage;
    $q = trim($q);
    $qLower = strtolower($q);

    $byPath = [];

    // Bundled site images first (always available).
    foreach (media_scan_asset_images() as $row) {
        $byPath[$row['path']] = media_row_public($row);
    }

    // Uploaded / registered library rows override assets when same path exists.
    try {
        $stmt = db()->query('SELECT * FROM media ORDER BY created_at DESC, id DESC');
        foreach ($stmt->fetchAll() as $row) {
            $public = media_row_public($row);
            $byPath[$public['path']] = $public;
        }
    } catch (Throwable $e) {
        // media table missing — still show assets
    }

    $all = array_values($byPath);
    if ($qLower !== '') {
        $all = array_values(array_filter($all, static function (array $item) use ($qLower) {
            return strpos(strtolower($item['name']), $qLower) !== false
                || strpos(strtolower($item['path']), $qLower) !== false;
        }));
    }

    usort($all, static function ($a, $b) {
        // Uploads (deletable / id>0) first by date, then assets A–Z
        $aUpload = !empty($a['deletable']) || (int) $a['id'] > 0;
        $bUpload = !empty($b['deletable']) || (int) $b['id'] > 0;
        if ($aUpload !== $bUpload) {
            return $aUpload ? -1 : 1;
        }
        if ($aUpload) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        }
        return strcasecmp((string) $a['name'], (string) $b['name']);
    });

    $total = count($all);
    $items = array_slice($all, $offset, $perPage);

    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ];
}

function media_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM media WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function media_by_path(string $path): ?array
{
    $path = ltrim(trim($path), '/');
    if ($path === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM media WHERE path = ? LIMIT 1');
    $stmt->execute([$path]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Ensure a path is registered in the media table. Returns the public row.
 */
function media_ensure_row(
    string $path,
    string $originalName = '',
    ?int $bytes = null,
    ?string $mime = null,
    ?int $width = null,
    ?int $height = null
): ?array {
    $path = ltrim(trim($path), '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return null;
    }

    $existing = media_by_path($path);
    if ($existing) {
        return media_row_public($existing);
    }

    if ($originalName === '') {
        $originalName = basename(str_replace('\\', '/', $path));
    }

    if ($bytes === null || $mime === null || $width === null || $height === null) {
        $full = media_filesystem_path($path);
        if ($full && is_file($full)) {
            if ($bytes === null) {
                $bytes = (int) filesize($full);
            }
            if ($mime === null) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file($full);
            }
            if ($width === null || $height === null) {
                $info = @getimagesize($full);
                if ($info) {
                    $width = $width ?? (int) $info[0];
                    $height = $height ?? (int) $info[1];
                }
            }
        }
    }

    $stmt = db()->prepare(
        'INSERT INTO media (path, original_name, mime, bytes, width, height)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE original_name = VALUES(original_name)'
    );
    $stmt->execute([
        $path,
        $originalName,
        (string) ($mime ?? ''),
        (int) ($bytes ?? 0),
        $width,
        $height,
    ]);

    $row = media_by_path($path);
    return $row ? media_row_public($row) : null;
}

function media_filesystem_path(string $path): ?string
{
    $path = ltrim(trim($path), '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return null;
    }
    if (admin_is_upload_path($path)) {
        $full = rtrim((string) config('upload_dir'), '/\\') . DIRECTORY_SEPARATOR . substr($path, strlen('uploads/'));
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $full);
    }
    if (strpos($path, 'assets/') === 0) {
        $full = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        return $full;
    }
    return null;
}

/**
 * Upload a file into uploads/library (or $subdir) and register it.
 * @return array|null public media row
 */
function media_register_upload(array $file, string $subdir = 'library'): ?array
{
    $path = admin_store_upload($file, $subdir);
    if (!$path) {
        return null;
    }

    $tmpInfo = null;
    // File already moved; read from disk.
    $full = media_filesystem_path($path);
    $bytes = $full && is_file($full) ? (int) filesize($full) : (int) ($file['size'] ?? 0);
    $mime = '';
    $width = null;
    $height = null;
    if ($full && is_file($full)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($full);
        $info = @getimagesize($full);
        if ($info) {
            $width = (int) $info[0];
            $height = (int) $info[1];
        }
    }

    return media_ensure_row(
        $path,
        (string) ($file['name'] ?? ''),
        $bytes,
        $mime,
        $width,
        $height
    );
}

/**
 * Register a path that was already stored via admin_store_upload.
 */
function media_register_stored_path(string $path, string $originalName = ''): ?array
{
    return media_ensure_row($path, $originalName);
}

/**
 * Collect every known image path stored on content rows.
 * @return string[]
 */
function media_referenced_paths(): array
{
    $paths = [];
    $add = static function ($value) use (&$paths): void {
        if (is_string($value) && $value !== '') {
            $paths[] = ltrim($value, '/');
        } elseif (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && $item !== '') {
                    $paths[] = ltrim($item, '/');
                }
            }
        }
    };

    try {
        foreach (db()->query('SELECT image, gallery_json FROM packages')->fetchAll() as $row) {
            $add($row['image'] ?? '');
            $add(json_decode_array($row['gallery_json'] ?? null));
        }
        foreach (db()->query('SELECT image, gallery_json FROM resorts')->fetchAll() as $row) {
            $add($row['image'] ?? '');
            $add(json_decode_array($row['gallery_json'] ?? null));
        }
        foreach (['getaways', 'gift_cards', 'investment_plans'] as $table) {
            foreach (db()->query("SELECT image FROM {$table}")->fetchAll() as $row) {
                $add($row['image'] ?? '');
            }
        }
        foreach (db()->query('SELECT images_json FROM places')->fetchAll() as $row) {
            $add(json_decode_array($row['images_json'] ?? null));
        }
        foreach (db()->query('SELECT setting_value FROM settings')->fetchAll() as $row) {
            $val = (string) ($row['setting_value'] ?? '');
            if ($val !== '' && (strpos($val, 'uploads/') === 0 || strpos($val, 'assets/') === 0)) {
                $add($val);
            }
        }
        foreach (db()->query('SELECT sections_json FROM page_content')->fetchAll() as $row) {
            $sections = json_decode_array($row['sections_json'] ?? null);
            $add($sections['hero_image'] ?? '');
            $add($sections['hero_visual'] ?? '');
            if (!empty($sections['hero_cards']) && is_array($sections['hero_cards'])) {
                foreach ($sections['hero_cards'] as $card) {
                    if (is_array($card)) {
                        $add($card['image'] ?? '');
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // Partial schema / missing tables — return what we have.
    }

    return array_values(array_unique(array_filter($paths)));
}

function media_is_referenced(string $path): bool
{
    $path = ltrim(trim($path), '/');
    if ($path === '') {
        return false;
    }
    return in_array($path, media_referenced_paths(), true);
}

/**
 * Delete media row + file only when unused. Returns true on success.
 */
function media_delete_if_unused(int $id): bool
{
    $row = media_by_id($id);
    if (!$row) {
        admin_upload_last_error('Media item not found.');
        return false;
    }
    $path = (string) $row['path'];
    if (media_is_referenced($path)) {
        admin_upload_last_error('This image is still used on the site. Remove it from packages or other content first.');
        return false;
    }

    $stmt = db()->prepare('DELETE FROM media WHERE id = ?');
    $stmt->execute([$id]);
    admin_delete_upload($path);
    admin_upload_last_error('');
    return true;
}

/**
 * Import referenced upload paths into the media table.
 * @return int number of newly registered rows
 */
function media_import_referenced_uploads(): int
{
    $added = 0;
    foreach (media_referenced_paths() as $path) {
        if (!admin_is_upload_path($path)) {
            continue;
        }
        $full = media_filesystem_path($path);
        if (!$full || !is_file($full)) {
            continue;
        }
        if (media_by_path($path)) {
            continue;
        }
        if (media_ensure_row($path)) {
            $added++;
        }
    }
    return $added;
}
