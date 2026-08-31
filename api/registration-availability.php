<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $settingsStmt = $pdo->prepare('SELECT hall_capacity, public_offline_limit, offline_registration_open, online_registration_open FROM event_registration_settings WHERE event_id = :event_id LIMIT 1');
    $settingsStmt->execute([':event_id' => EVENT_ID]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) throw new RuntimeException('Event registration settings missing');

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND participation_format = "offline" AND registration_status = "confirmed"');
    $countStmt->execute([':event_id' => EVENT_ID]);
    $confirmedOffline = (int)$countStmt->fetchColumn();

    $hallCapacity = max(0, (int)$settings['hall_capacity']);
    $configuredPublicLimit = max(0, (int)$settings['public_offline_limit']);
    $effectivePublicLimit = min($configuredPublicLimit, $hallCapacity);
    $remaining = max(0, $effectivePublicLimit - $confirmedOffline);
    $offlineOpen = (bool)$settings['offline_registration_open'];
    $onlineOpen = (bool)$settings['online_registration_open'];

    if (!$offlineOpen || $remaining === 0) {
        $offlineState = 'full';
    } elseif ($remaining <= 10) {
        $offlineState = 'limited';
    } else {
        $offlineState = 'available';
    }

    respond(200, [
        'ok' => true,
        'offline' => [
            'available' => $offlineOpen && $remaining > 0,
            'state' => $offlineState,
            'waitlist_available' => true
        ],
        'online' => [
            'available' => $onlineOpen
        ]
    ]);
} catch (Throwable $e) {
    respond(503, ['ok' => false, 'error' => 'availability_unavailable']);
}
