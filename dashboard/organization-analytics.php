<?php

declare(strict_types=1);

function dashboardNormalizeOrganization(string $value): string {
    $value = str_replace('ё', 'е', mb_strtolower(trim($value)));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    $value = str_replace(['«', '»', '“', '”', '"'], '', $value);
    $value = (string)preg_replace('/\s*([.,])\s*/u', '$1', $value);
    return trim($value);
}

function dashboardOrganizationAliases(): array {
    static $aliases = null;
    if (is_array($aliases)) return $aliases;

    $aliases = [];
    $add = static function (array $variants, string $canonical) use (&$aliases): void {
        foreach ($variants as $variant) {
            $aliases[dashboardNormalizeOrganization($variant)] = $canonical;
        }
    };

    $add([
        'МОНИКИ',
        'МОНИКИ им. Владимирского',
        'МОНИКИ им Владимирского',
        'ГБУЗ МО МОНИКИ',
        'ГБУЗ МО МОНИКИ им. М.Ф. Владимирского',
        'ГБУЗ МО МОНИКИ им М.Ф. Владимирского',
    ], 'ГБУЗ МО МОНИКИ им. М.Ф. Владимирского');

    $add([
        'ГУЗ УОДКБ',
        'ГУЗ УОДКБ им Горячева',
        'ГУЗ УОДКБ им. Горячева',
        'ГУЗ УОДКБ имени Ю. Ф.Горячева',
        'ГУЗ УОДКБ имени Ю. Ф. Горячева',
        'ГУЗ УОДКБ имени Ю.Ф.Горячева',
        'ГУЗ УОДКБ имени Ю.Ф. Горячева',
    ], 'ГУЗ «УОДКБ им. Ю.Ф. Горячева»');

    return $aliases;
}

function dashboardCanonicalOrganization(string $organization): string {
    $raw = trim($organization);
    if ($raw === '') return '';
    $key = dashboardNormalizeOrganization($raw);
    return dashboardOrganizationAliases()[$key] ?? $raw;
}

function dashboardOrganizerLabel(string $organization): ?string {
    $n = dashboardNormalizeOrganization(dashboardCanonicalOrganization($organization));
    if ($n === 'рцлсмо' || str_contains($n, 'референс-центр лабораторной службы')) return 'РЦЛСМО';
    if (str_contains($n, 'цвиод') || str_contains($n, 'центр внедрения изменений')) return 'ЦВИОД';
    if (str_contains($n, 'моники')) return 'МОНИКИ';
    if (str_contains($n, 'министерство здравоохранения') && (str_contains($n, 'московской области') || preg_match('/\bмо\b/u', $n))) return 'Минздрав МО';
    return null;
}

function dashboardGovernmentOrganizations(): array {
    static $set = null;
    if (is_array($set)) return $set;

    $set = [];
    $path = dirname(__DIR__) . '/js/data/organizations-2026.js';
    if (is_readable($path)) {
        $source = (string)file_get_contents($path);
        if (preg_match_all("/'([^']+)'/u", $source, $matches)) {
            foreach ($matches[1] as $name) {
                $canonical = dashboardCanonicalOrganization((string)$name);
                $set[dashboardNormalizeOrganization($canonical)] = true;
            }
        }
    }
    return $set;
}

function dashboardOrganizationCategory(string $organization): array {
    $canonical = dashboardCanonicalOrganization($organization);
    $organizer = dashboardOrganizerLabel($canonical);
    if ($organizer !== null) return ['organizer', $organizer];

    $n = dashboardNormalizeOrganization($canonical);
    if (isset(dashboardGovernmentOrganizations()[$n])) {
        return ['government', 'Государственная организация'];
    }

    if (preg_match('/^\s*(ооо|ао|пао|зао|ип)\b/iu', $canonical)
        || preg_match('/^\s*гк\s*[«" ]/iu', $canonical)
        || str_contains($n, 'лабораторная служба хеликс')
        || preg_match('/\bниармедик\b/iu', $canonical)
        || $n === 'формит') {
        return ['private', 'Частная / коммерческая организация'];
    }

    if (preg_match('/(^|[\s«"(])(?:гбузс|гбуз|гкуз|гку|гбу|гауз|гау|гуз|буз|муз|фгбу|фгбуз|фгау|фгаоу|фгбоу|фбун|фбуз|фкуз|фку|фгку|гнц|гну|бу)(?=$|[\s«"(\-])/iu', $canonical)) {
        return ['government', 'Государственная организация'];
    }

    foreach (['минздрава', 'фмба', 'рманпо', 'пспбгму', 'рниму', 'ростгму', 'нмиц', 'мкнц', 'рнпц', 'црб', 'гкб', 'сгб', 'бсмп', 'квд'] as $marker) {
        if (str_contains($n, $marker)) return ['government', 'Государственная организация'];
    }

    return ['unknown', 'Не определено'];
}

function dashboardMergeOrganizationRows(array $rows): array {
    $merged = [];
    $numericFields = [
        'total', 'confirmed_count', 'offline_count', 'online_count', 'waitlist_count',
        'checked_in_count', 'online_present_count',
    ];

    foreach ($rows as $row) {
        $canonical = dashboardCanonicalOrganization((string)($row['organization'] ?? ''));
        $key = dashboardNormalizeOrganization($canonical);
        if ($key === '') continue;

        if (!isset($merged[$key])) {
            $row['organization'] = $canonical;
            foreach ($numericFields as $field) $row[$field] = (int)($row[$field] ?? 0);
            $merged[$key] = $row;
            continue;
        }

        foreach ($numericFields as $field) {
            $merged[$key][$field] = (int)($merged[$key][$field] ?? 0) + (int)($row[$field] ?? 0);
        }
    }

    $result = array_values($merged);
    usort($result, static function (array $a, array $b): int {
        $byConfirmed = (int)($b['confirmed_count'] ?? 0) <=> (int)($a['confirmed_count'] ?? 0);
        if ($byConfirmed !== 0) return $byConfirmed;
        $byTotal = (int)($b['total'] ?? 0) <=> (int)($a['total'] ?? 0);
        if ($byTotal !== 0) return $byTotal;
        return strcmp((string)($a['organization'] ?? ''), (string)($b['organization'] ?? ''));
    });
    return $result;
}
