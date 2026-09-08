<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const TEST_TOKEN = 'rclsmo-8f3a9c-20260908';
const LOCK_PATH = '/home/c/cx314477/public_html/.private/unisender_smoke_8f3a9c.lock';

if (!hash_equals(TEST_TOKEN, (string)($_GET['k'] ?? ''))) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

if (is_file(LOCK_PATH)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'already_sent']);
    exit;
}

require_once __DIR__ . '/smtp-mailer.php';

$subject = 'Тест доставки через Unisender Go — РЦЛСМО';
$body = '<!doctype html><html lang="ru"><body style="font-family:Arial,sans-serif;color:#173126;background:#f4f7f5;padding:24px;">'
    . '<div style="max-width:620px;margin:0 auto;background:#fff;border:1px solid #dfe8e2;border-radius:14px;padding:28px;">'
    . '<h2 style="margin-top:0;">Тест доставки РЦЛСМО</h2>'
    . '<p>Это тестовое письмо отправлено сайтом rclsmo.ru через SMTP Unisender Go.</p>'
    . '<p>Если вы получили его на Mail.ru без возврата 550, новый почтовый транспорт работает.</p>'
    . '</div></body></html>';

$sent = sendConfiguredMail('ge.vo.m@mail.ru', $subject, $body);
if ($sent) {
    @file_put_contents(LOCK_PATH, date(DATE_ATOM));
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(500);
echo json_encode(['ok' => false, 'error' => 'send_failed']);
