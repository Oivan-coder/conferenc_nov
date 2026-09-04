<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';
const TEST_ORGANIZATION = 'Тестовая МО';
const GOVERNMENT_ORG_LIST_PATH = '/home/c/cx314477/public_html/js/data/organizations-2026.js';

require_once dirname(__DIR__) . '/api/registration-config.php';

if (empty($_SESSION['conference_dashboard_auth'])) {
    header('Location: /dashboard/');
    exit;
}

function normalizeOrg(string $value): string {
    $value = mb_strtolower(trim($value));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    return trim(str_replace(['«', '»', '"'], '', $value));
}

function organizerLabel(string $organization): ?string {
    $n = normalizeOrg($organization);
    if ($n === 'рцлсмо' || str_contains($n, 'референс-центр лабораторной службы')) return 'РЦЛСМО';
    if (str_contains($n, 'цвиод') || str_contains($n, 'центр внедрения изменений')) return 'ЦВИОД';
    if (str_contains($n, 'министерство здравоохранения') && (str_contains($n, 'мо') || str_contains($n, 'московской области'))) return 'Минздрав МО';
    return null;
}

function governmentOrganizations(): array {
    static $set = null;
    if (is_array($set)) return $set;
    $set = [];
    if (is_readable(GOVERNMENT_ORG_LIST_PATH)) {
        $source = (string)file_get_contents(GOVERNMENT_ORG_LIST_PATH);
        if (preg_match_all("/'([^']+)'/u", $source, $matches)) {
            foreach ($matches[1] as $name) $set[normalizeOrg((string)$name)] = true;
        }
    }
    return $set;
}

function organizationCategory(string $organization): array {
    $organizer = organizerLabel($organization);
    if ($organizer !== null) return ['organizer', $organizer];

    $n = normalizeOrg($organization);
    $government = governmentOrganizations();
    if (isset($government[$n])) return ['government', 'Государственная организация'];

    if (preg_match('/^(гбуз|гку|гбу|гауз|фгбу|фбун|фгаоу|фгбоу|пмгму)\b/iu', trim($organization))) {
        return ['government', 'Государственная организация'];
    }
    if (str_contains($n, 'московский областной медицинский колледж')) {
        return ['government', 'Государственная организация'];
    }

    return ['private', 'Частная / иная организация'];
}

