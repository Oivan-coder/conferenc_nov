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
$loginMode = (string)($_POST['login_mode'] ?? 'search');
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
                     WHERE event_id = :event_id
                       AND participant_code = :code
                       AND participation_format = 'offline'
                       AND registration_status = 'confirmed'
                     LIMIT 1"
                );
                $stmt->execute([':event_id' => EVENT_ID, ':code' => $codeValue]);
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
        } elseif ($loginMode === 'select') {
            $selectionToken = strtolower(trim((string)($_POST['selection_token'] ?? '')));
            $selections = $_SESSION['discussion_selections'] ?? [];
            $selection = is_array($selections) && isset($selections[$selectionToken]) && is_array($selections[$selectionToken])
                ? $selections[$selectionToken]
                : null;

            if (!preg_match('/^[a-f0-9]{32}$/', $selectionToken) || !$selection || (int)($selection['expires'] ?? 0) < time()) {
                $error = 'Поиск устарел. Найдите себя ещё раз.';
                $loginMode = 'search';
            } else {
                unset($_SESSION['discussion_selections'][$selectionToken]);
                $participantId = (int)($selection['participant_id'] ?? 0);
                $stmt = $pdo->prepare(
                    "SELECT id, participant_code, full_name, position, organization
                     FROM participants
                     WHERE event_id = :event_id
                       AND id = :id
                       AND participation_format = 'offline'
                       AND registration_status = 'confirmed'
                     LIMIT 1"
                );
                $stmt->execute([':event_id' => EVENT_ID, ':id' => $participantId]);
                $found = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                if (!$found) {
                    $error = 'Не удалось открыть участника. Повторите поиск.';
                    $loginMode = 'search';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['conference_attendee_id'] = (int)$found['id'];
                    header('Location: /discussion/');
                    exit;
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
                 WHERE event_id = :event_id
                   AND id = :id
                   AND participation_format = 'offline'
                   AND registration_status = 'confirmed'
                 LIMIT 1"
            );
            $stmt->execute([':event_id' => EVENT_ID, ':id' => $id]);
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
<link rel="stylesheet" href="/discussion/style.css?v=20260831-1">
</head>
<body>
<?php if (!$participant): ?>
<main class="access-shell">
    <section class="access-card">
        <div class="access-kicker">Форум лабораторных инноваций Московской области — 2026</div>
        <div class="access-icon" aria-hidden="true">?</div>
        <h1>Задайте вопрос спикеру</h1>
        <p class="access-lead">Найдите себя в списке участников — и сразу переходите в общий чат.</p>

        <div class="access-steps">
            <div><span>1</span><p>Начните вводить фамилию или имя.</p></div>
            <div><span>2</span><p>Выберите себя из списка.</p></div>
            <div><span>3</span><p>Пишите в чат или задайте вопрос спикеру.</p></div>
        </div>

        <?php if ($error): ?><div class="access-error"><?= h($error) ?></div><?php endif; ?>

        <div class="login-switch" role="tablist" aria-label="Способ входа">
            <button type="button" class="login-switch__btn <?= $loginMode !== 'code' ? 'active' : '' ?>" data-login-tab="search">Найти себя</button>
            <button type="button" class="login-switch__btn <?= $loginMode === 'code' ? 'active' : '' ?>" data-login-tab="code">По коду</button>
        </div>

        <div class="access-form <?= $loginMode === 'code' ? 'is-hidden' : '' ?>" data-login-panel="search">
            <label for="participant-search">Фамилия или имя</label>
            <div class="participant-search">
                <input id="participant-search" type="search" maxlength="80" placeholder="Например, Иванов" autocomplete="off" spellcheck="false">
                <div class="search-results" data-search-results hidden></div>
            </div>
            <p data-search-hint>Введите минимум 3 буквы. В списке покажем только ФИО, организацию и должность.</p>
            <form method="post" data-selection-form>
                <input type="hidden" name="login_mode" value="select">
                <input type="hidden" name="selection_token" value="" data-selection-token>
            </form>
        </div>

        <form method="post" class="access-form <?= $loginMode !== 'code' ? 'is-hidden' : '' ?>" data-login-panel="code" autocomplete="off">
            <input type="hidden" name="login_mode" value="code">
            <label for="participant_code">Код участника</label>
            <div class="code-row">
                <input id="participant_code" name="participant_code" type="text" inputmode="text" autocapitalize="characters" maxlength="10" placeholder="LE12AB34CD" value="<?= h($codeValue) ?>" required>
                <button type="submit">Войти</button>
            </div>
            <p>Запасной вариант, если не нашли себя по ФИО. Код указан в подтверждении регистрации и на бейдже.</p>
        </form>

        <div class="access-footer">7 октября 2026 · Дом Правительства Московской области</div>
    </section>
</main>
<script>
(() => {
    const buttons = [...document.querySelectorAll('[data-login-tab]')];
    const panels = [...document.querySelectorAll('[data-login-panel]')];
    const searchInput = document.querySelector('#participant-search');
    const resultsBox = document.querySelector('[data-search-results]');
    const hint = document.querySelector('[data-search-hint]');
    const selectionForm = document.querySelector('[data-selection-form]');
    const selectionToken = document.querySelector('[data-selection-token]');
    let searchTimer = null;
    let requestId = 0;

    buttons.forEach((button) => button.addEventListener('click', () => {
        const mode = button.dataset.loginTab;
        buttons.forEach((item) => item.classList.toggle('active', item === button));
        panels.forEach((panel) => panel.classList.toggle('is-hidden', panel.dataset.loginPanel !== mode));
        const field = document.querySelector(mode === 'search' ? '#participant-search' : '#participant_code');
        field?.focus();
    }));

    function clearResults(message = '') {
        resultsBox.innerHTML = '';
        resultsBox.hidden = true;
        if (message) hint.textContent = message;
    }

    function renderResults(items) {
        resultsBox.innerHTML = '';
        if (!items.length) {
            clearResults('Никого не нашли. Проверьте написание или попробуйте войти по коду.');
            return;
        }

        items.forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'search-result';

            const main = document.createElement('span');
            main.className = 'search-result__main';
            const name = document.createElement('strong');
            name.textContent = item.name || '';
            const organization = document.createElement('span');
            organization.textContent = item.organization || 'Организация не указана';
            main.append(name, organization);

            const position = document.createElement('span');
            position.className = 'search-result__position';
            position.textContent = item.position || '';

            button.append(main, position);
            button.addEventListener('click', () => {
                selectionToken.value = item.token || '';
                button.disabled = true;
                hint.textContent = 'Открываем обсуждение…';
                selectionForm.submit();
            });
            resultsBox.appendChild(button);
        });

        resultsBox.hidden = false;
        hint.textContent = 'Нажмите на своё имя, чтобы войти.';
    }

    async function searchParticipants(value) {
        const id = ++requestId;
        hint.textContent = 'Ищем участника…';
        try {
            const response = await fetch('/api/discussion-participant-search.php?q=' + encodeURIComponent(value), {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const data = await response.json().catch(() => null);
            if (id !== requestId) return;
            if (!response.ok || !data || !data.ok) throw new Error('search_failed');
            renderResults(Array.isArray(data.results) ? data.results : []);
        } catch (_) {
            if (id !== requestId) return;
            clearResults('Поиск временно недоступен. Попробуйте ещё раз или войдите по коду.');
        }
    }

    searchInput?.addEventListener('input', () => {
        const value = searchInput.value.trim();
        clearTimeout(searchTimer);
        if (value.length < 3) {
            requestId++;
            clearResults('Введите минимум 3 буквы. В списке покажем только ФИО, организацию и должность.');
            return;
        }
        searchTimer = setTimeout(() => searchParticipants(value), 250);
    });
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
