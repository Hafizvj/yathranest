<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/gemini.php';
require_once __DIR__ . '/_form_helpers.php';
require_admin();

@set_time_limit(180);
ignore_user_abort(true);

header('Content-Type: application/json; charset=utf-8');

function ai_json(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    ai_json(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$raw = file_get_contents('php://input');
$input = [];
if (is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (!$input) {
    $input = $_POST;
}

$token = $input['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf(is_string($token) ? $token : null)) {
    ai_json(['ok' => false, 'error' => 'Invalid CSRF token.'], 403);
}

$apiKey = trim((string) config('gemini_api_key', ''));
if ($apiKey === '') {
    ai_json(['ok' => false, 'error' => 'Gemini API key is not configured. Add gemini_api_key in config/config.php.'], 503);
}

$days = max(1, min(60, (int) ($input['days'] ?? 1)));
$nights = max(0, min(60, (int) ($input['nights'] ?? 0)));
if ($nights > $days) {
    $nights = $days;
}

$types = isset($input['types']) && is_array($input['types'])
    ? array_values(array_filter(array_map('strval', $input['types'])))
    : [];
$destSlugs = isset($input['destinations']) && is_array($input['destinations'])
    ? array_values(array_filter(array_map('strval', $input['destinations'])))
    : [];
$stays = isset($input['stays']) && is_array($input['stays'])
    ? array_values(array_map('strval', $input['stays']))
    : [];
$highlights = isset($input['highlights']) && is_array($input['highlights'])
    ? array_values(array_filter(array_map(static fn($h) => trim((string) $h), $input['highlights'])))
    : [];
$pickup = trim((string) ($input['pickup'] ?? ''));

if ($types === []) {
    ai_json(['ok' => false, 'error' => 'Select at least one type.'], 400);
}
if ($destSlugs === []) {
    ai_json(['ok' => false, 'error' => 'Select at least one destination.'], 400);
}
if ($pickup === '') {
    ai_json(['ok' => false, 'error' => 'Pickup / drop is required.'], 400);
}

$places = places_all();
$typeOptions = package_type_options();
$destLabels = [];
foreach ($destSlugs as $slug) {
    $destLabels[] = (string) ($places[$slug]['label'] ?? $slug);
}
$stayLabels = [];
foreach (array_slice($stays, 0, $nights) as $i => $slug) {
    $stayLabels[] = 'Night ' . ($i + 1) . ': ' . (string) ($places[$slug]['label'] ?? ($slug !== '' ? $slug : '(not set)'));
}
$typeLabels = [];
foreach ($types as $type) {
    $typeLabels[] = (string) ($typeOptions[$type] ?? ucwords(str_replace('-', ' ', $type)));
}

$model = gemini_default_model();

$prompt = package_ai_seo_prompt([
    'days' => $days,
    'nights' => $nights,
    'dest_labels' => $destLabels,
    'type_labels' => $typeLabels,
    'pickup' => $pickup,
    'stay_labels' => $stayLabels,
    'highlights' => $highlights,
]);

$result = package_ai_call_gemini($apiKey, $model, $prompt, $days);
if (!$result['ok']) {
    ai_json(['ok' => false, 'error' => $result['error']], (int) ($result['code'] ?? 502));
}

ai_json([
    'ok' => true,
    'title' => $result['title'],
    'card_text' => $result['card_text'],
    'overview' => $result['overview'],
    'itinerary' => $result['itinerary'],
]);

/**
 * @param array{
 *   days:int,nights:int,dest_labels:list<string>,type_labels:list<string>,
 *   pickup:string,stay_labels:list<string>,highlights:list<string>
 * } $facts
 */
function package_ai_seo_prompt(array $facts): string
{
    $destLine = implode(', ', $facts['dest_labels']);
    $typeLine = implode(', ', $facts['type_labels']);
    $highlightLine = $facts['highlights'] !== []
        ? implode('; ', $facts['highlights'])
        : '(none provided)';
    $stayLine = $facts['stay_labels'] !== []
        ? implode('; ', $facts['stay_labels'])
        : '(day trip / no overnight stays)';

    return <<<PROMPT
You write SEO-focused travel package copy for YathraNest, an Indian travel brand.

Return ONLY valid JSON with this exact shape:
{
  "title": "string",
  "card_text": "string",
  "overview": "string",
  "itinerary": [{"day": 1, "title": "string", "text": "string"}]
}

Trip facts (use only these; do not invent destinations):
- Destinations: {$destLine}
- Duration: {$facts['days']} days, {$facts['nights']} nights
- Travel types: {$typeLine}
- Pickup / drop (logistics only): {$facts['pickup']}
- Overnight stays: {$stayLine}
- Highlights: {$highlightLine}

SEO rules:
- title: destinations + duration + package/tour wording (example: "Munnar Alleppey 4 Days Package"). NEVER add pickup intent such as "from Kochi", "ex-Kochi", "starting from", or similar.
- card_text: one benefit-led line, max 90 characters, with searchable place/theme words.
- overview: natural scannable copy (max 500 characters) weaving destinations, duration, and trip type. No keyword stuffing. Pickup may appear only if useful as logistics, not as title bait.
- itinerary: exactly {$facts['days']} day objects (day 1..{$facts['days']}). Day titles use place/activity phrases. Day text is descriptive for readers and search (activities, places). Align nights with the stay list when overnight stays exist.
- Tone: clear, inviting, professional. Real destination names only. JSON only, no markdown.
PROMPT;
}

/**
 * @return array{ok:bool,error?:string,code?:int,title?:string,card_text?:string,overview?:string,itinerary?:list<array{day:int,title:string,text:string}>}
 */
function package_ai_call_gemini(string $apiKey, string $model, string $prompt, int $days): array
{
    $schema = [
        'type' => 'OBJECT',
        'properties' => [
            'title' => ['type' => 'STRING'],
            'card_text' => ['type' => 'STRING'],
            'overview' => ['type' => 'STRING'],
            'itinerary' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'day' => ['type' => 'INTEGER'],
                        'title' => ['type' => 'STRING'],
                        'text' => ['type' => 'STRING'],
                    ],
                    'required' => ['day', 'title', 'text'],
                ],
            ],
        ],
        'required' => ['title', 'card_text', 'overview', 'itinerary'],
    ];

    $parts = gemini_build_parts($prompt);
    $result = gemini_call_json_schema($apiKey, $model, $parts, $schema, 100);
    if (!$result['ok']) {
        return $result;
    }

    return package_ai_normalize_content($result['data'], $days);
}

