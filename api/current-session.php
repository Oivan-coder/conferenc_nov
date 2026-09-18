<?php

declare(strict_types=1);

require dirname(__DIR__) . '/qa/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow,noarchive', true);
header('Access-Control-Allow-Origin: https://rclsmo.ru');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = qa_pdo();
    qa_ensure_schema($pdo);
    $session = qa_current_session($pdo);

    echo json_encode([
        'ok' => true,
        'session' => $session ? [
            'speaker_name' => (string)$session['speaker_name'],
            'title' => (string)$session['title'],
        ] : null,
        'server_time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
