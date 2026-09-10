<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const TEST_ORGANIZATION = 'Тестовая МО';
const BADGE_ORG_PREFIX = '__BADGE_ORG__:';

if (empty($_SESSION['conference_dashboard_auth'])) {
    http_response_code(403);
    exit('Access denied');
}

if (empty($_SESSION['badge_seed_csrf'])) {
    $_SESSION['badge_seed_csrf'] = bin2hex(random_bytes(32));
}
$csrf = (string)$_SESSION['badge_seed_csrf'];

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generateParticipantCode(PDO $pdo): string {
    for ($i = 0; $i < 20; $i++) {
        $code = 'LE' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('SELECT 1 FROM participants WHERE participant_code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        if (!$stmt->fetchColumn()) return $code;
    }
    throw new RuntimeException('Could not generate participant code');
}

$cases = [
    [
        'last' => 'Иванов',
        'first' => 'Иван',
        'middle' => 'Ильич',
        'print_org' => 'РЦЛСМО',
        'email' => 'badge-test-01@tests.invalid',
    ],
    [
        'last' => 'Смирнова',
        'first' => 'Анна',
        'middle' => 'Сергеевна',
        'print_org' => 'ГБУЗ МО «Истринская клиническая больница»',
        'email' => 'badge-test-02@tests.invalid',
    ],
    [
        'last' => 'Александрова-Григорьева',
        'first' => 'Екатерина',
        'middle' => 'Константиновна',
        'print_org' => 'ГБУЗ МО «Московский областной онкологический диспансер»',
        'email' => 'badge-test-03@tests.invalid',
    ],
    [
        'last' => 'Константинопольский',
        'first' => 'Вячеслав',
        'middle' => 'Александрович',
        'print_org' => 'ГБУЗ МО МОНИКИ им. М. Ф. Владимирского',
        'email' => 'badge-test-04@tests.invalid',
    ],
    [
        'last' => 'Преображенский-Александровский',
        'first' => 'Максимилиан',
        'middle' => 'Константинович',
        'print_org' => 'ГБУЗ МО «Московский областной научно-исследовательский клинический институт детства»',
        'email' => 'badge-test-05@tests.invalid',
    ],
];

$result = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provided = (string)($_POST['csrf'] ?? '');
    if ($provided === '' || !hash_equals($csrf, $provided)) {
        http_response_code(403);
        exit('Invalid request');
    }

    try {
        $pdo = require DB_CONFIG_PATH;
        if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->beginTransaction();

        $find = $pdo->prepare('SELECT id, participant_code FROM participants WHERE event_id = :event AND email_normalized = :email LIMIT 1 FOR UPDATE');
        $update = $pdo->prepare('UPDATE participants SET last_name=:last, first_name=:first, middle_name=:middle, full_name=:full_name, position=:position, organization=:organization, participation_format="offline", registration_status="confirmed", registration_source="test", check_in_at=NULL, online_watch_seconds=0 WHERE id=:id');
        $insert = $pdo->prepare('INSERT INTO participants (event_id, participant_code, qr_token, online_token, last_name, first_name, middle_name, full_name, position, organization, email, email_normalized, phone, phone_normalized, participation_format, registration_status, registration_source, privacy_consent, consent_version, consent_at, created_at) VALUES (:event, :code, :qr, NULL, :last, :first, :middle, :full_name, :position, :organization, :email, :email_normalized, NULL, NULL, "offline", "confirmed", "test", 1, "badge-test-2026-09-10", NOW(), NOW())');

        foreach ($cases as $case) {
            $fullName = trim($case['last'] . ' ' . $case['first'] . ' ' . $case['middle']);
            $position = BADGE_ORG_PREFIX . $case['print_org'];
            $emailNormalized = mb_strtolower($case['email'], 'UTF-8');

            $find->execute([':event' => EVENT_ID, ':email' => $emailNormalized]);
            $existing = $find->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($existing) {
                $update->execute([
                    ':last' => $case['last'],
                    ':first' => $case['first'],
                    ':middle' => $case['middle'],
                    ':full_name' => $fullName,
                    ':position' => $position,
                    ':organization' => TEST_ORGANIZATION,
                    ':id' => $existing['id'],
                ]);
                $code = (string)$existing['participant_code'];
                $mode = 'обновлена';
            } else {
                $code = generateParticipantCode($pdo);
                $insert->execute([
                    ':event' => EVENT_ID,
                    ':code' => $code,
                    ':qr' => bin2hex(random_bytes(32)),
                    ':last' => $case['last'],
                    ':first' => $case['first'],
                    ':middle' => $case['middle'],
                    ':full_name' => $fullName,
                    ':position' => $position,
                    ':organization' => TEST_ORGANIZATION,
                    ':email' => $case['email'],
                    ':email_normalized' => $emailNormalized,
                ]);
                $mode = 'создана';
            }

            $result[] = ['name' => $fullName, 'code' => $code, 'org' => $case['print_org'], 'mode' => $mode];
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
        $error = 'Не удалось создать тестовые записи.';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Тестовые бейджи</title>
<style>
body{margin:0;background:#f4f7f5;color:#173126;font-family:Arial,sans-serif}.wrap{max-width:920px;margin:45px auto;padding:0 18px}.box{background:#fff;border:1px solid #dbe6df;border-radius:18px;padding:24px;box-shadow:0 8px 30px rgba(28,67,50,.05)}h1{margin:0 0 8px}.muted{color:#6b7c73;line-height:1.55}.btn{border:0;border-radius:11px;background:#214f3b;color:#fff;padding:12px 18px;font:inherit;font-weight:700;cursor:pointer}.ok{margin-top:20px;padding:16px;background:#e6f6ec;border-radius:12px}.err{margin-top:20px;padding:16px;background:#fff0ef;color:#8d302b;border-radius:12px}table{width:100%;border-collapse:collapse;margin-top:14px;font-size:14px}th,td{text-align:left;padding:10px;border-bottom:1px solid #e8eeea;vertical-align:top}code{font-size:13px}.back{display:inline-block;margin-top:18px;color:#214f3b;text-decoration:none;font-weight:700}
</style>
</head>
<body><main class="wrap"><section class="box">
<h1>5 тестовых очных бейджей</h1>
<p class="muted">Записи имеют источник <strong>test</strong> и системную организацию «Тестовая МО», поэтому не занимают реальные места и не входят в основную статистику. Для печати каждой записи используется отдельный тестовый текст организации разной длины.</p>
<?php if (!$result): ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button class="btn" type="submit">Создать / сбросить 5 тестовых записей</button></form>
<?php endif; ?>
<?php if ($error !== ''): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
<?php if ($result): ?><div class="ok"><strong>Готово. Все 5 записей сброшены в состояние «ещё не пришёл».</strong><table><thead><tr><th>ФИО</th><th>Код</th><th>Организация на бейдже</th></tr></thead><tbody><?php foreach ($result as $row): ?><tr><td><?= h($row['name']) ?></td><td><code><?= h($row['code']) ?></code></td><td><?= h($row['org']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<a class="back" href="/dashboard/#test-records">← Вернуться в дашборд</a>
</section></main></body></html>
