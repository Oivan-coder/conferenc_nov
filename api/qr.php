<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const AUTOLOAD_PATH = '/home/c/cx314477/public_html/.private/vendor/autoload.php';

$token = strtolower(trim((string)($_GET['t'] ?? '')));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit;
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');

    $stmt = $pdo->prepare('SELECT participant_code FROM participants WHERE qr_token = :token AND participation_format = "offline" AND registration_status = "confirmed" LIMIT 1');
    $stmt->execute([':token' => $token]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        exit;
    }

    if (!is_readable(AUTOLOAD_PATH)) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'QR library is not installed';
        exit;
    }

    require_once AUTOLOAD_PATH;

    $ticketUrl = 'https://rclsmo.ru/participant.php?t=' . rawurlencode($token);

    $qrCode = Endroid\QrCode\QrCode::create($ticketUrl)
        ->setEncoding(new Endroid\QrCode\Encoding\Encoding('ISO-8859-1'))
        ->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::Medium)
        ->setSize(320)
        ->setMargin(12)
        ->setRoundBlockSizeMode(Endroid\QrCode\RoundBlockSizeMode::Margin);

    $writer = new Endroid\QrCode\Writer\SvgWriter();
    $result = $writer->write($qrCode);

    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'QR generation failed';
}
