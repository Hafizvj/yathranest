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
  <link rel="icon" type="image/png" sizes="32x32" href="../assets/logo/favicon-32.png" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" />
  <style>
    :root {
      color-scheme: light;
      --ink: #1d3f36;
      --teal: #346356;
      --muted: #5f6b66;
      --line: #e6e3dc;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "DM Sans", system-ui, -apple-system, sans-serif;
      color: #141414;
      background: #f4f2ee;
      line-height: 1.6;
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
      padding: 0.9rem 1.5rem;
      background: linear-gradient(135deg, #274f45, #142d27);
      color: rgba(255, 255, 255, 0.82);
      font-size: 0.9375rem;
    }
    .toolbar strong { color: #fff; }
    .toolbar a, .toolbar button {
      font: inherit;
      font-weight: 600;
      cursor: pointer;
      border-radius: 999px;
      border: 1px solid transparent;
      padding: 0.55rem 1.1rem;
      text-decoration: none;
    }
    .toolbar .btn-print { background: #fff; color: var(--ink); }
    .toolbar .btn-back { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.28); color: #fff; }
    .sheet {
      max-width: 820px;
      margin: 2rem auto 3rem;
      background: #fff;
      padding: 2.5rem 2.75rem;
      border: 1px solid var(--line);
      border-radius: 20px;
      box-shadow: 0 18px 48px rgba(21, 45, 39, 0.1);
    }
    .brand {
      height: 40px;
      width: auto;
      display: block;
      margin: 0 0 1rem;
    }
    h1 {
      margin: 0 0 0.5rem;
      font-size: 1.9rem;
      letter-spacing: -0.03em;
      color: var(--ink);
    }
    .meta { color: var(--muted); margin: 0 0 1.5rem; font-size: 0.9375rem; }
    h2 {
      margin: 2rem 0 0.75rem;
      font-size: 1.05rem;
      letter-spacing: -0.01em;
      color: var(--ink);
      border-bottom: 1px solid var(--line);
      padding-bottom: 0.45rem;
    }
    p { margin: 0 0 0.75rem; color: #3a4446; }
    ul { padding-left: 1.2rem; margin: 0; color: #3a4446; }
    ul li { margin-bottom: 0.35rem; }
    ul li::marker { color: var(--teal); }
    .day {
      margin: 0.75rem 0;
      padding: 0.9rem 1.05rem;
      background: #f7faf9;
      border: 1px solid var(--line);
      border-radius: 12px;
      break-inside: avoid;
    }
    .day strong { display: block; margin-bottom: 0.2rem; color: var(--ink); }
    .footer-note {
      margin-top: 2.5rem;
      padding-top: 1.25rem;
      border-top: 1px solid var(--line);
      font-size: 0.875rem;
      color: var(--muted);
    }
    @media print {
      body { background: #fff; }
      .toolbar { display: none !important; }
      .sheet {
        margin: 0;
        border: 0;
        border-radius: 0;
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
    <div>YathraNest itinerary — choose <strong>Save as PDF</strong> in the print dialog</div>
    <div style="display:flex;gap:0.5rem">
      <a class="btn-back" href="package-details.php?package=<?= e(rawurlencode($pkg['slug'])) ?>">← Back to package</a>
      <button class="btn-print" type="button" onclick="window.print()">Download PDF</button>
    </div>
  </div>

  <article class="sheet">
    <img class="brand" src="../assets/logo/logo-wordmark.png" alt="YathraNest" width="293" height="98" />
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
