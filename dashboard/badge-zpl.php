<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

if (empty($_SESSION['conference_dashboard_auth'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Access denied');
}

function zplText(string $value): string {
    $value = trim((string)preg_replace('/\s+/u', ' ', $value));
    return str_replace(['^', '~'], ['', ''], $value);
}

function zplTruncate(string $value, int $maxLength): string {
    return mb_substr(zplText($value), 0, $maxLength, 'UTF-8');
}

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
if (!preg_match('/^LE[A-F0-9]{8}$/', $code)) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Invalid participant code');
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');

    $stmt = $pdo->prepare(
        'SELECT participant_code, full_name, organization, position
         FROM participants
         WHERE event_id = :event
           AND participant_code = :code
           AND participation_format = "offline"
           AND registration_status = "confirmed"
         LIMIT 1'
    );
    $stmt->execute([':event' => EVENT_ID, ':code' => $code]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$participant) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Participant not found');
    }

    // Настройки этикетки сохранены из предыдущей рабочей схемы без изменения геометрии:
    // 800 x 520 dots, ^CI28, шрифт 50x50, центральный блок 500 dots.
    $LABEL_WIDTH = 800;
    $LABEL_HEIGHT = 520;

    $nameParts = preg_split('/\s+/u', trim((string)$participant['full_name'])) ?: [];
    $lastName = mb_strtoupper(zplTruncate((string)($nameParts[0] ?? ''), 20), 'UTF-8');
    $firstName = mb_strtoupper(zplTruncate((string)($nameParts[1] ?? ''), 20), 'UTF-8');
    $middleName = mb_strtoupper(zplTruncate((string)($nameParts[2] ?? ''), 20), 'UTF-8');
    $organization = zplTruncate((string)$participant['organization'], 40);

    $zpl = "^XA\n"
        . "^CI28\n"
        . "^PW{$LABEL_WIDTH}\n"
        . "^LL{$LABEL_HEIGHT}\n\n"
        . "^CF0,50,50\n"
        . "^FO135,30^FB500,1,0,C^FD{$lastName}^FS\n\n"
        . "^CF0,50,50\n"
        . "^FO135,100^FB500,1,0,C^FD{$firstName}^FS\n\n"
        . "^CF0,50,50\n"
        . "^FO135,170^FB500,1,0,C^FD{$middleName}^FS\n\n"
        . "^CF0,50,50\n"
        . "^FO135,250^FB500,3,0,C^FD{$organization}^FS\n\n"
        . "^XZ";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="badge_' . $code . '.zpl"');
    echo $zpl;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ZPL generation failed';
}
