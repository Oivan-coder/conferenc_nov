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
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6f4;color:#173126;font-family:Arial,sans-serif}.wrap{max-width:1100px;margin:0 auto;padding:24px 16px 44px}.top{background:#214f3b;color:#fff;border-radius:18px;padding:28px;margin-bottom:20px}.eyebrow{font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.8}.top h1{margin:8px 0 0;font-size:30px;line-height:1.2}.grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px}.card{background:#fff;border:1px solid #dfe8e2;border-radius:16px;overflow:hidden}.player{aspect-ratio:16/9;background:#13221c;display:flex;align-items:center;justify-content:center;color:#fff;text-align:center}.player iframe{width:100%;height:100%;border:0}.placeholder{padding:30px;max-width:560px;line-height:1.6}.body{padding:22px}.body h2{margin:0 0 8px;font-size:22px}.muted{color:#62756c;line-height:1.55}.info{padding:22px}.info p{margin:7px 0;line-height:1.5}.badge{display:inline-block;padding:7px 11px;border-radius:999px;background:#eef5f1;color:#214f3b;font-size:13px;font-weight:700}.test{margin-top:14px;padding:12px;border-radius:10px;background:#fff6d9;color:#695400;font-size:13px;line-height:1.5}.error{max-width:680px;margin:80px auto;background:#fff;border:1px solid #dfe8e2;border-radius:16px;padding:36px;text-align:center}.small{font-size:13px;color:#6f8078;line-height:1.5;margin-top:14px}@media(max-width:800px){.grid{grid-template-columns:1fr}.top h1{font-size:25px}.wrap{padding-top:14px}.top{padding:22px}}
    </style>
</head>
<body>
<?php if (!$participant): ?>
    <div class="error"><h1>Ссылка недействительна</h1><p class="muted">Проверьте ссылку из письма о регистрации или обратитесь по адресу info@rclsmo.ru.</p></div>
<?php else: ?>
<div class="wrap">
    <div class="top"><div class="eyebrow">Референс-центр лабораторной службы Московской области</div><h1>Форум лабораторных инноваций 2026</h1></div>
    <div class="grid">
        <section class="card">
            <div class="player">
                <?php if ($state === 'live' && LIVE_EMBED_URL !== ''): ?>
                    <iframe src="<?= h(LIVE_EMBED_URL) ?>" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen title="Прямая трансляция"></iframe>
                <?php elseif ($state === 'before'): ?>
                    <div class="placeholder"><strong>Трансляция ещё не началась.</strong><br>Вернитесь на эту страницу 7 октября 2026 года. Ссылка останется той же.</div>
                <?php elseif ($state === 'after'): ?>
                    <div class="placeholder"><strong>Прямая трансляция завершена.</strong><br>Информация о записи мероприятия будет опубликована дополнительно.</div>
                <?php else: ?>
                    <div class="placeholder"><strong>Страница трансляции готова.</strong><br>Источник видеопотока будет подключён организаторами перед мероприятием.</div>
                <?php endif; ?>
            </div>
            <div class="body"><h2><?= h($participant['full_name']) ?></h2><div class="muted"><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></div><div class="small">Персональная ссылка используется для учёта фактического онлайн-присутствия. Не пересылайте её другим участникам.</div></div>
        </section>
        <aside class="card info">
            <span class="badge">Онлайн-участие</span>
            <p><strong>Код:</strong> <?= h($participant['participant_code']) ?></p>
            <p><strong>Дата:</strong> 7 октября 2026 года</p>
            <p><strong>Учёт присутствия:</strong> <?= $isTestParticipant ? 'тестовый режим активен' : 'активен только во время мероприятия' ?></p>
            <p class="small">Участник считается фактически присутствовавшим онлайн при суммарном активном времени на странице от 15 минут.</p>
            <?php if ($isTestParticipant): ?><div class="test">Тест: накоплено <strong data-watch-seconds><?= (int)$participant['online_watch_seconds'] ?></strong> сек. Оставьте вкладку открытой примерно минуту.</div><?php endif; ?>
        </aside>
    </div>
</div>
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
