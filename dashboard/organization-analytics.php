<?php

declare(strict_types=1);

function dashboardNormalizeOrganization(string $value): string {
    $value = str_replace('ё', 'е', mb_strtolower(trim($value)));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    $value = str_replace(['«', '»', '“', '”', '"'], '', $value);
    $value = (string)preg_replace('/\s*([.,])\s*/u', '$1', $value);
    return trim($value);
}

function dashboardOrganizationRegistry(): array {
    return [
        'rclsmo' => [
            'display' => 'РЦЛСМО',
            'category' => 'organizer',
            'label' => 'РЦЛСМО',
            'aliases' => [
                'РЦЛСМО',
                'Референс-центр лабораторной службы Московской области',
                'Референс-центр лабораторной службы МО',
            ],
        ],
        'cviod' => [
            'display' => 'ЦВИОД',
            'category' => 'organizer',
            'label' => 'ЦВИОД',
            'aliases' => [
                'ЦВИОД',
                'ГКУ МО ЦВИОД',
                'Центр внедрения изменений',
                'ГКУ МО Центр внедрения изменений',
            ],
        ],
        'moniki' => [
            'display' => 'МОНИКИ им. Владимирского',
            'category' => 'government',
            'aliases' => [
                'МОНИКИ',
                'МОНИКИ им. Владимирского',
                'МОНИКИ им Владимирского',
                'ГБУЗ МО МОНИКИ',
                'ГБУЗ МО МОНИКИ им. М.Ф. Владимирского',
                'ГБУЗ МО МОНИКИ им М.Ф. Владимирского',
            ],
        ],
        'uodkb' => [
            'display' => 'УОДКБ им. Горячева',
            'category' => 'government',
            'aliases' => [
                'ГУЗ УОДКБ',
                'ГУЗ УОДКБ им Горячева',
                'ГУЗ УОДКБ им. Горячева',
                'ГУЗ УОДКБ имени Ю. Ф.Горячева',
                'ГУЗ УОДКБ имени Ю. Ф. Горячева',
                'ГУЗ УОДКБ имени Ю.Ф.Горячева',
                'ГУЗ УОДКБ имени Ю.Ф. Горячева',
                'ГУЗ «УОДКБ им. Ю.Ф. Горячева»',
            ],
        ],
        'korolev_b' => [
            'display' => 'Королевская Б',
            'category' => 'government',
            'aliases' => [
                'Королевская Б',
                'Королёвская Б',
                'Королевская больница',
                'Королёвская больница',
                'ГБУЗ МО Королевская больница',
                'ГБУЗ МО Королёвская больница',
            ],
        ],
        'istra_b' => [
            'display' => 'Истринская КБ',
            'category' => 'government',
            'aliases' => ['Истринская КБ', 'ГБУЗ МО «Истринская клиническая больница»', 'ГБУЗ МО Истринская клиническая больница'],
        ],
        'odintsovo_b' => [
            'display' => 'Одинцовская ОБ',
            'category' => 'government',
            'aliases' => ['Одинцовская ОБ', 'ГБУЗ МО «Одинцовская областная больница»', 'ГБУЗ МО Одинцовская областная больница'],
        ],
        'vidnoe_pc' => [
            'display' => 'Видновский ПЦ',
            'category' => 'government',
            'aliases' => ['Видновский ПЦ', 'ГБУЗ МО «Видновский перинатальный центр»', 'ГБУЗ МО Видновский перинатальный центр'],
        ],
        'kashira_b' => [
            'display' => 'Каширская Б',
            'category' => 'government',
            'aliases' => ['Каширская Б', 'ГБУЗ МО «Каширская больница»', 'ГБУЗ МО Каширская больница'],
        ],
        'vlasikha' => [
            'display' => 'Поликлиника г.о. Власиха',
            'category' => 'government',
            'aliases' => ['Поликлиника г.о. Власиха', 'ГБУЗ МО Поликлиника городского округа Власиха'],
        ],
        'moknd' => [
            'display' => 'МОКНД',
            'category' => 'government',
            'aliases' => ['МОКНД', 'ГБУЗ МО МОКНД'],
        ],
        'tuymazy' => [
            'display' => 'Туймазинская ЦРБ',
            'category' => 'government',
            'aliases' => ['Туймазинская ЦРБ', 'ГБУЗ РБ Туймазинская ЦРБ'],
        ],
        'rmgc' => [
            'display' => 'РМГЦ',
            'category' => 'government',
            'aliases' => ['РМГЦ', 'ГБУЗ РМГЦ'],
        ],
        'sgod' => [
            'display' => 'СГОД им. А.А. Задорожного',
            'category' => 'government',
            'aliases' => ['ГБУЗС СГОД им А А. Задорожного', 'СГОД им. А.А. Задорожного'],
        ],
        'kommunarka' => [
            'display' => 'ММКЦ «Коммунарка»',
            'category' => 'government',
            'aliases' => ['ГБУЗ "ММКЦ "Коммунарка" ДЗМ"', 'ММКЦ Коммунарка', 'ММКЦ «Коммунарка»'],
        ],
        'donetsk_gkb7' => [
            'display' => 'ГКБ №7 г. Донецка',
            'category' => 'government',
            'aliases' => ['ГБУЗ ДНР " ГКБ № 7 г. Донецка"', 'ГКБ №7 г. Донецка', 'ГКБ № 7 г. Донецка'],
        ],
        'angapov' => [
            'display' => 'БСМП им. В.В. Ангапова',
            'category' => 'government',
            'aliases' => ['Бсмп им. В.В.Ангапова', 'БСМП им. В.В. Ангапова'],
        ],
        'surgut_gkp5' => [
            'display' => 'Сургутская ГКП №5',
            'category' => 'government',
            'aliases' => ['БУ «Сургутская городская клиническая поликлиника 5»', 'Сургутская городская клиническая поликлиника 5', 'Сургутская ГКП №5'],
        ],
        'nokb_yatskiv' => [
            'display' => 'НОКБ им. В.И. Яцкив',
            'category' => 'government',
            'aliases' => ['БУ НОКБ им. В. И. Яцкив', 'НОКБ им. В.И. Яцкив'],
        ],
        'vgkp1' => [
            'display' => 'ВГКП №1',
            'category' => 'government',
            'aliases' => ['БУЗ ВО ВГКП 1', 'ВГКП №1', 'ВГКП 1'],
        ],
        'erba' => [
            'display' => 'Эрба Рус',
            'category' => 'private',
            'aliases' => ['АО "Эрба Рус"', 'АО «Эрба Рус»', 'Эрба Рус'],
        ],
        'rfarm' => [
            'display' => 'Р-Фарм',
            'category' => 'private',
            'aliases' => ['АО Р-Фарм', 'Р-Фарм'],
        ],
        'diakon' => [
            'display' => 'ДИАКОН',
            'category' => 'private',
            'aliases' => ['ГК "ДИАКОН"', 'ГК «ДИАКОН»', 'ДИАКОН'],
        ],
        'helix' => [
            'display' => 'ХЕЛИКС',
            'category' => 'private',
            'aliases' => ['Лабораторная служба «ХЕЛИКС»', 'Лабораторная служба ХЕЛИКС', 'ХЕЛИКС'],
        ],
        'niarmedik' => [
            'display' => 'НИАРМЕДИК',
            'category' => 'private',
            'aliases' => ['Ниармедик', 'НИАРМЕДИК'],
        ],
        'ils' => [
            'display' => 'ИЛС',
            'category' => 'private',
            'aliases' => ['ООО "ИЛС"', 'ООО «ИЛС»', 'ИЛС'],
        ],
        'biofarmaholding' => [
            'display' => 'БиоФАРМАХОЛДИНГ',
            'category' => 'private',
            'aliases' => ['ООО «БиоФАРМАХОЛДИНГ»', 'ООО "БиоФАРМАХОЛДИНГ"', 'БиоФАРМАХОЛДИНГ'],
        ],
        'smartbiotest' => [
            'display' => 'СмартБиоТест',
            'category' => 'private',
            'aliases' => ['ООО «СмартБиоТест»', 'ООО "СмартБиоТест"', 'СмартБиоТест'],
        ],
        'medica_product' => [
            'display' => 'Медика Продакт',
            'category' => 'private',
            'aliases' => ['ООО Медика Продакт', 'Медика Продакт'],
        ],
        'omb' => [
            'display' => 'ОМБ',
            'category' => 'private',
            'aliases' => ['ООО ОМБ', 'ОМБ'],
        ],
        'skylab' => [
            'display' => 'Скайлаб',
            'category' => 'private',
            'aliases' => ['ООО Скайлаб', 'Скайлаб'],
        ],
        'soptmed' => [
            'display' => 'СОптМед',
            'category' => 'private',
            'aliases' => ['ООО СОптМед', 'СОптМед'],
        ],
        'mknts_loginov' => [
            'display' => 'МКНЦ им. А.С. Логинова',
            'category' => 'government',
            'aliases' => ['МКНЦ им А С Логинова', 'МКНЦ им. А.С. Логинова'],
        ],
        'mrnc_tsyba' => [
            'display' => 'МРНЦ им. А.Ф. Цыба',
            'category' => 'government',
            'aliases' => ['Мрнц имени А.Ф. Цыба -филиал ФГБУ «НМИЦ радиологии «Минздрава России', 'МРНЦ им. А.Ф. Цыба'],
        ],
        'slavyanskaya_crb' => [
            'display' => 'Славянская ЦРБ',
            'category' => 'government',
            'aliases' => ['МУЗ КК Славянская ЦРБ', 'Славянская ЦРБ'],
        ],
        'rnpс_pif' => [
            'display' => 'РНПЦ ПиФ',
            'category' => 'government',
            'aliases' => ['ГУ РНПЦ ПиФ', 'РНПЦ ПиФ'],
        ],
        'piuv' => [
            'display' => 'ПИУВ — филиал РМАНПО',
            'category' => 'government',
            'aliases' => ['ПИУВ-филиал РМАНПО', 'ПИУВ — филиал РМАНПО'],
        ],
        'pspbgu' => [
            'display' => 'ПСПбГМУ им. И.П. Павлова',
            'category' => 'government',
            'aliases' => ['ПСПбГМУ им. И.П. Павлова'],
        ],
        'rmanpo' => [
            'display' => 'РМАНПО',
            'category' => 'government',
            'aliases' => ['РМАНПО'],
        ],
        'sgb5' => [
            'display' => 'СГБ №5',
            'category' => 'government',
            'aliases' => ['СГБ 5', 'СГБ №5'],
        ],
        'kvd8' => [
            'display' => 'КВД №8',
            'category' => 'government',
            'aliases' => ['СПБ ГБУЗ КВД 8', 'КВД №8'],
        ],
        'rnimu' => [
            'display' => 'РНИМУ им. Н.И. Пирогова',
            'category' => 'government',
            'aliases' => ['ФГАОУ ВО РНИМУ им. Н.И. Пирогова Минздрава России (Пироговский Университет)', 'РНИМУ им. Н.И. Пирогова'],
        ],
        'burdenko' => [
            'display' => 'НМИЦ нейрохирургии им. Н.Н. Бурденко',
            'category' => 'government',
            'aliases' => ['ФГАУ "НМИЦ Нейрохирургии им. Ак. Н.Н. Бурденко" Минздрава России', 'НМИЦ нейрохирургии им. Н.Н. Бурденко'],
        ],
        'rostgmu' => [
            'display' => 'РостГМУ',
            'category' => 'government',
            'aliases' => ['ФГБОУ ВО ростГМУ (временно не работаю)', 'РостГМУ', 'ростГМУ'],
        ],
        'nmic_rk' => [
            'display' => 'НМИЦ РК Минздрава России',
            'category' => 'government',
            'aliases' => ['ФГБУ НМИЦ РК МИНЗДРАВА РФ', 'НМИЦ РК Минздрава России'],
        ],
        'fmba_blood' => [
            'display' => 'Центр крови ФМБА России',
            'category' => 'government',
            'aliases' => ['ФГБУЗ Центр крови ФМБА России', 'Центр крови ФМБА России'],
        ],
        'makeevka_blood' => [
            'display' => 'Республиканский центр крови, Макеевка',
            'category' => 'government',
            'aliases' => ['Филиал ГБУ ДНР "Республиканский центр крови" в г. Макеевке', 'Республиканский центр крови, Макеевка'],
        ],
        'formit' => [
            'display' => 'ФОРМИТ',
            'category' => 'private',
            'aliases' => ['Формит', 'ФОРМИТ'],
        ],
        'mozhayskaya' => [
            'display' => 'Можайская Б',
            'category' => 'government',
            'aliases' => ['Можайская Б', 'Можайская Больница', 'Можайская больница'],
        ],
        'shakhovskaya' => [
            'display' => 'Шаховская Б',
            'category' => 'government',
            'aliases' => ['Шаховская Б', 'Шаховская Больница', 'Шаховская больница'],
        ],
        'lobnenskaya' => [
            'display' => 'Лобненская Б',
            'category' => 'government',
            'aliases' => ['Лобненская Б', 'Лобненская больница'],
        ],
        'orekhovo_zuevo' => [
            'display' => 'Орехово-Зуевская Б',
            'category' => 'government',
            'aliases' => ['Орехово-Зуевская Б', 'Орехово-Зуевская больница'],
        ],
        'chekhovskaya' => [
            'display' => 'Чеховская Б',
            'category' => 'government',
            'aliases' => ['Чеховская Б', 'Чеховская больница'],
        ],
    ];
}

