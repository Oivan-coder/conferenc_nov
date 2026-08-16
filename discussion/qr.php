<?php

declare(strict_types=1);

header('Cache-Control: public, max-age=3600');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('X-Content-Type-Options: nosniff');

const AUTOLOAD_PATH = '/home/c/cx314477/public_html/.private/vendor/autoload.php';
const DISCUSSION_URL = 'https://rclsmo.ru/discussion/';

try {
    if (!is_readable(AUTOLOAD_PATH)) {
        throw new RuntimeException('QR library unavailable');
    }

    require_once AUTOLOAD_PATH;

    $qrCode = Endroid\QrCode\QrCode::create(DISCUSSION_URL)
        ->setEncoding(new Endroid\QrCode\Encoding\Encoding('ISO-8859-1'))
        ->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::Medium)
        ->setSize(520)
        ->setMargin(18)
        ->setRoundBlockSizeMode(Endroid\QrCode\RoundBlockSizeMode::Margin);

    $writer = new Endroid\QrCode\Writer\SvgWriter();
    $result = $writer->write($qrCode);

    header('Content-Type: ' . $result->getMimeType());
    echo $result->getString();
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'QR generation unavailable';
}
