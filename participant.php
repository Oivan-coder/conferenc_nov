<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';

$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = require DB_CONFIG_PATH;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT participant_code, full_name, position, organization, participation_format, qr_token, online_token FROM participants WHERE registration_status = "confirmed" AND (qr_token = :qr_token OR online_token = :online_token) LIMIT 1');
            $stmt->execute([':qr_token' => $token, ':online_token' => $token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) {
        error_log('Participant ticket lookup failed: ' . $e->getMessage());
        $participant = null;
    }
}

if (!$participant) http_response_code(404);

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function googleCalendarUrl(): string {
    $params = [
        'action' => 'TEMPLATE',
        'text' => 'Форум лабораторных инноваций Московской области — 2026',
        'dates' => '20261007T063000Z/20261007T150000Z',
        'details' => 'Форум лабораторных инноваций Московской области. Программа и актуальная информация: https://rclsmo.ru/conference-2026/',
        'location' => 'Дом Правительства Московской области, б-р Строителей, 1, Красногорск, Московская область',
    ];
    return 'https://calendar.google.com/calendar/render?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

$isOnline = $participant && $participant['participation_format'] === 'online';
$calendarUrl = $participant ? '/calendar.php?t=' . rawurlencode($token) : '#';
$googleUrl = $participant ? googleCalendarUrl() : '#';
$liveUrl = ($participant && $isOnline && !empty($participant['online_token'])) ? '/live/?t=' . rawurlencode((string)$participant['online_token']) : null;
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="theme-color" content="#07182b">
<title><?= $participant ? 'Мой билет — Форум лабораторных инноваций Московской области — 2026' : 'Билет не найден' ?></title>
<link rel="icon" type="image/png" href="/images/favicon-32x32.png">
<style>
:root{--bg:#061426;--line:rgba(106,218,238,.2);--cyan:#66def1;--text:#edf8fb;--muted:#9cb6c5;--soft:rgba(102,222,241,.08)}*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 82% 10%,rgba(73,76,202,.18),transparent 34%),radial-gradient(circle at 12% 76%,rgba(37,199,221,.12),transparent 35%),linear-gradient(145deg,var(--bg),#04101e 70%);color:var(--text);font-family:Inter,Arial,sans-serif}.wrap{width:min(1100px,calc(100% - 32px));margin:0 auto;padding:34px 0 48px}.brand{display:flex;align-items:center;gap:12px;color:var(--text);text-decoration:none;margin-bottom:24px}.brand img{width:42px;height:42px;object-fit:contain}.brand strong{font-size:18px;letter-spacing:.08em}.shell{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);background:linear-gradient(145deg,rgba(14,38,61,.96),rgba(6,24,42,.97));border:1px solid var(--line);border-radius:28px;overflow:hidden;box-shadow:0 28px 90px rgba(0,0,0,.28)}.main{padding:42px}.eyebrow{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--cyan);font-weight:800}.main h1{margin:12px 0 8px;font-size:clamp(32px,5vw,54px);line-height:1.02}.event{font-size:18px;line-height:1.45;color:#c9dce5;max-width:720px}.person{margin:34px 0 0;padding:22px 0;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.person strong{display:block;font-size:24px;margin-bottom:7px}.person span{color:var(--muted);line-height:1.5}.facts{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:22px 0}.fact{padding:15px;border-radius:15px;background:var(--soft);border:1px solid rgba(102,222,241,.1)}.fact small{display:block;color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px}.fact b{font-size:14px;line-height:1.35}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:24px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:46px;padding:11px 17px;border-radius:12px;text-decoration:none;font-weight:800;font-size:14px;border:1px solid var(--line);color:var(--text);background:rgba(255,255,255,.035)}.btn.primary{background:var(--cyan);color:#042033;border-color:transparent}.btn.live{background:linear-gradient(135deg,#7558df,#29cfe2);border-color:transparent;color:#fff}.calendar{margin-top:26px;padding:20px;border-radius:18px;background:rgba(255,255,255,.03);border:1px solid var(--line)}.calendar h2{font-size:17px;margin:0 0 6px}.calendar p{margin:0;color:var(--muted);font-size:13px;line-height:1.5}.calendar .actions{margin-top:14px}.side{display:flex;align-items:center;justify-content:center;padding:38px;background:linear-gradient(180deg,rgba(102,222,241,.055),rgba(8,28,47,.55));border-left:1px solid var(--line)}.ticket-box{width:100%;text-align:center}.qr{width:min(320px,100%);aspect-ratio:1;background:#fff;border-radius:22px;padding:10px;display:block;margin:0 auto 18px}.code{font-size:26px;font-weight:900;letter-spacing:.08em;color:var(--cyan)}.hint{color:var(--muted);font-size:13px;line-height:1.5;margin-top:9px}.online-icon{width:190px;height:190px;margin:0 auto 24px;border-radius:50%;display:grid;place-items:center;background:radial-gradient(circle,rgba(102,222,241,.25),rgba(117,88,223,.1) 60%,transparent 70%);border:1px solid var(--line);font-size:58px}.privacy{margin:18px auto 0;max-width:330px;color:#6f8c9c;font-size:11px;line-height:1.5}.error{padding:70px 30px;text-align:center}.error h1{font-size:36px;margin:0 0 12px}.error p{color:var(--muted)}@media(max-width:820px){.shell{grid-template-columns:1fr}.side{border-left:0;border-top:1px solid var(--line)}.main{padding:30px}.facts{grid-template-columns:1fr 1fr}.wrap{padding-top:20px}}@media(max-width:520px){.wrap{width:min(100% - 20px,1100px)}.main,.side{padding:22px}.facts{grid-template-columns:1fr}.actions{display:grid;grid-template-columns:1fr}.btn{width:100%}.brand{margin-left:4px}.main h1{font-size:36px}}
</style>
</head>
<body>
<div class="wrap">
<a class="brand" href="/"><img src="/images/logo.png" alt="Логотип РЦЛСМО"><strong>РЦЛСМО</strong></a>
<?php if ($participant): ?>
<section class="shell">
<div class="main">
<div class="eyebrow">Персональная страница участника</div>
<h1>Мой билет</h1>
<div class="event">Форум лабораторных инноваций Московской области — 2026</div>
<div class="person"><strong><?= h($participant['full_name']) ?></strong><span><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></span></div>
<div class="facts"><div class="fact"><small>Дата и время</small><b>7 октября · 09:30–18:00</b></div><div class="fact"><small>Место</small><b>Дом Правительства МО</b></div><div class="fact"><small>Формат</small><b><?= $isOnline ? 'Онлайн-участие' : 'Очное участие' ?></b></div></div>
<div class="actions"><a class="btn primary" href="/conference-2026/">Программа форума</a><?php if ($liveUrl): ?><a class="btn live" href="<?= h($liveUrl) ?>">Открыть трансляцию</a><?php endif; ?></div>
<div class="calendar"><h2>Добавить форум в календарь</h2><p>Дата, время и адрес сохранятся автоматически. Персональная ссылка в календарь не передаётся.</p><div class="actions"><a class="btn primary" href="<?= h($calendarUrl) ?>">Apple / Outlook · .ics</a><a class="btn" href="<?= h($googleUrl) ?>" target="_blank" rel="noopener noreferrer">Google Calendar</a></div></div>
</div>
<div class="side"><div class="ticket-box"><?php if (!$isOnline): ?><img class="qr" src="/api/qr.php?t=<?= h((string)$participant['qr_token']) ?>" alt="QR-код участника"><div class="code"><?= h($participant['participant_code']) ?></div><div class="hint">Покажите QR-код на стойке регистрации.</div><?php else: ?><div class="online-icon" aria-hidden="true">▶</div><div class="code"><?= h($participant['participant_code']) ?></div><div class="hint">Ваш онлайн-доступ подтверждён.<br>Кнопка трансляции находится слева.</div><?php endif; ?><div class="privacy">Эта страница персональная. Не пересылайте ссылку другим участникам.</div></div></div>
</section>
<?php else: ?><section class="shell"><div class="error"><h1>Билет не найден</h1><p>Ссылка недействительна или регистрация была изменена.</p><a class="btn primary" href="/conference-2026/">К странице форума</a></div></section><?php endif; ?>
</div>
</body></html>