function dashboardOrganizationLookup(): array {
    static $lookup = null;
    if (is_array($lookup)) return $lookup;

    $lookup = [];
    foreach (dashboardOrganizationRegistry() as $key => $entry) {
        $aliases = array_values(array_unique(array_merge(
            [(string)$entry['display']],
            array_map('strval', $entry['aliases'] ?? [])
        )));
        foreach ($aliases as $alias) {
            $lookup[dashboardNormalizeOrganization($alias)] = [
                'key' => (string)$key,
                'display' => (string)$entry['display'],
                'category' => (string)$entry['category'],
                'label' => (string)($entry['label'] ?? ''),
                'known' => true,
            ];
        }
    }
    return $lookup;
}

function dashboardGovernmentOrganizationNames(): array {
    static $names = null;
    if (is_array($names)) return $names;

    $names = [];
    $path = dirname(__DIR__) . '/js/data/organizations-2026.js';
    if (is_readable($path)) {
        $source = (string)file_get_contents($path);
        if (preg_match_all("/'([^']+)'/u", $source, $matches)) {
            foreach ($matches[1] as $name) {
                $value = trim((string)$name);
                if ($value !== '') $names[] = $value;
            }
        }
    }
    return array_values(array_unique($names));
}

function dashboardGovernmentOrganizations(): array {
    static $set = null;
    if (is_array($set)) return $set;

    $set = [];
    foreach (dashboardGovernmentOrganizationNames() as $name) {
        $set[dashboardNormalizeOrganization($name)] = true;
    }
    return $set;
}

