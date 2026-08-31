<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const CONSENT_VERSION = '2026-08-31';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

require_once __DIR__ . '/smtp-mailer.php';
require_once __DIR__ . '/invite-access.php';

function inviteRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function inviteCleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function inviteNormalizeName(string $value): string {
    return str_replace('ё', 'е', mb_strtolower(inviteCleanText($value)));
}

function inviteNormalizeEmail(string $value): string {
    return mb_strtolower(trim($value));
}

function inviteNormalizePhone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if (strlen($digits) === 10) return '7' . $digits;
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) return '7' . substr($digits, 1);
    return $digits;
}

function inviteIsTrue(mixed $value): bool {
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

function inviteValidPersonName(string $value, bool $required = true): bool {
    $value = inviteCleanText($value);
    if ($value === '') return !$required;
    if (mb_strlen($value) < 2 || mb_strlen($value) > 100) return false;
    return preg_match("/^[\p{L}\p{M}][\p{L}\p{M}'’\- ]*$/u", $value) === 1;
}

function inviteHasLetters(string $value, int $minimum = 2): bool {
    $matches = [];
    preg_match_all('/\p{L}/u', $value, $matches);
    return isset($matches[0]) && count($matches[0]) >= $minimum;
}

function inviteTicketUrl(string $qrToken): string {
    return 'https://rclsmo.ru/ticket.php?t=' . rawurlencode($qrToken);
}

function inviteQrImageUrl(string $qrToken): string {
    return 'https://rclsmo.ru/api/qr.php?t=' . rawurlencode($qrToken);
}

function inviteLiveUrl(string $onlineToken): string {
    return 'https://rclsmo.ru/live/?t=' . rawurlencode($onlineToken);
}

function inviteMailShell(string $title, string $body): string {
    return '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#173126;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f4;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dfe8e2;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:#214f3b;color:#ffffff;padding:26px 28px;"><div style="font-size:12px;line-height:1.5;letter-spacing:.06em;text-transform:uppercase;opacity:.82;">Референс-центр лабораторной службы Московской области</div><div style="font-size:24px;line-height:1.25;font-weight:700;margin-top:8px;">' . $title . '</div></td></tr>'
        . '<tr><td style="padding:28px;">' . $body . '</td></tr>'
        . '<tr><td style="padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}

function inviteSendOfflineMail(string $to, string $fullName, string $participantCode, string $qrToken): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeTicketUrl = htmlspecialchars(inviteTicketUrl($qrToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeQrUrl = htmlspecialchars(inviteQrImageUrl($qrToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = mb_encode_mimeheader('Подтверждение очной регистрации — Форум лабораторных инноваций Московской области — 2026', 'UTF-8', 'B');
    $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Ваше персональное приглашение использовано. Очное участие в Форуме лабораторных инноваций Московской области 2026 года подтверждено.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
        . '<div style="text-align:center;margin:18px 0 24px;"><img src="' . $safeQrUrl . '" width="260" height="260" alt="QR-код участника" style="display:block;width:260px;height:260px;max-width:100%;margin:0 auto;border:0;"><div style="font-size:13px;line-height:1.5;color:#607268;margin-top:10px;">Покажите этот QR-код на стойке регистрации.</div></div>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Дата:</strong> 7 октября 2026 года</p><p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Формат:</strong> Очное участие</p><p style="font-size:15px;line-height:1.7;margin:0 0 22px;"><strong>Место:</strong> Дом Правительства Московской области, Красногорск</p>'
        . '<div style="text-align:center;margin:24px 0 8px;"><a href="' . $safeTicketUrl . '" style="display:inline-block;background:#214f3b;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 20px;border-radius:9px;">Открыть билет с QR-кодом</a></div>';
    return sendConfiguredMail($to, $subject, inviteMailShell('Регистрация подтверждена', $body));
}

function inviteSendOnlineMail(string $to, string $fullName, string $participantCode, string $onlineToken): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeLiveUrl = htmlspecialchars(inviteLiveUrl($onlineToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = mb_encode_mimeheader('Подтверждение онлайн-регистрации — Форум лабораторных инноваций Московской области — 2026', 'UTF-8', 'B');
    $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Ваше персональное приглашение использовано. Онлайн-участие в Форуме лабораторных инноваций Московской области 2026 года подтверждено.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Дата:</strong> 7 октября 2026 года</p><p style="font-size:15px;line-height:1.7;margin:0 0 20px;"><strong>Формат:</strong> Онлайн-участие</p>'
        . '<div style="text-align:center;margin:26px 0 12px;"><a href="' . $safeLiveUrl . '" style="display:inline-block;background:#214f3b;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:14px 24px;border-radius:9px;">Открыть страницу трансляции</a></div>'
        . '<p style="font-size:13px;line-height:1.6;color:#607268;margin:18px 0 0;">Ссылка персональная и используется для учёта фактического онлайн-присутствия.</p>';
    return sendConfiguredMail($to, $subject, inviteMailShell('Онлайн-регистрация подтверждена', $body));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') inviteRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) inviteRespond(413, ['ok' => false, 'error' => 'request_too_large']);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) inviteRespond(403, ['ok' => false, 'error' => 'origin_not_allowed']);

$token = trim((string)($_GET['invite'] ?? ''));
if (!inviteTokenIsKnown($token)) inviteRespond(404, ['ok' => false, 'error' => 'not_found']);
if (!inviteTokenIsActive($token)) inviteRespond(410, ['ok' => false, 'error' => 'invite_expired']);
$tokenHash = inviteTokenHash($token);

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) inviteRespond(400, ['ok' => false, 'error' => 'invalid_json']);

$lastName = inviteCleanText((string)($data['lastName'] ?? ''));
$firstName = inviteCleanText((string)($data['firstName'] ?? ''));
$middleName = inviteCleanText((string)($data['middleName'] ?? ''));
$position = inviteCleanText((string)($data['position'] ?? ''));
$organization = inviteCleanText((string)($data['organization'] ?? ''));
$email = inviteNormalizeEmail((string)($data['email'] ?? ''));
$phone = inviteCleanText((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consent = inviteIsTrue($data['privacyConsent'] ?? false);
$policyAcknowledged = inviteIsTrue($data['policyAcknowledged'] ?? false);
$confirmDuplicate = inviteIsTrue($data['confirmDuplicate'] ?? false);
$honeypot = inviteCleanText((string)($data['website'] ?? ''));
if ($honeypot !== '') inviteRespond(200, ['ok' => true, 'accepted' => true]);

$phoneNormalized = inviteNormalizePhone($phone);
$errors = [];
if (!inviteValidPersonName($lastName)) $errors['lastName'] = 'invalid_name';
if (!inviteValidPersonName($firstName)) $errors['firstName'] = 'invalid_name';
if (!inviteValidPersonName($middleName, false)) $errors['middleName'] = 'invalid_name';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255 || !inviteHasLetters($position)) $errors['position'] = 'invalid_text';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255 || !inviteHasLetters($organization)) $errors['organization'] = 'invalid_text';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors['email'] = 'invalid_email';
if ($phone !== '' && (strlen($phoneNormalized) < 10 || strlen($phoneNormalized) > 15)) $errors['phone'] = 'invalid_ru_phone';
if (!in_array($format, ['offline', 'online'], true)) $errors['participationFormat'] = 'invalid';
if (!$consent) $errors['privacyConsent'] = 'required';
if (!$policyAcknowledged) $errors['policyAcknowledged'] = 'required';
if ($errors) inviteRespond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);

$fullName = implode(' ', array_values(array_filter([$lastName, $firstName, $middleName], static fn($v) => $v !== '')));

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    inviteEnsureSchema($pdo);

    if (inviteIsUsed($pdo, $tokenHash)) inviteRespond(410, ['ok' => false, 'error' => 'invite_used']);

    $duplicateReasons = [];
    $stmtEmail = $pdo->prepare('SELECT id FROM participants WHERE event_id = :event_id AND email_normalized = :email AND registration_status <> "cancelled" LIMIT 1');
    $stmtEmail->execute([':event_id' => EVENT_ID, ':email' => $email]);
    if ($stmtEmail->fetchColumn()) $duplicateReasons[] = 'email';
    if ($phoneNormalized !== '') {
        $stmtPhone = $pdo->prepare('SELECT id FROM participants WHERE event_id = :event_id AND phone_normalized = :phone AND registration_status <> "cancelled" LIMIT 1');
        $stmtPhone->execute([':event_id' => EVENT_ID, ':phone' => $phoneNormalized]);
        if ($stmtPhone->fetchColumn()) $duplicateReasons[] = 'phone';
    }
    $stmtPerson = $pdo->prepare('SELECT id FROM participants WHERE event_id = :event_id AND registration_status <> "cancelled" AND LOWER(REPLACE(last_name, "ё", "е")) = :last_name AND LOWER(REPLACE(first_name, "ё", "е")) = :first_name AND LOWER(REPLACE(COALESCE(middle_name, ""), "ё", "е")) = :middle_name AND LOWER(TRIM(organization)) = LOWER(TRIM(:organization)) LIMIT 1');
    $stmtPerson->execute([
        ':event_id' => EVENT_ID,
        ':last_name' => inviteNormalizeName($lastName),
        ':first_name' => inviteNormalizeName($firstName),
        ':middle_name' => inviteNormalizeName($middleName),
        ':organization' => $organization,
    ]);
    if ($stmtPerson->fetchColumn()) $duplicateReasons[] = 'same_person';
    $duplicateReasons = array_values(array_unique($duplicateReasons));
    if ($duplicateReasons && !$confirmDuplicate) inviteRespond(409, ['ok' => false, 'error' => 'possible_duplicate', 'reasons' => $duplicateReasons]);

    $pdo->beginTransaction();
    $pdo->prepare('INSERT IGNORE INTO registration_invite_usage (event_id, token_hash, participant_id, used_at, created_at) VALUES (:event_id, :token_hash, NULL, NULL, NOW())')->execute([':event_id' => EVENT_ID, ':token_hash' => $tokenHash]);
    if (inviteIsUsed($pdo, $tokenHash, true)) {
        $pdo->rollBack();
        inviteRespond(410, ['ok' => false, 'error' => 'invite_used']);
    }

    $settingsStmt = $pdo->prepare('SELECT hall_capacity FROM event_registration_settings WHERE event_id = :event_id LIMIT 1 FOR UPDATE');
    $settingsStmt->execute([':event_id' => EVENT_ID]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) throw new RuntimeException('Event registration settings missing');

    if ($format === 'offline') {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND participation_format = "offline" AND registration_status = "confirmed"');
        $countStmt->execute([':event_id' => EVENT_ID]);
        $confirmedOffline = (int)$countStmt->fetchColumn();
        if ($confirmedOffline >= (int)$settings['hall_capacity']) {
            $pdo->rollBack();
            inviteRespond(409, ['ok' => false, 'error' => 'offline_full', 'can_switch_online' => true, 'can_join_waitlist' => false]);
        }
    }

    $participantCode = null;
    $qrToken = null;
    $onlineToken = null;
    $insert = $pdo->prepare('INSERT INTO participants (event_id, participant_code, qr_token, online_token, last_name, first_name, middle_name, full_name, position, organization, email, email_normalized, phone, phone_normalized, participation_format, registration_status, privacy_consent, consent_version, consent_at, created_at) VALUES (:event_id, :participant_code, :qr_token, :online_token, :last_name, :first_name, :middle_name, :full_name, :position, :organization, :email, :email_normalized, :phone, :phone_normalized, :participation_format, "confirmed", 1, :consent_version, NOW(), NOW())');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $participantCode = 'LE' . strtoupper(bin2hex(random_bytes(4)));
        $qrToken = bin2hex(random_bytes(32));
        $onlineToken = $format === 'online' ? bin2hex(random_bytes(32)) : null;
        try {
            $insert->execute([
                ':event_id' => EVENT_ID,
                ':participant_code' => $participantCode,
                ':qr_token' => $qrToken,
                ':online_token' => $onlineToken,
                ':last_name' => $lastName,
                ':first_name' => $firstName,
                ':middle_name' => $middleName !== '' ? $middleName : null,
                ':full_name' => $fullName,
                ':position' => $position,
                ':organization' => $organization,
                ':email' => $email,
                ':email_normalized' => $email,
                ':phone' => $phone !== '' ? $phone : null,
                ':phone_normalized' => $phoneNormalized !== '' ? $phoneNormalized : null,
                ':participation_format' => $format,
                ':consent_version' => CONSENT_VERSION,
            ]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000' || $attempt === 4) throw $e;
            $participantCode = $qrToken = $onlineToken = null;
        }
    }

    if ($participantCode === null || $qrToken === null || ($format === 'online' && $onlineToken === null)) throw new RuntimeException('Unable to generate participant token');

    $participantId = (int)$pdo->lastInsertId();
    inviteMarkUsed($pdo, $tokenHash, $participantId);
    $pdo->commit();

    if ($format === 'online') {
        $emailSent = inviteSendOnlineMail($email, $fullName, $participantCode, (string)$onlineToken);
    } else {
        $emailSent = inviteSendOfflineMail($email, $fullName, $participantCode, $qrToken);
        if ($emailSent) $pdo->prepare('UPDATE participants SET qr_sent_at = NOW() WHERE participant_code = :code')->execute([':code' => $participantCode]);
    }

    inviteRespond(201, [
        'ok' => true,
        'participant_code' => $participantCode,
        'participation_format' => $format,
        'registration_status' => 'confirmed',
        'duplicate_override' => $duplicateReasons !== [],
        'email_sent' => $emailSent,
        'invite_mode' => true
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    inviteRespond(500, ['ok' => false, 'error' => 'server_error']);
}
