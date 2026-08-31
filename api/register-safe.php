<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

const PREVIEW_TOKEN_HASHES_SAFE = [
    '6fbe6025563f098ca8756103aa6cb93f4ac2c5bbb5f769e42aff0de2de2c14b9',
    '65b8c5c44e5fdba2620f30c5e434f0893258809bc1a9d2650de8316b48a6f324',
];
const TEST_KEY_PATH_SAFE = '/home/c/cx314477/public_html/.private/registration_test_key';

function safeRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function safeCleanText(string $value): string {
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function safeIsTrue(mixed $value): bool {
    return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
}

function safeValidPersonName(string $value, bool $required = true): bool {
    $value = safeCleanText($value);
    if ($value === '') return !$required;
    if (mb_strlen($value) < 2 || mb_strlen($value) > 100) return false;
    return preg_match("/^[\p{L}\p{M}][\p{L}\p{M}'’\- ]*$/u", $value) === 1;
}

function safeHasEnoughLetters(string $value, int $minimum = 2): bool {
    $matches = [];
    preg_match_all('/\p{L}/u', $value, $matches);
    return isset($matches[0]) && count($matches[0]) >= $minimum;
}

function safeNormalizeRussianPhone(string $value): ?string {
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') return '';
    if (strlen($digits) === 10) return '7' . $digits;
    if (strlen($digits) === 11 && ($digits[0] === '7' || $digits[0] === '8')) {
        return '7' . substr($digits, 1);
    }
    return null;
}

function safePreviewTokenIsValid(string $token): bool {
    if ($token === '') return false;
    $hash = hash('sha256', $token);
    foreach (PREVIEW_TOKEN_HASHES_SAFE as $allowedHash) {
        if (hash_equals($allowedHash, $hash)) return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safeRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) {
    safeRespond(413, ['ok' => false, 'error' => 'request_too_large']);
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    safeRespond(403, ['ok' => false, 'error' => 'origin_not_allowed']);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    safeRespond(400, ['ok' => false, 'error' => 'invalid_json']);
}

$lastName = safeCleanText((string)($data['lastName'] ?? ''));
$firstName = safeCleanText((string)($data['firstName'] ?? ''));
$middleName = safeCleanText((string)($data['middleName'] ?? ''));
$position = safeCleanText((string)($data['position'] ?? ''));
$organization = safeCleanText((string)($data['organization'] ?? ''));
$email = mb_strtolower(trim((string)($data['email'] ?? '')));
$phone = safeCleanText((string)($data['phone'] ?? ''));
$format = trim((string)($data['participationFormat'] ?? ''));
$consent = safeIsTrue($data['privacyConsent'] ?? false);
$policyAcknowledged = safeIsTrue($data['policyAcknowledged'] ?? false);
$honeypot = safeCleanText((string)($data['website'] ?? ''));

$errors = [];
if (!safeValidPersonName($lastName, true)) $errors['lastName'] = 'invalid_name';
if (!safeValidPersonName($firstName, true)) $errors['firstName'] = 'invalid_name';
if (!safeValidPersonName($middleName, false)) $errors['middleName'] = 'invalid_name';

if (mb_strlen($position) < 2 || mb_strlen($position) > 255 || !safeHasEnoughLetters($position)) {
    $errors['position'] = 'invalid_text';
}
if (mb_strlen($organization) < 2 || mb_strlen($organization) > 255 || !safeHasEnoughLetters($organization)) {
    $errors['organization'] = 'invalid_text';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) {
    $errors['email'] = 'invalid_email';
}

$normalizedPhone = safeNormalizeRussianPhone($phone);
if ($phone !== '' && $normalizedPhone === null) {
    $errors['phone'] = 'invalid_ru_phone';
}

if (!in_array($format, ['offline', 'online'], true)) $errors['participationFormat'] = 'invalid';
if (!$consent) $errors['privacyConsent'] = 'required';
if (!$policyAcknowledged) $errors['policyAcknowledged'] = 'required';
if ($honeypot !== '') safeRespond(200, ['ok' => true, 'accepted' => true]);

if ($errors) {
    safeRespond(422, ['ok' => false, 'error' => 'validation_failed', 'fields' => $errors]);
}

$previewToken = trim((string)($_GET['key'] ?? ''));
if ($previewToken !== '') {
    if (!safePreviewTokenIsValid($previewToken)) {
        safeRespond(404, ['ok' => false, 'error' => 'not_found']);
    }
    if (!is_readable(TEST_KEY_PATH_SAFE)) {
        safeRespond(503, ['ok' => false, 'error' => 'preview_unavailable']);
    }
    $testKey = trim((string)file_get_contents(TEST_KEY_PATH_SAFE));
    if ($testKey === '') {
        safeRespond(503, ['ok' => false, 'error' => 'preview_unavailable']);
    }
    $_SERVER['HTTP_X_REGISTRATION_TEST'] = $testKey;
}

$_SERVER['RCLSMO_REGISTRATION_VALIDATED'] = '1';
require __DIR__ . '/register.php';