function dashboardCompactOrganizationDisplay(string $value, int $maxLength = 52): string {
    $display = trim((string)preg_replace('/\s+/u', ' ', $value));
    if ($display === '') return '';

    $display = (string)preg_replace(
        '/^(?:ГБУЗ\s+МО|ГКУ\s+МО|СПБ\s+ГБУЗ|ФГАОУ\s+ВО|ФГБОУ\s+ВО|ФГБУЗ|ФГБУ|ФГАУ|ГБУЗС|ГБУЗ|ГКУЗ|ГКУ|ГБУ|ГАУЗ|ГАУ|ГУЗ|БУЗ|БУ|МУЗ|ООО|АО|ПАО|ЗАО)\s+/iu',
        '',
        $display
    );
    $display = trim($display, " \t\n\r\0\x0B\"«»");
    $display = (string)preg_replace('/\s+Минздрава\s+(?:России|РФ)$/iu', '', $display);
    $display = trim((string)preg_replace('/\s+/u', ' ', $display));

    if (mb_strlen($display, 'UTF-8') <= $maxLength) return $display;

    $cut = mb_substr($display, 0, max(1, $maxLength - 1), 'UTF-8');
    $space = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($space !== false && $space >= (int)floor($maxLength * 0.65)) {
        $cut = mb_substr($cut, 0, $space, 'UTF-8');
    }
    return rtrim($cut, " ,.;:-–—") . '…';
}

