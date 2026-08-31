<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
require_once __DIR__ . '/invite-access.php';

function inviteAvailabilityRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') inviteAvailabilityRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);

$token = trim((string)($_GET['t'] ?? ''));
if (!inviteTokenIsActive($token)) inviteAvailabilityRespond(404, ['ok' => false, 'error' => 'not_found']);
$tokenHash = inviteTokenHash($token);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    inviteEnsureSchema($pdo);

    if (inviteIsUsed($pdo, $tokenHash)) {
        inviteAvailabilityRespond(410, ['ok' => false, 'error' => 'invite_used']);
    }

    $settingsStmt = $pdo->prepare('SELECT hall_capacity, offline_registration_open FROM event_registration_settings WHERE event_id = :event_id LIMIT 1');
    $settingsStmt->execute([':event_id' => EVENT_ID]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) throw new RuntimeException('Event registration settings missing');

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND participation_format = "offline" AND registration_status = "confirmed"');
    $countStmt->execute([':event_id' => EVENT_ID]);
    $confirmedOffline = (int)$countStmt->fetchColumn();
    $hallCapacity = max(0, (int)$settings['hall_capacity']);
    $offlineAvailable = (bool)$settings['offline_registration_open'] && $confirmedOffline < $hallCapacity;

    inviteAvailabilityRespond(200, [
        'ok' => true,
        'offline' => [
            'available' => $offlineAvailable,
            'state' => $offlineAvailable ? 'available' : 'full',
            'waitlist_available' => false
        ],
        'online' => [
            'available' => false
        ],
        'invite' => [
            'active' => true,
            'offline_only' => true,
            'expires_at' => INVITE_EXPIRES_AT
        ]
    ]);
} catch (Throwable $e) {
    inviteAvailabilityRespond(503, ['ok' => false, 'error' => 'availability_unavailable']);
}
