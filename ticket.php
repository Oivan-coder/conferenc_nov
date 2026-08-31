<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';

$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = require DB_CONFIG_PATH;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT participant_code, full_name, position, organization, participation_format FROM participants WHERE qr_token = :token AND participation_format = "offline" AND registration_status = "confirmed" LIMIT 1');
            $stmt->execute([':token' => $token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) {
        $participant = null;
    }
}

if (!$participant) {
    http_response_code(404);
}

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= $participant ? 'Билет участника — Форум лабораторных инноваций Московской области — 2026' : 'Билет не найден' ?></title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f6f4;color:#173126;font-family:Arial,sans-serif}.wrap{max-width:620px;margin:0 auto;padding:28px 16px}.card{background:#fff;border:1px solid #dfe8e2;border-radius:18px;overflow:hidden;box-shadow:0 12px 36px rgba(25,57,43,.08)}.head{background:#214f3b;color:#fff;padding:28px}.eyebrow{font-size:12px;line-height:1.5;letter-spacing:.06em;text-transform:uppercase;opacity:.8}.head h1{font-size:26px;line-height:1.2;margin:8px 0 0}.body{padding:28px}.name{font-size:24px;font-weight:700;margin:0 0 6px}.muted{color:#63766d;line-height:1.5}.qr{display:block;width:300px;height:300px;max-width:100%;margin:26px auto 12px}.code{text-align:center;font-size:23px;font-weight:700;letter-spacing:.06em;color:#214f3b}.info{margin-top:26px;padding:18px;background:#f1f6f3;border-radius:12px}.info p{margin:6px 0;line-height:1.55}.hint{text-align:center;color:#63766d;font-size:13px;line-height:1.5;margin-top:12px}.error{text-align:center;padding:50px 28px}.error h1{font-size:24px}.footer{padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;color:#66776f;font-size:13px}.footer a{color:#214f3b}</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <?php if ($participant): ?>
            <div class="head"><div class="eyebrow">Референс-центр лабораторной службы Московской области</div><h1>Форум лабораторных инноваций Московской области — 2026</h1></div>
            <div class="body">
                <div class="name"><?= h($participant['full_name']) ?></div>
                <div class="muted"><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></div>
                <img class="qr" src="/api/qr.php?t=<?= h($token) ?>" alt="QR-код участника">
                <div class="code"><?= h($participant['participant_code']) ?></div>
                <div class="hint">Покажите этот QR-код на стойке регистрации.</div>
                <div class="info">
                    <p><strong>Дата:</strong> 7 октября 2026 года</p>
                    <p><strong>Место:</strong> Дом Правительства Московской области, Красногорск</p>
                    <p><strong>Формат:</strong> <?= $participant['participation_format'] === 'offline' ? 'Очное участие' : 'Онлайн-участие' ?></p>
                </div>
            </div>
            <div class="footer">По вопросам регистрации: <a href="mailto:info@rclsmo.ru">info@rclsmo.ru</a></div>
        <?php else: ?>
            <div class="error"><h1>Билет не найден</h1><p class="muted">Ссылка недействительна или регистрация была изменена.</p></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
