<?php
/**
 * Printable itinerary — open and use browser "Save as PDF".
 * ?package=slug
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$slug = get_query('package');
$pkg = null;
try {
    if ($slug !== '') {
        $pkg = package_by_slug($slug);
    }
} catch (Throwable $e) {
    $pkg = null;
}

if (!$pkg) {
    http_response_code(404);
    echo 'Package not found.';
    exit;
}

$days = (int) $pkg['days'];
$nights = (int) $pkg['nights'];
$nightsLabel = $nights . ' Night' . ($nights === 1 ? '' : 's');
$phone = setting('phone', '+91 98765 43210');
$email = setting('email', 'hello@yathranest.com');
$autoPrint = get_query('print') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pkg['title']) ?> — Itinerary | YathraNest</title>
  <style>
    :root { color-scheme: light; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Georgia, "Times New Roman", serif;
      color: #1c1a17;
      background: #f3f2ee;
      line-height: 1.45;
    }
    .toolbar {
      position: sticky;
      top: 0;
      z-index: 10;
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
      justify-content: space-between;
      padding: 0.85rem 1.25rem;
      background: #14231f;
      color: #fff;
    }
    .toolbar a, .toolbar button {
      font: inherit;
      cursor: pointer;
      border-radius: 8px;
      border: 0;
      padding: 0.55rem 0.9rem;
      text-decoration: none;
    }
    .toolbar .btn-print { background: #c45c26; color: #fff; }
    .toolbar .btn-back { background: rgba(255,255,255,0.12); color: #fff; }
    .sheet {
      max-width: 820px;
      margin: 1.5rem auto 2.5rem;
      background: #fff;
      padding: 2rem 2.25rem;
      border: 1px solid #e4dfd6;
      box-shadow: 0 12px 40px rgba(0,0,0,0.06);
    }
    .brand { font-weight: 700; letter-spacing: 0.04em; color: #0f5c4c; margin: 0 0 0.35rem; }
    h1 { margin: 0 0 0.75rem; font-size: 1.75rem; }
    .meta { color: #6b6560; margin: 0 0 1.25rem; }
    h2 {
      margin: 1.5rem 0 0.6rem;
      font-size: 1.1rem;
      border-bottom: 1px solid #e4dfd6;
      padding-bottom: 0.35rem;
    }
    ul { padding-left: 1.2rem; }
    .day {
      margin: 0.85rem 0;
      padding: 0.75rem 0.85rem;
      background: #f7f5f1;
      border-radius: 8px;
    }
    .day strong { display: block; margin-bottom: 0.25rem; }
    .footer-note {
      margin-top: 2rem;
      padding-top: 1rem;
      border-top: 1px solid #e4dfd6;
      font-size: 0.9rem;
      color: #6b6560;
    }
    @media print {
      body { background: #fff; }
      .toolbar { display: none !important; }
      .sheet {
        margin: 0;
        border: 0;
        box-shadow: none;
        max-width: none;
        padding: 0;
      }
      a[href]::after { content: ""; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <div>YathraNest itinerary — use <strong>Save as PDF</strong> in the print dialog</div>
    <div style="display:flex;gap:0.5rem">
      <a class="btn-back" href="package-details.php?package=<?= e(rawurlencode($pkg['slug'])) ?>">← Back to package</a>
      <button class="btn-print" type="button" onclick="window.print()">Download PDF</button>
    </div>
  </div>

  <article class="sheet">
    <p class="brand">YATHRANEST</p>
    <h1><?= e($pkg['title']) ?></h1>
    <p class="meta">
      <?= e($pkg['dest_line']) ?> · <?= $days ?> Days / <?= e($nightsLabel) ?><br />
      Pickup <?= e($pkg['pickup']) ?> · Drop <?= e($pkg['drop_point']) ?>
    </p>

    <h2>Overview</h2>
    <p><?= e($pkg['overview']) ?></p>

    <?php if (!empty($pkg['highlights'])): ?>
      <h2>Highlights</h2>
      <ul>
        <?php foreach ($pkg['highlights'] as $h): ?>
          <li><?= e($h) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <h2>Day-by-day itinerary</h2>
    <?php foreach ($pkg['itinerary'] as $i => $day): ?>
      <div class="day">
        <strong>Day <?= (int) ($day['day'] ?? ($i + 1)) ?> — <?= e($day['title'] ?? '') ?></strong>
        <div><?= e($day['text'] ?? '') ?></div>
      </div>
    <?php endforeach; ?>

    <h2>Accommodation</h2>
    <p><?= e($pkg['accommodation']) ?></p>

    <h2>Inclusions</h2>
    <ul>
      <li>Accommodation as per itinerary (<?= e($pkg['stay_summary']) ?>)</li>
      <li><?= !empty($pkg['has_houseboat']) ? 'Daily breakfast and houseboat meals as applicable' : 'Daily breakfast' ?></li>
      <li>Private transfers for sightseeing segments</li>
      <li>Assistance at pickup and drop</li>
    </ul>

    <h2>Exclusions</h2>
    <ul>
      <li>Flights / trains</li>
      <li>Personal expenses &amp; optional activities</li>
      <li>Entry fees not mentioned</li>
      <li>Anything not listed in inclusions</li>
    </ul>

    <div class="footer-note">
      Pricing is shared personally after enquiry — no online rates.<br />
      Contact: <?= e($phone) ?> · <?= e($email) ?><br />
      Generated <?= e(date('d M Y')) ?> · yathranest.com
    </div>
  </article>

  <?php if ($autoPrint): ?>
  <script>window.addEventListener('load', function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
