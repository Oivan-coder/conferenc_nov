<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow,noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const LIVE_EMBED_URL_PATH = '/home/c/cx314477/public_html/.private/live_embed_url';
const TEST_EMBED_URL = 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ';
const EVENT_START = '2026-10-07 07:00:00';
const EVENT_END = '2026-10-07 20:00:00';
const TEST_ORGANIZATION = 'Тестовая МО';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function eventWindowState(): string {
    $tz = new DateTimeZone('Europe/Moscow');
    $now = new DateTimeImmutable('now', $tz);
    $start = new DateTimeImmutable(EVENT_START, $tz);
    $end = new DateTimeImmutable(EVENT_END, $tz);
    if ($now < $start) return 'before';
    if ($now > $end) return 'after';
    return 'live';
}

function loadLiveEmbedUrl(): string {
    if (!is_readable(LIVE_EMBED_URL_PATH)) return '';
    $url = trim((string)file_get_contents(LIVE_EMBED_URL_PATH));
    if (!filter_var($url, FILTER_VALIDATE_URL)) return '';

    $parts = parse_url($url);
    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    $host = strtolower((string)($parts['host'] ?? ''));
    $allowedHosts = [
        'www.youtube.com',
        'www.youtube-nocookie.com',
        'rutube.ru',
        'vk.com',
        'vkvideo.ru',
    ];

    return $scheme === 'https' && in_array($host, $allowedHosts, true) ? $url : '';
}

$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = require DB_CONFIG_PATH;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare(
                'SELECT id, participant_code, full_name, position, organization, online_watch_seconds
                 FROM participants
                 WHERE online_token = :token AND participation_format = "online" AND registration_status = "confirmed"
                 LIMIT 1'
            );
            $stmt->execute([':token' => $token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) {
        $participant = null;
    }
}

