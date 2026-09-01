<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    try {
        $pdo = require DB_CONFIG_PATH;
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT participant_code, full_name, participation_format FROM participants WHERE registration_status = "confirmed" AND (qr_token = :qr_token OR online_token = :online_token) LIMIT 1');
            $stmt->execute([':qr_token' => $token, ':online_token' => $token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    } catch (Throwable $e) {
        error_log('Calendar ticket lookup failed: ' . $e->getMessage());
        $participant = null;
    }
}
if (!$participant) {http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Билет не найден';exit;}
function icsEscape(string $value): string {return str_replace(["\\", ";", ",", "\r\n", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", "\\n", "\\n"], $value);}
$format = $participant['participation_format'] === 'online' ? 'Онлайн-участие' : 'Очное участие';
$description = 'Форум лабораторных инноваций Московской области — 2026. Формат: ' . $format . '. Программа: https://rclsmo.ru/conference-2026/';
$ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//RCLSMO//Forum 2026//RU\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\nUID:forum-lab-innovations-2026-10-07@rclsmo.ru\r\nDTSTAMP:" . gmdate('Ymd\\THis\\Z') . "\r\nDTSTART:20261007T063000Z\r\nDTEND:20261007T150000Z\r\nSUMMARY:" . icsEscape('Форум лабораторных инноваций Московской области — 2026') . "\r\nDESCRIPTION:" . icsEscape($description) . "\r\nLOCATION:" . icsEscape('Дом Правительства Московской области, б-р Строителей, 1, Красногорск, Московская область') . "\r\nURL:https://rclsmo.ru/conference-2026/\r\nSTATUS:CONFIRMED\r\nBEGIN:VALARM\r\nTRIGGER:-P1D\r\nACTION:DISPLAY\r\nDESCRIPTION:Форум лабораторных инноваций — завтра\r\nEND:VALARM\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="rclsmo-forum-2026.ics"');
echo $ics;
