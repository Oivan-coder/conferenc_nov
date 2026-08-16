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
<title>Управление вопросами — Форум 2026</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Управление вопросами') ?>
<?php else: ?>
<div class="wrap">
<div class="topbar"><div><div class="brand">РЦЛС МО · служебный раздел</div></div><form method="post"><button class="btn ghost" name="qa_logout" value="1">Выйти</button></form></div>
<section class="hero"><div class="brand" style="color:#b9d2c7">Форум лабораторных инноваций 2026</div><h1>Чат и вопросы спикеру</h1><p>Участники ничего отдельно не открывают: чат и кнопка «Вопрос спикеру» находятся прямо на их персональной странице трансляции.</p></section>
<div class="portal">
<a href="/qa/moderator.php"><span class="pill">1</span><h2>Модератор</h2><p class="muted">Видит только вопросы из общего чата, выбирает текущего спикера и отправляет нужный вопрос на экран возле сцены.</p></a>
<a href="/qa/speaker.php" target="_blank"><span class="pill live">2</span><h2>Экран спикера</h2><p class="muted">Показывает только один выбранный модератором вопрос. Обновляется автоматически.</p></a>
</div>
<div class="card" style="margin-top:16px"><strong>Как это работает</strong><p class="muted">Участник пишет в общем чате. Если выбирает «Вопрос спикеру», сообщение остаётся в чате и одновременно попадает в очередь модератора. Модератор нажимает «Показать спикеру», после ответа — «Отвечен». В общем чате у вопроса появляется соответствующая отметка.</p></div>
</div>
<?php endif; ?>
</body>
</html>
