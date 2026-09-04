<?php

function dashboardNormalizePersonName(string $value): string {
    $value = str_replace('ё', 'е', mb_strtolower(trim($value)));
    $value = (string)preg_replace('/\s+/u', ' ', $value);
    return trim($value);
}

function dashboardSpeakerNames(): array {
    return [
        'Татьяна Ивановна Долгих',
        'Александр Польевич Ройтман',
        'Фаниль Салимович Билалов',
        'Дмитрий Геннадьевич Денисов',
        'Мария Георгиевна Ламбакахар',
        'Евгений Юрьевич Никитин',
        'Екатерина Игоревна Ким',
        'Павел Олегович Богомолов',
        'Тигран Гагикович Геворкян',
        'Галина Викторовна Волкова',
        'Антонина Николаевна Зинина',
        'Ольга Николаевна Ткачева',
        'Светлана Александровна Бернс',
        'Анна Сергеевна Омельянович',
        'Мария Сергеевна Извекова',
        'Татьяна Сергеевна Сидорова',
        'Марина Витальевна Сухорукова',
        'Михаил Васильевич Иконников',
        'Андрей Викторович Варивода',
    ];
}

function dashboardSpeakerSet(): array {
    static $set = null;
    if (is_array($set)) return $set;
    $set = [];
    foreach (dashboardSpeakerNames() as $name) {
        $set[dashboardNormalizePersonName($name)] = true;
    }
    return $set;
}

function dashboardIsSpeaker(string $fullName): bool {
    return isset(dashboardSpeakerSet()[dashboardNormalizePersonName($fullName)]);
}

function dashboardParticipantRoleLabel(string $fullName): string {
    return dashboardIsSpeaker($fullName) ? 'Докладчик' : 'Участник';
}
