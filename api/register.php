<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const REGISTRATION_OPEN = false;
const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const CONSENT_VERSION = '2026-08-31';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

require_once __DIR__ . '/smtp-mailer.php';

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function normalizeName(string $value): string {
    return str_replace('ё', 'е', mb_strtolower(cleanText($value)));
}

function normalizeEmail(string $value): string {
    return mb_strtolower(trim($value));
}

function normalizePhone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if (strlen($digits) === 10) return '7' . $digits;
    if (strlen($digits) === 11 && $digits[0] === '8') return '7' . substr($digits, 1);
    return $digits;
}

function isTrue(mixed $value): bool {
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

function isAuthorizedTestRequest(): bool {
    $provided = trim((string)($_SERVER['HTTP_X_REGISTRATION_TEST'] ?? ''));
    if ($provided === '' || !is_readable(TEST_KEY_PATH)) return false;
    $expected = trim((string)file_get_contents(TEST_KEY_PATH));
    return $expected !== '' && hash_equals($expected, $provided);
}

function ticketUrl(string $qrToken): string {
    return 'https://rclsmo.ru/ticket.php?t=' . rawurlencode($qrToken);
}

function qrImageUrl(string $qrToken): string {
    return 'https://rclsmo.ru/api/qr.php?t=' . rawurlencode($qrToken);
}

function liveUrl(string $onlineToken): string {
    return 'https://rclsmo.ru/live/?t=' . rawurlencode($onlineToken);
}

function mailHeaders(): string {
    return implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: RCLSMO <info@rclsmo.ru>',
        'Reply-To: info@rclsmo.ru'
    ]);
}

