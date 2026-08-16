<?php
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const TEST_MARKER = '/home/c/cx314477/public_html/.private/online_registration_browser_test_done';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_file(TEST_MARKER)) {
        $error = 'Тест уже выполнен.';
    } elseif (!is_readable(TEST_KEY_PATH)) {
        $error = 'Не найден тестовый ключ.';
    } else {
        $key = trim((string)file_get_contents(TEST_KEY_PATH));
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nX-Registration-Test: {$key}\r\n",
                'content' => '{}',
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);
        $raw = @file_get_contents('https://rclsmo.ru/api/register.php?mode=online', false, $context);
        if ($raw === false) {
            $error = 'Не удалось вызвать регистрацию.';
        } else {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || empty($decoded['ok'])) {
                $error = 'Регистрация вернула ошибку: ' . htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } else {
                @file_put_contents(TEST_MARKER, date('c'));
                $result = $decoded;
            }
        }
    }
}
?><!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Тест онлайн-регистрации</title><style>body{font-family:Arial,sans-serif;background:#f3f6f4;color:#173126;margin:0}.box{max-width:620px;margin:60px auto;background:#fff;padding:28px;border-radius:14px;border:1px solid #dfe8e2}button{font:inherit;border:0;background:#214f3b;color:#fff;font-weight:700;padding:13px 18px;border-radius:8px;cursor:pointer}.ok,.err{margin-top:18px;padding:14px;border-radius:8px}.ok{background:#eef7f1}.err{background:#fff0f0}.code{font-family:monospace;word-break:break-all}</style></head><body><div class="box"><h1>Тест онлайн-регистрации</h1><p>Нажми кнопку. Система создаст одного тестового онлайн-участника и отправит письмо на info@rclsmo.ru через SMTP.</p><?php if (!$result && !$error && !is_file(TEST_MARKER)): ?><form method="post"><button type="submit">Создать тестовую онлайн-регистрацию</button></form><?php endif; ?><?php if ($result): ?><div class="ok"><strong>Готово.</strong><br>Код: <?= htmlspecialchars((string)$result['participant_code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><br>Письмо: <?= !empty($result['email_sent']) ? 'отправлено' : 'не отправлено' ?><br><span class="code"><?= htmlspecialchars((string)($result['live_url'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></div><?php endif; ?><?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?></div></body></html>
