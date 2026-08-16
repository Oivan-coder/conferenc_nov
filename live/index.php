<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const LIVE_EMBED_URL = '';
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

$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = require DB_CONFIG_PATH;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare(
                'SELECT participant_code, full_name, position, organization, online_watch_seconds
                 FROM participants
                 WHERE online_token = :token AND participation_format = "online"
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
$isTestParticipant = $participant && trim((string)$participant['organization']) === TEST_ORGANIZATION;
$trackingActive = $participant && ($state === 'live' || $isTestParticipant);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= $participant ? 'Онлайн-трансляция — Форум лабораторных инноваций 2026' : 'Ссылка недействительна' ?></title>
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png">
    <link rel="stylesheet" href="/css/conference-premium.css?v=20260816-01">
</head>
<body class="live-premium-page">
<div class="event-shell">
    <header class="event-brandbar">
        <div class="event-brandbar__eyebrow">Референс-центр лабораторной службы Московской области</div>
        <h1>Форум лабораторных инноваций 2026</h1>
    </header>

    <?php if (!$participant): ?>
        <main class="event-panel" style="padding:48px;text-align:center">
            <span class="event-chip">Ошибка</span>
            <h2 style="font-family:Georgia,serif;font-size:34px;margin:18px 0 8px">Ссылка недействительна</h2>
            <p class="event-muted">Проверьте ссылку из письма о регистрации или обратитесь по адресу info@rclsmo.ru.</p>
        </main>
    <?php else: ?>
        <main class="live-layout">
            <div>
                <section class="live-stage">
                    <div class="live-player">
                        <?php if ($state === 'live' && LIVE_EMBED_URL !== ''): ?>
                            <iframe src="<?= h(LIVE_EMBED_URL) ?>" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen title="Прямая трансляция"></iframe>
                        <?php elseif ($state === 'before'): ?>
                            <div class="live-placeholder"><strong>Трансляция ещё не началась</strong>Вернитесь на эту страницу 7 октября 2026 года. Персональная ссылка останется той же.</div>
                        <?php elseif ($state === 'after'): ?>
                            <div class="live-placeholder"><strong>Прямая трансляция завершена</strong>Информация о записи мероприятия будет опубликована дополнительно.</div>
                        <?php else: ?>
                            <div class="live-placeholder"><strong>Страница трансляции готова</strong>Источник видеопотока будет подключён организаторами перед мероприятием.</div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="live-person">
                    <h2><?= h($participant['full_name']) ?></h2>
                    <p><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></p>
                    <div class="live-person__note">Это персональная ссылка участника. Она используется для учёта фактического онлайн-присутствия, поэтому не пересылайте её другим людям.</div>
                </section>
            </div>

            <aside class="live-side">
                <span class="event-chip">Онлайн-участие</span>
                <h3>Ваш доступ к форуму</h3>

                <div class="live-detail"><span>Код участника</span><strong><?= h($participant['participant_code']) ?></strong></div>
                <div class="live-detail"><span>Дата</span><strong>7 октября 2026 года</strong></div>
                <div class="live-detail"><span>Статус трансляции</span><strong><?= $state === 'before' ? 'Ожидает начала' : ($state === 'after' ? 'Завершена' : 'В эфире') ?></strong></div>
                <div class="live-detail"><span>Учёт присутствия</span><strong><?= $isTestParticipant ? 'Тестовый режим активен' : 'Активен во время мероприятия' ?></strong></div>

                <div class="live-presence">Фактическое онлайн-присутствие учитывается при суммарном активном времени на странице от <b>15 минут</b>.</div>
                <?php if ($isTestParticipant): ?>
                    <div class="live-presence" style="margin-top:10px;background:#fff5d9;color:#6a5607">Тест: накоплено <b data-watch-seconds><?= (int)$participant['online_watch_seconds'] ?></b> сек. Оставьте вкладку открытой примерно минуту.</div>
                <?php endif; ?>
                <div class="live-help">Если страница или трансляция не открывается, напишите на <a href="mailto:info@rclsmo.ru" style="color:#164b38;font-weight:700">info@rclsmo.ru</a>.</div>
            </aside>
        </main>
    <?php endif; ?>
</div>

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
