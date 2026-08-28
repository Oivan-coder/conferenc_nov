<?php
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const PREVIEW_TOKEN_HASH = '6fbe6025563f098ca8756103aa6cb93f4ac2c5bbb5f769e42aff0de2de2c14b9';
const TEST_KEY_PATH_PREVIEW = '/home/c/cx314477/public_html/.private/registration_test_key';

$previewToken = trim((string)($_GET['key'] ?? ''));
if ($previewToken === '' || !hash_equals(PREVIEW_TOKEN_HASH, hash('sha256', $previewToken))) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!is_readable(TEST_KEY_PATH_PREVIEW)) {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'preview_unavailable'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$testKey = trim((string)file_get_contents(TEST_KEY_PATH_PREVIEW));
if ($testKey === '') {
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'preview_unavailable'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$_SERVER['HTTP_X_REGISTRATION_TEST'] = $testKey;
require __DIR__ . '/register.php';
