<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/moderator.php');

$error = '';
$currentSession = null;
$questions = [];
$counts = ['new' => 0, 'on_air' => 0, 'answered' => 0, 'hidden' => 0];

if ($authorized) {
    try {
        $pdo = qa_pdo();
        qa_ensure_schema($pdo);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            qa_verify_csrf();
            $action = (string)$_POST['action'];

            if ($action === 'set_session') {
                $speaker = trim((string)($_POST['speaker_name'] ?? ''));
                $title = trim((string)($_POST['session_title'] ?? ''));
                if ($speaker === '' || $title === '' || mb_strlen($speaker) > 255 || mb_strlen($title) > 255) {
                    throw new InvalidArgumentException('Укажите спикера и название доклада.');
                }
                $pdo->beginTransaction();
                $clear = $pdo->prepare('UPDATE conference_sessions SET is_current = 0 WHERE event_id = :event_id AND is_current = 1');
                $clear->execute([':event_id' => QA_EVENT_ID]);
                $insert = $pdo->prepare('INSERT INTO conference_sessions (event_id, title, speaker_name, is_current) VALUES (:event_id, :title, :speaker_name, 1)');
                $insert->execute([':event_id' => QA_EVENT_ID, ':title' => $title, ':speaker_name' => $speaker]);
                $pdo->commit();
                header('Location: /qa/moderator.php');
                exit;
            }

            if ($action === 'clear_session') {
                $stmt = $pdo->prepare('UPDATE conference_sessions SET is_current = 0 WHERE event_id = :event_id AND is_current = 1');
                $stmt->execute([':event_id' => QA_EVENT_ID]);
                header('Location: /qa/moderator.php');
                exit;
            }

            if (in_array($action, ['air', 'answered', 'hide', 'restore'], true)) {
                $id = filter_input(INPUT_POST, 'question_id', FILTER_VALIDATE_INT);
                if (!$id) throw new InvalidArgumentException('Некорректный номер вопроса.');

                if ($action === 'air') {
                    $pdo->beginTransaction();
                    $reset = $pdo->prepare("UPDATE conference_messages SET status = 'new' WHERE event_id = :event_id AND message_type = 'question' AND status = 'on_air'");
                    $reset->execute([':event_id' => QA_EVENT_ID]);
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'on_air', approved_at = COALESCE(approved_at, NOW()), on_air_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                    $pdo->commit();
                } elseif ($action === 'answered') {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'answered', answered_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                } elseif ($action === 'hide') {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'hidden', hidden_at = NOW() WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                } else {
                    $stmt = $pdo->prepare("UPDATE conference_messages SET status = 'new', hidden_at = NULL WHERE id = :id AND event_id = :event_id AND message_type = 'question'");
                    $stmt->execute([':id' => $id, ':event_id' => QA_EVENT_ID]);
                }

                header('Location: /qa/moderator.php');
                exit;
            }
        }

        $currentSession = qa_current_session($pdo);

        $countStmt = $pdo->prepare("SELECT status, COUNT(*) AS cnt FROM conference_messages WHERE event_id = :event_id AND message_type = 'question' GROUP BY status");
        $countStmt->execute([':event_id' => QA_EVENT_ID]);
        foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (array_key_exists($row['status'], $counts)) $counts[$row['status']] = (int)$row['cnt'];
        }

        $stmt = $pdo->prepare(
            "SELECT
                m.id,
                m.participant_name,
                m.organization,
                m.message_text AS question_text,
                m.status,
                m.created_at,
                m.reply_to_id,
                s.title AS session_title,
                s.speaker_name,
                (SELECT COUNT(*) FROM conference_message_votes v WHERE v.message_id = m.id) AS votes
             FROM conference_messages m
             LEFT JOIN conference_sessions s ON s.id = m.session_id
             WHERE m.event_id = :event_id
               AND m.message_type = 'question'
             ORDER BY
                CASE m.status WHEN 'on_air' THEN 0 WHEN 'new' THEN 1 WHEN 'answered' THEN 2 ELSE 3 END,
                votes DESC,
                m.created_at DESC
             LIMIT 200"
        );
        $stmt->execute([':event_id' => QA_EVENT_ID]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (InvalidArgumentException $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = $e->getMessage();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'Ошибка работы Q&A. Проверьте подключение к БД.';
    }
}

