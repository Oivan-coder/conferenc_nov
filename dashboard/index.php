<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow,noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const DASHBOARD_PASSWORD_PATH = '/home/c/cx314477/public_html/.private/dashboard_pass';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const TEST_ORGANIZATION = 'Тестовая МО';

require_once __DIR__ . '/reporting.php';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isAuthenticated(): bool {
    return !empty($_SESSION['conference_dashboard_auth']);
}

function dashboardCsrfToken(): string {
    if (empty($_SESSION['dashboard_csrf'])) $_SESSION['dashboard_csrf'] = bin2hex(random_bytes(32));
    return (string)$_SESSION['dashboard_csrf'];
}

function dashboardVerifyCsrf(): void {
    $expected = (string)($_SESSION['dashboard_csrf'] ?? '');
    $provided = (string)($_POST['csrf'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Некорректный запрос. Обновите страницу и повторите.');
    }
}

function dashboardRedirect(string $anchor = ''): never {
    header('Location: /dashboard/' . $anchor);
    exit;
}

function dashboardSetFlash(array $value): void {
    $_SESSION['dashboard_flash'] = $value;
}

function dashboardPopFlash(): ?array {
    $value = $_SESSION['dashboard_flash'] ?? null;
    unset($_SESSION['dashboard_flash']);
    return is_array($value) ? $value : null;
}

function normalizeScannerKeyboardLayout(string $value): string {
    if (!preg_match('/[А-Яа-яЁё]/u', $value)) return $value;

    return strtr($value, [
        'ё' => '`', 'й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't', 'н' => 'y',
        'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p', 'х' => '[', 'ъ' => ']',
        'ф' => 'a', 'ы' => 's', 'в' => 'd', 'а' => 'f', 'п' => 'g', 'р' => 'h',
        'о' => 'j', 'л' => 'k', 'д' => 'l', 'ж' => ';', 'э' => "'",
        'я' => 'z', 'ч' => 'x', 'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n',
        'ь' => 'm', 'б' => ',', 'ю' => '.', '.' => '/', ',' => '?',
        'Ё' => '~', 'Й' => 'Q', 'Ц' => 'W', 'У' => 'E', 'К' => 'R', 'Е' => 'T', 'Н' => 'Y',
        'Г' => 'U', 'Ш' => 'I', 'Щ' => 'O', 'З' => 'P', 'Х' => '{', 'Ъ' => '}',
        'Ф' => 'A', 'Ы' => 'S', 'В' => 'D', 'А' => 'F', 'П' => 'G', 'Р' => 'H',
        'О' => 'J', 'Л' => 'K', 'Д' => 'L', 'Ж' => ':', 'Э' => '"',
        'Я' => 'Z', 'Ч' => 'X', 'С' => 'C', 'М' => 'V', 'И' => 'B', 'Т' => 'N',
        'Ь' => 'M', 'Б' => '<', 'Ю' => '>',
    ]);
}

function extractScanValue(string $raw): ?array {
    $raw = trim(normalizeScannerKeyboardLayout($raw));
    if (preg_match('/^LE[A-F0-9]{8}$/i', $raw)) return ['code', strtoupper($raw)];
    if (preg_match('/^[a-f0-9]{64}$/i', $raw)) return ['token', strtolower($raw)];
    if (!filter_var($raw, FILTER_VALIDATE_URL)) return null;
    $query = parse_url($raw, PHP_URL_QUERY);
    if (!is_string($query)) return null;
    parse_str($query, $params);
    $token = strtolower(trim((string)($params['t'] ?? '')));
    return preg_match('/^[a-f0-9]{64}$/', $token) ? ['token', $token] : null;
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

$dashboardCsrf = dashboardCsrfToken();
$dashboardFlash = dashboardPopFlash();

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        dashboardVerifyCsrf();
        $action = (string)$_POST['action'];

        if ($action === 'scan') {
            $scan = extractScanValue((string)($_POST['scan'] ?? ''));
            if (!$scan) {
                dashboardSetFlash(['type' => 'error', 'title' => 'QR-код не распознан', 'message' => 'Отсканируйте QR целиком либо введите код участника формата LE1234ABCD.']);
                dashboardRedirect('#scanner');
            }

            [$kind, $value] = $scan;
            $pdo->beginTransaction();
            $sql = $kind === 'token'
                ? 'SELECT id, participant_code, full_name, position, organization, check_in_at FROM participants WHERE event_id = :event AND qr_token = :value AND participation_format = "offline" AND registration_status = "confirmed" LIMIT 1 FOR UPDATE'
                : 'SELECT id, participant_code, full_name, position, organization, check_in_at FROM participants WHERE event_id = :event AND participant_code = :value AND participation_format = "offline" AND registration_status = "confirmed" LIMIT 1 FOR UPDATE';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':event' => EVENT_ID, ':value' => $value]);
            $scannedParticipant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$scannedParticipant) {
                $pdo->rollBack();
                dashboardSetFlash(['type' => 'error', 'title' => 'Участник не найден', 'message' => 'QR относится не к подтверждённой очной регистрации либо ссылка повреждена.']);
                dashboardRedirect('#scanner');
            }

            $alreadyCheckedIn = $scannedParticipant['check_in_at'] !== null;
            if (!$alreadyCheckedIn) {
                $update = $pdo->prepare('UPDATE participants SET check_in_at = NOW() WHERE id = :id');
                $update->execute([':id' => $scannedParticipant['id']]);
                $timeStmt = $pdo->prepare('SELECT check_in_at FROM participants WHERE id = :id');
                $timeStmt->execute([':id' => $scannedParticipant['id']]);
                $scannedParticipant['check_in_at'] = (string)$timeStmt->fetchColumn();
            }
            $pdo->commit();

            dashboardSetFlash([
                'type' => $alreadyCheckedIn ? 'warning' : 'success',
                'title' => $alreadyCheckedIn ? 'Участник уже был отмечен' : 'Участник отмечен',
                'message' => $scannedParticipant['full_name'] . ' · ' . $scannedParticipant['organization'],
                'code' => $scannedParticipant['participant_code'],
                'time' => $scannedParticipant['check_in_at'],
            ]);
            dashboardRedirect('#scanner');
        }

        if ($action === 'delete_test') {
            $participantId = filter_var($_POST['participant_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$participantId || (string)($_POST['confirm'] ?? '') !== 'yes') {
                dashboardSetFlash(['type' => 'error', 'title' => 'Удаление не выполнено', 'message' => 'Не подтверждено удаление тестовой записи.']);
                dashboardRedirect('#test-records');
            }

            $pdo->beginTransaction();
            $testStmt = $pdo->prepare("SELECT id, participant_code, full_name, organization FROM participants WHERE id = :id AND event_id = :event AND (organization = :test_org OR LOWER(TRIM(organization)) IN ('ovan','oivan')) LIMIT 1 FOR UPDATE");
            $testStmt->execute([':id' => $participantId, ':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
            $testParticipant = $testStmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$testParticipant) {
                $pdo->rollBack();
                dashboardSetFlash(['type' => 'error', 'title' => 'Запись не найдена', 'message' => 'Удалять через этот блок можно только тестовые записи.']);
                dashboardRedirect('#test-records');
            }

            try {
                $pdo->prepare('DELETE v FROM conference_message_votes v INNER JOIN conference_messages m ON m.id = v.message_id WHERE m.participant_id = :id')->execute([':id' => $participantId]);
                $pdo->prepare('DELETE FROM conference_message_votes WHERE participant_id = :id')->execute([':id' => $participantId]);
                $pdo->prepare('DELETE FROM conference_messages WHERE participant_id = :id')->execute([':id' => $participantId]);
            } catch (PDOException $ignored) {}
            $pdo->prepare('DELETE FROM participants WHERE id = :id')->execute([':id' => $participantId]);
            $pdo->commit();

            dashboardSetFlash(['type' => 'success', 'title' => 'Тестовая запись удалена', 'message' => $testParticipant['full_name'] . ' · ' . $testParticipant['participant_code']]);
            dashboardRedirect('#test-records');
        }
    }

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
        COUNT(DISTINCT CASE WHEN registration_status='confirmed' THEN organization END) AS organizations
      FROM participants
      WHERE event_id = :event
        AND organization <> :test_org
        AND LOWER(TRIM(organization)) NOT IN ('ovan','oivan')");
    $summaryStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $orgStmt = $pdo->prepare("SELECT organization,
        COUNT(*) AS total,
        SUM(registration_status='confirmed') AS confirmed_count,
        SUM(participation_format='offline' AND registration_status='confirmed') AS offline_count,
        SUM(participation_format='online' AND registration_status='confirmed') AS online_count,
        SUM(registration_status='waitlist') AS waitlist_count,
        SUM(participation_format='offline' AND registration_status='confirmed' AND check_in_at IS NOT NULL) AS checked_in_count,
        SUM(participation_format='online' AND registration_status='confirmed' AND online_watch_seconds >= 900) AS online_present_count
      FROM participants
      WHERE event_id = :event
        AND organization <> :test_org
        AND LOWER(TRIM(organization)) NOT IN ('ovan','oivan')
      GROUP BY organization
      ORDER BY confirmed_count DESC, total DESC, organization ASC");
    $orgStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $organizations = $orgStmt->fetchAll(PDO::FETCH_ASSOC);

    $peopleStmt = $pdo->prepare("SELECT participant_code, full_name, position, organization, participation_format, registration_status, created_at, check_in_at, online_watch_seconds
      FROM participants
      WHERE event_id = :event
        AND organization <> :test_org
        AND LOWER(TRIM(organization)) NOT IN ('ovan','oivan')
      ORDER BY created_at DESC, id DESC
      LIMIT 500");
    $peopleStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $participants = $peopleStmt->fetchAll(PDO::FETCH_ASSOC);

    $testRecordsStmt = $pdo->prepare("SELECT id, participant_code, full_name, organization, participation_format, created_at, check_in_at, online_watch_seconds
      FROM participants
      WHERE event_id = :event AND (organization = :test_org OR LOWER(TRIM(organization)) IN ('ovan','oivan'))
      ORDER BY created_at DESC, id DESC
      LIMIT 100");
    $testRecordsStmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $testRecords = $testRecordsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo '<!doctype html><html lang="ru"><meta charset="utf-8"><body style="font-family:Arial;padding:40px">Не удалось загрузить данные дашборда.</body></html>';
    exit;
}

$offlineConfirmed = (int)($summary['offline_confirmed'] ?? 0);
$onlineConfirmed = (int)($summary['online_confirmed'] ?? 0);
$waitlist = (int)($summary['waitlist'] ?? 0);
$checkedIn = (int)($summary['checked_in'] ?? 0);
$onlinePresent = (int)($summary['online_present'] ?? 0);
$leadershipStats = dashboardLeadershipStats($organizations);
$orgCount = (int)$leadershipStats['organizations'];
$leadershipBrief = dashboardLeadershipBrief($leadershipStats, $offlineConfirmed, $onlineConfirmed, $checkedIn, $onlinePresent, $waitlist);
$publicLimit = (int)$settings['public_offline_limit'];
$remaining = max(0, $publicLimit - $offlineConfirmed);
$fillPct = $publicLimit > 0 ? min(100, round($offlineConfirmed / $publicLimit * 100)) : 0;
$maxOrg = 1;
foreach ($organizations as $org) $maxOrg = max($maxOrg, (int)($org['confirmed_count'] ?? 0));
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Дашборд — Форум лабораторных инноваций Московской области — 2026</title>
<style>
:root{--green:#214f3b;--green2:#2e6c51;--ink:#173126;--muted:#6b7c73;--paper:#f4f7f5;--line:#dbe6df;--white:#fff;--warn:#b98720}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font-family:Arial,sans-serif}.wrap{max-width:1440px;margin:0 auto;padding:26px}.top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:22px}.eyebrow{font-size:11px;letter-spacing:.11em;text-transform:uppercase;color:#71837a}.top h1{margin:7px 0 5px;font-size:31px}.top p{margin:0;color:var(--muted)}.actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end}.pill{background:#e9f2ed;color:var(--green);padding:8px 11px;border-radius:999px;font-size:12px;font-weight:700}.logout{color:var(--muted);text-decoration:none;font-size:13px}.action-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:10px;background:var(--green);color:#fff;text-decoration:none;border:0;font:inherit;font-size:12px;font-weight:700;cursor:pointer}.action-btn.secondary{background:#e9f2ed;color:var(--green)}.cards{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:18px}.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;box-shadow:0 8px 30px rgba(28,67,50,.04)}.card span{display:block;color:var(--muted);font-size:12px;margin-bottom:7px}.card strong{font-size:30px}.card small{display:block;color:var(--muted);font-size:11px;margin-top:6px}.progress{height:7px;background:#e9efeb;border-radius:999px;margin-top:11px;overflow:hidden}.progress i{display:block;height:100%;background:var(--green2);border-radius:999px}.brief-panel{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;margin-bottom:18px;background:linear-gradient(135deg,#fff,#f2f8f5);border-color:#b9d7c9}.brief-panel h2{margin:0 0 7px;font-size:18px}.brief-text{margin:0;color:#365548;font-size:14px;line-height:1.58;white-space:pre-line}.brief-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:16px;margin-bottom:18px}.panel{background:#fff;border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:0 8px 30px rgba(28,67,50,.04)}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:14px}.panel h2{margin:0;font-size:19px}.muted{color:var(--muted);font-size:12px}.org-list{display:grid;gap:10px}.org-row{display:grid;grid-template-columns:minmax(180px,1fr) 2.2fr 64px;gap:13px;align-items:center}.org-name{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.bar{height:9px;background:#edf2ef;border-radius:999px;overflow:hidden}.bar i{display:block;height:100%;background:linear-gradient(90deg,var(--green),#4c8a6b);border-radius:999px}.org-num{text-align:right;font-size:13px;font-weight:700}.table-wrap{overflow:auto;max-height:660px;border:1px solid #e6ece8;border-radius:12px}table{width:100%;border-collapse:collapse;font-size:13px}th{position:sticky;top:0;background:#f6f9f7;color:#66796f;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;padding:11px 12px;border-bottom:1px solid var(--line);z-index:1}td{padding:11px 12px;border-bottom:1px solid #edf1ef;vertical-align:top}tr:last-child td{border-bottom:0}.tag{display:inline-flex;padding:5px 8px;border-radius:999px;font-size:11px;font-weight:700;background:#eef4f1;color:var(--green)}.tag.wait{background:#fff4d9;color:#8a6511}.tag.cancel{background:#f5eeee;color:#8a4b4b}.filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}.filters input,.filters select{height:38px;border:1px solid #d4e0d9;border-radius:9px;background:#fff;padding:0 10px;font:inherit;font-size:13px}.filters input{min-width:270px}.org-table{width:100%}.legend{display:flex;gap:12px;flex-wrap:wrap;color:var(--muted);font-size:11px;margin-top:12px}.mobile-note{display:none}.scanner{margin-bottom:18px;border-color:#b9d7c9;background:linear-gradient(135deg,#fff,#f1f8f4)}.scanner-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,.45fr);gap:20px;align-items:end}.scan-form label{display:block;font-size:13px;font-weight:700;margin-bottom:8px}.scan-row{display:flex;gap:10px}.scan-row input{min-width:0;flex:1;height:52px;border:2px solid #8fb6a3;border-radius:12px;padding:0 15px;font:inherit;font-size:17px;background:#fff}.scan-row input:focus{outline:0;border-color:var(--green);box-shadow:0 0 0 4px rgba(33,79,59,.1)}.scan-row button,.delete-btn{border:0;border-radius:11px;background:var(--green);color:#fff;padding:0 19px;font:inherit;font-weight:700;cursor:pointer}.scan-help{margin:9px 0 0;color:var(--muted);font-size:12px;line-height:1.5}.scan-status{min-height:92px;border-radius:14px;padding:16px;background:#eef4f1;display:flex;flex-direction:column;justify-content:center}.scan-status.success{background:#e6f6ec;color:#175d36}.scan-status.warning{background:#fff4d9;color:#7b5a0c}.scan-status.error{background:#fff0ef;color:#8d302b}.scan-status strong{font-size:18px}.scan-status span{margin-top:5px;font-size:13px;line-height:1.45}.test-panel{margin-top:18px}.test-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}.test-actions label{display:flex;align-items:center;gap:6px;font-size:12px;color:#6b7c73}.delete-btn{min-height:34px;background:#8c3b38;padding:7px 11px;font-size:12px}.empty-note{padding:18px;color:var(--muted);font-size:13px}.global-flash{margin-bottom:18px}.global-flash code{font-size:12px}.test-org{color:#8a6511}@media(max-width:1100px){.cards{grid-template-columns:repeat(3,1fr)}.grid{grid-template-columns:1fr}.scanner-layout{grid-template-columns:1fr}.brief-panel{grid-template-columns:1fr}.brief-actions{justify-content:flex-start}}@media(max-width:700px){.wrap{padding:15px}.top{display:block}.actions{margin-top:12px;justify-content:flex-start}.cards{grid-template-columns:repeat(2,1fr)}.card strong{font-size:25px}.org-row{grid-template-columns:minmax(120px,1fr) 1.4fr 42px}.filters input{min-width:100%;width:100%}.panel{padding:14px}.mobile-note{display:block}.top h1{font-size:26px}.scan-row{flex-direction:column}.scan-row button{min-height:48px}.scanner-layout{gap:13px}.scan-status{min-height:auto}.test-actions{align-items:flex-start;flex-direction:column}.delete-btn{width:100%}.brief-actions{display:grid;grid-template-columns:1fr 1fr}.action-btn{width:100%}}@media(max-width:430px){.cards{grid-template-columns:1fr 1fr}.card{padding:14px}.brief-actions{grid-template-columns:1fr}}
</style></head>
<body><main class="wrap">
<header class="top"><div><div class="eyebrow">Референс-центр лабораторной службы Московской области</div><h1>Регистрация · Форум 2026</h1><p>7 октября 2026 · данные обновляются при перезагрузке страницы</p></div><div class="actions"><a class="action-btn" href="/dashboard/export.php">Скачать Excel</a><a class="action-btn secondary" href="/dashboard/questions.php">Вопросы спикерам</a><span class="pill">Тестовые записи исключены</span><a class="logout" href="?logout=1">Выйти</a></div></header>
<section class="panel scanner" id="scanner">
<div class="scanner-layout"><div><div class="panel-head"><div><h2>Сканирование QR на входе</h2><p class="muted">Подходит USB-сканер, ручной ввод кода и полная ссылка из QR-билета.</p></div><span class="pill">Очные участники</span></div>
<form class="scan-form" method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?= h($dashboardCsrf) ?>"><input type="hidden" name="action" value="scan"><label for="scan">QR-код, ссылка или код участника</label><div class="scan-row"><input id="scan" name="scan" type="text" inputmode="text" autocapitalize="characters" placeholder="Наведите сканер или введите LE1234ABCD" autofocus required><button type="submit">Отметить вход</button></div><p class="scan-help">После успешного сканирования поле снова получает фокус. Повторный QR не создаёт второе посещение.</p></form></div>
<?php if ($dashboardFlash): ?><div class="scan-status <?= h((string)($dashboardFlash['type'] ?? '')) ?> global-flash" role="status"><strong><?= h((string)($dashboardFlash['title'] ?? '')) ?></strong><span><?= h((string)($dashboardFlash['message'] ?? '')) ?><?php if (!empty($dashboardFlash['code'])): ?><br><code><?= h((string)$dashboardFlash['code']) ?></code><?php endif; ?><?php if (!empty($dashboardFlash['time'])): ?> · <?= h((string)$dashboardFlash['time']) ?><?php endif; ?></span></div><?php else: ?><div class="scan-status"><strong>Готово к сканированию</strong><span>Отсканируйте QR с телефона участника или распечатанного бейджа.</span></div><?php endif; ?></div>
</section>
<section class="cards">
<div class="card"><span>Всего подтверждено</span><strong><?= $offlineConfirmed + $onlineConfirmed ?></strong><small>очно + онлайн</small></div>
<div class="card"><span>Очно</span><strong><?= $offlineConfirmed ?></strong><small>из <?= $publicLimit ?> публичных мест · осталось <?= $remaining ?></small><div class="progress"><i style="width:<?= $fillPct ?>%"></i></div></div>
<div class="card"><span>Онлайн</span><strong><?= $onlineConfirmed ?></strong><small>подтверждено</small></div>
<div class="card"><span>Лист ожидания</span><strong><?= $waitlist ?></strong><small>на очное участие</small></div>
<div class="card"><span>Организаций</span><strong><?= $orgCount ?></strong><small>с подтверждёнными участниками</small></div>
<div class="card"><span>Факт участия</span><strong><?= $checkedIn + $onlinePresent ?></strong><small><?= $checkedIn ?> очно · <?= $onlinePresent ?> онлайн ≥15 мин</small></div>
</section>
<section class="panel brief-panel" id="leadership-brief"><div><h2>Короткая сводка для руководства</h2><p class="brief-text" id="briefText"><?= h($leadershipBrief) ?></p></div><div class="brief-actions"><button class="action-btn" type="button" id="copyBrief">Скопировать сводку</button><a class="action-btn secondary" href="/dashboard/export.php">Excel по текущей ситуации</a></div></section>
<section class="grid">
<div class="panel"><div class="panel-head"><h2>Регистрации по организациям</h2><span class="muted"><?= $orgCount ?> организаций</span></div><div class="org-list">
<?php if (!$organizations): ?><div class="muted">Пока нет регистраций.</div><?php endif; ?>
<?php foreach (array_slice($organizations, 0, 18) as $org): $confirmedForOrg = (int)($org['confirmed_count'] ?? 0); if ($confirmedForOrg <= 0) continue; $width = round(($confirmedForOrg / $maxOrg) * 100); ?>
<div class="org-row" title="<?= h($org['organization']) ?>"><div class="org-name"><?= h($org['organization']) ?></div><div class="bar"><i style="width:<?= $width ?>%"></i></div><div class="org-num"><?= $confirmedForOrg ?></div></div>
<?php endforeach; ?></div><div class="legend">Топ-18 по числу подтверждённых регистраций. Полная детализация — справа и в Excel.</div></div>
<div class="panel"><div class="panel-head"><h2>Разрез по организациям</h2><span class="muted">очно / онлайн / ожидание / факт</span></div><div class="table-wrap" style="max-height:430px"><table class="org-table"><thead><tr><th>Организация</th><th>Подтв.</th><th>Очно</th><th>Онлайн</th><th>Ожидание</th><th>Пришли</th><th>Онлайн ≥15м</th></tr></thead><tbody><?php foreach ($organizations as $org): ?><tr><td><strong><?= h($org['organization']) ?></strong></td><td><?= (int)$org['confirmed_count'] ?></td><td><?= (int)$org['offline_count'] ?></td><td><?= (int)$org['online_count'] ?></td><td><?= (int)$org['waitlist_count'] ?></td><td><?= (int)$org['checked_in_count'] ?></td><td><?= (int)$org['online_present_count'] ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>
<section class="panel"><div class="panel-head"><h2>Участники</h2><span class="muted">последние <?= count($participants) ?> записей</span></div><div class="filters"><input id="q" type="search" placeholder="Поиск по ФИО, организации, должности, коду"><select id="fmt"><option value="">Все форматы</option><option value="offline">Очно</option><option value="online">Онлайн</option></select><select id="sts"><option value="">Все статусы</option><option value="confirmed">Подтверждено</option><option value="waitlist">Лист ожидания</option><option value="cancelled">Отменено</option></select></div><div class="table-wrap"><table id="people"><thead><tr><th>Участник</th><th>Организация / должность</th><th>Формат</th><th>Статус</th><th>Регистрация</th><th>Факт участия</th></tr></thead><tbody>
<?php foreach ($participants as $p): $present = $p['participation_format']==='offline' ? ($p['check_in_at'] ? 'Пришёл' : '—') : ((int)$p['online_watch_seconds']>=900 ? 'Онлайн ≥15 мин' : ((int)$p['online_watch_seconds']>0 ? round((int)$p['online_watch_seconds']/60).' мин' : '—')); ?>
<tr data-format="<?= h($p['participation_format']) ?>" data-status="<?= h($p['registration_status']) ?>" data-search="<?= h(mb_strtolower($p['full_name'].' '.$p['organization'].' '.$p['position'].' '.$p['participant_code'])) ?>"><td><strong><?= h($p['full_name']) ?></strong><div class="muted"><?= h($p['participant_code']) ?></div></td><td><strong><?= h($p['organization']) ?></strong><div class="muted"><?= h($p['position']) ?></div></td><td><span class="tag"><?= $p['participation_format']==='offline'?'Очно':'Онлайн' ?></span></td><td><span class="tag <?= $p['registration_status']==='waitlist'?'wait':($p['registration_status']==='cancelled'?'cancel':'') ?>"><?= $p['registration_status']==='confirmed'?'Подтверждено':($p['registration_status']==='waitlist'?'Ожидание':'Отменено') ?></span></td><td><?= h(date('d.m.Y H:i', strtotime($p['created_at']))) ?></td><td><?= h($present) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
<section class="panel test-panel" id="test-records"><div class="panel-head"><div><h2>Тестовые записи</h2><p class="muted">Не входят ни в KPI, ни в сводку, ни в Excel-выгрузку.</p></div><span class="pill"><?= count($testRecords) ?> записей</span></div>
<?php if (!$testRecords): ?><div class="empty-note">Тестовых записей в базе нет.</div><?php else: ?><div class="table-wrap" style="max-height:420px"><table><thead><tr><th>Участник</th><th>Организация</th><th>Формат</th><th>Создан</th><th>Действие</th></tr></thead><tbody><?php foreach ($testRecords as $testRecord): ?><tr><td><strong><?= h($testRecord['full_name']) ?></strong><div class="muted"><?= h($testRecord['participant_code']) ?></div></td><td class="test-org"><?= h($testRecord['organization']) ?></td><td><?= $testRecord['participation_format']==='offline'?'Очно':'Онлайн' ?></td><td><?= h(date('d.m.Y H:i', strtotime($testRecord['created_at']))) ?></td><td><form class="test-actions" method="post"><input type="hidden" name="csrf" value="<?= h($dashboardCsrf) ?>"><input type="hidden" name="action" value="delete_test"><input type="hidden" name="participant_id" value="<?= (int)$testRecord['id'] ?>"><label><input type="checkbox" name="confirm" value="yes" required> подтверждаю удаление</label><button class="delete-btn" type="submit">Удалить тестовую запись</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
</main><script>
(() => {
    const q=document.getElementById('q'), f=document.getElementById('fmt'), s=document.getElementById('sts'), scan=document.getElementById('scan'), rows=[...document.querySelectorAll('#people tbody tr')];
    function apply(){const text=(q.value||'').trim().toLowerCase(); rows.forEach(r=>{const okText=!text||r.dataset.search.includes(text); const okF=!f.value||r.dataset.format===f.value; const okS=!s.value||r.dataset.status===s.value; r.hidden=!(okText&&okF&&okS);});}
    [q,f,s].forEach(el=>el.addEventListener('input',apply));
    if(scan){scan.focus();scan.select();}

    const copyButton=document.getElementById('copyBrief'), brief=document.getElementById('briefText');
    if(copyButton&&brief){copyButton.addEventListener('click',async()=>{const text=brief.textContent.trim();let ok=false;try{await navigator.clipboard.writeText(text);ok=true;}catch(e){const area=document.createElement('textarea');area.value=text;area.setAttribute('readonly','');area.style.position='fixed';area.style.opacity='0';document.body.appendChild(area);area.select();ok=document.execCommand('copy');area.remove();}const old=copyButton.textContent;copyButton.textContent=ok?'Скопировано':'Не удалось скопировать';setTimeout(()=>copyButton.textContent=old,1600);});}

    setInterval(()=>{const active=document.activeElement;if(document.hidden||(active&&active!==scan&&active.matches('input,select,textarea'))||(scan&&scan.value))return;location.reload();},60000);
})();
</script></body></html>
