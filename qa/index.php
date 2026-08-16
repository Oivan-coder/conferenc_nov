<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Q&A — тестовый контур</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Тестовый контур Q&A') ?>
<?php else: ?>
<div class="wrap">
<div class="topbar"><div><div class="brand">РЦЛС МО · скрытый тестовый контур</div></div><form method="post"><button class="btn ghost" name="qa_logout" value="1">Выйти</button></form></div>
<section class="hero"><div class="brand" style="color:#b9d2c7">Форум лабораторных инноваций 2026</div><h1>Q&A: прототип</h1><p>Три независимых экрана: участник задаёт вопрос, модератор выбирает его, спикер видит выбранный вопрос автоматически.</p></section>
<div class="portal">
<a href="/qa/participant.php"><span class="pill">1</span><h2>Участник</h2><p class="muted">Задать тестовый вопрос от имени участника и организации.</p></a>
<a href="/qa/moderator.php"><span class="pill">2</span><h2>Модератор</h2><p class="muted">Выбрать текущего спикера, одобрить вопрос и вывести его спикеру.</p></a>
<a href="/qa/speaker.php" target="_blank"><span class="pill live">3</span><h2>Экран спикера</h2><p class="muted">Полноэкранный режим. Вопрос меняется без перезагрузки каждые 2 секунды.</p></a>
</div>
<div class="card" style="margin-top:16px"><strong>Как проверить</strong><p class="muted">Откройте «Экран спикера» в отдельной вкладке. Затем в «Модераторе» задайте текущий доклад. В «Участнике» отправьте вопрос. Вернитесь в «Модератор» и нажмите «Показать спикеру».</p></div>
</div>
<?php endif; ?>
</body>
</html>
