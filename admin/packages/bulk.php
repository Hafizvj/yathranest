<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/_form_helpers.php';

require_admin();

$filters = [];
parse_str((string) post('return_query'), $returnQuery);
foreach (['destination', 'duration', 'scope'] as $key) {
    if (isset($returnQuery[$key]) && is_string($returnQuery[$key]) && $returnQuery[$key] !== '') {
        $filters[$key] = $returnQuery[$key];
    }
}
$back = 'admin/packages/index.php' . ($filters ? '?' . http_build_query($filters) : '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect($back);
}

$ids = [];
foreach ((array) ($_POST['ids'] ?? []) as $id) {
    $id = (int) $id;
    if ($id > 0 && !in_array($id, $ids, true)) {
        $ids[] = $id;
    }
}
if (!$ids) {
    flash_set('error', 'Select at least one package.');
    redirect($back);
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = db()->prepare("SELECT * FROM packages WHERE id IN ({$placeholders})");
$stmt->execute($ids);
$rows = $stmt->fetchAll();
if (!$rows) {
    flash_set('error', 'Those packages no longer exist.');
    redirect($back);
}

$action = post('bulk_action');

/** Publishing is held to the same rules as the edit form; returns the first problem, or null. */
$publishProblem = static function (array $row): ?string {
    $errors = package_form_validate($row, 'publish');
    if (!$errors && trim((string) ($row['image'] ?? '')) === '') {
        $errors[] = 'A cover image is required.';
    }
    return $errors[0] ?? null;
};

$plural = static fn(int $n): string => $n . ' package' . ($n === 1 ? '' : 's');

if ($action === 'delete') {
    db()->prepare("DELETE FROM packages WHERE id IN ({$placeholders})")->execute($ids);
    flash_set('success', $plural(count($rows)) . ' deleted.');
    redirect($back);
}

$changes = [];
switch ($action) {
    case 'publish':
        $changes['is_published'] = 1;
        break;
    case 'draft':
        $changes['is_published'] = 0;
        break;
    case 'feature':
        $changes['is_featured'] = 1;
        break;
    case 'unfeature':
        $changes['is_featured'] = 0;
        break;
    case 'edit':
        $status = post('edit_status');
        if ($status === 'published' || $status === 'draft') {
            $changes['is_published'] = $status === 'published' ? 1 : 0;
        }
        $featured = post('edit_featured');
        if ($featured === 'yes' || $featured === 'no') {
            $changes['is_featured'] = $featured === 'yes' ? 1 : 0;
        }
        $pickup = post('edit_pickup');
        if ($pickup !== '') {
            $changes['pickup'] = $pickup;
            $changes['drop_point'] = $pickup;
            $changes['pickup_slug'] = slugify($pickup);
        }
        if (post('edit_sort_order') !== '') {
            $changes['sort_order'] = (int) post('edit_sort_order');
        }
        break;
    default:
        flash_set('error', 'Unknown bulk action.');
        redirect($back);
}

$typesMode = $action === 'edit' ? post('edit_types_mode') : '';
$editTypes = in_array($typesMode, ['add', 'remove', 'replace'], true)
    ? package_types_from_array((array) ($_POST['edit_types'] ?? []))
    : [];
if ($typesMode !== '' && !$editTypes) {
    $typesMode = '';
}

if (!$changes && $typesMode === '') {
    flash_set('error', 'Nothing to change — pick at least one field.');
    redirect($back);
}

$updated = 0;
$skipped = [];
db()->beginTransaction();
try {
    foreach ($rows as $row) {
        $set = $changes;

        if ($typesMode !== '') {
            $current = json_decode_array($row['types_json'] ?? null) ?: array_filter([(string) $row['type']]);
            if ($typesMode === 'replace') {
                $types = $editTypes;
            } elseif ($typesMode === 'add') {
                $types = array_values(array_unique(array_merge($current, $editTypes)));
            } else {
                $types = array_values(array_diff($current, $editTypes));
            }
            if (!$types) {
                $skipped[] = $row['title'] . ' (would have no type left)';
                continue;
            }
            $set['types_json'] = json_encode($types, JSON_UNESCAPED_UNICODE);
            $set['type'] = $types[0];
        }

        if (($set['is_published'] ?? 0) === 1 && empty($row['is_published'])) {
            $problem = $publishProblem(array_merge($row, $set));
            if ($problem !== null) {
                $skipped[] = $row['title'] . ' (' . rtrim($problem, '.') . ')';
                continue;
            }
        }

        $cols = implode(', ', array_map(static fn($col) => "{$col} = ?", array_keys($set)));
        $values = array_values($set);
        $values[] = (int) $row['id'];
        db()->prepare("UPDATE packages SET {$cols} WHERE id = ?")->execute($values);
        $updated++;
    }
    db()->commit();
} catch (Throwable $e) {
    db()->rollBack();
    flash_set('error', 'Bulk update failed: ' . $e->getMessage());
    redirect($back);
}

if ($updated) {
    flash_set('success', $plural($updated) . ' updated.');
}
if ($skipped) {
    $shown = array_slice($skipped, 0, 5);
    $more = count($skipped) - count($shown);
    flash_set('error', 'Skipped ' . $plural(count($skipped)) . ': ' . implode('; ', $shown) . ($more > 0 ? '; +' . $more . ' more' : '') . '.');
}
redirect($back);
