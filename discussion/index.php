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

function normalize_name(string $value): string
{
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = str_replace('ё', 'е', $value);
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    return trim($value);
}

function name_matches(string $storedName, string $inputName): bool
{
    $stored = normalize_name($storedName);
    $input = normalize_name($inputName);
    if ($stored === '' || $input === '') return false;
    if ($stored === $input) return true;

    $storedParts = explode(' ', $stored);
    $inputParts = explode(' ', $input);

    if (count($inputParts) === 2 && count($storedParts) >= 2) {
        return $inputParts[0] === $storedParts[0] && $inputParts[1] === $storedParts[1];
    }

    return false;
}

if (isset($_POST['logout'])) {
    unset($_SESSION['conference_attendee_id']);
    session_regenerate_id(true);
    header('Location: /discussion/');
    exit;
}

$error = '';
$participant = null;
$loginMode = (string)($_POST['login_mode'] ?? 'name');
$nameValue = trim((string)($_POST['full_name'] ?? ''));
$codeValue = strtoupper(trim((string)($_POST['participant_code'] ?? '')));

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_mode'])) {
        if ($loginMode === 'code') {
            if (!preg_match('/^LE[A-F0-9]{8}$/', $codeValue)) {
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
                $stmt->execute([':code' => $codeValue]);
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
        } else {
            $normalized = normalize_name($nameValue);
            if ($normalized === '' || mb_strlen($normalized, 'UTF-8') < 5 || count(explode(' ', $normalized)) < 2) {
                $error = 'Введите фамилию и имя, как при регистрации.';
            } else {
                $stmt = $pdo->prepare(
                    "SELECT id, participant_code, full_name, position, organization
                     FROM participants
                     WHERE participation_format = 'offline'
                       AND registration_status = 'confirmed'
                     ORDER BY id"
                );
                $stmt->execute();
                $matches = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (name_matches((string)$row['full_name'], $nameValue)) $matches[] = $row;
                }

                if (count($matches) === 1) {
                    session_regenerate_id(true);
                    $_SESSION['conference_attendee_id'] = (int)$matches[0]['id'];
                    header('Location: /discussion/');
                    exit;
                }
                if (count($matches) > 1) {
                    $error = 'Нашли несколько участников с таким именем. В этом случае войдите по коду с бейджа.';
                    $loginMode = 'code';
                } else {
                    $error = 'Не нашли такую очную регистрацию. Проверьте написание ФИО или войдите по коду.';
                }
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
<link rel="stylesheet" href="/discussion/style.css?v=20260816-2">
</head>
<body>
<?php if (!$participant): ?>
<main class="access-shell">
    <section class="access-card">
        <div class="access-kicker">Форум лабораторных инноваций Московской области — 2026</div>
        <div class="access-icon" aria-hidden="true">?</div>
        <h1>Задайте вопрос спикеру</h1>
        <p class="access-lead">Или присоединитесь к общему обсуждению прямо со своего телефона.</p>

        <div class="access-steps">
            <div><span>1</span><p>Найдите себя по фамилии и имени. Код с бейджа — запасной вариант.</p></div>
            <div><span>2</span><p>Пишите в общий чат или выберите «Вопрос спикеру».</p></div>
            <div><span>3</span><p>Вопрос попадёт модератору и останется виден в обсуждении.</p></div>
        </div>

        <?php if ($error): ?><div class="access-error"><?= h($error) ?></div><?php endif; ?>

        <div class="login-switch" role="tablist" aria-label="Способ входа">
            <button type="button" class="login-switch__btn <?= $loginMode !== 'code' ? 'active' : '' ?>" data-login-tab="name">По ФИО</button>
            <button type="button" class="login-switch__btn <?= $loginMode === 'code' ? 'active' : '' ?>" data-login-tab="code">По коду</button>
        </div>

        <form method="post" class="access-form <?= $loginMode === 'code' ? 'is-hidden' : '' ?>" data-login-panel="name" autocomplete="off">
            <input type="hidden" name="login_mode" value="name">
            <label for="full_name">Фамилия и имя</label>
            <div class="code-row name-row">
                <input id="full_name" name="full_name" type="text" maxlength="255" placeholder="Иванов Иван" value="<?= h($nameValue) ?>" autocomplete="name" required>
                <button type="submit">Войти</button>
            </div>
            <p>Отчество можно не вводить. Если найдутся однофамильцы, система попросит использовать код с бейджа.</p>
        </form>

        <form method="post" class="access-form <?= $loginMode !== 'code' ? 'is-hidden' : '' ?>" data-login-panel="code" autocomplete="off">
            <input type="hidden" name="login_mode" value="code">
            <label for="participant_code">Код участника</label>
            <div class="code-row">
                <input id="participant_code" name="participant_code" type="text" inputmode="text" autocapitalize="characters" maxlength="10" placeholder="LE12AB34CD" value="<?= h($codeValue) ?>" required>
                <button type="submit">Войти</button>
            </div>
            <p>Код указан в подтверждении регистрации и на бейдже. Это запасной способ входа.</p>
        </form>

        <div class="access-footer">7 октября 2026 · Дом Правительства Московской области</div>
    </section>
</main>
<script>
(() => {
    const buttons = [...document.querySelectorAll('[data-login-tab]')];
    const panels = [...document.querySelectorAll('[data-login-panel]')];
    buttons.forEach((button) => button.addEventListener('click', () => {
        const mode = button.dataset.loginTab;
        buttons.forEach((item) => item.classList.toggle('active', item === button));
        panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.loginPanel !== mode));
        const field = document.querySelector(mode === 'name' ? '#full_name' : '#participant_code');
        field?.focus();
    }));
})();
</script>
<?php else: ?>
<div class="discussion-shell">
    <header class="discussion-topbar">
        <div>
            <div class="discussion-kicker">Форум лабораторных инноваций Московской области — 2026</div>
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
