<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

const SMTP_TEST_MARKER = '/home/c/cx314477/public_html/.private/smtp_test_done';

if (is_file(SMTP_TEST_MARKER)) {
    echo json_encode(['ok' => true, 'already_tested' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/smtp-mailer.php';

$body = '<!doctype html><html lang="ru"><body style="font-family:Arial,sans-serif;color:#173126;">'
    . '<h2>SMTP работает</h2>'
    . '<p>Это тестовое письмо отправлено сайтом rclsmo.ru через авторизованный SMTP Timeweb.</p>'
    . '<p>Если вы получили это письмо, почтовый контур регистрации настроен корректно.</p>'
    . '</body></html>';

$sent = sendConfiguredMail('info@rclsmo.ru', 'SMTP-тест rclsmo.ru', $body);

if ($sent) {
    @file_put_contents(SMTP_TEST_MARKER, date('c'));
    echo json_encode(['ok' => true, 'sent' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

http_response_code(503);
echo json_encode(['ok' => false, 'sent' => false, 'error' => 'smtp_send_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
