<?php
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

const SMTP_EXTERNAL_TEST_MARKER = '/home/c/cx314477/public_html/.private/smtp_external_test_done';
require_once __DIR__ . '/smtp-mailer.php';

$message = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_file(SMTP_EXTERNAL_TEST_MARKER)) {
        $message = 'Тест уже был выполнен.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Введите корректный email.';
        } else {
            $body = '<!doctype html><html lang="ru"><body style="font-family:Arial,sans-serif;color:#173126;">'
                . '<h2>SMTP-тест rclsmo.ru</h2>'
                . '<p>Это тестовое письмо отправлено через авторизованный SMTP Timeweb.</p>'
                . '<p>Если оно пришло на внешний адрес, отправка с сайта работает корректно.</p>'
                . '</body></html>';
            $sent = sendConfiguredMail($email, 'SMTP-тест rclsmo.ru на внешний адрес', $body);
            if ($sent) {
                @file_put_contents(SMTP_EXTERNAL_TEST_MARKER, date('c') . ' ' . hash('sha256', strtolower($email)));
                $message = 'SMTP принял письмо. Проверьте этот ящик и папку «Спам».';
            } else {
                $message = 'SMTP-отправка не удалась.';
            }
        }
    }
}
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>SMTP тест</title><style>body{font-family:Arial,sans-serif;background:#f3f6f4;color:#173126;margin:0}.box{max-width:520px;margin:60px auto;background:#fff;padding:28px;border-radius:14px;border:1px solid #dfe8e2}input,button{font:inherit;padding:12px 14px;border-radius:8px}input{width:100%;box-sizing:border-box;border:1px solid #b9c9c0;margin:10px 0 14px}button{border:0;background:#214f3b;color:#fff;font-weight:700;cursor:pointer}.msg{margin-top:18px;padding:12px;background:#f1f6f3;border-radius:8px}</style></head><body><div class="box"><h1>Проверка SMTP</h1><p>Введи любой внешний адрес, куда ты можешь сразу посмотреть — Gmail, Яндекс, Mail.ru и т.п.</p><?php if (!is_file(SMTP_EXTERNAL_TEST_MARKER)): ?><form method="post"><input type="email" name="email" placeholder="your@email.ru" required><button type="submit">Отправить тест</button></form><?php endif; ?><?php if ($message !== ''): ?><div class="msg"><?= htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?></div></body></html>