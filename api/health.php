<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configPath = '/home/c/cx314477/private/db.php';
$errorLogPath = '/home/c/cx314477/private/health_error.log';

$diagnostics = [
    'ok' => false,
    'database' => false,
    'config_exists' => file_exists($configPath),
    'config_readable' => is_readable($configPath),
    'php_sapi' => PHP_SAPI,
    'php_version' => PHP_VERSION,
    'pdo_loaded' => class_exists('PDO'),
    'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
    'mysqli_loaded' => extension_loaded('mysqli')
];

try {
    $pdo = require $configPath;
    $pdo->query('SELECT 1');
    $diagnostics['ok'] = true;
    $diagnostics['database'] = true;
    @unlink($errorLogPath);
    http_response_code(200);
} catch (Throwable $e) {
    $diagnostics['error_type'] = get_class($e);
    $safeMessage = get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    @file_put_contents($errorLogPath, $safeMessage, LOCK_EX);
    http_response_code(500);
}

echo json_encode($diagnostics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