function mailShell(string $title, string $body): string {
    return '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#173126;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f4;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dfe8e2;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:#214f3b;color:#ffffff;padding:26px 28px;"><div style="font-size:12px;line-height:1.5;letter-spacing:.06em;text-transform:uppercase;opacity:.82;">Референс-центр лабораторной службы Московской области</div><div style="font-size:24px;line-height:1.25;font-weight:700;margin-top:8px;">' . $title . '</div></td></tr>'
        . '<tr><td style="padding:28px;">' . $body . '</td></tr>'
        . '<tr><td style="padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}

function sendOfflineConfirmationEmail(string $to, string $fullName, string $participantCode, string $qrToken): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeTicketUrl = htmlspecialchars(ticketUrl($qrToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeQrUrl = htmlspecialchars(qrImageUrl($qrToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = mb_encode_mimeheader('Подтверждение очной регистрации — Форум лабораторных инноваций Московской области — 2026', 'UTF-8', 'B');

    $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Вы зарегистрированы для очного участия в Форуме лабораторных инноваций Московской области 2026 года.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
        . '<div style="text-align:center;margin:18px 0 24px;"><img src="' . $safeQrUrl . '" width="260" height="260" alt="QR-код участника" style="display:block;width:260px;height:260px;max-width:100%;margin:0 auto;border:0;"><div style="font-size:13px;line-height:1.5;color:#607268;margin-top:10px;">Покажите этот QR-код на стойке регистрации.</div></div>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Дата:</strong> 7 октября 2026 года</p><p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Формат:</strong> Очное участие</p><p style="font-size:15px;line-height:1.7;margin:0 0 22px;"><strong>Место:</strong> Дом Правительства Московской области, Красногорск</p>'
        . '<div style="text-align:center;margin:24px 0 8px;"><a href="' . $safeTicketUrl . '" style="display:inline-block;background:#214f3b;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 20px;border-radius:9px;">Открыть билет с QR-кодом</a></div>'
        . '<p style="font-size:12px;line-height:1.6;color:#738078;margin:18px 0 0;">Если QR-код не отображается в письме, откройте билет по кнопке выше.</p>';

    return sendConfiguredMail($to, $subject, mailShell('Регистрация подтверждена', $body));
}

function sendOnlineConfirmationEmail(string $to, string $fullName, string $participantCode, string $onlineToken): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeLiveUrl = htmlspecialchars(liveUrl($onlineToken), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = mb_encode_mimeheader('Подтверждение онлайн-регистрации — Форум лабораторных инноваций Московской области — 2026', 'UTF-8', 'B');

    $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Вы зарегистрированы для онлайн-участия в Форуме лабораторных инноваций Московской области 2026 года.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 7px;"><strong>Дата:</strong> 7 октября 2026 года</p><p style="font-size:15px;line-height:1.7;margin:0 0 20px;"><strong>Формат:</strong> Онлайн-участие</p>'
        . '<div style="text-align:center;margin:26px 0 12px;"><a href="' . $safeLiveUrl . '" style="display:inline-block;background:#214f3b;color:#ffffff;text-decoration:none;font-size:16px;font-weight:700;padding:14px 24px;border-radius:9px;">Открыть страницу трансляции</a></div>'
        . '<p style="font-size:13px;line-height:1.6;color:#607268;margin:18px 0 0;">Ссылка персональная. По ней система фиксирует фактическое онлайн-присутствие участника. Не пересылайте её другим людям.</p><p style="font-size:13px;line-height:1.6;color:#607268;margin:10px 0 0;">Трансляция станет доступна на этой странице в день мероприятия.</p>';

    return sendConfiguredMail($to, $subject, mailShell('Онлайн-регистрация подтверждена', $body));
}

function sendWaitlistEmail(string $to, string $fullName, string $participantCode): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $subject = mb_encode_mimeheader('Лист ожидания — Форум лабораторных инноваций Московской области — 2026', 'UTF-8', 'B');

    $body = '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Свободные места для очного участия закончились. Ваша заявка добавлена в лист ожидания.</p>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код заявки</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">' . $safeCode . '</div></td></tr></table>'
        . '<p style="font-size:14px;line-height:1.6;color:#607268;margin:0;">Это письмо не является подтверждением очного места. Если место освободится, мы направим отдельное подтверждение с QR-кодом.</p>';

    return sendConfiguredMail($to, $subject, mailShell('Вы в листе ожидания', $body));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(405, ['ok' => false, 'error' => 'method_not_allowed']);

$isTestRequest = isAuthorizedTestRequest();
$isValidatedGateway = ($_SERVER['RCLSMO_REGISTRATION_VALIDATED'] ?? '') === '1';
if (!$isTestRequest && !$isValidatedGateway) respond(404, ['ok' => false, 'error' => 'not_found']);
if (!REGISTRATION_OPEN && !$isTestRequest) respond(503, ['ok' => false, 'error' => 'registration_closed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) respond(413, ['ok' => false, 'error' => 'request_too_large']);

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    respond(403, ['ok' => false, 'error' => 'origin_not_allowed']);
}

$data = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($data)) respond(400, ['ok' => false, 'error' => 'invalid_json']);

if ($isTestRequest && $data === []) {
    $testFormat = (($_GET['mode'] ?? '') === 'online') ? 'online' : 'offline';
    $data = [
        'eventId' => EVENT_ID,
        'lastName' => 'Тестов',
        'firstName' => $testFormat === 'online' ? 'Онлайн' : 'Тест',
        'middleName' => 'Тестович',
        'position' => 'Врач КЛД',
        'organization' => 'Тестовая МО',
        'email' => 'info@rclsmo.ru',
        'phone' => $testFormat === 'online' ? '+79990000001' : '+79990000000',
        'participationFormat' => $testFormat,
        'privacyConsent' => true,
        'confirmDuplicate' => true
    ];
}

$eventId = trim((string)($data['eventId'] ?? ''));
$lastName = cleanText((string)($data['lastName'] ?? ''));
$firstName = cleanText((string)($data['firstName'] ?? ''));
$middleName = cleanText((string)($data['middleName'] ?? ''));
$position = cleanText((string)($data['position'] ?? ''));
$organization = cleanText((string)($data['organization'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$phone = cleanText((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consent = isTrue($data['privacyConsent'] ?? false);
$confirmDuplicate = isTrue($data['confirmDuplicate'] ?? false);
$waitlistIfFull = isTrue($data['waitlistIfFull'] ?? false);

$fullName = implode(' ', array_values(array_filter([$lastName, $firstName, $middleName], static fn($v) => $v !== '')));
$emailNormalized = normalizeEmail($email);
$phoneNormalized = normalizePhone($phone);

$errors = [];
if ($eventId !== EVENT_ID) $errors['eventId'] = 'invalid';
if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 100) $errors['lastName'] = 'invalid';
if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 100) $errors['firstName'] = 'invalid';
if ($middleName !== '' && mb_strlen($middleName) > 100) $errors['middleName'] = 'invalid';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255) $errors['position'] = 'invalid';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255) $errors['organization'] = 'invalid';
if (!filter_var($emailNormalized, FILTER_VALIDATE_EMAIL) || mb_strlen($emailNormalized) > 255) $errors['email'] = 'invalid';
if ($phone !== '' && (strlen($phoneNormalized) < 10 || strlen($phoneNormalized) > 15)) $errors['phone'] = 'invalid';
if (!in_array($format, ['offline', 'online'], true)) $errors['participationFormat'] = 'invalid';
if (!$consent) $errors['privacyConsent'] = 'required';
if ($errors) respond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $duplicateReasons = [];
    $stmtEmail = $pdo->prepare('SELECT id FROM participants WHERE event_id = :event_id AND email_normalized = :email AND registration_status <> "cancelled" LIMIT 1');
    $stmtEmail->execute([':event_id' => $eventId, ':email' => $emailNormalized]);
    if ($stmtEmail->fetchColumn()) $duplicateReasons[] = 'email';

    if ($phoneNormalized !== '') {
        $stmtPhone = $pdo->prepare('SELECT id FROM participants WHERE event_id = :event_id AND phone_normalized = :phone AND registration_status <> "cancelled" LIMIT 1');
        $stmtPhone->execute([':event_id' => $eventId, ':phone' => $phoneNormalized]);
        if ($stmtPhone->fetchColumn()) $duplicateReasons[] = 'phone';
    }

    $stmtPerson = $pdo->prepare(
        'SELECT id FROM participants
         WHERE event_id = :event_id
           AND registration_status <> "cancelled"
           AND LOWER(REPLACE(last_name, "ё", "е")) = :last_name
           AND LOWER(REPLACE(first_name, "ё", "е")) = :first_name
           AND LOWER(REPLACE(COALESCE(middle_name, ""), "ё", "е")) = :middle_name
           AND LOWER(TRIM(organization)) = LOWER(TRIM(:organization))
         LIMIT 1'
    );
    $stmtPerson->execute([
        ':event_id' => $eventId,
        ':last_name' => normalizeName($lastName),
        ':first_name' => normalizeName($firstName),
        ':middle_name' => normalizeName($middleName),
        ':organization' => $organization
    ]);
    if ($stmtPerson->fetchColumn()) $duplicateReasons[] = 'same_person';

    $duplicateReasons = array_values(array_unique($duplicateReasons));
    if ($duplicateReasons && !$confirmDuplicate) {
        respond(409, ['ok' => false, 'error' => 'possible_duplicate', 'reasons' => $duplicateReasons]);
    }

    $pdo->beginTransaction();
    $settingsStmt = $pdo->prepare('SELECT public_offline_limit, offline_registration_open, online_registration_open FROM event_registration_settings WHERE event_id = :event_id LIMIT 1 FOR UPDATE');
    $settingsStmt->execute([':event_id' => $eventId]);
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$settings) throw new RuntimeException('Event registration settings missing');

    $registrationStatus = 'confirmed';

    if ($format === 'offline') {
        if (!(bool)$settings['offline_registration_open']) {
            $pdo->rollBack();
            respond(409, ['ok' => false, 'error' => 'offline_closed', 'can_switch_online' => (bool)$settings['online_registration_open']]);
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM participants WHERE event_id = :event_id AND participation_format = "offline" AND registration_status = "confirmed"');
        $countStmt->execute([':event_id' => $eventId]);
        $confirmedOffline = (int)$countStmt->fetchColumn();
        $limit = (int)$settings['public_offline_limit'];

        if ($confirmedOffline >= $limit) {
            if (!$waitlistIfFull) {
                $pdo->rollBack();
                respond(409, [
                    'ok' => false,
                    'error' => 'offline_full',
                    'can_switch_online' => (bool)$settings['online_registration_open'],
                    'can_join_waitlist' => true
                ]);
            }
            $registrationStatus = 'waitlist';
        }
    } elseif (!(bool)$settings['online_registration_open']) {
        $pdo->rollBack();
        respond(409, ['ok' => false, 'error' => 'online_closed']);
    }

    $sql = 'INSERT INTO participants (
        event_id, participant_code, qr_token, online_token, last_name, first_name, middle_name, full_name,
        position, organization, email, email_normalized, phone, phone_normalized,
        participation_format, registration_status, privacy_consent, consent_version, consent_at, created_at
    ) VALUES (
        :event_id, :participant_code, :qr_token, :online_token, :last_name, :first_name, :middle_name, :full_name,
        :position, :organization, :email, :email_normalized, :phone, :phone_normalized,
        :participation_format, :registration_status, 1, :consent_version, NOW(), NOW()
    )';

    $stmt = $pdo->prepare($sql);
    $collisionStmt = $pdo->prepare(
        'SELECT id FROM participants
         WHERE participant_code = :participant_code
            OR qr_token = :qr_token
            OR (:online_token_guard IS NOT NULL AND online_token = :online_token_value)
         LIMIT 1'
    );
    $participantCode = null;
    $qrToken = null;
    $onlineToken = null;

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $participantCode = 'LE' . strtoupper(bin2hex(random_bytes(4)));
        $qrToken = bin2hex(random_bytes(32));
        $onlineToken = $format === 'online' ? bin2hex(random_bytes(32)) : null;

        try {
            $collisionStmt->execute([
                ':participant_code' => $participantCode,
                ':qr_token' => $qrToken,
                ':online_token_guard' => $onlineToken,
                ':online_token_value' => $onlineToken,
            ]);
            if ($collisionStmt->fetchColumn()) {
                $participantCode = null;
                $qrToken = null;
                $onlineToken = null;
                continue;
            }

            $stmt->execute([
                ':event_id' => $eventId,
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
                ':email_normalized' => $emailNormalized,
                ':phone' => $phone !== '' ? $phone : null,
                ':phone_normalized' => $phoneNormalized !== '' ? $phoneNormalized : null,
                ':participation_format' => $format,
                ':registration_status' => $registrationStatus,
                ':consent_version' => CONSENT_VERSION
            ]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000' || $attempt === 4) throw $e;
            $participantCode = null;
            $qrToken = null;
            $onlineToken = null;
        }
    }

    if ($participantCode === null || $qrToken === null || ($format === 'online' && $onlineToken === null)) {
        throw new RuntimeException('Unable to generate participant token');
    }

    $pdo->commit();

    if ($registrationStatus === 'waitlist') {
        $emailSent = sendWaitlistEmail($email, $fullName, $participantCode);
    } elseif ($format === 'online') {
        $emailSent = sendOnlineConfirmationEmail($email, $fullName, $participantCode, (string)$onlineToken);
    } else {
        $emailSent = sendOfflineConfirmationEmail($email, $fullName, $participantCode, $qrToken);
        if ($emailSent) {
            $pdo->prepare('UPDATE participants SET qr_sent_at = NOW() WHERE participant_code = :code')
                ->execute([':code' => $participantCode]);
        }
    }

    $response = [
        'ok' => true,
        'participant_code' => $participantCode,
        'participation_format' => $format,
        'registration_status' => $registrationStatus,
        'duplicate_override' => $duplicateReasons !== [],
        'email_sent' => $emailSent,
        'test_mode' => $isTestRequest
    ];

    if ($isTestRequest && $registrationStatus === 'confirmed') {
        if ($format === 'online') $response['live_url'] = liveUrl((string)$onlineToken);
        else $response['ticket_url'] = ticketUrl($qrToken);
    }

    respond(201, $response);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
