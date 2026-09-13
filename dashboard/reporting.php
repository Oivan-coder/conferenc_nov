<?php

require_once __DIR__ . '/speakers.php';
require_once __DIR__ . '/organization-analytics.php';

if (!defined('DASHBOARD_PRINT_CLIENT_INJECTED')) {
    define('DASHBOARD_PRINT_CLIENT_INJECTED', true);
    ob_start(static function (string $html): string {
        if (stripos($html, '</body>') === false) return $html;

        $config = json_encode([
            'speakerNames' => dashboardSpeakerNames(),
            'organizationAliases' => dashboardOrganizationAliases(),
            'organizationMeta' => dashboardOrganizationClientMap(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $assets = '<script src="/dashboard/print-client.js?v=20260910-2"></script>'
            . '<script>window.DASHBOARD_REPORTING_CONFIG=' . $config . ';</script>'
            . '<script src="/dashboard/reporting-client.js?v=20260911-org3"></script>'
            . '<script>document.addEventListener("DOMContentLoaded",function(){var el=document.getElementById("briefText");if(!el)return;el.textContent=el.textContent.split("\\n").filter(function(line){return !line.includes("названия организаций требуют проверки")&&!line.includes("неуточнённые организации");}).join("\\n").replace(/\\n{3,}/g,"\\n\\n");});</script>';

        return str_ireplace('</body>', $assets . '</body>', $html);
    });
}

function dashboardMinisterOrganizationCategory(string $organization): array {
    [$category, $label] = dashboardOrganizationCategory($organization);
    if ($category !== 'unknown') return [$category, $label];

    $normalized = dashboardNormalizeOrganization($organization);

    if ($normalized === 'kdl'
        || $normalized === 'независимый эксперт'
        || str_contains($normalized, 'многопрофильный медицинский центр')) {
        return ['private', 'Частная / коммерческая организация'];
    }

    foreach ([
        'больница',
        'ркод',
        'гц гсэн',
        'цкдл ро',
        'кдц здоровье',
        'елабужская центральная районная больница',
        'ерамишанцева',
        'липецкая городская больница',
        'домодедовская больница',
        'онкологический диспансер',
    ] as $marker) {
        if (str_contains($normalized, $marker)) {
            return ['government', 'Государственная организация'];
        }
    }

    return ['private', 'Частная / коммерческая организация'];
}

function dashboardLeadershipStats(array $organizations): array {
    $organizations = dashboardMergeOrganizationRows($organizations);
    $stats = [
        'organizations' => 0,
        'government_orgs' => 0,
        'government_people' => 0,
        'private_orgs' => 0,
        'private_people' => 0,
        'organizer_orgs' => 0,
        'organizer_people' => 0,
        'unknown_orgs' => 0,
        'unknown_people' => 0,
        'organizers' => ['РЦЛСМО' => 0, 'ЦВИОД' => 0],
    ];

    foreach ($organizations as $org) {
        $confirmed = (int)($org['offline_count'] ?? 0) + (int)($org['online_count'] ?? 0);
        if ($confirmed <= 0) continue;

        $stats['organizations']++;
        [$category, $label] = dashboardMinisterOrganizationCategory((string)($org['organization'] ?? ''));
        if ($category === 'government') {
            $stats['government_orgs']++;
            $stats['government_people'] += $confirmed;
        } elseif ($category === 'organizer') {
            $stats['organizer_orgs']++;
            $stats['organizer_people'] += $confirmed;
            if (isset($stats['organizers'][$label])) $stats['organizers'][$label] += $confirmed;
        } elseif ($category === 'private') {
            $stats['private_orgs']++;
            $stats['private_people'] += $confirmed;
        } else {
            $stats['unknown_orgs']++;
            $stats['unknown_people'] += $confirmed;
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

    $brief = 'Форум лабораторных инноваций МО — 07.10.2026' . "\n"
        . 'Зарегистрировано: ' . $confirmed . ' участников / ' . $stats['organizations'] . ' организаций' . "\n\n"
        . 'Представленность:' . "\n"
        . '• государственные организации — ' . $stats['government_orgs'] . ' (' . $governmentOrgPct . '%); ' . $stats['government_people'] . ' участников (' . $governmentPeoplePct . '%)' . "\n"
        . '• частные/коммерческие организации и независимые эксперты — ' . $stats['private_orgs'] . ' (' . $privateOrgPct . '%); ' . $stats['private_people'] . ' участников (' . $privatePeoplePct . '%)' . "\n"
        . '• организаторы — ' . $stats['organizer_orgs'] . ' (' . $organizerOrgPct . '%); ' . $stats['organizer_people'] . ' участников (' . $organizerPeoplePct . '%)' . "\n"
        . '  РЦЛСМО — ' . $o['РЦЛСМО'] . '; ЦВИОД — ' . $o['ЦВИОД'] . "\n\n"
        . 'Формат участия:' . "\n"
        . '• очно — ' . $offlineConfirmed . ' (' . $offlinePct . '%)' . "\n"
        . '• онлайн — ' . $onlineConfirmed . ' (' . $onlinePct . '%)';

    if ($fact > 0) {
        $brief .= "\n\n" . 'Факт участия:' . "\n"
            . '• очно — ' . $checkedIn . ' (' . $checkedInPct . '% от зарегистрированных очно)' . "\n"
            . '• онлайн ≥15 мин — ' . $onlinePresent . ' (' . $onlinePresentPct . '% от зарегистрированных онлайн)' . "\n"
            . '• всего — ' . $fact . ' (' . $factPct . '% от зарегистрированных)';
    }

    if ($waitlist > 0) {
        $brief .= "\n" . '• лист ожидания — ' . $waitlist;
    }

    return $brief;
}