function dashboardOrganizationInfo(string $organization): array {
    $raw = trim($organization);
    if ($raw === '') {
        return ['key' => '', 'display' => '', 'category' => 'unknown', 'label' => '', 'known' => false, 'raw' => ''];
    }

    $normalized = dashboardNormalizeOrganization($raw);
    $lookup = dashboardOrganizationLookup();
    if (isset($lookup[$normalized])) {
        return $lookup[$normalized] + ['raw' => $raw];
    }

    if (isset(dashboardGovernmentOrganizations()[$normalized])) {
        return [
            'key' => 'government:' . $normalized,
            'display' => $raw,
            'category' => 'government',
            'label' => '',
            'known' => true,
            'raw' => $raw,
        ];
    }

    $category = 'unknown';
    if (preg_match('/^\s*(ооо|ао|пао|зао|ип)\b/iu', $raw)
        || preg_match('/^\s*гк\s*[«" ]/iu', $raw)
        || str_contains($normalized, 'лабораторная служба хеликс')
        || preg_match('/\bниармедик\b/iu', $raw)
        || $normalized === 'формит') {
        $category = 'private';
    } elseif (preg_match('/(^|[\s«"(])(?:гбузс|гбуз|гкуз|гку|гбу|гауз|гау|гуз|буз|муз|фгбу|фгбуз|фгау|фгаоу|фгбоу|фбун|фбуз|фкуз|фку|фгку|гнц|гну|бу)(?=$|[\s«"(\-])/iu', $raw)) {
        $category = 'government';
    } else {
        foreach (['министерство здравоохранения', 'минздрав', 'минздрава', 'фмба', 'рманпо', 'пспбгму', 'рниму', 'ростгму', 'нмиц', 'мкнц', 'рнпц', 'црб', 'гкб', 'сгб', 'бсмп', 'квд', 'в/ч'] as $marker) {
            if (str_contains($normalized, $marker)) {
                $category = 'government';
                break;
            }
        }
    }

    return [
        'key' => 'raw:' . $normalized,
        'display' => dashboardCompactOrganizationDisplay($raw),
        'category' => $category,
        'label' => '',
        'known' => false,
        'raw' => $raw,
    ];
}

