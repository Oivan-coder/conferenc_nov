<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const BADGE_ORG_PREFIX = '__BADGE_ORG__:';

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

function zplFitSingleLineFont(string $value, int $boxWidth, int $preferred = 60, int $minimum = 18): int {
    $length = max(1, mb_strlen(zplText($value), 'UTF-8'));
    // Font 0 is close enough to the requested character width for a conservative fit estimate.
    // 0.88 leaves safety space for wide Cyrillic glyphs, hyphens and printer tolerances.
    $fitted = (int)floor(($boxWidth * 0.88) / $length);
    return max($minimum, min($preferred, $fitted));
}

function zplFitMultilineFont(string $value, int $boxWidth, int $maxLines = 4, int $preferred = 36, int $minimum = 22): int {
    $length = max(1, mb_strlen(zplText($value), 'UTF-8'));
    $fitted = (int)floor(($boxWidth * $maxLines * 0.82) / $length);
    return max($minimum, min($preferred, $fitted));
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
        'SELECT participant_code, full_name, organization, position, registration_source
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

    // TSC TE200, 203 dpi. Current stock is laid out as 800 x 520 dots (~100 x 65 mm).
    $LABEL_WIDTH = 800;
    $LABEL_HEIGHT = 520;
    $CONTENT_X = 50;
    $CONTENT_WIDTH = 700;

    $nameParts = preg_split('/\s+/u', zplText((string)$participant['full_name'])) ?: [];
    $lastName = mb_strtoupper((string)($nameParts[0] ?? ''), 'UTF-8');
    $firstName = mb_strtoupper((string)($nameParts[1] ?? ''), 'UTF-8');
    $middleName = mb_strtoupper(implode(' ', array_slice($nameParts, 2)), 'UTF-8');
    $nameLines = array_values(array_filter([$lastName, $firstName, $middleName], static fn(string $v): bool => $v !== ''));

    $organizationSource = (string)$participant['organization'];
    if ((string)$participant['registration_source'] === 'test') {
        $position = (string)$participant['position'];
        if (str_starts_with($position, BADGE_ORG_PREFIX)) {
            $organizationSource = mb_substr($position, mb_strlen(BADGE_ORG_PREFIX, 'UTF-8'), null, 'UTF-8');
        }
    }
    $organization = zplTruncate($organizationSource, 120);

    $zpl = "^XA\n"
        . "^CI28\n"
        . "^PW{$LABEL_WIDTH}\n"
        . "^LL{$LABEL_HEIGHT}\n\n";

    // Name lines get their own font size. Long surnames shrink without affecting short first/middle names.
    $y = 24;
    foreach ($nameLines as $line) {
        $font = zplFitSingleLineFont($line, $CONTENT_WIDTH, 60, 18);
        $zpl .= "^A0N,{$font},{$font}\n"
            . "^FO{$CONTENT_X},{$y}^FB{$CONTENT_WIDTH},1,0,C^FD{$line}^FS\n\n";
        $y += $font + 10;
    }

    // Keep a stable visual separation between the name block and organization.
    $organizationY = max(250, $y + 14);
    $organizationY = min($organizationY, 360);
    $organizationFont = zplFitMultilineFont($organization, $CONTENT_WIDTH, 4, 36, 22);
    $organizationGap = max(2, (int)round($organizationFont * 0.10));

    $zpl .= "^A0N,{$organizationFont},{$organizationFont}\n"
        . "^FO{$CONTENT_X},{$organizationY}^FB{$CONTENT_WIDTH},4,{$organizationGap},C^FD{$organization}^FS\n\n"
        . "^XZ";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="badge_' . $code . '.zpl"');
    echo $zpl;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ZPL generation failed';
}
