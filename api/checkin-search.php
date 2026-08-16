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

$provided = trim((string)($_SERVER['HTTP_X_CHECKIN_KEY'] ?? ''));
$expected = is_readable(CHECKIN_KEY_PATH) ? trim((string)file_get_contents(CHECKIN_KEY_PATH)) : '';
if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
    respond(403, ['ok' => false, 'error' => 'forbidden']);
}

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2 || mb_strlen($q) > 100) respond(422, ['ok' => false, 'error' => 'invalid_query']);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $like = '%' . $q . '%';
    $stmt = $pdo->prepare('SELECT participant_code, full_name, position, organization, participation_format, check_in_at FROM participants WHERE full_name LIKE :q OR organization LIKE :q OR participant_code LIKE :q ORDER BY full_name LIMIT 15');
    $stmt->execute([':q' => $like]);

    respond(200, ['ok' => true, 'results' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
