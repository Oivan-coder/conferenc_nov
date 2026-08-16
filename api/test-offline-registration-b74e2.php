<?php
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const TEST_MARKER = '/home/c/cx314477/public_html/.private/offline_registration_browser_test_done';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_file(TEST_MARKER)) {
        $error = 'Тест уже был выполнен.';
    } elseif (!is_readable(TEST_KEY_PATH)) {
        $error = 'Не найден тестовый ключ.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Введите корректный email.';
        } else {
            $payload = [
                'eventId' => 'forum-lab-innovations-2026-10-07',
                'lastName' => 'Тестов',
                'firstName' => 'Очный',
                'middleName' => 'Тестович',
                'position' => 'Врач КЛД',
                'organization' => 'Тестовая МО',
                'email' => $email,
                'phone' => '+79990000002',
                'participationFormat' => 'offline',
                'privacyConsent' => true,
                'confirmDuplicate' => true
            ];

            $key = trim((string)file_get_contents(TEST_KEY_PATH));
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nX-Registration-Test: {$key}\r\n",
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'ignore_errors' => true,
                    'timeout' => 25,
                ],
            ]);

            $raw = @file_get_contents('https://rclsmo.ru/api/register.php', false, $context);
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
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Тест очной регистрации и QR</title>
<style>
body{font-family:Arial,sans-serif;background:#f3f6f4;color:#173126;margin:0}.box{max-width:620px;margin:60px auto;background:#fff;padding:28px;border-radius:14px;border:1px solid #dfe8e2}input,button{font:inherit;padding:12px 14px;border-radius:8px}input{width:100%;box-sizing:border-box;border:1px solid #b9c9c0;margin:10px 0 14px}button{border:0;background:#214f3b;color:#fff;font-weight:700;cursor:pointer}.ok,.err{margin-top:18px;padding:14px;border-radius:8px}.ok{background:#eef7f1}.err{background:#fff0f0}.code{font-family:monospace;word-break:break-all}
</style>
</head>
<body><div class="box">
<h1>Тест очной регистрации + QR</h1>
<p>Введи свой внешний email. Система создаст тестового очного участника и отправит ему такое же письмо с QR-билетом, как реальному участнику.</p>
<?php if (!$result && !$error && !is_file(TEST_MARKER)): ?>
<form method="post"><input type="email" name="email" placeholder="your@email.ru" required><button type="submit">Отправить тестовый QR-билет</button></form>
<?php endif; ?>
<?php if ($result): ?><div class="ok"><strong>Готово.</strong><br>Код участника: <?= htmlspecialchars((string)$result['participant_code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><br>Письмо: <?= !empty($result['email_sent']) ? 'отправлено' : 'не отправлено' ?><?php if (!empty($result['ticket_url'])): ?><br><a href="<?= htmlspecialchars((string)$result['ticket_url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" target="_blank" rel="noopener">Открыть билет в браузере</a><?php endif; ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= $error ?></div><?php endif; ?>
</div></body></html>