/**
 * @param array<string,mixed> $data
 * @return array{ok:bool,error?:string,code?:int,title?:string,card_text?:string,overview?:string,itinerary?:list<array{day:int,title:string,text:string}>}
 */
function package_ai_normalize_content(array $data, int $days): array
{
    $title = trim((string) ($data['title'] ?? ''));
    $cardText = trim((string) ($data['card_text'] ?? ''));
    $overview = trim((string) ($data['overview'] ?? ''));

    if ($title === '' || $overview === '') {
        return ['ok' => false, 'error' => 'AI response missing title or overview.', 'code' => 502];
    }

    $title = preg_replace('/\s*[\-\|–—]?\s*(from|ex[-\s]?|starting from)\s+[A-Za-z .]+$/iu', '', $title) ?? $title;
    $title = trim($title);

    if (mb_strlen($cardText) > 90) {
        $cardText = rtrim(mb_substr($cardText, 0, 87)) . '…';
    }
    if (mb_strlen($overview) > 500) {
        $overview = rtrim(mb_substr($overview, 0, 497)) . '…';
    }

    $rawItinerary = $data['itinerary'] ?? [];
    if (!is_array($rawItinerary)) {
        $rawItinerary = [];
    }

    $itinerary = [];
    foreach (array_values($rawItinerary) as $day) {
        if (!is_array($day)) {
            continue;
        }
        $dayTitle = trim((string) ($day['title'] ?? ''));
        $dayText = trim((string) ($day['text'] ?? ''));
        if ($dayTitle === '' && $dayText === '') {
            continue;
        }
        if (mb_strlen($dayText) > 1000) {
            $dayText = rtrim(mb_substr($dayText, 0, 997)) . '…';
        }
        $itinerary[] = [
            'day' => count($itinerary) + 1,
            'title' => $dayTitle !== '' ? $dayTitle : ('Day ' . (count($itinerary) + 1)),
            'text' => $dayText,
        ];
        if (count($itinerary) >= $days) {
            break;
        }
    }

    while (count($itinerary) < $days) {
        $n = count($itinerary) + 1;
        $itinerary[] = ['day' => $n, 'title' => 'Day ' . $n, 'text' => ''];
    }

    return [
        'ok' => true,
        'title' => $title,
        'card_text' => $cardText,
        'overview' => $overview,
        'itinerary' => $itinerary,
    ];
}
