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
            . '<script src="/dashboard/reporting-client.js?v=20260911-org3"></script>';

        return str_ireplace('</body>', $assets . '</body>', $html);
    });
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
        [$category, $label] = dashboardOrganizationCategory((string)($org['organization'] ?? ''));
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

    $offlinePct = dashboardPct($offlineConfirmed, $confirmed);
    $onlinePct = dashboardPct($onlineConfirmed, $confirmed);
    $checkedInPct = dashboardPct($checkedIn, $offlineConfirmed);
    $onlinePresentPct = dashboardPct($onlinePresent, $onlineConfirmed);
    $factPct = dashboardPct($fact, $confirmed);

    $organizationBlock = '';
    if ((int)$stats['unknown_orgs'] === 0 && (int)$stats['unknown_people'] === 0) {
        $governmentOrgPct = dashboardPct((int)$stats['government_orgs'], (int)$stats['organizations']);
        $privateOrgPct = dashboardPct((int)$stats['private_orgs'], (int)$stats['organizations']);
        $organizerOrgPct = dashboardPct((int)$stats['organizer_orgs'], (int)$stats['organizations']);

        $governmentPeoplePct = dashboardPct((int)$stats['government_people'], $confirmed);
        $privatePeoplePct = dashboardPct((int)$stats['private_people'], $confirmed);
        $organizerPeoplePct = dashboardPct((int)$stats['organizer_people'], $confirmed);

        $organizationBlock = 'Организации:' . "\n"
            . '• государственные — ' . $stats['government_orgs'] . ' орг. (' . $governmentOrgPct . '%); ' . $stats['government_people'] . ' чел. (' . $governmentPeoplePct . '%)' . "\n"
            . '• частные/коммерческие — ' . $stats['private_orgs'] . ' орг. (' . $privateOrgPct . '%); ' . $stats['private_people'] . ' чел. (' . $privatePeoplePct . '%)' . "\n"
            . '• организаторы — ' . $stats['organizer_orgs'] . ' орг. (' . $organizerOrgPct . '%); ' . $stats['organizer_people'] . ' чел. (' . $organizerPeoplePct . '%)' . "\n"
            . '  РЦЛСМО — ' . $o['РЦЛСМО'] . '; ЦВИОД — ' . $o['ЦВИОД'] . "\n\n";
    }

    return 'Форум 07.10.2026' . "\n"
        . 'Зарегистрировано: ' . $confirmed . ' участников / ' . $stats['organizations'] . ' организаций' . "\n\n"
        . $organizationBlock
        . 'Формат участия:' . "\n"
        . '• очно — ' . $offlineConfirmed . ' (' . $offlinePct . '%); пришли — ' . $checkedIn . ' (' . $checkedInPct . '% от очных)' . "\n"
        . '• онлайн — ' . $onlineConfirmed . ' (' . $onlinePct . '%); факт ≥15 мин — ' . $onlinePresent . ' (' . $onlinePresentPct . '% от онлайн)' . "\n"
        . '• лист ожидания — ' . $waitlist . "\n\n"
        . 'Факт участия: ' . $fact . ' (' . $factPct . '% от зарегистрированных)';
}