$statusLabels = ['new' => 'Новый', 'on_air' => 'У спикера', 'answered' => 'Отвечен', 'hidden' => 'Скрыт'];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Модератор — вопросы спикеру</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Вопросы · модератор') ?>
<?php else: ?>
<div class="wrap">
<div class="topbar"><div class="nav"><a href="/qa/">← управление</a><a href="/qa/speaker.php" target="_blank">Экран спикера ↗</a></div><form method="post"><button class="btn ghost" name="qa_logout" value="1">Выйти</button></form></div>
<section class="hero"><div class="brand" style="color:#b9d2c7">Панель модератора</div><h1>Вопросы из общего чата</h1><p>Участник пишет вопрос на своей странице трансляции. Здесь он появляется автоматически. Нажмите «Показать спикеру» — вопрос сразу появится на отдельном экране возле сцены.</p></section>
<?php if ($error): ?><div class="notice"><?= qa_h($error) ?></div><?php endif; ?>
<div class="stats" style="margin-bottom:16px"><div class="stat"><span class="brand">Новые</span><b><?= $counts['new'] ?></b></div><div class="stat"><span class="brand">У спикера</span><b><?= $counts['on_air'] ?></b></div><div class="stat"><span class="brand">Отвечены</span><b><?= $counts['answered'] ?></b></div><div class="stat"><span class="brand">Скрыты</span><b><?= $counts['hidden'] ?></b></div></div>
<div class="grid">
<section class="card two">
<div class="brand">Текущий доклад</div>
<?php if ($currentSession): ?><div class="session" style="margin-top:8px"><div><strong><?= qa_h($currentSession['speaker_name']) ?></strong><p class="muted" style="margin:5px 0 0"><?= qa_h($currentSession['title']) ?></p></div><span class="pill live">● текущий</span></div><?php else: ?><h3 style="margin:8px 0">Не выбран</h3><p class="muted">Вопросы будут сохраняться, но без привязки к конкретному докладу.</p><?php endif; ?>
<?php if ($currentSession): ?><form method="post" style="margin-top:14px"><input type="hidden" name="csrf" value="<?= qa_h(qa_csrf_token()) ?>"><button class="btn ghost" name="action" value="clear_session">Завершить текущий доклад</button></form><?php endif; ?>
</section>
<section class="card two">
<h3>Переключить спикера</h3>
<form method="post" class="form-grid" autocomplete="off">
<input type="hidden" name="csrf" value="<?= qa_h(qa_csrf_token()) ?>">
<div class="field"><label for="speaker_name">Спикер</label><input id="speaker_name" name="speaker_name" maxlength="255" placeholder="Иванов И.И." required></div>
<div class="field"><label for="session_title">Доклад</label><input id="session_title" name="session_title" maxlength="255" placeholder="Название доклада" required></div>
<div class="field wide"><button class="btn" name="action" value="set_session">Сделать текущим</button></div>
</form>
</section>
<section class="card">
<div class="session"><div><div class="brand">Очередь</div><h2 style="margin:6px 0 0">Вопросы спикеру</h2></div><span class="pill">обновление 3 сек</span></div>
<?php if (!$questions): ?><p class="muted">Пока вопросов нет. Они появятся здесь, когда участник выберет «Вопрос спикеру» в общем чате.</p><?php endif; ?>
<?php foreach ($questions as $q): ?>
<div class="question <?= $q['status'] === 'on_air' ? 'onair' : '' ?>">
<div class="question-head"><div><span class="pill <?= qa_h($q['status']) ?>"><?= qa_h($statusLabels[$q['status']] ?? $q['status']) ?></span><?php if ($q['speaker_name']): ?> <span class="pill"><?= qa_h($q['speaker_name']) ?></span><?php endif; ?><?php if ((int)$q['votes'] > 0): ?> <span class="pill">👍 <?= (int)$q['votes'] ?></span><?php endif; ?></div><strong>#<?= (int)$q['id'] ?></strong></div>
<div class="question-text"><?= qa_h($q['question_text']) ?></div>
<div class="question-meta"><?= qa_h($q['participant_name']) ?> · <?= qa_h($q['organization']) ?> · <?= qa_h($q['created_at']) ?><?php if ($q['session_title']): ?><br><?= qa_h($q['session_title']) ?><?php endif; ?></div>
<form method="post" class="actions" style="margin-top:13px">
<input type="hidden" name="csrf" value="<?= qa_h(qa_csrf_token()) ?>"><input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>">
<?php if ($q['status'] === 'new'): ?><button class="btn" name="action" value="air">Показать спикеру</button><?php endif; ?>
<?php if ($q['status'] === 'on_air'): ?><button class="btn" name="action" value="answered">✓ Отвечен</button><?php endif; ?>
<?php if ($q['status'] !== 'hidden' && $q['status'] !== 'answered'): ?><button class="btn ghost" name="action" value="hide">Скрыть</button><?php elseif ($q['status'] === 'hidden'): ?><button class="btn ghost" name="action" value="restore">Вернуть</button><?php endif; ?>
</form>
</div>
<?php endforeach; ?>
</section>
</div>
</div>
<script>
let qaBusy=false;document.addEventListener('submit',()=>{qaBusy=true});setInterval(()=>{const a=document.activeElement;if(qaBusy)return;if(a&&['INPUT','TEXTAREA','SELECT'].includes(a.tagName))return;location.reload();},3000);
</script>
<?php endif; ?>
</body>
</html>
