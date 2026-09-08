<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const TOKEN = 'rclsmo-resend-20260908-3f29';
const LOCK_PATH = '/home/c/cx314477/public_html/.private/resend_missed_20260908.lock';
const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';

if (!hash_equals(TOKEN, (string)($_GET['k'] ?? ''))) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}
if (is_file(LOCK_PATH)) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'already_sent']);
    exit;
}

require_once __DIR__ . '/smtp-mailer.php';

$targets = ['tkacheva@rgnkc.ru','nktndrugs@bk.ru','edmanvel209818@mail.ru'];

function shell(string $title, string $body): string {
    return '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#173126;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f4;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#fff;border:1px solid #dfe8e2;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:#214f3b;color:#fff;padding:26px 28px;"><div style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.82;">Референс-центр лабораторной службы Московской области</div><div style="font-size:24px;font-weight:700;margin-top:8px;">' . $title . '</div></td></tr>'
        . '<tr><td style="padding:28px;">' . $body . '</td></tr>'
        . '<tr><td style="padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('db_unavailable');

    $in = implode(',', array_fill(0, count($targets), '?'));
    $stmt = $pdo->prepare("SELECT email, full_name, participant_code, participation_format, qr_token, online_token, registration_status FROM participants WHERE email IN ($in)");
    $stmt->execute($targets);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byEmail = [];
    foreach ($rows as $row) $byEmail[mb_strtolower((string)$row['email'])] = $row;

    $results = [];
    $allOk = true;

    foreach ($targets as $email) {
        $key = mb_strtolower($email);
        $p = $byEmail[$key] ?? null;
        if (!$p || ($p['registration_status'] ?? '') !== 'confirmed') {
            $results[$email] = 'not_found_or_not_confirmed';
            $allOk = false;
            continue;
        }

        $name = htmlspecialchars((string)$p['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $code = htmlspecialchars((string)$p['participant_code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $isOnline = (($p['participation_format'] ?? '') === 'online');
        $token = $isOnline ? (string)$p['online_token'] : (string)$p['qr_token'];
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $results[$email] = 'invalid_token';
            $allOk = false;
            continue;
        }

        $participantUrl = 'https://rclsmo.ru/participant.php?t=' . rawurlencode($token);
        $calendarUrl = 'https://rclsmo.ru/calendar.php?t=' . rawurlencode($token);
        $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $name . '</strong>.</p>'
            . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Повторно направляем подтверждение вашей регистрации на Форум лабораторных инноваций Московской области 2026 года.</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $code . '</div></td></tr></table>'
            . '<p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Дата:</strong> 7 октября 2026 года</p>'
            . '<p style="font-size:15px;line-height:1.7;margin:0 0 22px;"><strong>Формат:</strong> ' . ($isOnline ? 'Онлайн-участие' : 'Очное участие') . '</p>'
            . '<div style="text-align:center;margin:24px 0 8px;"><a href="' . htmlspecialchars($participantUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#214f3b;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 20px;border-radius:9px;">Открыть мой билет</a></div>'
            . '<div style="text-align:center;margin:12px 0 8px;"><a href="' . htmlspecialchars($calendarUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#214f3b;text-decoration:underline;font-size:14px;font-weight:700;">Добавить форум в календарь</a></div>';

        $subject = 'Подтверждение регистрации — Форум лабораторных инноваций Московской области — 2026';
        $ok = sendConfiguredMail($email, $subject, shell('Регистрация подтверждена', $body));
        $results[$email] = $ok ? 'sent' : 'send_failed';
        if (!$ok) $allOk = false;
    }

    if ($allOk) @file_put_contents(LOCK_PATH, date(DATE_ATOM));
    http_response_code($allOk ? 200 : 207);
    echo json_encode(['ok' => $allOk, 'results' => $results], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Resend missed confirmations failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal_error']);
}
