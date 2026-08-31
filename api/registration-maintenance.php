<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

const PREVIEW_TOKEN_HASHES = [
    '6fbe6025563f098ca8756103aa6cb93f4ac2c5bbb5f769e42aff0de2de2c14b9',
    '65b8c5c44e5fdba2620f30c5e434f0893258809bc1a9d2650de8316b48a6f324',
];
const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const TEST_ORGANIZATION = 'Тестовая МО';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function tokenIsValid(string $token): bool {
    if ($token === '') return false;
    $hash = hash('sha256', $token);
    foreach (PREVIEW_TOKEN_HASHES as $allowedHash) {
        if (hash_equals($allowedHash, $hash)) return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false]);
if (!tokenIsValid(trim((string)($_GET['key'] ?? '')))) respond(404, ['ok' => false]);

$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) respond(400, ['ok' => false, 'error' => 'invalid_json']);
$action = (string)($body['action'] ?? '');

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'delete_ovan') {
        $stmt = $pdo->prepare('DELETE FROM participants WHERE event_id = :event AND LOWER(TRIM(organization)) = "ovan"');
        $stmt->execute([':event' => EVENT_ID]);
        respond(200, ['ok' => true, 'deleted' => $stmt->rowCount()]);
    }

    if ($action === 'scan_test') {
        $value = trim((string)($body['value'] ?? ''));
        $kind = null;
        if (preg_match('/^LE[A-F0-9]{8}$/i', $value)) {
            $kind = 'participant_code';
            $value = strtoupper($value);
        } elseif (preg_match('/^[a-f0-9]{64}$/i', $value)) {
            $kind = 'qr_token';
            $value = strtolower($value);
        } elseif (filter_var($value, FILTER_VALIDATE_URL)) {
            parse_str((string)parse_url($value, PHP_URL_QUERY), $query);
            $token = strtolower(trim((string)($query['t'] ?? '')));
            if (preg_match('/^[a-f0-9]{64}$/', $token)) {
                $kind = 'qr_token';
                $value = $token;
            }
        }
        if ($kind === null) respond(422, ['ok' => false, 'error' => 'invalid_scan']);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id, participant_code, check_in_at FROM participants WHERE event_id = :event AND organization = :test_org AND participation_format = 'offline' AND registration_status = 'confirmed' AND {$kind} = :value LIMIT 1 FOR UPDATE");
        $stmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION, ':value' => $value]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$participant) {
            $pdo->rollBack();
            respond(404, ['ok' => false, 'error' => 'not_found']);
        }
        $alreadyCheckedIn = $participant['check_in_at'] !== null;
        if (!$alreadyCheckedIn) {
            $update = $pdo->prepare('UPDATE participants SET check_in_at = NOW() WHERE id = :id');
            $update->execute([':id' => $participant['id']]);
        }
        $pdo->commit();
        respond(200, ['ok' => true, 'participant_code' => $participant['participant_code'], 'already_checked_in' => $alreadyCheckedIn]);
    }

    respond(400, ['ok' => false, 'error' => 'unknown_action']);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
