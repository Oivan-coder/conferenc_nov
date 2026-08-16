<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = require '/home/c/cx314477/private/db.php';
    $pdo->query('SELECT 1');

    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'database' => true
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'database' => false
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
