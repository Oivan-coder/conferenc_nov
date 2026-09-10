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

function zplEstimatedFont(string $value, int $boxWidth): int {
    $length = max(1, mb_strlen(zplText($value), 'UTF-8'));
    // Font 0 is proportional. In the tested Cyrillic layout an average glyph occupies
    // roughly 0.62 of the declared character width, so this is deliberately less
    // conservative than the previous character-count formula.
    return (int)floor($boxWidth / ($length * 0.62));
}

function zplFitSingleLineFont(string $value, int $boxWidth, int $preferred = 64, int $minimum = 30): int {
    return max($minimum, min($preferred, zplEstimatedFont($value, $boxWidth)));
}

function zplSplitSurname(string $surname, int $boxWidth): array {
    if ($surname === '') return [];

    // Keep ordinary surnames on one line. A long hyphenated surname is much more
    // readable as two large lines than as one tiny line.
    if (zplEstimatedFont($surname, $boxWidth) >= 42 || !str_contains($surname, '-')) {
        return [$surname];
    }

    $parts = array_values(array_filter(explode('-', $surname), static fn(string $part): bool => $part !== ''));
    if (count($parts) < 2) return [$surname];

    if (count($parts) === 2) {
        return [$parts[0] . '-', $parts[1]];
    }

    $half = (int)ceil(count($parts) / 2);
    return [implode('-', array_slice($parts, 0, $half)) . '-', implode('-', array_slice($parts, $half))];
}

function zplWrapForFont(string $value, int $boxWidth, int $font): array {
    $value = zplText($value);
    if ($value === '') return [];

    $maxChars = max(8, (int)floor($boxWidth / ($font * 0.62)));
    $words = preg_split('/\s+/u', $value) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $candidate = $current === '' ? $word : $current . ' ' . $word;
        if ($current === '' || mb_strlen($candidate, 'UTF-8') <= $maxChars) {
            $current = $candidate;
            continue;
        }
        $lines[] = $current;
        $current = $word;
    }
    if ($current !== '') $lines[] = $current;

    return $lines;
}

function zplOrganizationLayout(string $value, int $boxWidth, int $maxLines = 4): array {
    foreach ([42, 40, 38, 36, 34, 32, 30, 28, 26, 24] as $font) {
        $lines = zplWrapForFont($value, $boxWidth, $font);
        if (count($lines) > $maxLines) continue;

        $fits = true;
        foreach ($lines as $line) {
            if (zplEstimatedFont($line, $boxWidth) < $font) {
                $fits = false;
                break;
            }
        }
        if ($fits) return [$font, $lines];
    }

    return [22, array_slice(zplWrapForFont($value, $boxWidth, 22), 0, $maxLines)];
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

    $LABEL_WIDTH = 800;
    $LABEL_HEIGHT = 520;
    $CONTENT_X = 40;
    $CONTENT_WIDTH = 720;

    $nameParts = preg_split('/\s+/u', zplText((string)$participant['full_name'])) ?: [];
    $lastName = mb_strtoupper((string)($nameParts[0] ?? ''), 'UTF-8');
    $firstName = mb_strtoupper((string)($nameParts[1] ?? ''), 'UTF-8');
    $middleName = mb_strtoupper(implode(' ', array_slice($nameParts, 2)), 'UTF-8');

    $nameLines = [];
    foreach (zplSplitSurname($lastName, $CONTENT_WIDTH) as $surnameLine) {
        $nameLines[] = ['text' => $surnameLine, 'font' => zplFitSingleLineFont($surnameLine, $CONTENT_WIDTH, 64, 34)];
    }
    if ($firstName !== '') {
        $nameLines[] = ['text' => $firstName, 'font' => zplFitSingleLineFont($firstName, $CONTENT_WIDTH, 64, 34)];
    }
    if ($middleName !== '') {
        $nameLines[] = ['text' => $middleName, 'font' => zplFitSingleLineFont($middleName, $CONTENT_WIDTH, 64, 30)];
    }

    $organizationSource = (string)$participant['organization'];
    if ((string)$participant['registration_source'] === 'test') {
        $position = (string)$participant['position'];
        if (str_starts_with($position, BADGE_ORG_PREFIX)) {
            $organizationSource = mb_substr($position, mb_strlen(BADGE_ORG_PREFIX, 'UTF-8'), null, 'UTF-8');
        }
    }
    $organization = zplTruncate($organizationSource, 140);
    [$organizationFont, $organizationLines] = zplOrganizationLayout($organization, $CONTENT_WIDTH, 4);

    $NAME_GAP = 7;
    $BLOCK_GAP = 18;
    $ORG_GAP = max(3, (int)round($organizationFont * 0.12));

    $nameHeight = 0;
    foreach ($nameLines as $i => $line) {
        $nameHeight += (int)$line['font'];
        if ($i < count($nameLines) - 1) $nameHeight += $NAME_GAP;
    }
    $organizationHeight = 0;
    foreach ($organizationLines as $i => $line) {
        $organizationHeight += $organizationFont;
        if ($i < count($organizationLines) - 1) $organizationHeight += $ORG_GAP;
    }

    $totalHeight = $nameHeight + ($organizationLines ? $BLOCK_GAP + $organizationHeight : 0);
    $startY = max(16, (int)floor(($LABEL_HEIGHT - $totalHeight) / 2));

    // If an extreme combination is still too tall, move it to the top safety margin.
    if ($startY + $totalHeight > $LABEL_HEIGHT - 16) $startY = 16;

    $zpl = "^XA\n"
        . "^CI28\n"
        . "^PW{$LABEL_WIDTH}\n"
        . "^LL{$LABEL_HEIGHT}\n\n";

    $y = $startY;
    foreach ($nameLines as $index => $line) {
        $font = (int)$line['font'];
        $text = (string)$line['text'];
        $zpl .= "^A0N,{$font},{$font}\n"
            . "^FO{$CONTENT_X},{$y}^FB{$CONTENT_WIDTH},1,0,C^FD{$text}^FS\n\n";
        $y += $font;
        if ($index < count($nameLines) - 1) $y += $NAME_GAP;
    }

    if ($organizationLines) {
        $y += $BLOCK_GAP;
        foreach ($organizationLines as $index => $line) {
            $zpl .= "^A0N,{$organizationFont},{$organizationFont}\n"
                . "^FO{$CONTENT_X},{$y}^FB{$CONTENT_WIDTH},1,0,C^FD{$line}^FS\n\n";
            $y += $organizationFont;
            if ($index < count($organizationLines) - 1) $y += $ORG_GAP;
        }
    }

    $zpl .= "^XZ";

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: inline; filename="badge_' . $code . '.zpl"');
    echo $zpl;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ZPL generation failed';
}
