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
$fullName = trim(preg_replace('/\s+/u', ' ', (string)($data['participantName'] ?? '')));
$position = trim(preg_replace('/\s+/u', ' ', (string)($data['position'] ?? '')));
$organization = trim(preg_replace('/\s+/u', ' ', (string)($data['organization'] ?? '')));
$email = mb_strtolower(trim((string)($data['email'] ?? '')));
$phone = trim((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consentRaw = $data['privacyConsent'] ?? false;
$consent = in_array($consentRaw, [true, 1, '1', 'true', 'on', 'yes'], true);

$errors = [];
if ($eventId !== 'forum-lab-innovations-2026-10-07') $errors['eventId'] = 'invalid';
if (mb_strlen($fullName) < 3 || mb_strlen($fullName) > 255) $errors['participantName'] = 'invalid';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255) $errors['position'] = 'invalid';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255) $errors['organization'] = 'invalid';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors['email'] = 'invalid';
if ($phone !== '' && mb_strlen($phone) > 32) $errors['phone'] = 'invalid';
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

    $sql = 'INSERT INTO participants (
        participant_code, qr_token, full_name, position, organization, email, phone,
        participation_format, privacy_consent, consent_version, consent_at, created_at
    ) VALUES (
        :participant_code, :qr_token, :full_name, :position, :organization, :email, :phone,
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
                ':full_name' => $fullName,
                ':position' => $position,
                ':organization' => $organization,
                ':email' => $email,
                ':phone' => $phone !== '' ? $phone : null,
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

    respond(201, [
        'ok' => true,
        'participant_code' => $participantCode
    ]);
} catch (Throwable $e) {
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
