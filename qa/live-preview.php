<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/live-preview.php');

if ($authorized) {
    try {
        $pdo = qa_pdo();
        $stmt = $pdo->prepare("SELECT online_token FROM participants WHERE participation_format = 'online' AND organization = 'Тестовая МО' AND online_token IS NOT NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $token = (string)($stmt->fetchColumn() ?: '');
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            header('Location: /live/?t=' . $token);
            exit;
        }
        $error = 'Тестовый онлайн-участник не найден.';
    } catch (Throwable $e) {
        $error = 'Не удалось открыть тестовую персональную страницу.';
    }
}
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Открыть тестовую трансляцию</title><link rel="stylesheet" href="/qa/style.css?v=20260816-1"></head><body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Открыть тестовую трансляцию') ?>
<?php else: ?>
<div class="login-card"><div class="brand">Форум лабораторных инноваций Московской области — 2026</div><h1>Тестовая трансляция</h1><div class="notice"><?= qa_h($error ?? 'Не удалось открыть страницу.') ?></div><a class="btn" href="/qa/">Вернуться</a></div>
<?php endif; ?>
</body></html>
