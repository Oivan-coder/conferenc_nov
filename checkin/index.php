<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const PIN_PATH = '/home/c/cx314477/public_html/.private/checkin_pin';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function extractIdentifier(string $raw): array {
    $raw = trim($raw);
    if (preg_match('/^[a-f0-9]{64}$/i', $raw)) return ['token', strtolower($raw)];
    if (preg_match('/^LE[A-F0-9]{8}$/i', $raw)) return ['code', strtoupper($raw)];
    if (filter_var($raw, FILTER_VALIDATE_URL)) {
        $query = parse_url($raw, PHP_URL_QUERY);
        if (is_string($query)) {
            parse_str($query, $params);
            $token = strtolower(trim((string)($params['t'] ?? '')));
            if (preg_match('/^[a-f0-9]{64}$/', $token)) return ['token', $token];
        }
    }
    return ['', ''];
}

if (isset($_POST['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /checkin/');
    exit;
}

$pinConfigured = is_readable(PIN_PATH) && trim((string)file_get_contents(PIN_PATH)) !== '';
$loginError = '';

if (!($_SESSION['checkin_auth'] ?? false) && isset($_POST['pin'])) {
    $expected = $pinConfigured ? trim((string)file_get_contents(PIN_PATH)) : '';
    $provided = trim((string)$_POST['pin']);
    if ($expected !== '' && hash_equals($expected, $provided)) {
        session_regenerate_id(true);
        $_SESSION['checkin_auth'] = true;
        header('Location: /checkin/');
        exit;
    }
    sleep(1);
    $loginError = 'Неверный PIN.';
}

$authorized = (bool)($_SESSION['checkin_auth'] ?? false);
$result = null;
$resultType = '';

if ($authorized && isset($_POST['scan'])) {
    [$kind, $value] = extractIdentifier((string)$_POST['scan']);
    if ($kind === '') {
        $resultType = 'error';
        $result = ['message' => 'QR-код или код участника не распознан.'];
    } else {
        try {
            $pdo = require DB_CONFIG_PATH;
            if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
            $pdo->beginTransaction();

            if ($kind === 'token') {
                $stmt = $pdo->prepare('SELECT id, participant_code, full_name, position, organization, participation_format, check_in_at FROM participants WHERE qr_token = :value LIMIT 1 FOR UPDATE');
            } else {
                $stmt = $pdo->prepare('SELECT id, participant_code, full_name, position, organization, participation_format, check_in_at FROM participants WHERE participant_code = :value LIMIT 1 FOR UPDATE');
            }
            $stmt->execute([':value' => $value]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$participant) {
                $pdo->rollBack();
                $resultType = 'error';
                $result = ['message' => 'Участник не найден.'];
            } else {
                $already = $participant['check_in_at'] !== null;
                if (!$already) {
                    $update = $pdo->prepare('UPDATE participants SET check_in_at = NOW() WHERE id = :id');
                    $update->execute([':id' => $participant['id']]);
                    $participant['check_in_at'] = date('Y-m-d H:i:s');
                }
                $pdo->commit();
                $resultType = $already ? 'warning' : 'success';
                $result = ['participant' => $participant, 'already' => $already];
            }
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            $resultType = 'error';
            $result = ['message' => 'Ошибка регистрации прихода. Проверьте БД и повторите.'];
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Регистрация участников — РЦЛС МО</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6f4;color:#173126;font-family:Arial,sans-serif}.wrap{max-width:760px;margin:0 auto;padding:28px 16px}.card{background:#fff;border:1px solid #dfe8e2;border-radius:18px;overflow:hidden;box-shadow:0 12px 36px rgba(25,57,43,.08)}.head{background:#214f3b;color:#fff;padding:26px 28px}.eyebrow{font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.82}.head h1{font-size:27px;margin:7px 0 0}.body{padding:28px}.muted{color:#64776e;line-height:1.55}.field{width:100%;font-size:22px;padding:16px 18px;border:2px solid #cad9d1;border-radius:12px;outline:none}.field:focus{border-color:#214f3b}.btn{border:0;border-radius:10px;padding:13px 18px;background:#214f3b;color:#fff;font-weight:700;font-size:15px;cursor:pointer}.btn.secondary{background:#e9efec;color:#214f3b}.row{display:flex;gap:10px;margin-top:12px}.result{margin-top:22px;border-radius:14px;padding:22px}.result.success{background:#e6f5eb;border:1px solid #a9d8b8}.result.warning{background:#fff5d9;border:1px solid #e4c96b}.result.error{background:#fde9e7;border:1px solid #e7aaa4}.status{font-size:22px;font-weight:800;margin-bottom:14px}.name{font-size:26px;font-weight:800;margin-bottom:6px}.meta{font-size:16px;line-height:1.6}.code{font-size:21px;font-weight:800;letter-spacing:.06em;margin-top:12px}.login{max-width:430px;margin:80px auto}.login .body{padding:30px}.small{font-size:13px}.topline{display:flex;align-items:center;justify-content:space-between;gap:16px}.logout{margin:0}.logout button{border:0;background:transparent;color:#fff;opacity:.85;cursor:pointer}.notice{padding:14px 16px;border-radius:10px;background:#fde9e7;margin-bottom:14px}
</style>
</head>
<body>
<?php if (!$authorized): ?>
<div class="wrap login"><div class="card"><div class="head"><div class="eyebrow">РЦЛС МО</div><h1>Стойка регистрации</h1></div><div class="body">
<?php if (!$pinConfigured): ?><div class="notice">PIN ещё не настроен на сервере.</div><?php endif; ?>
<?php if ($loginError): ?><div class="notice"><?= h($loginError) ?></div><?php endif; ?>
<form method="post" autocomplete="off"><label for="pin"><strong>PIN оператора</strong></label><p class="muted small">Введите PIN для доступа к сканированию участников.</p><input class="field" id="pin" name="pin" type="password" inputmode="numeric" autocomplete="off" autofocus required><div class="row"><button class="btn" type="submit">Войти</button></div></form>
</div></div></div>
<?php else: ?>
<div class="wrap"><div class="card"><div class="head"><div class="topline"><div><div class="eyebrow">Форум лабораторных инноваций Московской области — 2026</div><h1>Регистрация участников</h1></div><form class="logout" method="post"><button name="logout" value="1" type="submit">Выйти</button></form></div></div><div class="body">
<form method="post" autocomplete="off" id="scanForm"><label for="scan"><strong>Сканируйте QR-код</strong></label><p class="muted">Курсор остаётся в поле. Поддерживается QR-ссылка, токен и код участника LE…</p><input class="field" id="scan" name="scan" type="text" autocomplete="off" autofocus required><div class="row"><button class="btn" type="submit">Отметить приход</button></div></form>
<?php if ($result): ?><div class="result <?= h($resultType) ?>">
<?php if ($resultType === 'success' || $resultType === 'warning'): $p=$result['participant']; ?>
<div class="status"><?= $resultType === 'success' ? '✓ Участник отмечен' : '⚠ Уже был отмечен ранее' ?></div><div class="name"><?= h($p['full_name']) ?></div><div class="meta"><?= h($p['organization']) ?> · <?= h($p['position']) ?><br>Формат: <?= $p['participation_format']==='offline'?'Очный':'Онлайн' ?><br>Время входа: <?= h($p['check_in_at']) ?></div><div class="code"><?= h($p['participant_code']) ?></div>
<?php else: ?><div class="status">Ошибка</div><div class="meta"><?= h($result['message'] ?? 'Неизвестная ошибка') ?></div><?php endif; ?>
</div><?php endif; ?>
</div></div></div>
<script>const s=document.getElementById('scan');if(s){window.addEventListener('load',()=>{s.focus();s.select()});document.addEventListener('click',e=>{if(!e.target.closest('button,input'))s.focus()})}</script>
<?php endif; ?>
</body></html>