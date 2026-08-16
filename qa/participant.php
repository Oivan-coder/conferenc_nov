<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/participant.php');

$success = '';
$error = '';
$currentSession = null;

if ($authorized) {
    try {
        $pdo = qa_pdo();
        qa_ensure_schema($pdo);
        $currentSession = qa_current_session($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_question'])) {
            qa_verify_csrf();
            $participantName = trim((string)($_POST['participant_name'] ?? ''));
            $organization = trim((string)($_POST['organization'] ?? ''));
            $question = trim((string)($_POST['question_text'] ?? ''));

            if ($participantName === '' || mb_strlen($participantName) > 255) {
                throw new InvalidArgumentException('Укажите имя участника.');
            }
            if ($organization === '' || mb_strlen($organization) > 255) {
                throw new InvalidArgumentException('Укажите организацию.');
            }
            if (mb_strlen($question) < 3 || mb_strlen($question) > 2000) {
                throw new InvalidArgumentException('Вопрос должен содержать от 3 до 2000 символов.');
            }

            $currentSession = qa_current_session($pdo);
            $stmt = $pdo->prepare('INSERT INTO conference_questions (event_id, participant_name, organization, session_id, question_text, status) VALUES (:event_id, :participant_name, :organization, :session_id, :question_text, \'new\')');
            $stmt->execute([
                ':event_id' => QA_EVENT_ID,
                ':participant_name' => $participantName,
                ':organization' => $organization,
                ':session_id' => $currentSession['id'] ?? null,
                ':question_text' => $question,
            ]);
            $success = 'Вопрос отправлен модератору.';
        }
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    } catch (Throwable $e) {
        $error = 'Не удалось сохранить вопрос. Проверьте подключение к БД.';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Участник — Q&A</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Q&A · участник') ?>
<?php else: ?>
<div class="wrap">
<div class="topbar"><div class="nav"><a href="/qa/">← Q&A</a><a href="/qa/moderator.php">Модератор</a><a href="/qa/speaker.php" target="_blank">Экран спикера</a></div><form method="post"><button class="btn ghost" name="qa_logout" value="1">Выйти</button></form></div>
<section class="hero"><div class="brand" style="color:#b9d2c7">Тестовый интерфейс участника</div><h1>Задать вопрос спикеру</h1><p>В рабочей версии имя и организация будут определяться автоматически по персональной ссылке участника.</p></section>
<div class="grid">
<section class="card two">
<div class="session"><div><div class="brand">Сейчас выступает</div><?php if ($currentSession): ?><strong><?= qa_h($currentSession['speaker_name']) ?></strong><p class="muted" style="margin:5px 0 0"><?= qa_h($currentSession['title']) ?></p><?php else: ?><strong>Доклад ещё не выбран</strong><p class="muted" style="margin:5px 0 0">Модератор может назначить текущий доклад.</p><?php endif; ?></div><span class="pill <?= $currentSession ? 'live' : '' ?>"><?= $currentSession ? '● эфир' : 'ожидание' ?></span></div>
</section>
<section class="card two">
<?php if ($success): ?><div class="ok"><?= qa_h($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice"><?= qa_h($error) ?></div><?php endif; ?>
<form method="post" class="form-grid" autocomplete="off">
<input type="hidden" name="csrf" value="<?= qa_h(qa_csrf_token()) ?>">
<div class="field"><label for="participant_name">Участник</label><input id="participant_name" name="participant_name" maxlength="255" value="Тестовый участник" required></div>
<div class="field"><label for="organization">Организация</label><input id="organization" name="organization" maxlength="255" value="Тестовая МО" required></div>
<div class="field wide"><label for="question_text">Ваш вопрос</label><textarea id="question_text" name="question_text" maxlength="2000" placeholder="Например: как организована маршрутизация биоматериала между площадками?" required></textarea></div>
<div class="field wide"><button class="btn" type="submit" name="submit_question" value="1">Отправить модератору</button></div>
</form>
</section>
</div>
</div>
<?php endif; ?>
</body>
</html>
