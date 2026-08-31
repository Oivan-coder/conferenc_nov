<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');

const RUN_TOKEN_HASH = 'bd4ed3e3bc0fcf72816a9bcc6bb4a858793048dc8e4ba733a79357285e220f48';
const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const BASE_URL = 'https://rclsmo.ru';

function out(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function httpRequest(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if ($raw === false) return ['status' => 0, 'body' => '', 'content_type' => '', 'error' => $error];
        return [
            'status' => $status,
            'body' => substr((string)$raw, $headerSize),
            'content_type' => $contentType,
            'error' => $error,
        ];
    }

    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", $headers),
        'content' => $body ?? '',
        'ignore_errors' => true,
        'timeout' => 25,
    ]]);
    $responseBody = @file_get_contents($url, false, $context);
    $status = 0;
    $contentType = '';
    foreach ($http_response_header ?? [] as $line) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $m)) $status = (int)$m[1];
        if (stripos($line, 'Content-Type:') === 0) $contentType = trim(substr($line, 13));
    }
    return ['status' => $status, 'body' => (string)$responseBody, 'content_type' => $contentType, 'error' => ''];
}

$runToken = trim((string)($_GET['key'] ?? ''));
if ($runToken === '' || !hash_equals(RUN_TOKEN_HASH, hash('sha256', $runToken))) {
    out(404, ['ok' => false, 'error' => 'not_found']);
}
if (!is_readable(TEST_KEY_PATH)) out(503, ['ok' => false, 'error' => 'test_key_unavailable']);
$testKey = trim((string)file_get_contents(TEST_KEY_PATH));
if ($testKey === '') out(503, ['ok' => false, 'error' => 'test_key_unavailable']);

$stamp = gmdate('YmdHis') . '-' . random_int(100, 999);
$cases = [
    ['format' => 'offline', 'suffix' => 'off1', 'first' => 'ОчноОдин'],
    ['format' => 'offline', 'suffix' => 'off2', 'first' => 'ОчноДва'],
    ['format' => 'online',  'suffix' => 'on1',  'first' => 'ОнлайнОдин'],
    ['format' => 'online',  'suffix' => 'on2',  'first' => 'ОнлайнДва'],
];

$results = [];
foreach ($cases as $index => $case) {
    $email = 'ivangoltsev8+qa-' . $case['suffix'] . '-' . $stamp . '@gmail.com';
    $phone = '+7998' . str_pad((string)random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
    $payload = [
        'eventId' => EVENT_ID,
        'lastName' => 'Автотест' . ($index + 1),
        'firstName' => $case['first'],
        'middleName' => 'Проверочный',
        'position' => 'Тестовый участник',
        'organization' => 'Тестовая МО',
        'email' => $email,
        'phone' => $phone,
        'participationFormat' => $case['format'],
        'privacyConsent' => true,
        'confirmDuplicate' => true,
    ];

    $registration = httpRequest(
        BASE_URL . '/api/register.php',
        'POST',
        [
            'Content-Type: application/json',
            'Origin: https://rclsmo.ru',
            'X-Registration-Test: ' . $testKey,
        ],
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    $decoded = json_decode($registration['body'], true);
    $decoded = is_array($decoded) ? $decoded : [];

    $entry = [
        'format' => $case['format'],
        'email' => $email,
        'http_status' => $registration['status'],
        'api_ok' => (bool)($decoded['ok'] ?? false),
        'participant_code' => $decoded['participant_code'] ?? null,
        'registration_status' => $decoded['registration_status'] ?? null,
        'email_sent' => $decoded['email_sent'] ?? null,
        'test_mode' => $decoded['test_mode'] ?? null,
        'api_error' => $decoded['error'] ?? ($registration['error'] ?: null),
    ];

    if ($case['format'] === 'offline' && !empty($decoded['ticket_url'])) {
        $ticket = httpRequest((string)$decoded['ticket_url']);
        $entry['ticket_url'] = $decoded['ticket_url'];
        $entry['ticket_http_status'] = $ticket['status'];
        $entry['ticket_valid'] = $ticket['status'] === 200
            && str_contains($ticket['body'], (string)($decoded['participant_code'] ?? ''))
            && str_contains($ticket['body'], 'Автотест' . ($index + 1));

        if (preg_match('#/ticket\.php\?t=([a-f0-9]{64})#', (string)$decoded['ticket_url'], $m)) {
            $qr = httpRequest(BASE_URL . '/api/qr.php?t=' . $m[1]);
            $entry['qr_http_status'] = $qr['status'];
            $entry['qr_content_type'] = $qr['content_type'];
            $entry['qr_valid'] = $qr['status'] === 200 && str_starts_with(strtolower($qr['content_type']), 'image/');
        }
    }

    if ($case['format'] === 'online' && !empty($decoded['live_url'])) {
        $live = httpRequest((string)$decoded['live_url']);
        $entry['live_url'] = $decoded['live_url'];
        $entry['live_http_status'] = $live['status'];
        $entry['live_valid'] = $live['status'] === 200
            && (str_contains($live['body'], 'Автотест' . ($index + 1)) || str_contains($live['body'], (string)($decoded['participant_code'] ?? '')));
    }

    $results[] = $entry;
    usleep(200000);
}

$dbChecks = [];
try {
    $pdo = require '/home/c/cx314477/public_html/.private/db.php';
    if (!$pdo instanceof PDO) throw new RuntimeException('db_config_invalid');
    $stmt = $pdo->prepare('SELECT participant_code, full_name, email, participation_format, registration_status, qr_sent_at FROM participants WHERE email LIKE :prefix ORDER BY id');
    $stmt->execute([':prefix' => 'ivangoltsev8+qa-%-' . $stamp . '@gmail.com']);
    $dbChecks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $dbChecks = [['error' => $e->getMessage()]];
}

$health = httpRequest(BASE_URL . '/api/health.php');
$availability = httpRequest(BASE_URL . '/api/registration-availability.php');

$allApiOk = count($results) === count($cases);
foreach ($results as $item) {
    if (!$item['api_ok'] || $item['http_status'] !== 201 || $item['registration_status'] !== 'confirmed' || $item['email_sent'] !== true) $allApiOk = false;
    if ($item['format'] === 'offline' && (empty($item['ticket_valid']) || empty($item['qr_valid']))) $allApiOk = false;
    if ($item['format'] === 'online' && empty($item['live_valid'])) $allApiOk = false;
}

out(200, [
    'ok' => $allApiOk,
    'stamp' => $stamp,
    'cases' => $results,
    'db_rows' => $dbChecks,
    'health' => [
        'http_status' => $health['status'],
        'body' => json_decode($health['body'], true) ?: $health['body'],
    ],
    'availability' => [
        'http_status' => $availability['status'],
        'body' => json_decode($availability['body'], true) ?: $availability['body'],
    ],
]);
