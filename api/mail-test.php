<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$provided = trim((string)($_SERVER['HTTP_X_REGISTRATION_TEST'] ?? ''));
$expected = is_readable(TEST_KEY_PATH) ? trim((string)file_get_contents(TEST_KEY_PATH)) : '';
if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
    respond(403, ['ok' => false, 'error' => 'forbidden']);
}

$headers = "From: info@rclsmo.ru\r\nReply-To: info@rclsmo.ru\r\n";
$sent = mail('info@rclsmo.ru', 'Web PHP mail test', 'Plain text test from Apache PHP', $headers, '-finfo@rclsmo.ru');
respond($sent ? 200 : 500, ['ok' => $sent]);
