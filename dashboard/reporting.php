<?php

if (!defined('DASHBOARD_PRINT_CLIENT_INJECTED')) {
    define('DASHBOARD_PRINT_CLIENT_INJECTED', true);
    ob_start(static function (string $html): string {
        if (stripos($html, '</body>') === false) return $html;
        $asset = '<script src="/dashboard/print-client.js?v=20260831-1"></script>';
        return str_ireplace('</body>', $asset . '</body>', $html);
    });
}

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

function dashboardPct(int $part, int $total): int {
    return $total > 0 ? (int)round($part / $total * 100) : 0;
}

function dashboardLeadershipBrief(array $stats, int $offlineConfirmed, int $onlineConfirmed, int $checkedIn, int $onlinePresent, int $waitlist): string {
    $confirmed = $offlineConfirmed + $onlineConfirmed;
    $fact = $checkedIn + $onlinePresent;
    $o = $stats['organizers'];

    $governmentOrgPct = dashboardPct((int)$stats['government_orgs'], (int)$stats['organizations']);
    $privateOrgPct = dashboardPct((int)$stats['private_orgs'], (int)$stats['organizations']);
    $organizerOrgPct = dashboardPct((int)$stats['organizer_orgs'], (int)$stats['organizations']);

    $governmentPeoplePct = dashboardPct((int)$stats['government_people'], $confirmed);
    $privatePeoplePct = dashboardPct((int)$stats['private_people'], $confirmed);
    $organizerPeoplePct = dashboardPct((int)$stats['organizer_people'], $confirmed);

    $offlinePct = dashboardPct($offlineConfirmed, $confirmed);
    $onlinePct = dashboardPct($onlineConfirmed, $confirmed);
    $checkedInPct = dashboardPct($checkedIn, $offlineConfirmed);
    $onlinePresentPct = dashboardPct($onlinePresent, $onlineConfirmed);
    $factPct = dashboardPct($fact, $confirmed);

    return 'Форум 07.10.2026' . "\n"
        . 'Зарегистрировано: ' . $confirmed . ' участников / ' . $stats['organizations'] . ' организаций' . "\n\n"
        . 'Организации:' . "\n"
        . '• государственные — ' . $stats['government_orgs'] . ' орг. (' . $governmentOrgPct . '%); ' . $stats['government_people'] . ' чел. (' . $governmentPeoplePct . '%)' . "\n"
        . '• частные/иные — ' . $stats['private_orgs'] . ' орг. (' . $privateOrgPct . '%); ' . $stats['private_people'] . ' чел. (' . $privatePeoplePct . '%)' . "\n"
        . '• организаторы — ' . $stats['organizer_orgs'] . ' орг. (' . $organizerOrgPct . '%); ' . $stats['organizer_people'] . ' чел. (' . $organizerPeoplePct . '%)' . "\n"
        . '  РЦЛСМО — ' . $o['РЦЛСМО'] . '; ЦВИОД — ' . $o['ЦВИОД'] . '; Минздрав МО — ' . $o['Минздрав МО'] . "\n\n"
        . 'Формат участия:' . "\n"
        . '• очно — ' . $offlineConfirmed . ' (' . $offlinePct . '%); пришли — ' . $checkedIn . ' (' . $checkedInPct . '% от очных)' . "\n"
        . '• онлайн — ' . $onlineConfirmed . ' (' . $onlinePct . '%); факт ≥15 мин — ' . $onlinePresent . ' (' . $onlinePresentPct . '% от онлайн)' . "\n"
        . '• лист ожидания — ' . $waitlist . "\n\n"
        . 'Факт участия: ' . $fact . ' (' . $factPct . '% от зарегистрированных)';
}
