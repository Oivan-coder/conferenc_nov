<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const REGISTRATION_OPEN = false;

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function normalizeName(string $value): string {
    $value = mb_strtolower(cleanText($value));
    return str_replace('ё', 'е', $value);
}

function normalizeEmail(string $value): string {
    return mb_strtolower(trim($value));
}

function normalizePhone(string $value): string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if (strlen($digits) === 10) {
        return '7' . $digits;
    }
    if (strlen($digits) === 11 && $digits[0] === '8') {
        return '7' . substr($digits, 1);
    }
    return $digits;
}

function sendConfirmationEmail(string $to, string $fullName, string $participantCode, string $format): bool {
    $safeName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeCode = htmlspecialchars($participantCode, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $formatText = $format === 'offline' ? 'Очное участие' : 'Онлайн-участие';
    $safeFormat = htmlspecialchars($formatText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $subjectText = 'Подтверждение регистрации — Форум лабораторных инноваций 2026';
    $subject = mb_encode_mimeheader($subjectText, 'UTF-8', 'B', "\r\n");

    $htmlBody = '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f4f7f5;font-family:Arial,sans-serif;color:#173126;">'
        . '<div style="max-width:620px;margin:0 auto;padding:28px 16px;">'
        . '<div style="background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #dfe8e2;">'
        . '<div style="background:#214f3b;color:#ffffff;padding:28px 30px;">'
        . '<div style="font-size:13px;letter-spacing:.08em;text-transform:uppercase;opacity:.8;">Референс-центр лабораторной службы Московской области</div>'
        . '<h1 style="font-size:24px;line-height:1.25;margin:10px 0 0;">Регистрация подтверждена</h1>'
        . '</div>'
        . '<div style="padding:30px;">'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">Здравствуйте, <strong>' . $safeName . '</strong>.</p>'
        . '<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Вы зарегистрированы на Форум лабораторных инноваций 2026.</p>'
        . '<div style="background:#f1f6f3;border-radius:12px;padding:20px;margin:0 0 22px;">'
        . '<div style="font-size:13px;color:#5d7468;margin-bottom:6px;">Код участника</div>'
        . '<div style="font-size:24px;font-weight:700;letter-spacing:.06em;color:#214f3b;">' . $safeCode . '</div>'
        . '</div>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 8px;"><strong>Дата:</strong> 7 октября 2026 года</p>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 8px;"><strong>Формат:</strong> ' . $safeFormat . '</p>'
        . '<p style="font-size:15px;line-height:1.7;margin:0 0 22px;"><strong>Место:</strong> Дом Правительства Московской области, Красногорск</p>'
        . '<p style="font-size:14px;line-height:1.6;color:#607268;margin:0;">QR-код и дополнительная организационная информация будут направлены отдельным письмом после завершения настройки регистрации.</p>'
        . '</div>'
        . '<div style="padding:18px 30px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">'
        . 'По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a>'
        . '</div>'
        . '</div></div></body></html>';

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: =?UTF-8?B?' . base64_encode('Референс-центр лабораторной службы МО') . '?= <info@rclsmo.ru>',
        'Reply-To: info@rclsmo.ru',
        'X-Content-Type-Options: nosniff'
    ];

    return mail($to, $subject, $htmlBody, implode("\r\n", $headers), '-finfo@rclsmo.ru');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if (!REGISTRATION_OPEN) {
    respond(503, ['ok' => false, 'error' => 'registration_closed']);
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
    respond(413, ['ok' => false, 'error' => 'request_too_large']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    respond(403, ['ok' => false, 'error' => 'origin_not_allowed']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    respond(400, ['ok' => false, 'error' => 'invalid_json']);
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
$consentRaw = $data['privacyConsent'] ?? false;
$consent = in_array($consentRaw, [true, 1, '1', 'true', 'on', 'yes'], true);
$confirmDuplicate = in_array($data['confirmDuplicate'] ?? false, [true, 1, '1', 'true', 'on', 'yes'], true);

$fullName = implode(' ', array_values(array_filter([$lastName, $firstName, $middleName], static fn($v) => $v !== '')));
$emailNormalized = normalizeEmail($email);
$phoneNormalized = normalizePhone($phone);

$errors = [];
if ($eventId !== 'forum-lab-innovations-2026-10-07') $errors['eventId'] = 'invalid';
if (mb_strlen($lastName) < 2 || mb_strlen($lastName) > 100) $errors['lastName'] = 'invalid';
if (mb_strlen($firstName) < 2 || mb_strlen($firstName) > 100) $errors['firstName'] = 'invalid';
if ($middleName !== '' && mb_strlen($middleName) > 100) $errors['middleName'] = 'invalid';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255) $errors['position'] = 'invalid';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255) $errors['organization'] = 'invalid';
if (!filter_var($emailNormalized, FILTER_VALIDATE_EMAIL) || mb_strlen($emailNormalized) > 255) $errors['email'] = 'invalid';
if ($phone !== '' && (strlen($phoneNormalized) < 10 || strlen($phoneNormalized) > 15)) $errors['phone'] = 'invalid';
if (!in_array($format, ['offline', 'online'], true)) $errors['participationFormat'] = 'invalid';
if (!$consent) $errors['privacyConsent'] = 'required';

if ($errors) {
    respond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);
}

try {
    $pdo = require '/home/c/cx314477/public_html/.private/db.php';
    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Database config invalid');
    }

    $duplicateReasons = [];

    $stmtEmail = $pdo->prepare('SELECT id FROM participants WHERE email_normalized = :email LIMIT 1');
    $stmtEmail->execute([':email' => $emailNormalized]);
    if ($stmtEmail->fetchColumn()) {
        $duplicateReasons[] = 'email';
    }

    if ($phoneNormalized !== '') {
        $stmtPhone = $pdo->prepare('SELECT id FROM participants WHERE phone_normalized = :phone LIMIT 1');
        $stmtPhone->execute([':phone' => $phoneNormalized]);
        if ($stmtPhone->fetchColumn()) {
            $duplicateReasons[] = 'phone';
        }
    }

    $stmtPerson = $pdo->prepare(
        'SELECT id FROM participants
         WHERE LOWER(REPLACE(last_name, "ё", "е")) = :last_name
           AND LOWER(REPLACE(first_name, "ё", "е")) = :first_name
           AND LOWER(REPLACE(COALESCE(middle_name, ""), "ё", "е")) = :middle_name
           AND LOWER(TRIM(organization)) = LOWER(TRIM(:organization))
         LIMIT 1'
    );
    $stmtPerson->execute([
        ':last_name' => normalizeName($lastName),
        ':first_name' => normalizeName($firstName),
        ':middle_name' => normalizeName($middleName),
        ':organization' => $organization
    ]);
    if ($stmtPerson->fetchColumn()) {
        $duplicateReasons[] = 'same_person';
    }

    $duplicateReasons = array_values(array_unique($duplicateReasons));
    if ($duplicateReasons && !$confirmDuplicate) {
        respond(409, [
            'ok' => false,
            'error' => 'possible_duplicate',
            'reasons' => $duplicateReasons
        ]);
    }

    $sql = 'INSERT INTO participants (
        participant_code, qr_token,
        last_name, first_name, middle_name, full_name,
        position, organization,
        email, email_normalized, phone, phone_normalized,
        participation_format, privacy_consent, consent_version, consent_at, created_at
    ) VALUES (
        :participant_code, :qr_token,
        :last_name, :first_name, :middle_name, :full_name,
        :position, :organization,
        :email, :email_normalized, :phone, :phone_normalized,
        :participation_format, 1, :consent_version, NOW(), NOW()
    )';

    $stmt = $pdo->prepare($sql);
    $participantCode = null;

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $participantCode = 'LE' . strtoupper(bin2hex(random_bytes(4)));
        $qrToken = bin2hex(random_bytes(32));

        try {
            $stmt->execute([
                ':participant_code' => $participantCode,
                ':qr_token' => $qrToken,
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
                ':consent_version' => 'draft-2026-08-16'
            ]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000' || $attempt === 4) {
                throw $e;
            }
            $participantCode = null;
        }
    }

    if ($participantCode === null) {
        throw new RuntimeException('Unable to generate participant code');
    }

    $emailSent = sendConfirmationEmail($email, $fullName, $participantCode, $format);

    respond(201, [
        'ok' => true,
        'participant_code' => $participantCode,
        'duplicate_override' => $duplicateReasons !== [],
        'email_sent' => $emailSent
    ]);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
