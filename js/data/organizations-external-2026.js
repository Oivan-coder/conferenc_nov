(function () {
    'use strict';

    const externalOrganizations = [
        'АО БиоХимМак',
        'АО Вектор-Бест-Европа',
        'АО ЛабКвест',
        'АО Центравиамед',
        'Б3-Мед',
        'Витаспектр',
        'ГБУЗ ГП №46 ДЗМ',
        'ГБУЗ МО Бюро СМЭ',
        'ГБУЗ МО ЦПБ СПИД',
        'ГК МЕДСИ',
        'ГК Медскан клиника Хадасса',
        'ГКУ МО ЦВИОД',
        'Клиника Вербенкина',
        'Лабораторная служба «ХЕЛИКС»',
        'Министерство здравоохранения МО',
        'Московский областной медицинский колледж',
        'НПФ Литех',
        'ООО Брегис',
        'ООО Медика Продакт',
        'ООО Эбботт Лэбораториз',
        'ООО ЭЛ',
        'ПМГМУ им. И.М. Сеченова',
        'Референс-центр лабораторной службы',
        'Санофи',
        'Ситилаб',
        'ФБУН ЦНИИ ЭПИДЕМИОЛОГИИ',
        'ФГБУ Клиническая больница №1 УДП РФ',
        'Частное лицо'
    ];

    function appendExternalOrganizations() {
        const list = document.getElementById('organizationOptions');
        if (!list) return;

        const existing = new Set(Array.from(list.options).map((option) => option.value.trim().toLowerCase()));
        externalOrganizations.forEach((organization) => {
            const key = organization.trim().toLowerCase();
            if (existing.has(key)) return;
            const option = document.createElement('option');
            option.value = organization;
            list.appendChild(option);
            existing.add(key);
        });

        if (Array.isArray(window.RCLSMO_ORGANIZATIONS_2026)) {
            externalOrganizations.forEach((organization) => {
                if (!window.RCLSMO_ORGANIZATIONS_2026.includes(organization)) {
                    window.RCLSMO_ORGANIZATIONS_2026.push(organization);
                }
            });
        }

        const input = document.getElementById('organization');
        const hint = input?.parentElement?.querySelector('.r26-form__message');
        if (hint) {
            hint.textContent = 'Начните вводить название организации и выберите подходящий вариант. Если организации нет в списке — укажите название вручную.';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', appendExternalOrganizations);
    } else {
        appendExternalOrganizations();
    }
})();