function xmlText(string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function columnName(int $number): string {
    $name = '';
    while ($number > 0) {
        $number--;
        $name = chr(65 + ($number % 26)) . $name;
        $number = intdiv($number, 26);
    }
    return $name;
}

function sheetXml(array $rows, array $widths = []): string {
    $cols = '';
    foreach ($widths as $index => $width) {
        $col = $index + 1;
        $cols .= '<col min="' . $col . '" max="' . $col . '" width="' . (float)$width . '" customWidth="1"/>';
    }
    if ($cols !== '') $cols = '<cols>' . $cols . '</cols>';

    $rowXml = '';
    foreach ($rows as $rIndex => $row) {
        $excelRow = $rIndex + 1;
        $cells = '';
        foreach (array_values($row) as $cIndex => $cell) {
            $ref = columnName($cIndex + 1) . $excelRow;
            $style = $rIndex === 0 ? 1 : 0;
            if (is_int($cell) || is_float($cell)) {
                $cells .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $cell . '</v></c>';
            } else {
                $cells .= '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t xml:space="preserve">' . xmlText((string)$cell) . '</t></is></c>';
            }
        }
        $rowXml .= '<row r="' . $excelRow . '">' . $cells . '</row>';
    }

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
        . '<sheetFormatPr defaultRowHeight="15"/>'
        . $cols
        . '<sheetData>' . $rowXml . '</sheetData>'
        . '<autoFilter ref="A1:' . columnName(max(1, count($rows[0] ?? []))) . max(1, count($rows)) . '"/>'
        . '</worksheet>';
}

function writeXlsx(string $path, array $sheets): void {
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Unable to create XLSX');
    }

    $sheetOverrides = '';
    $workbookSheets = '';
    $workbookRels = '';
    foreach ($sheets as $i => $sheet) {
        $n = $i + 1;
        $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $workbookSheets .= '<sheet name="' . xmlText($sheet['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        $workbookRels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
        $zip->addFromString('xl/worksheets/sheet' . $n . '.xml', sheetXml($sheet['rows'], $sheet['widths'] ?? []));
    }

    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . $sheetOverrides . '</Types>');

    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets>' . $workbookSheets . '</sheets></workbook>');

    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . $workbookRels
        . '<Relationship Id="rId' . (count($sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>');

    $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF214F3B"/><bgColor indexed="64"/></patternFill></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
        . '</styleSheet>');

    $zip->close();
}

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    registrationEnsureSourceColumn($pdo);

    $stmt = $pdo->prepare("SELECT participant_code, full_name, position, organization, email, phone,
        participation_format, registration_status, registration_source, created_at, check_in_at, online_watch_seconds
      FROM participants
      WHERE event_id = :event
        AND organization <> :test_org
        AND LOWER(TRIM(organization)) NOT IN ('ovan','oivan')
      ORDER BY organization ASC, full_name ASC");
    $stmt->execute([':event' => EVENT_ID, ':test_org' => TEST_ORGANIZATION]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $confirmed = 0;
    $offlineConfirmed = 0;
    $onlineConfirmed = 0;
    $waitlist = 0;
    $checkedIn = 0;
    $onlinePresent = 0;
    $publicOffline = 0;
    $invitedOffline = 0;
    $orgs = [];
    $categoryStats = [
        'government' => ['orgs' => [], 'participants' => 0],
        'private' => ['orgs' => [], 'participants' => 0],
        'organizer' => ['orgs' => [], 'participants' => 0],
    ];
    $organizers = ['РЦЛСМО' => 0, 'ЦВИОД' => 0, 'Минздрав МО' => 0];

    foreach ($participants as $p) {
        if ($p['registration_status'] === 'waitlist') $waitlist++;
        if ($p['registration_status'] !== 'confirmed') continue;

        $confirmed++;
        $organization = trim((string)$p['organization']);
        $orgs[$organization] = true;
        [$category, $label] = organizationCategory($organization);
        $categoryStats[$category]['orgs'][$organization] = true;
        $categoryStats[$category]['participants']++;
        if ($category === 'organizer' && isset($organizers[$label])) $organizers[$label]++;

        if ($p['participation_format'] === 'offline') {
            $offlineConfirmed++;
            if ($p['registration_source'] === 'public') $publicOffline++;
            if ($p['registration_source'] === 'invited') $invitedOffline++;
            if (!empty($p['check_in_at'])) $checkedIn++;
        } elseif ($p['participation_format'] === 'online') {
            $onlineConfirmed++;
            if ((int)$p['online_watch_seconds'] >= 900) $onlinePresent++;
        }
    }

    $orgSummary = [];
    foreach ($participants as $p) {
        $organization = trim((string)$p['organization']);
        if (!isset($orgSummary[$organization])) {
            [$category, $label] = organizationCategory($organization);
            $orgSummary[$organization] = [
                'organization' => $organization,
                'category' => $label,
                'confirmed' => 0,
                'offline' => 0,
                'online' => 0,
                'waitlist' => 0,
                'checked_in' => 0,
                'online_present' => 0,
            ];
        }
        if ($p['registration_status'] === 'waitlist') $orgSummary[$organization]['waitlist']++;
        if ($p['registration_status'] !== 'confirmed') continue;
        $orgSummary[$organization]['confirmed']++;
        if ($p['participation_format'] === 'offline') {
            $orgSummary[$organization]['offline']++;
            if (!empty($p['check_in_at'])) $orgSummary[$organization]['checked_in']++;
        } elseif ($p['participation_format'] === 'online') {
            $orgSummary[$organization]['online']++;
            if ((int)$p['online_watch_seconds'] >= 900) $orgSummary[$organization]['online_present']++;
        }
    }
    uasort($orgSummary, static fn($a, $b) => ($b['confirmed'] <=> $a['confirmed']) ?: strcmp($a['organization'], $b['organization']));

    $generatedAt = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i');
    $summaryRows = [
        ['Показатель', 'Значение'],
        ['Сформировано', $generatedAt],
        ['Подтверждено участников', $confirmed],
        ['Организаций с подтвержденными участниками', count($orgs)],
        ['Государственных организаций', count($categoryStats['government']['orgs'])],
        ['Участников из государственных организаций', $categoryStats['government']['participants']],
        ['Частных / иных организаций', count($categoryStats['private']['orgs'])],
        ['Участников из частных / иных организаций', $categoryStats['private']['participants']],
        ['Организаций-организаторов', count($categoryStats['organizer']['orgs'])],
        ['Участников от организаторов', $categoryStats['organizer']['participants']],
        ['РЦЛСМО', $organizers['РЦЛСМО']],
        ['ЦВИОД', $organizers['ЦВИОД']],
        ['Минздрав МО', $organizers['Минздрав МО']],
        ['Очно зарегистрировано', $offlineConfirmed],
        ['Очно через публичную форму', $publicOffline],
        ['Очно по приглашению', $invitedOffline],
        ['Пришли очно', $checkedIn],
        ['Онлайн зарегистрировано', $onlineConfirmed],
        ['Онлайн присутствовали ≥15 мин', $onlinePresent],
        ['Фактически приняли участие', $checkedIn + $onlinePresent],
        ['Лист ожидания', $waitlist],
        [],
        ['Организация', 'Категория', 'Подтверждено', 'Очно', 'Онлайн', 'Лист ожидания', 'Пришли очно', 'Онлайн ≥15 мин', 'Факт всего'],
    ];
    foreach ($orgSummary as $o) {
        $summaryRows[] = [
            $o['organization'], $o['category'], $o['confirmed'], $o['offline'], $o['online'], $o['waitlist'],
            $o['checked_in'], $o['online_present'], $o['checked_in'] + $o['online_present']
        ];
    }

    $participantRows = [[
        'Код', 'ФИО', 'Организация', 'Категория', 'Должность', 'Email', 'Телефон', 'Формат', 'Источник регистрации', 'Статус',
        'Дата регистрации', 'Приход очно', 'Онлайн, мин', 'Факт участия'
    ]];
    foreach ($participants as $p) {
        [, $categoryLabel] = organizationCategory((string)$p['organization']);
        $onlineMinutes = $p['participation_format'] === 'online' ? round((int)$p['online_watch_seconds'] / 60, 1) : 0;
        $fact = 'Нет';
        if ($p['registration_status'] === 'confirmed') {
            if ($p['participation_format'] === 'offline' && !empty($p['check_in_at'])) $fact = 'Да, очно';
            if ($p['participation_format'] === 'online' && (int)$p['online_watch_seconds'] >= 900) $fact = 'Да, онлайн';
        }
        $participantRows[] = [
            (string)$p['participant_code'], (string)$p['full_name'], (string)$p['organization'], $categoryLabel,
            (string)$p['position'], (string)$p['email'], (string)($p['phone'] ?? ''),
            $p['participation_format'] === 'offline' ? 'Очно' : 'Онлайн',
            registrationSourceLabel((string)$p['registration_source']),
            $p['registration_status'] === 'confirmed' ? 'Подтверждено' : ($p['registration_status'] === 'waitlist' ? 'Лист ожидания' : 'Отменено'),
            (string)$p['created_at'], (string)($p['check_in_at'] ?? ''), $onlineMinutes, $fact
        ];
    }

    if (!class_exists('ZipArchive')) {
        $filename = 'forum_2026_current_' . date('Y-m-d_H-i') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'wb');
        foreach ($participantRows as $row) fputcsv($out, $row, ';');
        fclose($out);
        exit;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'rclsmo_xlsx_');
    if ($tmp === false) throw new RuntimeException('Unable to create temp file');
    writeXlsx($tmp, [
        ['name' => 'Сводка', 'rows' => $summaryRows, 'widths' => [42, 28, 16, 12, 12, 16, 14, 16, 14]],
        ['name' => 'Участники', 'rows' => $participantRows, 'widths' => [16, 32, 34, 28, 28, 30, 18, 12, 22, 18, 20, 20, 14, 18]],
    ]);

    $filename = 'forum_2026_current_' . date('Y-m-d_H-i') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Не удалось сформировать выгрузку.';
}
