<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
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

$model = trim((string) config('gemini_model', 'gemini-3.6-flash'));
if ($model === '') {
    $model = 'gemini-3.6-flash';
}

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

    // Prefer generateContent (more reliable under PHP request limits).
    // Fall back to Interactions API on transport failure.
    $result = package_ai_call_generate_content($apiKey, $model, $prompt, $schema);
    if (!$result['ok'] && package_ai_is_retryable_transport_error($result['error'] ?? '')) {
        $result = package_ai_call_interactions($apiKey, $model, $prompt, $schema);
    }
    if (!$result['ok']) {
        return $result;
    }

    return package_ai_normalize_content($result['data'], $days);
}

/**
 * @param array<string,mixed> $schema
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function package_ai_call_interactions(string $apiKey, string $model, string $prompt, array $schema): array
{
    $url = 'https://generativelanguage.googleapis.com/v1beta/interactions';
    $body = [
        'model' => $model,
        'input' => $prompt,
        'generation_config' => [
            'thinking_level' => 'minimal',
        ],
        'response_format' => [
            [
                'type' => 'text',
                'mime_type' => 'application/json',
                'schema' => $schema,
            ],
        ],
    ];

    $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return ['ok' => false, 'error' => 'Could not build AI request.', 'code' => 500];
    }

    $response = package_ai_http_post($url, $payload, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ], 100);

    return package_ai_parse_http_json_response($response, static function (array $decoded): string {
        return package_ai_extract_interaction_text($decoded);
    });
}

/**
 * @param array<string,mixed> $schema
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function package_ai_call_generate_content(string $apiKey, string $model, string $prompt, array $schema): array
{
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . rawurlencode($model)
        . ':generateContent';

    $body = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ],
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
            'thinkingConfig' => [
                'thinkingLevel' => 'MINIMAL',
            ],
        ],
    ];

    $payload = json_encode($body, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return ['ok' => false, 'error' => 'Could not build AI request.', 'code' => 500];
    }

    $response = package_ai_http_post($url, $payload, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ], 100);

    return package_ai_parse_http_json_response($response, static function (array $decoded): string {
        $text = '';
        $parts = $decoded['candidates'][0]['content']['parts'] ?? [];
        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }
        return $text;
    });
}

/**
 * @param array{body:string,code:int,error:string} $response
 * @param callable(array<string,mixed>):string $extractText
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function package_ai_parse_http_json_response(array $response, callable $extractText): array
{
    if ($response['error'] !== '') {
        $err = $response['error'];
        if (stripos($err, 'timed out') !== false || stripos($err, 'timeout') !== false) {
            $err = 'Gemini timed out. Please try Generate again.';
        }
        return ['ok' => false, 'error' => $err, 'code' => 502];
    }

    $httpCode = $response['code'];
    $decoded = json_decode($response['body'], true);
    if (!is_array($decoded)) {
        $hint = $httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '';
        $snippet = trim(preg_replace('/\s+/', ' ', substr($response['body'], 0, 160)));
        if ($snippet !== '') {
            $hint .= ': ' . $snippet;
        } elseif ($httpCode === 404) {
            $hint .= ': endpoint not found';
        } elseif ($response['body'] === '') {
            $hint .= ': empty body';
        }
        return ['ok' => false, 'error' => 'Invalid response from Gemini' . $hint . '.', 'code' => 502];
    }

    if ($httpCode >= 400) {
        $message = (string) ($decoded['error']['message'] ?? $decoded['message'] ?? 'Gemini request failed.');
        if ($httpCode === 429) {
            $message = 'Gemini rate limit reached. Try again in a moment.';
        }
        return ['ok' => false, 'error' => $message, 'code' => $httpCode >= 500 ? 502 : $httpCode];
    }

    $text = trim($extractText($decoded));
    if ($text === '') {
        $status = (string) ($decoded['status'] ?? '');
        $hint = $status !== '' ? ' (status: ' . $status . ')' : '';
        return ['ok' => false, 'error' => 'Gemini returned empty content' . $hint . '.', 'code' => 502];
    }

    if (preg_match('/```(?:json)?\s*([\s\S]*?)```/', $text, $m)) {
        $text = trim($m[1]);
    }

    $data = json_decode($text, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Could not parse AI JSON. Try generating again.', 'code' => 502];
    }

    return ['ok' => true, 'data' => $data];
}

function package_ai_is_retryable_transport_error(string $error): bool
{
    $error = strtolower($error);
    return str_contains($error, 'timed out')
        || str_contains($error, 'timeout')
        || str_contains($error, 'could not reach')
        || str_contains($error, 'failed to connect')
        || str_contains($error, '0 bytes received');
}

/**
 * Pull final model text from an Interactions API response.
 *
 * @param array<string,mixed> $decoded
 */
function package_ai_extract_interaction_text(array $decoded): string
{
    if (isset($decoded['outputs']) && is_array($decoded['outputs'])) {
        $chunks = [];
        foreach ($decoded['outputs'] as $output) {
            if (!is_array($output)) {
                continue;
            }
            if (($output['type'] ?? '') === 'text' && isset($output['text']) && is_string($output['text'])) {
                $chunks[] = $output['text'];
            }
        }
        if ($chunks !== []) {
            return implode('', $chunks);
        }
    }

    $steps = $decoded['steps'] ?? [];
    if (!is_array($steps)) {
        return '';
    }

    $text = '';
    foreach ($steps as $step) {
        if (!is_array($step) || ($step['type'] ?? '') !== 'model_output') {
            continue;
        }
        $content = $step['content'] ?? [];
        if (!is_array($content)) {
            continue;
        }
        foreach ($content as $part) {
            if (is_array($part) && ($part['type'] ?? '') === 'text' && isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }
        }
    }

    return $text;
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

    // Strip accidental pickup-intent phrases from titles.
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
    foreach (array_values($rawItinerary) as $i => $day) {
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

/**
 * @param list<string> $headers
 * @return array{body:string,code:int,error:string}
 */
function package_ai_http_post(string $url, string $payload, array $headers = [], int $timeoutSeconds = 100): array
{
    if ($headers === []) {
        $headers = ['Content-Type: application/json'];
    }
    $timeoutSeconds = max(30, $timeoutSeconds);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            // Windows/XAMPP often hangs on IPv6 routes to Google APIs.
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        ];
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            return ['body' => '', 'code' => 0, 'error' => $err !== '' ? $err : 'Could not reach Gemini.'];
        }
        return ['body' => (string) $body, 'code' => $code, 'error' => ''];
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers) . "\r\n",
            'content' => $payload,
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $code = (int) $m[1];
    }
    if ($body === false) {
        return ['body' => '', 'code' => 0, 'error' => 'Could not reach Gemini.'];
    }
    return ['body' => (string) $body, 'code' => $code, 'error' => ''];
}
