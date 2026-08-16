<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const CHECKIN_KEY_PATH = '/home/c/cx314477/public_html/.private/checkin_key';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function authorized(): bool {
    $provided = trim((string)($_SERVER['HTTP_X_CHECKIN_KEY'] ?? ''));
    if ($provided === '' || !is_readable(CHECKIN_KEY_PATH)) return false;
    $expected = trim((string)file_get_contents(CHECKIN_KEY_PATH));
    return $expected !== '' && hash_equals($expected, $provided);
}

function extractToken(string $raw): string {
    $raw = trim($raw);
    if (preg_match('/^[a-f0-9]{64}$/i', $raw)) return strtolower($raw);
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        $query = parse_url($raw, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            $token = strtolower(trim((string)($params['t'] ?? '')));
            if (preg_match('/^[a-f0-9]{64}$/', $token)) return $token;
        }
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if (!authorized()) respond(403, ['ok' => false, 'error' => 'forbidden']);

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'invalid_json']);

$token = extractToken((string)($data['token'] ?? $data['qr'] ?? ''));
if ($token === '') respond(422, ['ok' => false, 'error' => 'invalid_qr']);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT id, participant_code, full_name, position, organization, participation_format, check_in_at FROM participants WHERE qr_token = :token LIMIT 1 FOR UPDATE');
    $stmt->execute([':token' => $token]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$participant) {
        $pdo->rollBack();
        respond(404, ['ok' => false, 'error' => 'participant_not_found']);
    }

    $alreadyCheckedIn = $participant['check_in_at'] !== null;
    if (!$alreadyCheckedIn) {
        $update = $pdo->prepare('UPDATE participants SET check_in_at = NOW() WHERE id = :id');
        $update->execute([':id' => $participant['id']]);
        $participant['check_in_at'] = date('Y-m-d H:i:s');
    }

    $pdo->commit();

    respond(200, [
        'ok' => true,
        'already_checked_in' => $alreadyCheckedIn,
        'participant' => [
            'code' => $participant['participant_code'],
            'full_name' => $participant['full_name'],
            'position' => $participant['position'],
            'organization' => $participant['organization'],
            'format' => $participant['participation_format'],
            'check_in_at' => $participant['check_in_at']
        ]
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
