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

require_once __DIR__ . '/organization-analytics.php';

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
    return (int)floor($boxWidth / ($length * 0.62));
}

function zplFitSingleLineFont(string $value, int $boxWidth, int $preferred = 60, int $minimum = 28): int {
    return max($minimum, min($preferred, zplEstimatedFont($value, $boxWidth)));
}

function zplSplitSurname(string $surname, int $boxWidth): array {
    if ($surname === '') return [];

    // Обычную фамилию держим в одну строку. Длинную двойную фамилию
    // переносим по дефису, чтобы не превращать её в микротекст.
    if (zplEstimatedFont($surname, $boxWidth) >= 44 || !str_contains($surname, '-')) {
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

function zplOrganizationLayout(string $value, int $boxWidth, int $maxLines = 3): array {
    // Организация — вторичный уровень. Предпочитаем 2–3 хорошо читаемые строки
    // вместо слишком крупного текста, который может выйти за нижнюю границу этикетки.
    foreach ([34, 32, 30, 28, 26, 24, 22] as $font) {
        $lines = zplWrapForFont($value, $boxWidth, $font);
        if (count($lines) === 0 || count($lines) > $maxLines) continue;

        $fits = true;
        foreach ($lines as $line) {
            if (zplEstimatedFont($line, $boxWidth) < $font) {
                $fits = false;
                break;
            }
        }
        if ($fits) return [$font, $lines];
    }

    return [20, array_slice(zplWrapForFont($value, $boxWidth, 20), 0, $maxLines)];
}

function nameBlockHeight(array $lines, int $gap): int {
    $height = 0;
    foreach ($lines as $i => $line) {
        $height += (int)$line['font'];
        if ($i < count($lines) - 1) $height += $gap;
    }
    return $height;
}

function organizationBlockHeight(array $lines, int $font, int $gap): int {
    if (!$lines) return 0;
    return count($lines) * $font + max(0, count($lines) - 1) * $gap;
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

    // По физической печати нижнюю часть носителя оставляем как техническую
    // безопасную зону. Это предотвращает срез последней строки на длинных бейджах.
    $SAFE_TOP = 18;
    $SAFE_BOTTOM = 62;
    $SAFE_END_Y = $LABEL_HEIGHT - $SAFE_BOTTOM;
    $SAFE_HEIGHT = $SAFE_END_Y - $SAFE_TOP;

    $nameParts = preg_split('/\s+/u', zplText((string)$participant['full_name'])) ?: [];
    $lastName = mb_strtoupper((string)($nameParts[0] ?? ''), 'UTF-8');
    $firstName = mb_strtoupper((string)($nameParts[1] ?? ''), 'UTF-8');
    $middleName = mb_strtoupper(implode(' ', array_slice($nameParts, 2)), 'UTF-8');

    $surnameLines = zplSplitSurname($lastName, $CONTENT_WIDTH);
    $nameLineCount = count($surnameLines) + ($firstName !== '' ? 1 : 0) + ($middleName !== '' ? 1 : 0);

    // Чем больше строк у ФИО, тем ниже верхний предел кегля. Так длинное ФИО
    // остаётся выразительным, но не забирает место у организации.
    $namePreferred = $nameLineCount >= 4 ? 50 : 60;

    $nameLines = [];
    foreach ($surnameLines as $surnameLine) {
        $nameLines[] = [
            'text' => $surnameLine,
            'font' => zplFitSingleLineFont($surnameLine, $CONTENT_WIDTH, $namePreferred, 30),
        ];
    }
    if ($firstName !== '') {
        $nameLines[] = [
            'text' => $firstName,
            'font' => zplFitSingleLineFont($firstName, $CONTENT_WIDTH, $namePreferred, 30),
        ];
    }
    if ($middleName !== '') {
        $nameLines[] = [
            'text' => $middleName,
            'font' => zplFitSingleLineFont($middleName, $CONTENT_WIDTH, $namePreferred, 28),
        ];
    }

    $organizationSource = dashboardBadgeOrganizationName((string)$participant['organization']);
    if ((string)$participant['registration_source'] === 'test') {
        $position = (string)$participant['position'];
        if (str_starts_with($position, BADGE_ORG_PREFIX)) {
            $organizationSource = mb_substr($position, mb_strlen(BADGE_ORG_PREFIX, 'UTF-8'), null, 'UTF-8');
        }
    }
    $organization = zplTruncate($organizationSource, 140);
    [$organizationFont, $organizationLines] = zplOrganizationLayout($organization, $CONTENT_WIDTH, 3);

    $NAME_GAP = 4;
    $BLOCK_GAP = 14;
    $ORG_GAP = max(2, (int)round($organizationFont * 0.08));

    $nameHeight = nameBlockHeight($nameLines, $NAME_GAP);
    $organizationHeight = organizationBlockHeight($organizationLines, $organizationFont, $ORG_GAP);
    $totalHeight = $nameHeight + ($organizationLines ? $BLOCK_GAP + $organizationHeight : 0);

    // Жёсткая гарантия: готовый блок обязан помещаться в безопасную высоту.
    // Сначала понемногу уменьшаем ФИО, затем при необходимости организацию.
    while ($totalHeight > $SAFE_HEIGHT) {
        $changed = false;
        foreach ($nameLines as $i => $line) {
            $minimum = $i === count($nameLines) - 1 ? 26 : 28;
            if ((int)$nameLines[$i]['font'] > $minimum) {
                $nameLines[$i]['font']--;
                $changed = true;
            }
        }

        $nameHeight = nameBlockHeight($nameLines, $NAME_GAP);
        $totalHeight = $nameHeight + ($organizationLines ? $BLOCK_GAP + $organizationHeight : 0);
        if ($totalHeight <= $SAFE_HEIGHT) break;

        if (!$changed && $organizationFont > 18) {
            $organizationFont--;
            $ORG_GAP = max(2, (int)round($organizationFont * 0.08));
            $organizationHeight = organizationBlockHeight($organizationLines, $organizationFont, $ORG_GAP);
            $totalHeight = $nameHeight + ($organizationLines ? $BLOCK_GAP + $organizationHeight : 0);
            $changed = true;
        }

        if (!$changed) break;
    }

    // Центрируем не относительно всей заявленной высоты, а внутри проверенной
    // безопасной области, поэтому и верх, и низ остаются визуально стабильными.
    $startY = $SAFE_TOP + max(0, (int)floor(($SAFE_HEIGHT - $totalHeight) / 2));

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
