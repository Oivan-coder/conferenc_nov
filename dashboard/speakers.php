<?php

function dashboardNormalizePersonName(string $value): string {
    $value = str_replace('ё', 'е', mb_strtolower(trim($value)));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    return trim($value);
}

function dashboardPersonNameKey(string $value): string {
    $normalized = dashboardNormalizePersonName($value);
    if ($normalized === '') return '';
    $parts = preg_split('/\s+/u', $normalized) ?: [];
    sort($parts, SORT_STRING);
    return implode(' ', $parts);
}

function dashboardSpeakerNames(): array {
    return [
        'Долгих Татьяна Ивановна',
        'Ройтман Александр Польевич',
        'Билалов Фаниль Салимович',
        'Денисов Дмитрий Геннадьевич',
        'Ламбакахар Мария Георгиевна',
        'Никитин Евгений Юрьевич',
        'Ким Екатерина Игоревна',
        'Богомолов Павел Олегович',
        'Геворкян Тигран Гагикович',
        'Волкова Галина Викторовна',
        'Зинина Антонина Николаевна',
        'Ткачева Ольга Николаевна',
        'Бернс Светлана Александровна',
        'Омельянович Анна Сергеевна',
        'Извекова Мария Сергеевна',
        'Сидорова Татьяна Сергеевна',
        'Сухорукова Марина Витальевна',
        'Иконников Михаил Васильевич',
        'Варивода Андрей Викторович',
    ];
}

function dashboardSpeakerSet(): array {
    static $set = null;
    if (is_array($set)) return $set;
    $set = [];
    foreach (dashboardSpeakerNames() as $name) {
        $set[dashboardPersonNameKey($name)] = true;
    }
    return $set;
}

function dashboardIsSpeaker(string $fullName): bool {
    return isset(dashboardSpeakerSet()[dashboardPersonNameKey($fullName)]);
}

function dashboardParticipantRoleLabel(string $fullName): string {
    return dashboardIsSpeaker($fullName) ? 'Докладчик' : 'Участник';
}
