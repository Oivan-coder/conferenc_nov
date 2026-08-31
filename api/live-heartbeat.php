<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const EVENT_START = '2026-10-07 07:00:00';
const EVENT_END = '2026-10-07 20:00:00';
const TEST_ORGANIZATION = 'Тестовая МО';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function authorizedTestRequest(): bool {
    $provided = trim((string)($_SERVER['HTTP_X_REGISTRATION_TEST'] ?? ''));
    if ($provided === '' || !is_readable(TEST_KEY_PATH)) return false;
    $expected = trim((string)file_get_contents(TEST_KEY_PATH));
    return $expected !== '' && hash_equals($expected, $provided);
}

function trackingWindowOpen(): bool {
    $tz = new DateTimeZone('Europe/Moscow');
    $now = new DateTimeImmutable('now', $tz);
    $start = new DateTimeImmutable(EVENT_START, $tz);
    $end = new DateTimeImmutable(EVENT_END, $tz);
    return $now >= $start && $now <= $end;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 4096) respond(413, ['ok' => false, 'error' => 'request_too_large']);

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'invalid_json']);

$token = strtolower(trim((string)($data['token'] ?? '')));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) respond(422, ['ok' => false, 'error' => 'invalid_token']);

$isHeaderTest = authorizedTestRequest();
$isDashboardTest = !empty($_SESSION['conference_dashboard_auth']) && !empty($data['test_mode']);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id, organization, online_first_join_at, online_last_seen_at, online_watch_seconds, online_session_count
         FROM participants
         WHERE online_token = :token
           AND participation_format = "online"
           AND registration_status = "confirmed"
         LIMIT 1 FOR UPDATE'
    );
    $stmt->execute([':token' => $token]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        $pdo->rollBack();
        respond(404, ['ok' => false, 'error' => 'participant_not_found']);
    }

    $isSessionTest = !empty($data['test_mode'])
        && isset($_SESSION['conference_live_test_participant_id'])
        && (int)$_SESSION['conference_live_test_participant_id'] === (int)$participant['id'];
    $isTestParticipant = trim((string)$participant['organization']) === TEST_ORGANIZATION;

    if (!$isHeaderTest && !$isDashboardTest && !$isSessionTest && !$isTestParticipant && !trackingWindowOpen()) {
        $pdo->rollBack();
        respond(200, ['ok' => true, 'tracking_active' => false]);
    }

    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    $watchSeconds = (int)$participant['online_watch_seconds'];
    $sessionCount = (int)$participant['online_session_count'];
    $firstJoin = $participant['online_first_join_at'];
    $lastSeen = $participant['online_last_seen_at'];

    if ($firstJoin === null) {
        $update = $pdo->prepare(
            'UPDATE participants
             SET online_first_join_at = NOW(), online_last_seen_at = NOW(), online_session_count = online_session_count + 1
             WHERE id = :id'
        );
        $update->execute([':id' => $participant['id']]);
        $sessionCount++;
    } else {
        $gap = 0;
        if ($lastSeen !== null) {
            $last = new DateTimeImmutable($lastSeen, new DateTimeZone('Europe/Moscow'));
            $gap = max(0, $now->getTimestamp() - $last->getTimestamp());
        }

        if ($lastSeen === null || $gap > 120) {
            $update = $pdo->prepare(
                'UPDATE participants
                 SET online_last_seen_at = NOW(), online_session_count = online_session_count + 1
                 WHERE id = :id'
            );
            $update->execute([':id' => $participant['id']]);
            $sessionCount++;
        } else {
            $add = min($gap, 60);
            $update = $pdo->prepare(
                'UPDATE participants
                 SET online_last_seen_at = NOW(), online_watch_seconds = online_watch_seconds + :add_seconds
                 WHERE id = :id'
            );
            $update->execute([':add_seconds' => $add, ':id' => $participant['id']]);
            $watchSeconds += $add;
        }
    }

    $pdo->commit();

    respond(200, [
        'ok' => true,
        'tracking_active' => true,
        'watch_seconds' => $watchSeconds,
        'session_count' => $sessionCount,
        'present_15m' => $watchSeconds >= 900,
        'test_mode' => $isHeaderTest || $isDashboardTest || $isSessionTest || $isTestParticipant
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
