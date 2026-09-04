(function () {
    'use strict';

    const VENUE_ADDRESS = 'б-р Строителей, 1, Красногорск, Московская область, 143407';
    const VENUE_ROUTE_URL = 'https://yandex.ru/maps/?rtext=~55.816085%2C37.380812&rtt=auto';

    function loadAgendaStyles() {
        if (document.getElementById('conference-agenda-2026-styles')) return;
        const link = document.createElement('link');
        link.id = 'conference-agenda-2026-styles';
        link.rel = 'stylesheet';
        link.href = '/css/conference-agenda-2026.css?v=20260904-publish1';
        document.head.appendChild(link);
    }

    function updateEventState() {
        const root = document.querySelector('.conference-2026-page');
        if (!root) return;
        const eventStart = new Date('2026-10-07T09:30:00+03:00');
        const now = new Date();
        root.dataset.eventState = now < eventStart ? 'upcoming' : 'started';
    }

    function publishAgenda() {
        const root = document.querySelector('.c26-program');
        if (!root) return;
        document.documentElement.classList.add('agenda-public');

        const eyebrow = root.querySelector('.c26-section-heading > span');
        const title = root.querySelector('.c26-section-heading h2');
        const lead = root.querySelector('.c26-section-heading p');
        const summaryMeta = root.querySelector('[data-agenda-disclosure] summary em');
        if (eyebrow) eyebrow.textContent = 'Программа конференции';
        if (title) title.textContent = 'Один день — четыре содержательных блока';
        if (lead) lead.textContent = 'Актуальная сетка выступлений и состав спикеров форума 7 октября 2026 года.';
        if (summaryMeta) summaryMeta.textContent = '4 блока · 20 докладов · 09:30–18:00';

        const agenda = root.querySelector('.c26-agenda');
        if (!agenda) return;

        const attr = (value) => String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const talk = (time, n, talkTitle, speaker, photo = '', credentials = '', focus = '50% 28%') => `
            <article class="c26-agenda__item agenda-talk">
                <time>${time}</time>
                <div>
                    <div class="agenda-talk__meta"><span class="agenda-talk__tag">${String(n).padStart(2, '0')} · выступление</span></div>
                    <h3>${talkTitle}</h3>
                    <div class="agenda-speaker-row${photo ? '' : ' agenda-speaker-row--text'}">
                        ${photo ? `<button class="agenda-speaker-profile" type="button" aria-haspopup="dialog" aria-expanded="false" aria-label="Открыть фотографию и сведения: ${attr(speaker)}" data-speaker-name="${attr(speaker)}" data-speaker-credentials="${attr(credentials)}" data-speaker-photo="${attr(photo)}" data-speaker-focus="${attr(focus)}"><img class="agenda-speaker-photo" src="/images/speakers/2026/${photo}" alt="${attr(speaker)}" loading="lazy" decoding="async" style="--speaker-focus:${focus}"></button>` : ''}
                        <div class="agenda-speaker-copy"><p class="agenda-speaker"><strong>${speaker}</strong></p>${photo ? '<span class="agenda-speaker-hint">Нажмите для подробностей</span>' : ''}</div>
                    </div>
                </div>
            </article>`;

        const service = (time, label, serviceTitle, extra = '', cls = '') => `
            <article class="agenda-service ${cls}">
                <time>${time}</time>
                <div><div><span>${label}</span><h3>${serviceTitle}</h3>${extra ? `<p class="agenda-speaker">${extra}</p>` : ''}</div></div>
            </article>`;

        const block = (n, blockTitle) => `
            <section class="agenda-block" id="agenda-block-${n}">
                <div class="agenda-block__num">0${n}</div>
                <span class="agenda-block__label">Тематический блок ${n}</span>
                <h3>${blockTitle}</h3>
            </section>`;

        agenda.innerHTML = `
            <div class="agenda-overview">
                <div class="agenda-dayline">
                    <div><span class="agenda-dayline__eyebrow">Научно-практическая конференция</span><strong class="agenda-dayline__date">7 октября 2026</strong></div>
                    <div class="agenda-dayline__hours"><span>Продолжительность</span><strong>09:30–18:00</strong></div>
                </div>
                <div class="agenda-progress" aria-hidden="true"></div>
                <div class="agenda-facts">
                    <div class="agenda-fact"><small>Программа</small><strong>4 тематических блока</strong></div>
                    <div class="agenda-fact"><small>Содержание</small><strong>20 докладов</strong></div>
                    <div class="agenda-fact"><small>Площадка</small><strong>Дом Правительства Московской области</strong></div>
                </div>
            </div>
            <nav class="agenda-nav" aria-label="Навигация по тематическим блокам">
                <a href="#agenda-block-1"><b>01</b><span>Национальные приоритеты</span></a>
                <a href="#agenda-block-2"><b>02</b><span>Маршрут пациента</span></a>
                <a href="#agenda-block-3"><b>03</b><span>Здоровое долголетие</span></a>
                <a href="#agenda-block-4"><b>04</b><span>Цифровой контур</span></a>
            </nav>
            <div class="agenda-timeline">
                ${service('09:30–10:00', 'Сбор участников', 'Регистрация участников')}
                ${service('10:00–10:15', 'Открытие', 'Открытие конференции', '<strong>Максим Васильевич Забелин</strong><strong>Иван Михайлович Гольцев</strong>', 'agenda-opening')}
                ${block(1, 'Лабораторная служба в реализации национальных приоритетов')}
                ${talk('10:15–10:30', '1', 'Национальные проекты — как реализовать лабораторный потенциал?', 'Татьяна Ивановна Долгих', 'dolgikh.webp', 'д.м.н., профессор; ПИУВ — филиал РМАНПО Минздрава России; Учебный центр и ЦВКК')}
                ${talk('10:30–10:45', '2', 'Первичная профилактика в кардиологии — основа здорового долголетия. Как правильно оценить лабораторные показатели', 'Александр Польевич Ройтман', 'roytman.webp', 'д.м.н., профессор; РМАНПО Минздрава России; главный внештатный специалист по КЛД Минздрава России по ЦФО')}
                ${talk('10:45–11:00', '3', 'Централизация лабораторной службы Республики Башкортостан: переход от показателей деятельности лабораторий к показателям здоровья населения', 'Фаниль Салимович Билалов', 'bilalov.webp', 'д.м.н., доцент; главный врач ГБУЗ «Республиканский медико-генетический центр»')}
                ${talk('11:00–11:15', '4', 'Масштаб, качество и доступность: чему государственная лабораторная сеть может научиться у частного сектора', 'Дмитрий Геннадьевич Денисов', 'denisov.webp', 'медицинский директор Лабораторной службы «ХЕЛИКС»')}
                ${talk('11:15–11:40', '5', 'Слепые зоны процессов: потери из-за наших привычек и способы их изменения', 'Мария Георгиевна Ламбакахар', 'lambakakhar.webp', 'к.м.н., доцент кафедры КЛД с курсом лабораторной иммунологии ФГАОУ ДПО РМАНПО')}
                ${service('11:40–11:55', 'Перерыв', 'Кофе-брейк')}
                ${block(2, 'От исследования к решению: рациональная диагностика и маршрут пациента')}
                ${talk('11:55–12:10', '6', 'Посев: клиническая необходимость или рутинный анализ?', 'Евгений Юрьевич Никитин', 'nikitin.webp', 'врач — клинический фармаколог, к.м.н.')}
                ${talk('12:10–12:25', '7', 'От диагностики к гипердиагностике: как получить ответы, а не новые вопросы', 'Екатерина Игоревна Ким', 'kim.webp', 'врач-эндокринолог, к.м.н.; ФГБУ «НМИЦ эндокринологии им. академика И. И. Дедова» Минздрава России')}
                ${talk('12:25–12:40', '8', 'Гепатит C. Подтверждение, внесение в регистр, лечение и контроль устойчивого вирусологического ответа', 'Павел Олегович Богомолов')}
                ${talk('12:40–12:55', '9', 'Онкология. Текущие перспективные скрининговые направления в регионах', 'Тигран Гагикович Геворкян', 'gevorkyan.webp', 'заместитель директора по реализации федеральных проектов ФГБУ «НМИЦ онкологии им. Н. Н. Блохина» Минздрава России')}
                ${service('12:55–13:40', 'Перерыв', 'Обеденный перерыв')}
                ${block(3, 'Профилактика и здоровое долголетие: возможности лабораторной диагностики')}
                ${talk('13:40–13:55', '10', 'От риска к контролю: лабораторный маршрут пациента. Диабет, сердечно-сосудистый и почечный риск в системе диспансеризации', 'Галина Викторовна Волкова', 'volkova.webp', 'заведующая отделением медицинской профилактики, врач-терапевт ГБУЗ МО «Одинцовская областная больница»')}
                ${talk('13:55–14:10', '11', 'Модель реализации лабораторной части программы репродуктивной диспансеризации', 'Антонина Николаевна Зинина', 'zinina.webp', 'руководитель направления КЛД ООО «ИнтерЛабСервис»')}
                ${talk('14:10–14:25', '12', 'Биологический возраст: медицинский инструмент или маркетинговая конструкция?', 'Ольга Николаевна Ткачева', 'tkacheva.webp', 'д.м.н., профессор, член-корреспондент РАН; директор Российского геронтологического научно-клинического центра; главный внештатный гериатр Минздрава России')}
                ${talk('14:25–14:40', '13', 'Биомаркеры старения: что рутинно внедрить в лабораторную службу уже сегодня?', 'Светлана Александровна Бернс', 'berns.webp', 'д.м.н., профессор; ФГБУ «НМИЦ терапии и профилактической медицины» Минздрава России')}
                ${service('14:40–15:55', 'Перерыв', 'Перерыв')}
                ${block(4, 'Единый цифровой и технологический контур')}
                ${talk('15:55–16:10', '14', 'Влияние качества вакуумных систем на результаты лабораторных исследований', 'Анна Сергеевна Омельянович', 'omelyanovich.webp', 'ведущий специалист по продукции направления преаналитики ООО «ОМБ»')}
                ${talk('16:10–16:25', '15', 'Как видеть потерянную пробу до появления жалобы пациента?', 'Мария Сергеевна Извекова', 'izvekova.webp', 'руководитель службы лабораторной диагностики ГБУЗ МО «Истринская клиническая больница»')}
                ${talk('16:25–16:40', '16', 'ЕМИАС–ЛИС без разрывов: от назначения до результата и дальнейшей маршрутизации', 'Татьяна Сергеевна Сидорова', 'sidorova.webp', 'руководитель проектов ООО «Формит»')}
                ${talk('16:40–16:55', '17', 'Антимикробная резистентность: единый региональный контур регистрации и анализа', 'Марина Витальевна Сухорукова')}
                ${talk('16:55–17:10', '18', 'Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети', 'Михаил Васильевич Иконников', 'ikonnikov.webp', 'генеральный директор АО «Эрба Рус»')}
                ${talk('17:10–17:25', '19', 'Отечественные реагенты, оборудование и лабораторная автоматизация: готовность к работе в централизованной сети', 'Андрей Викторович Варивода', 'varivoda.webp', 'председатель Совета директоров ГК «ДИАКОН»')}
                ${talk('17:25–17:40', '20', 'ИИ в лаборатории: выявление аномальных назначений и интерпретация исследований', 'Мария · уточняется')}
                ${service('17:40–18:00', 'Завершение', 'Подведение итогов и заключительное слово')}
            </div>`;

        initSpeakerPopovers(agenda);
    }

    function initSpeakerPopovers(agenda) {
        if (document.querySelector('[data-speaker-popover]')) return;
        document.body.insertAdjacentHTML('beforeend', '<div class="speaker-popover-shade" data-speaker-shade></div><aside class="speaker-popover" data-speaker-popover role="dialog" aria-modal="false" aria-hidden="true" aria-labelledby="speaker-popover-name"><button class="speaker-popover__close" type="button" aria-label="Закрыть">×</button><img class="speaker-popover__photo" src="" alt=""><span class="speaker-popover__copy"><strong id="speaker-popover-name"></strong><small></small></span></aside>');
        const popover = document.querySelector('[data-speaker-popover]');
        const shade = document.querySelector('[data-speaker-shade]');
        const popoverPhoto = popover.querySelector('.speaker-popover__photo');
        const popoverName = popover.querySelector('.speaker-popover__copy strong');
        const popoverCredentials = popover.querySelector('.speaker-popover__copy small');
        const closeButton = popover.querySelector('.speaker-popover__close');
        const finePointer = () => matchMedia('(min-width:721px) and (hover:hover) and (pointer:fine)').matches;
        let activeTrigger = null;
        let closeTimer = 0;

        const cancelClose = () => window.clearTimeout(closeTimer);
        const positionPopover = (trigger) => {
            if (!finePointer()) {
                popover.style.removeProperty('left');
                popover.style.removeProperty('top');
                return;
            }
            const gap = 14;
            const edge = 16;
            const triggerRect = trigger.getBoundingClientRect();
            const popoverRect = popover.getBoundingClientRect();
            let left = triggerRect.right + gap;
            if (left + popoverRect.width > innerWidth - edge) left = triggerRect.left - popoverRect.width - gap;
            left = Math.max(edge, Math.min(left, innerWidth - popoverRect.width - edge));
            let top = triggerRect.top + (triggerRect.height - popoverRect.height) / 2;
            top = Math.max(edge, Math.min(top, innerHeight - popoverRect.height - edge));
            popover.style.left = `${Math.round(left)}px`;
            popover.style.top = `${Math.round(top)}px`;
        };
        const openPopover = (trigger) => {
            cancelClose();
            if (activeTrigger && activeTrigger !== trigger) activeTrigger.setAttribute('aria-expanded', 'false');
            activeTrigger = trigger;
            popoverPhoto.src = `/images/speakers/2026/${trigger.dataset.speakerPhoto}`;
            popoverPhoto.alt = trigger.dataset.speakerName;
            popoverPhoto.style.setProperty('--speaker-focus', trigger.dataset.speakerFocus || '50% 28%');
            popoverName.textContent = trigger.dataset.speakerName;
            popoverCredentials.textContent = trigger.dataset.speakerCredentials;
            popoverCredentials.hidden = !trigger.dataset.speakerCredentials;
            trigger.setAttribute('aria-expanded', 'true');
            popover.setAttribute('aria-hidden', 'false');
            popover.setAttribute('aria-modal', finePointer() ? 'false' : 'true');
            popover.classList.add('is-open');
            if (finePointer()) {
                shade.classList.remove('is-open');
                document.body.classList.remove('speaker-popover-open');
                requestAnimationFrame(() => positionPopover(trigger));
            } else {
                shade.classList.add('is-open');
                document.body.classList.add('speaker-popover-open');
                closeButton.focus({ preventScroll: true });
            }
        };
        const closePopover = (returnFocus = false) => {
            cancelClose();
            const trigger = activeTrigger;
            if (trigger) trigger.setAttribute('aria-expanded', 'false');
            activeTrigger = null;
            popover.classList.remove('is-open');
            shade.classList.remove('is-open');
            popover.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('speaker-popover-open');
            if (returnFocus && trigger) trigger.focus({ preventScroll: true });
        };
        const scheduleClose = () => {
            cancelClose();
            closeTimer = window.setTimeout(() => closePopover(), 140);
        };

        agenda.querySelectorAll('.agenda-speaker-profile').forEach((trigger) => {
            trigger.addEventListener('pointerenter', () => { if (finePointer()) openPopover(trigger); });
            trigger.addEventListener('pointerleave', () => { if (finePointer()) scheduleClose(); });
            trigger.addEventListener('focus', () => { if (finePointer()) openPopover(trigger); });
            trigger.addEventListener('blur', () => { if (finePointer()) scheduleClose(); });
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                if (finePointer()) openPopover(trigger);
                else if (activeTrigger === trigger && popover.classList.contains('is-open')) closePopover();
                else openPopover(trigger);
            });
        });
        popover.addEventListener('pointerenter', () => { if (finePointer()) cancelClose(); });
        popover.addEventListener('pointerleave', () => { if (finePointer()) scheduleClose(); });
        closeButton.addEventListener('click', () => closePopover(true));
        shade.addEventListener('click', () => closePopover(true));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && popover.classList.contains('is-open')) closePopover(true); });
        document.addEventListener('pointerdown', (event) => { if (finePointer() && popover.classList.contains('is-open') && !popover.contains(event.target) && !event.target.closest('.agenda-speaker-profile')) closePopover(); });
        addEventListener('resize', () => { if (activeTrigger) positionPopover(activeTrigger); }, { passive: true });
        addEventListener('scroll', () => { if (activeTrigger && finePointer()) positionPopover(activeTrigger); }, { passive: true });
    }

    function markAgendaOnScroll() {
        const items = Array.from(document.querySelectorAll('.c26-agenda__item'));
        if (!items.length || !('IntersectionObserver' in window)) return;
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.12 });
        items.forEach((item) => observer.observe(item));
    }

    function initAgendaDisclosure() {
        const disclosure = document.querySelector('[data-agenda-disclosure]');
        const label = disclosure?.querySelector('[data-agenda-toggle-label]');
        const agenda = disclosure?.querySelector('.c26-agenda');
        if (!disclosure || !label || !agenda) return;
        disclosure.open = false;
        const syncDisclosure = () => {
            const isOpen = disclosure.open;
            label.textContent = isOpen ? 'Свернуть полную программу' : 'Показать полную программу';
            if (isOpen) {
                agenda.style.removeProperty('display');
                agenda.querySelectorAll('.c26-agenda__item').forEach((item) => item.classList.add('is-visible'));
            } else {
                agenda.style.display = 'none';
            }
        };
        disclosure.addEventListener('toggle', syncDisclosure);
        syncDisclosure();
    }

    function copyTextFallback(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);
        let copied = false;
        try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
        textarea.remove();
        return copied;
    }

    async function copyVenueAddress() {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(VENUE_ADDRESS);
                return true;
            } catch (error) {}
        }
        return copyTextFallback(VENUE_ADDRESS);
    }

    function initLocationActions() {
        const routeButton = document.getElementById('openNavigationMap');
        const copyButton = document.getElementById('copyMapAddress');
        if (routeButton) {
            routeButton.addEventListener('click', () => {
                const routeWindow = window.open(VENUE_ROUTE_URL, '_blank');
                if (routeWindow) routeWindow.opener = null;
                else window.location.href = VENUE_ROUTE_URL;
            });
        }
        if (copyButton) {
            const originalLabel = copyButton.textContent;
            let resetTimer = null;
            copyButton.addEventListener('click', async () => {
                const copied = await copyVenueAddress();
                if (!copied) {
                    window.prompt('Скопируйте адрес:', VENUE_ADDRESS);
                    return;
                }
                copyButton.textContent = 'Адрес скопирован';
                copyButton.setAttribute('aria-live', 'polite');
                window.clearTimeout(resetTimer);
                resetTimer = window.setTimeout(() => { copyButton.textContent = originalLabel; }, 1800);
            });
        }
    }

    function initMobileMenuLayerFix() {
        if (document.getElementById('c26-mobile-menu-layer-fix')) return;
        const style = document.createElement('style');
        style.id = 'c26-mobile-menu-layer-fix';
        style.textContent = `@media (max-width: 968px){.conference-2026-page .skip-link{display:none!important}.conference-2026-page .c26-hero{isolation:auto}.conference-2026-page .c26-hero__media{z-index:0}.conference-2026-page .c26-hero__overlay{z-index:1}.conference-2026-page .c26-hero__content{z-index:2}.conference-2026-page .c26-hero .photo-header{z-index:1000}}`;
        document.head.appendChild(style);
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadAgendaStyles();
        initMobileMenuLayerFix();
        updateEventState();
        publishAgenda();
        markAgendaOnScroll();
        initAgendaDisclosure();
        initLocationActions();
    });
})();
