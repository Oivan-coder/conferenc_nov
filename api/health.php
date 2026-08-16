<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configPath = '/home/c/cx314477/private/db.php';

$result = [
    'ok' => false,
    'database' => false,
    'config_exists' => file_exists($configPath),
    'config_readable' => is_readable($configPath),
    'php_sapi' => PHP_SAPI
];

try {
    $pdo = require $configPath;
    $pdo->query('SELECT 1');

    $result['ok'] = true;
    $result['database'] = true;
    http_response_code(200);
} catch (Throwable $e) {
    $result['error_type'] = get_class($e);
    http_response_code(500);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