if (!$participant) http_response_code(404);
$state = eventWindowState();
$liveEmbedUrl = loadLiveEmbedUrl();
$isTestParticipant = $participant && trim((string)$participant['organization']) === TEST_ORGANIZATION;
$usingTestEmbed = $isTestParticipant && $liveEmbedUrl === '';
if ($usingTestEmbed) $liveEmbedUrl = TEST_EMBED_URL;
$playerActive = $liveEmbedUrl !== '' && ($state === 'live' || $isTestParticipant);
$trackingActive = $participant && ($state === 'live' || $isTestParticipant);
$interactionActive = $participant && ($state === 'live' || $isTestParticipant);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="theme-color" content="#061426">
    <title><?= $participant ? 'Онлайн-трансляция — Форум лабораторных инноваций Московской области — 2026' : 'Ссылка недействительна' ?></title>
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png">
    <style>
        :root{--bg:#061426;--bg2:#04101e;--panel:rgba(12,35,57,.88);--panel-strong:#0b2a42;--line:rgba(102,222,241,.19);--line-strong:rgba(102,222,241,.32);--cyan:#66def1;--cyan2:#27c7de;--text:#edf8fb;--muted:#9cb6c5;--muted2:#7693a4;--violet:#7859df;--danger:#ff8f8f;--ok:#77e0a9;--shadow:0 24px 80px rgba(0,0,0,.28)}
        *{box-sizing:border-box}
        html{background:var(--bg2)}
        body{margin:0;min-height:100vh;background:radial-gradient(circle at 87% 4%,rgba(95,76,210,.2),transparent 31%),radial-gradient(circle at 8% 76%,rgba(31,198,219,.12),transparent 34%),linear-gradient(145deg,var(--bg),var(--bg2) 72%);color:var(--text);font-family:Inter,Arial,sans-serif}
        a{color:inherit}.wrap{width:min(1180px,calc(100% - 32px));margin:0 auto;padding:26px 0 54px}
        .brand-row{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.brand{display:inline-flex;align-items:center;gap:11px;color:var(--text);text-decoration:none}.brand img{width:38px;height:38px;object-fit:contain}.brand strong{font-size:17px;letter-spacing:.08em}.brand-actions{display:flex;gap:9px;flex-wrap:wrap}.mini-link{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:#cfe6ef;background:rgba(255,255,255,.025);font-size:13px;font-weight:750}.mini-link:hover{border-color:var(--line-strong);background:rgba(102,222,241,.06)}
        .top{position:relative;overflow:hidden;background:linear-gradient(135deg,rgba(14,48,74,.94),rgba(8,30,50,.95));border:1px solid var(--line);border-radius:24px;padding:30px 32px;margin-bottom:18px;box-shadow:var(--shadow)}.top:after{content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-95px;top:-130px;background:radial-gradient(circle,rgba(102,222,241,.18),rgba(120,89,223,.08) 48%,transparent 70%);pointer-events:none}.eyebrow{position:relative;z-index:1;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--cyan);font-weight:900}.top h1{position:relative;z-index:1;margin:9px 0 0;max-width:850px;font-size:clamp(28px,4vw,42px);line-height:1.08}.top-sub{position:relative;z-index:1;margin-top:12px;color:#b8d0dc;font-size:14px}.live-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--cyan);box-shadow:0 0 14px rgba(102,222,241,.8);margin-right:7px;vertical-align:1px}
        .grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px}.card{background:linear-gradient(145deg,rgba(13,38,61,.93),rgba(7,25,43,.96));border:1px solid var(--line);border-radius:20px;overflow:hidden;box-shadow:0 18px 48px rgba(0,0,0,.16)}
        .player{position:relative;aspect-ratio:16/9;background:radial-gradient(circle at 50% 45%,#102f49,#06111f 68%);display:flex;align-items:center;justify-content:center;color:var(--text);text-align:center;overflow:hidden}.player:before{content:"";position:absolute;inset:0;background:linear-gradient(rgba(102,222,241,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(102,222,241,.025) 1px,transparent 1px);background-size:38px 38px;mask-image:linear-gradient(to bottom,rgba(0,0,0,.7),transparent 92%);pointer-events:none}.player iframe{position:relative;z-index:2;width:100%;height:100%;border:0}.placeholder{position:relative;z-index:1;padding:36px;max-width:600px;line-height:1.65;color:#b9d0dc}.placeholder strong{display:block;margin-bottom:9px;color:var(--text);font-size:23px}.placeholder:before{content:"▶";display:grid;place-items:center;width:72px;height:72px;margin:0 auto 20px;border-radius:50%;border:1px solid var(--line-strong);background:radial-gradient(circle,rgba(102,222,241,.18),rgba(120,89,223,.08));color:var(--cyan);font-size:24px;padding-left:4px;box-shadow:0 0 35px rgba(54,195,219,.08)}
        .body{padding:22px 24px 24px;border-top:1px solid rgba(102,222,241,.08)}.body h2{margin:0 0 7px;font-size:22px}.muted{color:var(--muted);line-height:1.55}.small{font-size:12.5px;color:var(--muted2);line-height:1.55;margin-top:13px}
        .info{padding:24px;align-self:stretch}.info-head{margin-bottom:20px;padding-bottom:17px;border-bottom:1px solid var(--line)}.info h2{margin:10px 0 4px;font-size:20px}.info p{margin:10px 0;line-height:1.48;color:#c8dce5}.info p strong{color:var(--text)}.badge{display:inline-flex;align-items:center;gap:7px;padding:7px 11px;border-radius:999px;background:rgba(102,222,241,.1);border:1px solid rgba(102,222,241,.22);color:var(--cyan);font-size:12px;font-weight:850}.badge:before{content:"";width:6px;height:6px;border-radius:50%;background:var(--cyan);box-shadow:0 0 10px rgba(102,222,241,.8)}.info-actions{display:grid;gap:9px;margin-top:19px}.action-link{display:flex;justify-content:center;align-items:center;min-height:43px;padding:10px 13px;border-radius:11px;text-decoration:none;font-weight:800;font-size:13px;border:1px solid var(--line);background:rgba(255,255,255,.025)}.action-link.primary{background:var(--cyan);border-color:transparent;color:#042033}.action-link:hover{transform:translateY(-1px)}
        .test{margin-top:15px;padding:13px;border-radius:12px;background:rgba(255,203,88,.08);border:1px solid rgba(255,203,88,.23);color:#ecd38b;font-size:12.5px;line-height:1.55}.error{width:min(680px,calc(100% - 32px));margin:90px auto;background:linear-gradient(145deg,rgba(13,38,61,.94),rgba(7,25,43,.97));border:1px solid var(--line);border-radius:22px;padding:44px;text-align:center;box-shadow:var(--shadow)}.error h1{margin:0 0 12px;font-size:34px}.error .muted{margin-bottom:22px}.error a{display:inline-flex;padding:11px 16px;border-radius:11px;background:var(--cyan);color:#042033;text-decoration:none;font-weight:850}
        .discussion{margin-top:18px}.discussion-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding:23px 24px 18px;border-bottom:1px solid var(--line)}.discussion-head h2{margin:0 0 5px;font-size:23px}.discussion-head p{margin:0}.session-now{max-width:430px;text-align:right;font-size:12.5px;color:var(--muted);line-height:1.45}.session-now strong{display:block;color:var(--cyan);margin-bottom:3px;font-size:11px;letter-spacing:.08em;text-transform:uppercase}.chat-list{height:430px;overflow-y:auto;padding:10px 20px;background:rgba(3,15,28,.27);scrollbar-color:#284b61 transparent}.chat-message{padding:15px 4px;border-bottom:1px solid rgba(102,222,241,.08)}.chat-message:last-child{border-bottom:0}.chat-message.is-question{margin:9px 0;padding:14px;border:1px solid rgba(102,222,241,.22);border-radius:13px;background:rgba(102,222,241,.055)}.chat-message__head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;font-size:12.5px;color:var(--muted2)}.chat-message__head strong{color:var(--text);font-size:14px}.chat-message__right{display:flex;align-items:center;gap:8px;flex-shrink:0}.chat-question-badge{padding:5px 8px;border-radius:999px;background:rgba(102,222,241,.13);border:1px solid rgba(102,222,241,.22);color:var(--cyan);font-size:10px;font-weight:800}.chat-message__text{font-size:15.5px;line-height:1.5;margin-top:8px;white-space:pre-wrap;overflow-wrap:anywhere;color:#d9eaf0}.chat-reply-quote{margin-top:9px;border-left:3px solid var(--cyan2);padding:7px 10px;background:rgba(102,222,241,.05);border-radius:0 8px 8px 0;display:flex;flex-direction:column;gap:2px;font-size:12px;color:var(--muted)}.chat-reply-quote strong{color:#cce6ef}.chat-question-status{display:inline-block;margin-top:10px;font-size:12px;font-weight:700}.chat-question-status.on-air{color:#ffd086}.chat-question-status.answered{color:var(--ok)}.chat-message__actions{display:flex;gap:8px;margin-top:9px}.chat-action{border:0;background:transparent;padding:4px 3px;color:#7fa0af;font-size:12px;font-weight:700;cursor:pointer}.chat-action:hover{color:var(--cyan)}.chat-action.vote{padding:5px 8px;border-radius:8px;background:rgba(255,255,255,.035)}.chat-action.vote.active{background:rgba(102,222,241,.12);color:var(--cyan)}.chat-compose{padding:17px 20px 19px;border-top:1px solid var(--line);background:rgba(4,17,30,.22)}.chat-modes{display:flex;gap:8px;margin-bottom:10px}.chat-mode input{position:absolute;opacity:0;pointer-events:none}.chat-mode span{display:block;padding:8px 11px;border-radius:9px;background:rgba(255,255,255,.04);border:1px solid transparent;color:#9eb7c3;font-size:12.5px;font-weight:750;cursor:pointer}.chat-mode input:checked+span{background:rgba(102,222,241,.11);border-color:rgba(102,222,241,.21);color:var(--cyan)}.chat-compose textarea{width:100%;min-height:86px;resize:vertical;border:1px solid var(--line);border-radius:12px;padding:13px 14px;background:rgba(3,15,28,.55);font:inherit;color:var(--text);outline:none}.chat-compose textarea::placeholder{color:#668696}.chat-compose textarea:focus{border-color:rgba(102,222,241,.45);box-shadow:0 0 0 3px rgba(102,222,241,.07)}.chat-compose-bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:10px}.chat-submit{border:0;border-radius:10px;padding:10px 16px;background:var(--cyan);color:#042033;font-weight:850;cursor:pointer}.chat-submit:disabled{opacity:.5;cursor:wait}.chat-status{font-size:12px;color:var(--muted2);line-height:1.4}.chat-status.error{color:var(--danger)}.chat-reply-banner{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:9px;padding:9px 10px;border-radius:9px;background:rgba(102,222,241,.06);border:1px solid rgba(102,222,241,.12);color:#a9c5d1;font-size:12px}.chat-reply-banner button{border:0;background:transparent;color:var(--cyan);font-weight:700;cursor:pointer}.chat-empty{min-height:360px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:7px;text-align:center;color:var(--muted2)}.chat-empty strong{color:#cce6ef;font-size:18px}.chat-closed{padding:36px;text-align:center}.chat-closed strong{display:block;margin-bottom:6px;font-size:18px;color:#cce6ef}
        @media(max-width:860px){.grid{grid-template-columns:1fr}.brand-row{align-items:flex-start}.brand-actions{justify-content:flex-end}.top{padding:25px}.info{padding:22px}.discussion-head{display:block}.session-now{text-align:left;margin-top:12px}.chat-list{height:390px}.chat-message__head{display:block}.chat-message__right{margin-top:5px}.chat-compose-bottom{align-items:flex-start;flex-direction:column}.chat-submit{width:100%}}
        @media(max-width:560px){.wrap{width:min(100% - 20px,1180px);padding-top:14px}.brand-row{display:block}.brand-actions{margin-top:12px;justify-content:flex-start}.mini-link{flex:1}.top{padding:22px;border-radius:19px}.top h1{font-size:30px}.card{border-radius:17px}.body,.info,.discussion-head{padding-left:18px;padding-right:18px}.placeholder{padding:24px 18px}.chat-list{padding-left:14px;padding-right:14px}.chat-modes{display:grid;grid-template-columns:1fr 1fr}.chat-mode span{text-align:center}.error{margin-top:55px;padding:30px 22px}}
    </style>
</head>
<body>
<?php if (!$participant): ?>
    <div class="error"><h1>Ссылка недействительна</h1><p class="muted">Проверьте персональную ссылку из письма о регистрации или обратитесь по адресу info@rclsmo.ru.</p><a href="/conference-2026/">К странице форума</a></div>
<?php else: ?>
<div class="wrap">
    <div class="brand-row">
        <a class="brand" href="/"><img src="/images/logo.png" alt="Логотип РЦЛСМО"><strong>РЦЛСМО</strong></a>
        <div class="brand-actions"><a class="mini-link" href="/participant.php?t=<?= h($token) ?>">Мой билет</a><a class="mini-link" href="/conference-2026/">Программа форума</a></div>
    </div>
    <div class="top"><div class="eyebrow"><span class="live-dot"></span>Персональный онлайн-доступ</div><h1>Форум лабораторных инноваций Московской области — 2026</h1><div class="top-sub">7 октября 2026 · прямая трансляция и обсуждение</div></div>
    <div class="grid">
        <section class="card">
            <div class="player">
                <?php if ($playerActive): ?>
                    <iframe src="<?= h($liveEmbedUrl) ?>" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen title="<?= $usingTestEmbed ? 'Тестовый видеоплеер' : 'Прямая трансляция' ?>"></iframe>
                <?php elseif ($state === 'before'): ?>
                    <div class="placeholder"><strong>Трансляция ещё не началась</strong>Вернитесь на эту страницу 7 октября 2026 года. Персональная ссылка останется той же.</div>
                <?php elseif ($state === 'after'): ?>
                    <div class="placeholder"><strong>Прямая трансляция завершена</strong>Информация о записи мероприятия будет опубликована дополнительно.</div>
                <?php else: ?>
                    <div class="placeholder"><strong>Страница трансляции готова</strong>Источник видеопотока будет подключён организаторами перед мероприятием.</div>
                <?php endif; ?>
            </div>
            <div class="body"><h2><?= h($participant['full_name']) ?></h2><div class="muted"><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></div><div class="small">Персональная ссылка используется для учёта фактического онлайн-присутствия. Не пересылайте её другим участникам.</div></div>
        </section>
        <aside class="card info">
            <div class="info-head"><span class="badge">Онлайн-участие</span><h2>Вы подключены</h2><div class="muted">Персональная сессия участника</div></div>
            <p><strong>Код:</strong> <?= h($participant['participant_code']) ?></p>
            <p><strong>Дата:</strong> 7 октября 2026 года</p>
            <p><strong>Учёт присутствия:</strong> <?= $isTestParticipant ? 'тестовый режим активен' : 'активен только во время мероприятия' ?></p>
            <p class="small">Участник считается фактически присутствовавшим онлайн при суммарном активном времени на странице от 15 минут.</p>
            <div class="info-actions"><a class="action-link primary" href="/participant.php?t=<?= h($token) ?>">Открыть мой билет</a><a class="action-link" href="/conference-2026/">Программа форума</a></div>
            <?php if ($isTestParticipant): ?><div class="test">Тест: накоплено <strong data-watch-seconds><?= (int)$participant['online_watch_seconds'] ?></strong> сек. Для тестового участника чат и Q&A также доступны уже сейчас.<?= $usingTestEmbed ? ' Сейчас показан нейтральный тестовый ролик; после подключения рабочей ссылки он заменится автоматически.' : '' ?></div><?php endif; ?>
        </aside>
    </div>

    <section class="card discussion" aria-labelledby="discussionTitle">
        <div class="discussion-head">
            <div><h2 id="discussionTitle">Обсуждение</h2><p class="muted">Общий чат участников и вопросы текущему спикеру.</p></div>
            <div class="session-now"><strong>Текущий доклад</strong><span data-chat-session>Текущий спикер пока не выбран</span></div>
        </div>
        <?php if ($interactionActive): ?>
            <div class="chat-list" data-chat-list><div class="chat-empty"><strong>Загружаем обсуждение…</strong><span>Сообщения появятся здесь.</span></div></div>
            <form class="chat-compose" data-chat-form autocomplete="off">
                <div class="chat-reply-banner" data-reply-banner hidden><span data-reply-label></span><button type="button" data-reply-cancel>Отмена</button></div>
                <div class="chat-modes" role="radiogroup" aria-label="Тип сообщения">
                    <label class="chat-mode"><input type="radio" name="messageType" value="chat" checked><span>Сообщение в чат</span></label>
                    <label class="chat-mode"><input type="radio" name="messageType" value="question"><span>Вопрос спикеру</span></label>
                </div>
                <textarea data-chat-input maxlength="1000" placeholder="Напишите сообщение участникам…" required></textarea>
                <div class="chat-compose-bottom"><div class="chat-status" data-chat-status>Вопрос спикеру также появится в общем чате и отдельно у модератора.</div><button class="chat-submit" type="submit">Отправить</button></div>
            </form>
        <?php else: ?>
            <div class="chat-closed"><strong>Обсуждение откроется во время форума</strong><span class="muted">7 октября на этой же персональной странице появятся чат и кнопка «Вопрос спикеру».</span></div>
        <?php endif; ?>
    </section>
</div>
<?php endif; ?>

<?php if ($participant && $interactionActive): ?>
<script>
window.CONFERENCE_CHAT_CONFIG = {
    enabled: true,
    token: <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>,
    endpoint: '/api/conference-chat.php'
};
</script>
<script src="/js/modules/live-chat.js?v=20260816-1" defer></script>
<?php endif; ?>

<?php if ($participant && $trackingActive): ?>
<script>
(() => {
    const token = <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;
    const endpoint = '/api/live-heartbeat.php';
    const watchEl = document.querySelector('[data-watch-seconds]');
    let timer = null;

    async function heartbeat() {
        if (document.visibilityState !== 'visible') return;
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({token}),
                credentials: 'same-origin',
                keepalive: true
            });
            const data = await response.json().catch(() => null);
            if (watchEl && data && data.tracking_active) watchEl.textContent = String(data.watch_seconds || 0);
        } catch (_) {}
    }

    heartbeat();
    timer = setInterval(heartbeat, 30000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') heartbeat();
    });
    window.addEventListener('pagehide', () => {
        if (timer) clearInterval(timer);
        try { navigator.sendBeacon(endpoint, JSON.stringify({token})); } catch (_) {}
    });
})();
</script>
<?php endif; ?>
</body>
</html>