<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

const GUEST_TOKEN_HASH = '50d629e1bc7e5a348ba3e81d03c4e0cd190493e3753f2c146e78cf77e5d92c9b';
const TEST_KEY_PATH_GUEST = '/home/c/cx314477/public_html/.private/registration_test_key';

require_once __DIR__ . '/registration-duplicate-guard.php';

function guestRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function guestCleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function guestIsTrue(mixed $value): bool {
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

function guestValidPersonName(string $value, bool $required = true): bool {
    $value = guestCleanText($value);
    if ($value === '') return !$required;
    if (mb_strlen($value) < 2 || mb_strlen($value) > 100) return false;
    return preg_match("/^[\p{L}\p{M}][\p{L}\p{M}'’\- ]*$/u", $value) === 1;
}

function guestHasEnoughLetters(string $value, int $minimum = 2): bool {
    $matches = [];
    preg_match_all('/\p{L}/u', $value, $matches);
    return isset($matches[0]) && count($matches[0]) >= $minimum;
}

function guestNormalizeRussianPhone(string $value): ?string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (strlen($digits) === 10) return '7' . $digits;
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) return '7' . substr($digits, 1);
    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') guestRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) guestRespond(413, ['ok' => false, 'error' => 'request_too_large']);

$token = trim((string)($_GET['key'] ?? ''));
if ($token === '' || !hash_equals(GUEST_TOKEN_HASH, hash('sha256', $token))) {
    guestRespond(404, ['ok' => false, 'error' => 'not_found']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    guestRespond(403, ['ok' => false, 'error' => 'origin_not_allowed']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) guestRespond(400, ['ok' => false, 'error' => 'invalid_json']);

$lastName = guestCleanText((string)($data['lastName'] ?? ''));
$firstName = guestCleanText((string)($data['firstName'] ?? ''));
$middleName = guestCleanText((string)($data['middleName'] ?? ''));
$position = guestCleanText((string)($data['position'] ?? ''));
$organization = guestCleanText((string)($data['organization'] ?? ''));
$email = mb_strtolower(trim((string)($data['email'] ?? '')));
$phone = guestCleanText((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consent = guestIsTrue($data['privacyConsent'] ?? false);
$policyAcknowledged = guestIsTrue($data['policyAcknowledged'] ?? false);
$honeypot = guestCleanText((string)($data['website'] ?? ''));

$errors = [];
if (!guestValidPersonName($lastName, true)) $errors['lastName'] = 'invalid_name';
if (!guestValidPersonName($firstName, true)) $errors['firstName'] = 'invalid_name';
if (!guestValidPersonName($middleName, false)) $errors['middleName'] = 'invalid_name';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255 || !guestHasEnoughLetters($position)) $errors['position'] = 'invalid_text';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255 || !guestHasEnoughLetters($organization)) $errors['organization'] = 'invalid_text';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors['email'] = 'invalid_email';
$normalizedPhone = guestNormalizeRussianPhone($phone);
if ($phone !== '' && $normalizedPhone === null) $errors['phone'] = 'invalid_ru_phone';
if ($format !== 'offline') $errors['participationFormat'] = 'offline_only';
if (!$consent) $errors['privacyConsent'] = 'required';
if (!$policyAcknowledged) $errors['policyAcknowledged'] = 'required';
if ($honeypot !== '') guestRespond(200, ['ok' => true, 'accepted' => true]);
if ($errors) guestRespond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);

$hardDuplicate = registrationDuplicateGuard(
    (string)($data['eventId'] ?? ''),
    $lastName,
    $firstName,
    $middleName,
    $organization,
    $email,
    (string)($normalizedPhone ?? '')
);
if ($hardDuplicate && !empty($hardDuplicate['hard'])) {
    guestRespond(409, [
        'ok' => false,
        'error' => 'possible_duplicate',
        'reasons' => $hardDuplicate['reasons'] ?? ['same_person', 'email', 'phone'],
        'hard_duplicate' => true,
    ]);
}

if (!is_readable(TEST_KEY_PATH_GUEST)) guestRespond(503, ['ok' => false, 'error' => 'registration_unavailable']);
$testKey = trim((string)file_get_contents(TEST_KEY_PATH_GUEST));
if ($testKey === '') guestRespond(503, ['ok' => false, 'error' => 'registration_unavailable']);

$_SERVER['HTTP_X_REGISTRATION_TEST'] = $testKey;
$_SERVER['RCLSMO_REGISTRATION_VALIDATED'] = '1';
$_SERVER['RCLSMO_REGISTRATION_SOURCE'] = 'invited';
require __DIR__ . '/register.php';
