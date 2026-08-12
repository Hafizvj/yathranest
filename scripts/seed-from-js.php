<?php
/**
 * One-time seed: import sql/seed-data.json into MySQL.
 * Usage (from project root): php scripts/seed-from-js.php
 *
 * First generate JSON: node scripts/export-packages-json.mjs
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$jsonPath = dirname(__DIR__) . '/sql/seed-data.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, "Missing sql/seed-data.json — run: node scripts/export-packages-json.mjs\n");
    exit(1);
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid seed-data.json\n");
    exit(1);
}

$pdo = db();
$pdo->beginTransaction();

try {
    $admin = $data['admin'] ?? [];
    $email = $admin['email'] ?? 'admin@yathranest.com';
    $pass = $admin['password'] ?? 'ChangeMe123!';
    $name = $admin['name'] ?? 'Admin';
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $pdo->exec('DELETE FROM admins');
    $pdo->prepare('INSERT INTO admins (email, password_hash, name) VALUES (?, ?, ?)')->execute([$email, $hash, $name]);
    echo "Admin: {$email} / {$pass}\n";

    $pdo->exec('DELETE FROM places');
    $insPlace = $pdo->prepare('INSERT INTO places (slug, label, tags_json, arrive_text, sightseeing_text, images_json, sort_order) VALUES (?,?,?,?,?,?,?)');
    $i = 0;
    foreach ($data['places'] ?? [] as $slug => $p) {
        $insPlace->execute([
            $p['slug'] ?? $slug,
            $p['label'],
            json_encode($p['tags'] ?? [], JSON_UNESCAPED_UNICODE),
            $p['arrive'] ?? '',
            $p['sightseeing'] ?? '',
            json_encode($p['images'] ?? [], JSON_UNESCAPED_UNICODE),
            $i++,
        ]);
    }
    echo 'Places: ' . $i . "\n";

    $pdo->exec('DELETE FROM packages');
    $insPkg = $pdo->prepare(
        'INSERT INTO packages (
            slug, sheet, group_name, pickup, drop_point, pickup_slug, days, nights, stay_split, stay_summary,
            destinations_json, dest_line, pages_json, type, state, duration_bucket, title, overview, card_text,
            highlights_json, itinerary_json, image, gallery_json, has_houseboat, accommodation, is_published, sort_order
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
    );
    foreach ($data['packages'] ?? [] as $idx => $p) {
        $insPkg->execute([
            $p['slug'],
            $p['sheet'] ?? '',
            $p['group_name'] ?? '',
            $p['pickup'] ?? '',
            $p['drop_point'] ?? '',
            $p['pickup_slug'] ?? '',
            (int) ($p['days'] ?? 1),
            (int) ($p['nights'] ?? 0),
            $p['stay_split'] ?? '',
            $p['stay_summary'] ?? '',
            json_encode($p['destinations'] ?? [], JSON_UNESCAPED_UNICODE),
            $p['dest_line'] ?? '',
            json_encode($p['pages'] ?? [], JSON_UNESCAPED_UNICODE),
            $p['type'] ?? 'leisure',
            $p['state'] ?? '',
            $p['duration_bucket'] ?? '',
            $p['title'],
            $p['overview'] ?? '',
            $p['card_text'] ?? '',
            json_encode($p['highlights'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($p['itinerary'] ?? [], JSON_UNESCAPED_UNICODE),
            $p['image'] ?? '',
            json_encode($p['gallery'] ?? [], JSON_UNESCAPED_UNICODE),
            (int) ($p['has_houseboat'] ?? 0),
            $p['accommodation'] ?? '',
            (int) ($p['is_published'] ?? 1),
            (int) ($p['sort_order'] ?? $idx),
        ]);
    }
    echo 'Packages: ' . count($data['packages'] ?? []) . "\n";

    $seedSimple = function (string $table, array $rows, callable $insert) use ($pdo) {
        $pdo->exec("DELETE FROM {$table}");
        foreach ($rows as $row) {
            $insert($row);
        }
        echo ucfirst($table) . ': ' . count($rows) . "\n";
    };

    $seedSimple('resorts', $data['resorts'] ?? [], function ($r) use ($pdo) {
        $pdo->prepare('INSERT INTO resorts (slug, title, location, category, summary, body, image, gallery_json, amenities_json, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $r['slug'], $r['title'], $r['location'] ?? '', $r['category'] ?? '', $r['summary'] ?? '', $r['body'] ?? '',
                $r['image'] ?? '', json_encode($r['gallery'] ?? [], JSON_UNESCAPED_UNICODE),
                json_encode($r['amenities'] ?? [], JSON_UNESCAPED_UNICODE), 1, 0,
            ]);
    });

    $seedSimple('getaways', $data['getaways'] ?? [], function ($r) use ($pdo) {
        $pdo->prepare('INSERT INTO getaways (slug, title, location, duration, summary, body, image, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([
                $r['slug'], $r['title'], $r['location'] ?? '', $r['duration'] ?? '', $r['summary'] ?? '', $r['body'] ?? '',
                $r['image'] ?? '', 1, 0,
            ]);
    });

    $seedSimple('gift_cards', $data['gift_cards'] ?? [], function ($r) use ($pdo) {
        $pdo->prepare('INSERT INTO gift_cards (slug, title, blurb, features_json, image, is_published, sort_order) VALUES (?,?,?,?,?,?,?)')
            ->execute([
                $r['slug'], $r['title'], $r['blurb'] ?? '', json_encode($r['features'] ?? [], JSON_UNESCAPED_UNICODE),
                $r['image'] ?? '', 1, 0,
            ]);
    });

    $seedSimple('investment_plans', $data['investment_plans'] ?? [], function ($r) use ($pdo) {
        $pdo->prepare('INSERT INTO investment_plans (slug, title, blurb, features_json, image, is_published, sort_order) VALUES (?,?,?,?,?,?,?)')
            ->execute([
                $r['slug'], $r['title'], $r['blurb'] ?? '', json_encode($r['features'] ?? [], JSON_UNESCAPED_UNICODE),
                $r['image'] ?? '', 1, 0,
            ]);
    });

    $pdo->exec('DELETE FROM settings');
    $insSet = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($data['settings'] ?? [] as $k => $v) {
        $insSet->execute([(string) $k, (string) $v]);
    }

    $pdo->exec('DELETE FROM page_content');
    $insPage = $pdo->prepare('INSERT INTO page_content (page_key, title, sections_json) VALUES (?, ?, ?)');
    foreach ($data['page_content'] ?? [] as $key => $page) {
        $insPage->execute([
            $key,
            $page['title'] ?? $key,
            json_encode($page['sections'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
    }

    $pdo->commit();
    echo "Seed complete.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
