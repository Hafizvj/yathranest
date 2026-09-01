<?php

/**
 * Shared Gemini API helpers for generateContent and Interactions.
 */

/**
 * @param list<string> $headers
 * @return array{body:string,code:int,error:string}
 */
function gemini_http_post(string $url, string $payload, array $headers = [], int $timeoutSeconds = 100): array
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

/**
 * @param array{body:string,code:int,error:string} $response
 * @param callable(array<string,mixed>):string $extractText
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function gemini_parse_http_json_response(array $response, callable $extractText): array
{
    if ($response['error'] !== '') {
        $err = $response['error'];
        if (stripos($err, 'timed out') !== false || stripos($err, 'timeout') !== false) {
            $err = 'Gemini timed out. Please try again.';
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
        return ['ok' => false, 'error' => 'Could not parse AI JSON. Try again.', 'code' => 502];
    }

    return ['ok' => true, 'data' => $data];
}

function gemini_is_retryable_transport_error(string $error): bool
{
    $error = strtolower($error);
    return str_contains($error, 'timed out')
        || str_contains($error, 'timeout')
        || str_contains($error, 'could not reach')
        || str_contains($error, 'failed to connect')
        || str_contains($error, '0 bytes received');
}

/**
 * @param array<string,mixed> $decoded
 */
function gemini_extract_interaction_text(array $decoded): string
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
 * Build Gemini content parts from a text prompt and/or raw bytes.
 *
 * @param string $prompt
 * @param string|null $binaryData MIME-checked binary (e.g. PDF bytes)
 * @param string $mimeType
 * @return list<array<string,mixed>>
 */
function gemini_build_parts(string $prompt, ?string $binaryData = null, string $mimeType = 'application/pdf'): array
{
    $parts = [];
    if ($binaryData !== null && $binaryData !== '') {
        $parts[] = [
            'inline_data' => [
                'mime_type' => $mimeType,
                'data' => base64_encode($binaryData),
            ],
        ];
    }
    if ($prompt !== '') {
        $parts[] = ['text' => $prompt];
    }
    return $parts;
}

/**
 * @param list<array<string,mixed>> $parts
 * @param array<string,mixed> $schema
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function gemini_call_interactions(string $apiKey, string $model, array $parts, array $schema, int $timeoutSeconds = 100): array
{
    $prompt = '';
    foreach ($parts as $part) {
        if (isset($part['text']) && is_string($part['text'])) {
            $prompt .= $part['text'];
        }
    }

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

    $response = gemini_http_post($url, $payload, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ], $timeoutSeconds);

    return gemini_parse_http_json_response($response, static function (array $decoded): string {
        return gemini_extract_interaction_text($decoded);
    });
}

/**
 * @param list<array<string,mixed>> $parts
 * @param array<string,mixed> $schema
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function gemini_call_generate_content(string $apiKey, string $model, array $parts, array $schema, int $timeoutSeconds = 100): array
{
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . rawurlencode($model)
        . ':generateContent';

    $body = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => $parts,
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

    $response = gemini_http_post($url, $payload, [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
    ], $timeoutSeconds);

    return gemini_parse_http_json_response($response, static function (array $decoded): string {
        $text = '';
        $partsOut = $decoded['candidates'][0]['content']['parts'] ?? [];
        if (is_array($partsOut)) {
            foreach ($partsOut as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    $text .= $part['text'];
                }
            }
        }
        return $text;
    });
}

/**
 * @param list<array<string,mixed>> $parts
 * @param array<string,mixed> $schema
 * @return array{ok:bool,error?:string,code?:int,data?:array<string,mixed>}
 */
function gemini_call_json_schema(string $apiKey, string $model, array $parts, array $schema, int $timeoutSeconds = 120): array
{
    $result = gemini_call_generate_content($apiKey, $model, $parts, $schema, $timeoutSeconds);
    if (!$result['ok'] && gemini_is_retryable_transport_error($result['error'] ?? '')) {
        $result = gemini_call_interactions($apiKey, $model, $parts, $schema, $timeoutSeconds);
    }
    return $result;
}

function gemini_default_model(): string
{
    $model = trim((string) config('gemini_model', 'gemini-3.6-flash'));
    return $model !== '' ? $model : 'gemini-3.6-flash';
}
