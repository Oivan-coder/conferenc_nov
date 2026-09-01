<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const CONSENT_VERSION = '2026-08-31';
const KEY_HASH = '169f8e0571b04e244f9a45cff4d7739ea6fbc33346ca936cb26f75fed4b5b2c9';

$key = trim((string)($_GET['key'] ?? ''));
if ($key === '' || !hash_equals(KEY_HASH, hash('sha256', $key))) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');

    $participantCode = 'LE' . strtoupper(bin2hex(random_bytes(4)));
    $qrToken = bin2hex(random_bytes(32));
    $onlineToken = bin2hex(random_bytes(32));
    $suffix = gmdate('YmdHis');
    $email = 'qa-online-' . $suffix . '@rclsmo.ru';
    $phone = '7999' . substr((string)time(), -7);

    $stmt = $pdo->prepare('INSERT INTO participants (
        event_id, participant_code, qr_token, online_token, last_name, first_name, middle_name, full_name,
        position, organization, email, email_normalized, phone, phone_normalized,
        participation_format, registration_status, privacy_consent, consent_version, consent_at, created_at
    ) VALUES (
        :event_id, :participant_code, :qr_token, :online_token, :last_name, :first_name, :middle_name, :full_name,
        :position, :organization, :email, :email_normalized, :phone, :phone_normalized,
        :participation_format, :registration_status, 1, :consent_version, NOW(), NOW()
    )');

    $stmt->execute([
        ':event_id' => EVENT_ID,
        ':participant_code' => $participantCode,
        ':qr_token' => $qrToken,
        ':online_token' => $onlineToken,
        ':last_name' => 'Тестов',
        ':first_name' => 'Онлайн',
        ':middle_name' => 'Тестович',
        ':full_name' => 'Тестов Онлайн Тестович',
        ':position' => 'Врач КЛД',
        ':organization' => 'Тестовая МО',
        ':email' => $email,
        ':email_normalized' => $email,
        ':phone' => $phone,
        ':phone_normalized' => $phone,
        ':participation_format' => 'online',
        ':registration_status' => 'confirmed',
        ':consent_version' => CONSENT_VERSION,
    ]);

    echo json_encode([
        'ok' => true,
        'participant_code' => $participantCode,
        'live_url' => 'https://rclsmo.ru/live/?t=' . $onlineToken,
        'participant_url' => 'https://rclsmo.ru/participant.php?t=' . $onlineToken,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'create_failed']);
}
