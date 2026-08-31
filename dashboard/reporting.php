<?php

function dashboardNormalizeOrganization(string $value): string {
    $value = mb_strtolower(trim($value));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    return trim(str_replace(['«', '»', '"'], '', $value));
}

function dashboardOrganizerLabel(string $organization): ?string {
    $n = dashboardNormalizeOrganization($organization);
    if ($n === 'рцлсмо' || str_contains($n, 'референс-центр лабораторной службы')) return 'РЦЛСМО';
    if (str_contains($n, 'цвиод') || str_contains($n, 'центр внедрения изменений')) return 'ЦВИОД';
    if (str_contains($n, 'министерство здравоохранения') && (str_contains($n, 'мо') || str_contains($n, 'московской области'))) return 'Минздрав МО';
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
            foreach ($matches[1] as $name) $set[dashboardNormalizeOrganization((string)$name)] = true;
        }
    }
    return $set;
}

function dashboardOrganizationCategory(string $organization): array {
    $organizer = dashboardOrganizerLabel($organization);
    if ($organizer !== null) return ['organizer', $organizer];

    $normalized = dashboardNormalizeOrganization($organization);
    if (isset(dashboardGovernmentOrganizations()[$normalized])) return ['government', 'Государственная организация'];
    if (preg_match('/^(гбуз|гку|гбу|гауз|фгбу|фбун|фгаоу|фгбоу|пмгму)\b/iu', trim($organization))) {
        return ['government', 'Государственная организация'];
    }
    if (str_contains($normalized, 'московский областной медицинский колледж')) {
        return ['government', 'Государственная организация'];
    }
    return ['private', 'Частная / иная организация'];
}

function dashboardLeadershipStats(array $organizations): array {
    $stats = [
        'organizations' => 0,
        'government_orgs' => 0,
        'government_people' => 0,
        'private_orgs' => 0,
        'private_people' => 0,
        'organizer_orgs' => 0,
        'organizer_people' => 0,
        'organizers' => ['РЦЛСМО' => 0, 'ЦВИОД' => 0, 'Минздрав МО' => 0],
    ];

    foreach ($organizations as $org) {
        $confirmed = (int)($org['offline_count'] ?? 0) + (int)($org['online_count'] ?? 0);
        if ($confirmed <= 0) continue;

        $stats['organizations']++;
        [$category, $label] = dashboardOrganizationCategory((string)($org['organization'] ?? ''));
        if ($category === 'government') {
            $stats['government_orgs']++;
            $stats['government_people'] += $confirmed;
        } elseif ($category === 'organizer') {
            $stats['organizer_orgs']++;
            $stats['organizer_people'] += $confirmed;
            if (isset($stats['organizers'][$label])) $stats['organizers'][$label] += $confirmed;
        } else {
            $stats['private_orgs']++;
            $stats['private_people'] += $confirmed;
        }
    }

    return $stats;
}

function dashboardLeadershipBrief(array $stats, int $offlineConfirmed, int $onlineConfirmed, int $checkedIn, int $onlinePresent, int $waitlist): string {
    $confirmed = $offlineConfirmed + $onlineConfirmed;
    $fact = $checkedIn + $onlinePresent;
    $o = $stats['organizers'];

    return 'Форум 07.10.2026. Зарегистрировано ' . $confirmed . ' участников из ' . $stats['organizations'] . ' организаций: '
        . $stats['government_orgs'] . ' гос. (' . $stats['government_people'] . ' чел.), '
        . $stats['private_orgs'] . ' частных/иных (' . $stats['private_people'] . ' чел.), '
        . $stats['organizer_orgs'] . ' организатора (' . $stats['organizer_people'] . ' чел.). '
        . 'Организаторы: РЦЛСМО — ' . $o['РЦЛСМО'] . ', ЦВИОД — ' . $o['ЦВИОД'] . ', Минздрав МО — ' . $o['Минздрав МО'] . '. '
        . 'Очно: ' . $offlineConfirmed . ' зарегистрировано, пришли ' . $checkedIn . '. '
        . 'Онлайн: ' . $onlineConfirmed . ' зарегистрировано, факт ≥15 мин — ' . $onlinePresent . '. '
        . 'Факт участия всего — ' . $fact . '.'
        . ($waitlist > 0 ? ' Лист ожидания — ' . $waitlist . '.' : '');
}
