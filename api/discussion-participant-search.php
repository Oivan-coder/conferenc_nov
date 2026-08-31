<?php

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

$q = trim((string)($_GET['q'] ?? ''));
$q = (string)preg_replace('/\s+/u', ' ', $q);

if (mb_strlen($q, 'UTF-8') < 3) {
    respond(200, ['ok' => true, 'results' => []]);
}
if (mb_strlen($q, 'UTF-8') > 80) {
    respond(422, ['ok' => false, 'error' => 'query_too_long']);
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare(
        "SELECT id, full_name, organization, position
         FROM participants
         WHERE event_id = :event_id
           AND participation_format = 'offline'
           AND registration_status = 'confirmed'
           AND full_name LIKE :query
         ORDER BY full_name
         LIMIT 8"
    );
    $stmt->execute([
        ':event_id' => EVENT_ID,
        ':query' => '%' . $q . '%',
    ]);

    if (!isset($_SESSION['discussion_selections']) || !is_array($_SESSION['discussion_selections'])) {
        $_SESSION['discussion_selections'] = [];
    }

    $now = time();
    foreach ($_SESSION['discussion_selections'] as $token => $entry) {
        if (!is_array($entry) || (int)($entry['expires'] ?? 0) < $now) {
            unset($_SESSION['discussion_selections'][$token]);
        }
    }

    $results = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $token = bin2hex(random_bytes(16));
        $_SESSION['discussion_selections'][$token] = [
            'participant_id' => (int)$row['id'],
            'expires' => $now + 900,
        ];
        $results[] = [
            'token' => $token,
            'name' => (string)$row['full_name'],
            'organization' => (string)$row['organization'],
            'position' => (string)$row['position'],
        ];
    }

    respond(200, ['ok' => true, 'results' => $results]);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
