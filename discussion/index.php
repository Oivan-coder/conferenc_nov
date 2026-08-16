<?php

declare(strict_types=1);

session_start();

header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (isset($_POST['logout'])) {
    unset($_SESSION['conference_attendee_id']);
    session_regenerate_id(true);
    header('Location: /discussion/');
    exit;
}

$error = '';
$participant = null;

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participant_code'])) {
        $code = strtoupper(trim((string)$_POST['participant_code']));
        if (!preg_match('/^LE[A-F0-9]{8}$/', $code)) {
            $error = 'Проверьте код участника. Он начинается с LE.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT id, participant_code, full_name, position, organization
                 FROM participants
                 WHERE participant_code = :code
                   AND participation_format = 'offline'
                   AND registration_status = 'confirmed'
                 LIMIT 1"
            );
            $stmt->execute([':code' => $code]);
            $found = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$found) {
                $error = 'Участник с таким кодом не найден среди очных регистраций.';
            } else {
                session_regenerate_id(true);
                $_SESSION['conference_attendee_id'] = (int)$found['id'];
                header('Location: /discussion/');
                exit;
            }
        }
    }

    if (isset($_SESSION['conference_attendee_id'])) {
        $id = filter_var($_SESSION['conference_attendee_id'], FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare(
                "SELECT id, participant_code, full_name, position, organization
                 FROM participants
                 WHERE id = :id
                   AND participation_format = 'offline'
                   AND registration_status = 'confirmed'
                 LIMIT 1"
            );
            $stmt->execute([':id' => $id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$participant) unset($_SESSION['conference_attendee_id']);
    }
} catch (Throwable $e) {
    $error = 'Сервис временно недоступен. Попробуйте ещё раз через минуту.';
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Обсуждение форума — 7 октября 2026</title>
<link rel="stylesheet" href="/discussion/style.css?v=20260816-1">
</head>
<body>
<?php if (!$participant): ?>
<main class="access-shell">
    <section class="access-card">
        <div class="access-kicker">Форум лабораторных инноваций 2026</div>
        <div class="access-icon" aria-hidden="true">?</div>
        <h1>Задайте вопрос спикеру</h1>
        <p class="access-lead">Или присоединитесь к общему обсуждению прямо со своего телефона.</p>

        <div class="access-steps">
            <div><span>1</span><p>Введите код участника с письма или бейджа.</p></div>
            <div><span>2</span><p>Пишите в общий чат или выберите «Вопрос спикеру».</p></div>
            <div><span>3</span><p>Вопрос попадёт модератору и останется виден в обсуждении.</p></div>
        </div>

        <?php if ($error): ?><div class="access-error"><?= h($error) ?></div><?php endif; ?>

        <form method="post" class="access-form" autocomplete="off">
            <label for="participant_code">Код участника</label>
            <div class="code-row">
                <input id="participant_code" name="participant_code" type="text" inputmode="text" autocapitalize="characters" maxlength="10" placeholder="LE12AB34CD" required autofocus>
                <button type="submit">Войти</button>
            </div>
            <p>Код указан в подтверждении регистрации. После входа повторно вводить ФИО и организацию не нужно.</p>
        </form>

        <div class="access-footer">7 октября 2026 · Дом Правительства Московской области</div>
    </section>
</main>
<?php else: ?>
<div class="discussion-shell">
    <header class="discussion-topbar">
        <div>
            <div class="discussion-kicker">Форум лабораторных инноваций 2026</div>
            <h1>Обсуждение</h1>
        </div>
        <form method="post"><button class="logout" name="logout" value="1" type="submit">Выйти</button></form>
    </header>

    <section class="participant-strip">
        <div class="avatar"><?= h(mb_substr((string)$participant['full_name'], 0, 1)) ?></div>
        <div><strong><?= h($participant['full_name']) ?></strong><span><?= h($participant['organization']) ?></span></div>
        <div class="hall-badge">В зале</div>
    </section>

    <section class="discussion-card" aria-labelledby="discussionTitle">
        <div class="discussion-head">
            <div><h2 id="discussionTitle">Чат и вопросы</h2><p>Обсуждайте доклад с коллегами или отправьте вопрос текущему спикеру.</p></div>
            <div class="session-now"><span>Сейчас</span><strong data-chat-session>Текущий спикер пока не выбран</strong></div>
        </div>

        <div class="chat-list" data-chat-list>
            <div class="chat-empty"><strong>Загружаем обсуждение…</strong><span>Сообщения появятся здесь.</span></div>
        </div>

        <form class="chat-compose" data-chat-form autocomplete="off">
            <div class="chat-reply-banner" data-reply-banner hidden><span data-reply-label></span><button type="button" data-reply-cancel>Отмена</button></div>
            <div class="chat-modes" role="radiogroup" aria-label="Тип сообщения">
                <label class="chat-mode"><input type="radio" name="messageType" value="chat" checked><span>💬 Сообщение</span></label>
                <label class="chat-mode question"><input type="radio" name="messageType" value="question"><span>🎤 Вопрос спикеру</span></label>
            </div>
            <textarea data-chat-input maxlength="1000" placeholder="Напишите сообщение участникам…" required></textarea>
            <div class="chat-compose-bottom">
                <div class="chat-status" data-chat-status>Вопрос спикеру также появится в общем обсуждении.</div>
                <button class="chat-submit" type="submit">Отправить</button>
            </div>
        </form>
    </section>
</div>

<script>
window.CONFERENCE_CHAT_CONFIG = {
    enabled: true,
    authMode: 'session',
    endpoint: '/api/conference-chat.php'
};
</script>
<script src="/js/modules/live-chat.js?v=20260816-2" defer></script>
<?php endif; ?>
</body>
</html>
