<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configPath = '/home/c/cx314477/private/db.php';

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
    $diagnostics['config_return_type'] = get_debug_type($pdo);
    $diagnostics['config_is_pdo'] = $pdo instanceof PDO;

    if (!$diagnostics['config_is_pdo']) {
        throw new RuntimeException('DB config did not return PDO');
    }

    $pdo->query('SELECT 1');
    $diagnostics['ok'] = true;
    $diagnostics['database'] = true;
    http_response_code(200);
} catch (Throwable $e) {
    $diagnostics['error_type'] = get_class($e);

    $message = $e->getMessage();
    if (str_contains($message, 'Access denied for user')) {
        $diagnostics['error_code'] = 'database_auth_failed';
    } elseif (str_contains($message, 'could not find driver')) {
        $diagnostics['error_code'] = 'pdo_driver_missing';
    } elseif (str_contains($message, 'Failed opening required')) {
        $diagnostics['error_code'] = 'config_require_failed';
    } elseif (str_contains($message, 'DB config did not return PDO')) {
        $diagnostics['error_code'] = 'config_return_invalid';
    } elseif (str_contains($message, 'Call to a member function')) {
        $diagnostics['error_code'] = 'config_return_invalid';
    } else {
        $diagnostics['error_code'] = 'other';
    }

    http_response_code(500);
}

echo json_encode($diagnostics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
