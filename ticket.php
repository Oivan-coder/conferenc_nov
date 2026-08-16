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
            $stmt = $pdo->prepare('SELECT participant_code, full_name, position, organization, participation_format FROM participants WHERE qr_token = :token LIMIT 1');
            $stmt->execute([':token' => $token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) {
        $participant = null;
    }
}

if (!$participant) http_response_code(404);

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
    <title><?= $participant ? 'Билет участника — Форум лабораторных инноваций 2026' : 'Билет не найден' ?></title>
    <link rel="icon" type="image/png" href="/images/favicon-32x32.png">
    <link rel="stylesheet" href="/css/conference-premium.css?v=20260816-01">
</head>
<body class="ticket-premium-page">
<div class="event-shell">
    <header class="event-brandbar">
        <div class="event-brandbar__eyebrow">Референс-центр лабораторной службы Московской области</div>
        <h1>Форум лабораторных инноваций 2026</h1>
    </header>

    <?php if ($participant): ?>
        <main class="event-panel ticket-layout">
            <section class="ticket-main">
                <div class="ticket-person">
                    <span class="event-chip">Очное участие · билет подтверждён</span>
                    <h2><?= h($participant['full_name']) ?></h2>
                    <p><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></p>
                </div>

                <div class="ticket-qrbox">
                    <div class="ticket-qrframe">
                        <img class="ticket-qr" src="/api/qr.php?t=<?= h($token) ?>" alt="QR-код участника">
                    </div>
                    <div class="ticket-code"><?= h($participant['participant_code']) ?></div>
                    <div class="ticket-hint">Покажите этот QR-код на стойке регистрации</div>
                </div>
            </section>

            <aside class="ticket-side">
                <span class="event-chip">7 октября 2026</span>
                <h3>Ваш электронный билет</h3>

                <div class="ticket-detail">
                    <span>Дата</span>
                    <strong>7 октября 2026 года</strong>
                </div>
                <div class="ticket-detail">
                    <span>Время начала регистрации</span>
                    <strong>09:30</strong>
                </div>
                <div class="ticket-detail">
                    <span>Место</span>
                    <strong>Дом Правительства Московской области, Красногорск</strong>
                </div>
                <div class="ticket-detail">
                    <span>Формат</span>
                    <strong><?= $participant['participation_format'] === 'offline' ? 'Очное участие' : 'Онлайн-участие' ?></strong>
                </div>

                <div class="ticket-note">Сохраните эту страницу или письмо с билетом. На площадке достаточно показать QR-код с экрана телефона.</div>
                <div class="ticket-contact">Вопросы по регистрации: <a href="mailto:info@rclsmo.ru">info@rclsmo.ru</a></div>
            </aside>
        </main>
    <?php else: ?>
        <main class="event-panel" style="padding:48px;text-align:center">
            <span class="event-chip">Ошибка</span>
            <h2 style="font-family:Georgia,serif;font-size:34px;margin:18px 0 8px">Билет не найден</h2>
            <p class="event-muted">Ссылка недействительна или регистрация была изменена.</p>
        </main>
    <?php endif; ?>
</div>
</body>
</html>
