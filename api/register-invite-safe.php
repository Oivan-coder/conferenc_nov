<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

require_once __DIR__ . '/invite-access.php';

function inviteSafeRespond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') inviteSafeRespond(405, ['ok' => false, 'error' => 'method_not_allowed']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) inviteSafeRespond(413, ['ok' => false, 'error' => 'request_too_large']);

$token = trim((string)($_GET['invite'] ?? ''));
if (!inviteTokenIsKnown($token)) inviteSafeRespond(404, ['ok' => false, 'error' => 'not_found']);
if (!inviteTokenIsActive($token)) inviteSafeRespond(410, ['ok' => false, 'error' => 'invite_expired']);

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) inviteSafeRespond(400, ['ok' => false, 'error' => 'invalid_json']);

$parts = [
    trim((string)($data['lastName'] ?? '')),
    trim((string)($data['firstName'] ?? '')),
    trim((string)($data['middleName'] ?? '')),
];
$fullName = implode(' ', array_values(array_filter($parts, static fn($v) => $v !== '')));
if (!inviteExpectedNameMatches($token, $fullName)) {
    inviteSafeRespond(422, [
        'ok' => false,
        'error' => 'invite_name_mismatch',
        'message' => 'Укажите ФИО приглашённого участника полностью, как в персональном приглашении.'
    ]);
}

require __DIR__ . '/register-invite.php';
