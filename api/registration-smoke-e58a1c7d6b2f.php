<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');

const TEST_KEY_PATH_SMOKE = '/home/c/cx314477/public_html/.private/registration_test_key';
const TEST_MARKER_SMOKE = '/home/c/cx314477/public_html/.private/registration_smoke_20260904_e58a1c7d6b2f.json';

function smokeRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function smokeHttpStatus(array $headers): int {
    foreach ($headers as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', (string)$header, $match)) return (int)$match[1];
    }
    return 0;
}

function smokeRequest(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array {
    $headerLines = $headers;
    if ($body !== null) $headerLines[] = 'Content-Type: application/json';
    $options = [
        'method' => $method,
        'header' => implode("\r\n", $headerLines) . "\r\n",
        'ignore_errors' => true,
        'timeout' => 25,
    ];
    if ($body !== null) $options['content'] = $body;
    $context = stream_context_create(['http' => $options]);
    $raw = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];
    return ['status' => smokeHttpStatus($responseHeaders), 'body' => $raw === false ? '' : $raw];
}

function smokeRegister(string $key, string $source, string $format, string $firstName): array {
    $payload = [
        'eventId' => 'forum-lab-innovations-2026-10-07',
        'lastName' => 'Проверка',
        'firstName' => $firstName,
        'middleName' => 'Системная',
        'position' => 'Тестирование регистрации',
        'organization' => 'Тестовая МО',
        'email' => 'info@rclsmo.ru',
        'phone' => '',
        'participationFormat' => $format,
        'privacyConsent' => true,
        'policyAcknowledged' => true,
        'confirmDuplicate' => true,
    ];
    $response = smokeRequest(
        'https://rclsmo.ru/api/register.php',
        'POST',
        ['X-Registration-Test: ' . $key, 'X-Registration-Source: ' . $source],
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $decoded = json_decode($response['body'], true);
    return [
        'http_status' => $response['status'],
        'response' => is_array($decoded) ? $decoded : ['raw' => $response['body']],
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') smokeRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);

$environment = [
    'php_version' => PHP_VERSION,
    'register_readable' => is_readable(__DIR__ . '/register.php'),
    'config_readable' => is_readable(__DIR__ . '/registration-config.php'),
    'config_loaded' => false,
    'config_error' => null,
];
if ($environment['config_readable']) {
    try {
        require_once __DIR__ . '/registration-config.php';
        $environment['config_loaded'] = function_exists('registrationEnsureSourceColumn');
    } catch (Throwable $configError) {
        $environment['config_error'] = get_class($configError) . ': ' . $configError->getMessage();
    }
}

if (is_readable(TEST_MARKER_SMOKE)) {
    $stored = json_decode((string)file_get_contents(TEST_MARKER_SMOKE), true);
    if (is_array($stored) && !empty($stored['ok'])) smokeRespond(200, $stored);
    @unlink(TEST_MARKER_SMOKE);
}

if (!is_readable(TEST_KEY_PATH_SMOKE)) smokeRespond(503, ['ok' => false, 'error' => 'test_key_unavailable']);
$key = trim((string)file_get_contents(TEST_KEY_PATH_SMOKE));
if ($key === '') smokeRespond(503, ['ok' => false, 'error' => 'test_key_unavailable']);

$runs = [
    'public_offline' => smokeRegister($key, 'public', 'offline', 'ПубличнаяОчная'),
    'invited_offline' => smokeRegister($key, 'invited', 'offline', 'ПриглашеннаяОчная'),
    'public_online' => smokeRegister($key, 'public', 'online', 'ПубличнаяОнлайн'),
];

$allOk = true;
foreach ($runs as $name => &$run) {
    $response = $run['response'];
    $expectedSource = str_starts_with($name, 'invited') ? 'invited' : 'public';
    $run['registration_ok'] = $run['http_status'] === 201
        && !empty($response['ok'])
        && ($response['registration_source'] ?? '') === $expectedSource
        && !empty($response['participant_code'])
        && !empty($response['email_sent']);

    $participantUrl = (string)($response['participant_url'] ?? '');
    if ($participantUrl !== '') {
        $probe = smokeRequest($participantUrl);
        $run['participant_page_status'] = $probe['status'];
        $run['participant_page_ok'] = $probe['status'] === 200;
    }

    if (($response['participation_format'] ?? '') === 'offline' && $participantUrl !== '') {
        $query = parse_url($participantUrl, PHP_URL_QUERY);
        parse_str(is_string($query) ? $query : '', $params);
        $token = trim((string)($params['t'] ?? ''));
        if ($token !== '') {
            $qrProbe = smokeRequest('https://rclsmo.ru/api/qr.php?t=' . rawurlencode($token));
            $run['qr_status'] = $qrProbe['status'];
            $run['qr_ok'] = $qrProbe['status'] === 200 && strlen($qrProbe['body']) > 100;
        }
    }

    if (empty($run['registration_ok']) || empty($run['participant_page_ok']) || (($response['participation_format'] ?? '') === 'offline' && empty($run['qr_ok']))) {
        $allOk = false;
    }
}
unset($run);

$result = ['ok' => $allOk, 'created_at' => date(DATE_ATOM), 'environment' => $environment, 'runs' => $runs];
@file_put_contents(TEST_MARKER_SMOKE, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
smokeRespond($allOk ? 200 : 503, $result);
