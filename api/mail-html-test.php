<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

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

$subjectText = 'Подтверждение регистрации — тест HTML';
$subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

$htmlBody = '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,sans-serif;color:#173126;">'
    . '<div style="max-width:620px;margin:0 auto;padding:28px 16px;">'
    . '<div style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dfe8e2;">'
    . '<div style="background:#214f3b;color:#ffffff;padding:28px 30px;">'
    . '<div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">Референс-центр лабораторной службы Московской области</div>'
    . '<h1 style="font-size:24px;line-height:1.25;margin:10px 0 0;">Регистрация подтверждена</h1>'
    . '</div>'
    . '<div style="padding:30px;">'
    . '<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">Здравствуйте, <strong>Тестов Тест Тестович</strong>.</p>'
    . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Вы зарегистрированы на Форум лабораторных инноваций 2026.</p>'
    . '<div style="background:#f1f6f3;border-radius:12px;padding:20px;margin:0 0 22px;">'
    . '<div style="font-size:13px;color:#5d7468;margin-bottom:6px;">Код участника</div>'
    . '<div style="font-size:24px;font-weight:700;letter-spacing:.06em;color:#214f3b;">LETEST2026</div>'
    . '</div>'
    . '<p style="font-size:15px;line-height:1.7;margin:0 0 8px;"><strong>Дата:</strong> 7 октября 2026 года</p>'
    . '<p style="font-size:15px;line-height:1.7;margin:0 0 8px;"><strong>Формат:</strong> Очное участие</p>'
    . '<p style="font-size:15px;line-height:1.7;margin:0;"><strong>Место:</strong> Дом Правительства Московской области, Красногорск</p>'
    . '</div>'
    . '<div style="padding:18px 30px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">'
    . 'По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a>'
    . '</div>'
    . '</div></div></body></html>';

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: info@rclsmo.ru',
    'Reply-To: info@rclsmo.ru'
];

$sent = mail('info@rclsmo.ru', $subject, $htmlBody, implode("\r\n", $headers), '-finfo@rclsmo.ru');
respond($sent ? 200 : 500, ['ok' => $sent]);
