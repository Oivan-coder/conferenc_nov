<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const RUN_KEY = 'dup-notice-20260910-7f31';
const LOCK_PATH = '/home/c/cx314477/public_html/.private/duplicate_notice_20260910.lock';

require_once __DIR__ . '/smtp-mailer.php';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function shell(string $title, string $body): string {
    return '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#173126;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f4;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#fff;border:1px solid #dfe8e2;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:#214f3b;color:#fff;padding:26px 28px;"><div style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.82;">Референс-центр лабораторной службы Московской области</div><div style="font-size:24px;font-weight:700;margin-top:8px;">' . $title . '</div></td></tr>'
        . '<tr><td style="padding:28px;">' . $body . '</td></tr>'
        . '<tr><td style="padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">Если возникнут вопросы, напишите нам: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if (!hash_equals(RUN_KEY, (string)($_GET['k'] ?? ''))) respond(404, ['ok' => false, 'error' => 'not_found']);
if (is_file(LOCK_PATH)) respond(409, ['ok' => false, 'error' => 'already_sent']);

$codes = ['LE0C3C15C4', 'LE2FFB9CD3', 'LE82178C5B'];

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('db_unavailable');

    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("SELECT participant_code, full_name, email, participation_format, qr_token, online_token FROM participants WHERE event_id = ? AND registration_status = 'confirmed' AND participant_code IN ($placeholders)");
    $stmt->execute(array_merge([EVENT_ID], $codes));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $byCode = [];
    foreach ($rows as $row) $byCode[(string)$row['participant_code']] = $row;

    $results = [];
    $allOk = true;

    foreach ($codes as $code) {
        $p = $byCode[$code] ?? null;
        if (!$p || empty($p['email'])) {
            $results[$code] = 'not_found';
            $allOk = false;
            continue;
        }

        $isOnline = (($p['participation_format'] ?? '') === 'online');
        $token = $isOnline ? (string)($p['online_token'] ?? '') : (string)($p['qr_token'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $results[$code] = 'invalid_token';
            $allOk = false;
            continue;
        }

        $name = htmlspecialchars((string)$p['full_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ticketUrl = 'https://rclsmo.ru/participant.php?t=' . rawurlencode($token);
        $safeTicketUrl = htmlspecialchars($ticketUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $name . '</strong>.</p>'
            . '<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">Мы заметили повторную регистрацию на Форум лабораторных инноваций Московской области 2026 года. Чтобы в системе не было дублей, оставили активной одну регистрацию.</p>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:20px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Ваш актуальный код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
            . '<p style="font-size:15px;line-height:1.65;margin:0 0 20px;">Пожалуйста, используйте именно этот билет. Вторая регистрация удалена и больше не используется.</p>'
            . '<div style="text-align:center;margin:24px 0 8px;"><a href="' . $safeTicketUrl . '" style="display:inline-block;background:#214f3b;color:#fff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 20px;border-radius:9px;">Открыть актуальный билет</a></div>';

        $subject = 'Актуальный билет — Форум лабораторных инноваций Московской области — 2026';
        $ok = sendConfiguredMail((string)$p['email'], $subject, shell('Актуальный билет участника', $body));
        $results[$code] = $ok ? 'sent' : 'send_failed';
        if (!$ok) $allOk = false;
    }

    if ($allOk) file_put_contents(LOCK_PATH, date(DATE_ATOM));
    respond($allOk ? 200 : 207, ['ok' => $allOk, 'results' => $results]);
} catch (Throwable $e) {
    error_log('Duplicate notice sender failed: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'internal_error']);
}