function dashboardOrganizationAliases(): array {
    $aliases = [];
    foreach (dashboardOrganizationLookup() as $alias => $entry) {
        $aliases[$alias] = (string)$entry['display'];
    }
    foreach (dashboardGovernmentOrganizationNames() as $name) {
        $key = dashboardNormalizeOrganization($name);
        if (!isset($aliases[$key])) $aliases[$key] = $name;
    }
    return $aliases;
}

function dashboardOrganizationClientMap(): array {
    $map = [];
    foreach (dashboardOrganizationLookup() as $alias => $entry) {
        $category = (string)$entry['category'];
        $label = $category === 'organizer'
            ? (string)$entry['label']
            : ($category === 'government' ? 'Государственная' : ($category === 'private' ? 'Частная / коммерческая' : 'Не определено'));
        $map[$alias] = [
            'display' => (string)$entry['display'],
            'category' => $category,
            'label' => $label,
            'known' => true,
        ];
    }
    foreach (dashboardGovernmentOrganizationNames() as $name) {
        $key = dashboardNormalizeOrganization($name);
        if (!isset($map[$key])) {
            $map[$key] = [
                'display' => $name,
                'category' => 'government',
                'label' => 'Государственная',
                'known' => true,
            ];
        }
    }
    return $map;
}

function dashboardCanonicalOrganization(string $organization): string {
    return (string)dashboardOrganizationInfo($organization)['display'];
}

function dashboardOrganizationKey(string $organization): string {
    return (string)dashboardOrganizationInfo($organization)['key'];
}

function dashboardOrganizationIsNormalized(string $organization): bool {
    return (bool)dashboardOrganizationInfo($organization)['known'];
}

function dashboardBadgeOrganizationName(string $organization): string {
    $display = (string)dashboardOrganizationInfo($organization)['display'];
    return dashboardCompactOrganizationDisplay($display, 42);
}

function dashboardOrganizerLabel(string $organization): ?string {
    $info = dashboardOrganizationInfo($organization);
    return $info['category'] === 'organizer' ? (string)$info['label'] : null;
}

function dashboardOrganizationCategory(string $organization): array {
    $info = dashboardOrganizationInfo($organization);
    $category = (string)$info['category'];
    if ($category === 'organizer') return ['organizer', (string)$info['label']];
    if ($category === 'government') return ['government', 'Государственная организация'];
    if ($category === 'private') return ['private', 'Частная / коммерческая организация'];
    return ['unknown', 'Не определено'];
}

function dashboardMergeOrganizationRows(array $rows): array {
    $merged = [];
    $numericFields = [
        'total', 'confirmed_count', 'offline_count', 'online_count', 'waitlist_count',
        'checked_in_count', 'online_present_count',
    ];

    foreach ($rows as $row) {
        $rawOrganization = (string)($row['organization'] ?? '');
        $info = dashboardOrganizationInfo($rawOrganization);
        $key = (string)$info['key'];
        if ($key === '') continue;

        if (!isset($merged[$key])) {
            $row['organization'] = (string)$info['display'];
            $row['organization_known'] = (bool)$info['known'];
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
