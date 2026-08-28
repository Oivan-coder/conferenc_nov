<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const DASHBOARD_PASSWORD_PATH = '/home/c/cx314477/public_html/.private/dashboard_pass';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const TEST_ORGANIZATION = 'Тестовая МО';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isAuthenticated(): bool {
    return !empty($_SESSION['conference_dashboard_auth']);
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: /dashboard/');
    exit;
}

$loginError = '';
$configured = is_readable(DASHBOARD_PASSWORD_PATH) && trim((string)file_get_contents(DASHBOARD_PASSWORD_PATH)) !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (!$configured) {
        $loginError = 'Доступ ещё не настроен.';
    } else {
        $expected = trim((string)file_get_contents(DASHBOARD_PASSWORD_PATH));
        $provided = (string)$_POST['password'];
        if (hash_equals($expected, $provided)) {
            session_regenerate_id(true);
            $_SESSION['conference_dashboard_auth'] = true;
            header('Location: /dashboard/');
            exit;
        }
        usleep(350000);
        $loginError = 'Неверный пароль.';
    }
}

if (!isAuthenticated()):
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Дашборд конференции</title>
<style>*{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#183126;font-family:Arial,sans-serif}.login{min-height:100vh;display:grid;place-items:center;padding:20px}.box{width:min(460px,100%);background:#fff;border:1px solid #dce7e1;border-radius:20px;padding:30px;box-shadow:0 20px 60px rgba(20,61,44,.09)}.mark{width:44px;height:44px;border-radius:14px;background:#214f3b;display:grid;place-items:center;color:#fff;font-weight:800;margin-bottom:18px}.eyebrow{font-size:11px;letter-spacing:.11em;text-transform:uppercase;color:#6b7e74}.box h1{margin:8px 0 8px;font-size:27px}.box p{color:#687a71;line-height:1.55}.box input{width:100%;height:48px;border:1px solid #cddbd3;border-radius:11px;padding:0 14px;font:inherit;margin-top:12px}.box button{width:100%;height:48px;border:0;border-radius:11px;background:#214f3b;color:#fff;font:inherit;font-weight:700;margin-top:12px;cursor:pointer}.err{background:#fff0f0;color:#8c3030;border-radius:10px;padding:11px 13px;margin-top:13px;font-size:14px}.setup{background:#fff7dd;color:#705a14;border-radius:10px;padding:11px 13px;margin-top:13px;font-size:14px;line-height:1.5}</style></head>
<body><main class="login"><section class="box"><div class="mark">РЦ</div><div class="eyebrow">Форум лабораторных инноваций Московской области — 2026</div><h1>Дашборд регистрации</h1><p>Закрытая страница для организаторов и руководства.</p>
<?php if (!$configured): ?><div class="setup">Пароль для дашборда ещё не создан на сервере.</div><?php else: ?><form method="post" autocomplete="off"><input type="password" name="password" placeholder="Пароль" required autofocus><button type="submit">Войти</button></form><?php endif; ?>
<?php if ($loginError !== ''): ?><div class="err"><?= h($loginError) ?></div><?php endif; ?>
</section></main></body></html>
<?php
exit;
endif;

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');

    $settingsStmt = $pdo->prepare('SELECT hall_capacity, public_offline_limit, reserved_seats FROM event_registration_settings WHERE event_id = :event LIMIT 1');
    $settingsStmt->execute([':event' => EVENT_ID]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC) ?: ['hall_capacity' => 95, 'public_offline_limit' => 80, 'reserved_seats' => 15];

    $summaryStmt = $pdo->prepare("SELECT
        COUNT(*) AS total,
        SUM(participation_format='offline' AND registration_status='confirmed') AS offline_confirmed,
        SUM(participation_format='online' AND registration_status='confirmed') AS online_confirmed,
        SUM(registration_status='waitlist') AS waitlist,
        SUM(registration_status='cancelled') AS cancelled,
        SUM(participation_format='offline' AND registration_status='confirmed' AND check_in_at IS NOT NULL) AS checked_in,
        SUM(participation_format='online' AND registration_status='confirmed' AND online_watch_seconds >= 900) AS online_present,
        COUNT(DISTINCT organization) AS organizations
      FROM participants
      WHERE event_id = :event AND organization <> :test_org");
    $summaryStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $orgStmt = $pdo->prepare("SELECT organization,
        COUNT(*) AS total,
        SUM(participation_format='offline' AND registration_status='confirmed') AS offline_count,
        SUM(participation_format='online' AND registration_status='confirmed') AS online_count,
        SUM(registration_status='waitlist') AS waitlist_count,
        SUM(participation_format='offline' AND registration_status='confirmed' AND check_in_at IS NOT NULL) AS checked_in_count,
        SUM(participation_format='online' AND registration_status='confirmed' AND online_watch_seconds >= 900) AS online_present_count
      FROM participants
      WHERE event_id = :event AND organization <> :test_org
      GROUP BY organization
      ORDER BY total DESC, organization ASC");
    $orgStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);

    $peopleStmt = $pdo->prepare("SELECT participant_code, full_name, position, organization, participation_format, registration_status, created_at, check_in_at, online_watch_seconds
      FROM participants
      WHERE event_id = :event AND organization <> :test_org
      ORDER BY created_at DESC, id DESC
      LIMIT 500");
    $peopleStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $participants = $peopleStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<!doctype html><html lang="ru"><meta charset="utf-8"><body style="font-family:Arial;padding:40px">Не удалось загрузить данные дашборда.</body></html>';
    exit;
}

$offlineConfirmed = (int)($summary['offline_confirmed'] ?? 0);
$onlineConfirmed = (int)($summary['online_confirmed'] ?? 0);
$waitlist = (int)($summary['waitlist'] ?? 0);
$checkedIn = (int)($summary['checked_in'] ?? 0);
$onlinePresent = (int)($summary['online_present'] ?? 0);
$orgCount = (int)($summary['organizations'] ?? 0);
$publicLimit = (int)$settings['public_offline_limit'];
$remaining = max(0, $publicLimit - $offlineConfirmed);
$fillPct = $publicLimit > 0 ? min(100, round($offlineConfirmed / $publicLimit * 100)) : 0;
$maxOrg = 1;
foreach ($organizations as $org) $maxOrg = max($maxOrg, (int)$org['total']);
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Дашборд — Форум лабораторных инноваций Московской области — 2026</title>
<style>
:root{--green:#214f3b;--green2:#2e6c51;--ink:#173126;--muted:#6b7c73;--paper:#f4f7f5;--line:#dbe6df;--white:#fff;--warn:#b98720}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}.wrap{max-width:1440px;margin:0 auto;padding:26px}.top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:22px}.eyebrow{font-size:11px;letter-spacing:.11em;text-transform:uppercase;color:#71837a}.top h1{margin:7px 0 5px;font-size:31px}.top p{margin:0;color:var(--muted)}.actions{display:flex;align-items:center;gap:10px}.pill{background:#e9f2ed;color:var(--green);padding:8px 11px;border-radius:999px;font-size:12px;font-weight:700}.logout{color:var(--muted);text-decoration:none;font-size:13px}.cards{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:18px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;box-shadow:0 8px 30px rgba(28,67,50,.04)}.card span{display:block;color:var(--muted);font-size:12px;margin-bottom:7px}.card strong{font-size:30px}.card small{display:block;color:var(--muted);font-size:11px;margin-top:6px}.progress{height:7px;background:#e9efeb;border-radius:999px;margin-top:11px;overflow:hidden}.progress i{display:block;height:100%;background:var(--green2);border-radius:999px}.grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:16px;margin-bottom:18px}.panel{background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 8px 30px rgba(28,67,50,.04)}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:14px}.panel h2{margin:0;font-size:19px}.muted{color:var(--muted);font-size:12px}.org-list{display:grid;gap:10px}.org-row{display:grid;grid-template-columns:minmax(180px,1fr) 2.2fr 64px;gap:13px;align-items:center}.org-name{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.bar{height:9px;background:#edf2ef;border-radius:999px;overflow:hidden}.bar i{display:block;height:100%;background:linear-gradient(90deg,var(--green),#4c8a6b);border-radius:999px}.org-num{text-align:right;font-size:13px;font-weight:700}.table-wrap{overflow:auto;max-height:660px;border:1px solid #e6ece8;border-radius:12px}table{width:100%;border-collapse:collapse;font-size:13px}th{position:sticky;top:0;background:#f6f9f7;color:#66796f;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:11px 12px;border-bottom:1px solid var(--line);z-index:1}td{padding:11px 12px;border-bottom:1px solid #edf1ef;vertical-align:top}tr:last-child td{border-bottom:0}.tag{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#eef4f1;color:var(--green)}.tag.wait{background:#fff4d9;color:#8a6511}.tag.cancel{background:#f5eeee;color:#8a4b4b}.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.filters input,.filters select{height:38px;border:1px solid #d4e0d9;border-radius:9px;background:#fff;padding:0 10px;font:inherit;font-size:13px}.filters input{min-width:270px}.org-table{width:100%}.legend{display:flex;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:11px;margin-top:12px}.mobile-note{display:none}@media(max-width:1100px){.cards{grid-template-columns:repeat(3,1fr)}.grid{grid-template-columns:1fr}}@media(max-width:700px){.wrap{padding:15px}.top{display:block}.actions{margin-top:12px}.cards{grid-template-columns:repeat(2,1fr)}.card strong{font-size:25px}.org-row{grid-template-columns:minmax(120px,1fr) 1.4fr 42px}.filters input{min-width:100%;width:100%}.panel{padding:14px}.mobile-note{display:block}.top h1{font-size:26px}}@media(max-width:430px){.cards{grid-template-columns:1fr 1fr}.card{padding:14px}}
</style></head>
<body><main class="wrap">
<header class="top"><div><div class="eyebrow">Референс-центр лабораторной службы Московской области</div><h1>Регистрация · Форум 2026</h1><p>7 октября 2026 · данные обновляются при перезагрузке страницы</p></div><div class="actions"><span class="pill">Тестовые записи исключены</span><a class="logout" href="?logout=1">Выйти</a></div></header>
<section class="cards">
<div class="card"><span>Всего подтверждено</span><strong><?= $offlineConfirmed + $onlineConfirmed ?></strong><small>очно + онлайн</small></div>
<div class="card"><span>Очно</span><strong><?= $offlineConfirmed ?></strong><small>из <?= $publicLimit ?> публичных мест · осталось <?= $remaining ?></small><div class="progress"><i style="width:<?= $fillPct ?>%"></i></div></div>
<div class="card"><span>Онлайн</span><strong><?= $onlineConfirmed ?></strong><small>подтверждено</small></div>
<div class="card"><span>Лист ожидания</span><strong><?= $waitlist ?></strong><small>на очное участие</small></div>
<div class="card"><span>Медорганизаций</span><strong><?= $orgCount ?></strong><small>уникальных</small></div>
<div class="card"><span>Факт участия</span><strong><?= $checkedIn + $onlinePresent ?></strong><small><?= $checkedIn ?> очно · <?= $onlinePresent ?> онлайн ≥15 мин</small></div>
</section>
<section class="grid">
<div class="panel"><div class="panel-head"><h2>Регистрации по медицинским организациям</h2><span class="muted"><?= count($organizations) ?> МО</span></div><div class="org-list">
<?php if (!$organizations): ?><div class="muted">Пока нет регистраций.</div><?php endif; ?>
<?php foreach (array_slice($organizations, 0, 18) as $org): $width = round(((int)$org['total'] / $maxOrg) * 100); ?>
<div class="org-row" title="<?= h($org['organization']) ?>"><div class="org-name"><?= h($org['organization']) ?></div><div class="bar"><i style="width:<?= $width ?>%"></i></div><div class="org-num"><?= (int)$org['total'] ?></div></div>
<?php endforeach; ?></div><div class="legend">Топ-18 по числу регистраций. Полная детализация — справа в таблице участников.</div></div>
<div class="panel"><div class="panel-head"><h2>Разрез по МО</h2><span class="muted">очно / онлайн / ожидание / факт</span></div><div class="table-wrap" style="max-height:430px"><table class="org-table"><thead><tr><th>МО</th><th>Всего</th><th>Очно</th><th>Онлайн</th><th>Ожидание</th><th>Пришли</th><th>Онлайн ≥15м</th></tr></thead><tbody><?php foreach ($organizations as $org): ?><tr><td><strong><?= h($org['organization']) ?></strong></td><td><?= (int)$org['total'] ?></td><td><?= (int)$org['offline_count'] ?></td><td><?= (int)$org['online_count'] ?></td><td><?= (int)$org['waitlist_count'] ?></td><td><?= (int)$org['checked_in_count'] ?></td><td><?= (int)$org['online_present_count'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>
<section class="panel"><div class="panel-head"><h2>Участники</h2><span class="muted">последние <?= count($participants) ?> записей</span></div><div class="filters"><input id="q" type="search" placeholder="Поиск по ФИО, МО, должности, коду"><select id="fmt"><option value="">Все форматы</option><option value="offline">Очно</option><option value="online">Онлайн</option></select><select id="sts"><option value="">Все статусы</option><option value="confirmed">Подтверждено</option><option value="waitlist">Лист ожидания</option><option value="cancelled">Отменено</option></select></div><div class="table-wrap"><table id="people"><thead><tr><th>Участник</th><th>МО / должность</th><th>Формат</th><th>Статус</th><th>Регистрация</th><th>Факт участия</th></tr></thead><tbody>
<?php foreach ($participants as $p): $present = $p['participation_format']==='offline' ? ($p['check_in_at'] ? 'Пришёл' : '—') : ((int)$p['online_watch_seconds']>=900 ? 'Онлайн ≥15 мин' : ((int)$p['online_watch_seconds']>0 ? round((int)$p['online_watch_seconds']/60).' мин' : '—')); ?>
<tr data-format="<?= h($p['participation_format']) ?>" data-status="<?= h($p['registration_status']) ?>" data-search="<?= h(mb_strtolower($p['full_name'].' '.$p['organization'].' '.$p['position'].' '.$p['participant_code'])) ?>"><td><strong><?= h($p['full_name']) ?></strong><div class="muted"><?= h($p['participant_code']) ?></div></td><td><strong><?= h($p['organization']) ?></strong><div class="muted"><?= h($p['position']) ?></div></td><td><span class="tag"><?= $p['participation_format']==='offline'?'Очно':'Онлайн' ?></span></td><td><span class="tag <?= $p['registration_status']==='waitlist'?'wait':($p['registration_status']==='cancelled'?'cancel':'') ?>"><?= $p['registration_status']==='confirmed'?'Подтверждено':($p['registration_status']==='waitlist'?'Ожидание':'Отменено') ?></span></td><td><?= h(date('d.m.Y H:i', strtotime($p['created_at']))) ?></td><td><?= h($present) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
</main><script>
(() => { const q=document.getElementById('q'), f=document.getElementById('fmt'), s=document.getElementById('sts'), rows=[...document.querySelectorAll('#people tbody tr')]; function apply(){const text=(q.value||'').trim().toLowerCase(); rows.forEach(r=>{const okText=!text||r.dataset.search.includes(text); const okF=!f.value||r.dataset.format===f.value; const okS=!s.value||r.dataset.status===s.value; r.hidden=!(okText&&okF&&okS);});} [q,f,s].forEach(el=>el.addEventListener('input',apply)); setTimeout(()=>location.reload(),60000); })();
</script></body></html>
