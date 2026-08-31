<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

const QA_TOKEN_HASH = '50d629e1bc7e5a348ba3e81d03c4e0cd190493e3753f2c146e78cf77e5d92c9b';
const TEST_KEY_PATH_QA = '/home/c/cx314477/public_html/.private/registration_test_key';

function qaRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function qaCleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function qaIsTrue(mixed $value): bool {
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

function qaValidPersonName(string $value, bool $required = true): bool {
    $value = qaCleanText($value);
    if ($value === '') return !$required;
    if (mb_strlen($value) < 2 || mb_strlen($value) > 100) return false;
    return preg_match("/^[\p{L}\p{M}][\p{L}\p{M}'’\- ]*$/u", $value) === 1;
}

function qaHasEnoughLetters(string $value, int $minimum = 2): bool {
    $matches = [];
    preg_match_all('/\p{L}/u', $value, $matches);
    return isset($matches[0]) && count($matches[0]) >= $minimum;
}

function qaNormalizeRussianPhone(string $value): ?string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (strlen($digits) === 10) return '7' . $digits;
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) return '7' . substr($digits, 1);
    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') qaRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) qaRespond(413, ['ok' => false, 'error' => 'request_too_large']);

$token = trim((string)($_GET['key'] ?? ''));
if ($token === '' || !hash_equals(QA_TOKEN_HASH, hash('sha256', $token))) {
    qaRespond(404, ['ok' => false, 'error' => 'not_found']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    qaRespond(403, ['ok' => false, 'error' => 'origin_not_allowed']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) qaRespond(400, ['ok' => false, 'error' => 'invalid_json']);

$lastName = qaCleanText((string)($data['lastName'] ?? ''));
$firstName = qaCleanText((string)($data['firstName'] ?? ''));
$middleName = qaCleanText((string)($data['middleName'] ?? ''));
$position = qaCleanText((string)($data['position'] ?? ''));
$organization = qaCleanText((string)($data['organization'] ?? ''));
$email = mb_strtolower(trim((string)($data['email'] ?? '')));
$phone = qaCleanText((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consent = qaIsTrue($data['privacyConsent'] ?? false);
$policyAcknowledged = qaIsTrue($data['policyAcknowledged'] ?? false);
$honeypot = qaCleanText((string)($data['website'] ?? ''));

$errors = [];
if (!qaValidPersonName($lastName, true)) $errors['lastName'] = 'invalid_name';
if (!qaValidPersonName($firstName, true)) $errors['firstName'] = 'invalid_name';
if (!qaValidPersonName($middleName, false)) $errors['middleName'] = 'invalid_name';
if (mb_strlen($position) < 2 || mb_strlen($position) > 255 || !qaHasEnoughLetters($position)) $errors['position'] = 'invalid_text';
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255 || !qaHasEnoughLetters($organization)) $errors['organization'] = 'invalid_text';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors['email'] = 'invalid_email';
$normalizedPhone = qaNormalizeRussianPhone($phone);
if ($phone !== '' && $normalizedPhone === null) $errors['phone'] = 'invalid_ru_phone';
if ($format !== 'offline') $errors['participationFormat'] = 'offline_only';
if (!$consent) $errors['privacyConsent'] = 'required';
if (!$policyAcknowledged) $errors['policyAcknowledged'] = 'required';
if ($honeypot !== '') qaRespond(200, ['ok' => true, 'accepted' => true]);
if ($errors) qaRespond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);

if (!is_readable(TEST_KEY_PATH_QA)) qaRespond(503, ['ok' => false, 'error' => 'registration_unavailable']);
$testKey = trim((string)file_get_contents(TEST_KEY_PATH_QA));
if ($testKey === '') qaRespond(503, ['ok' => false, 'error' => 'registration_unavailable']);

$_SERVER['HTTP_X_REGISTRATION_TEST'] = $testKey;
$_SERVER['RCLSMO_REGISTRATION_VALIDATED'] = '1';
require __DIR__ . '/register.php';
