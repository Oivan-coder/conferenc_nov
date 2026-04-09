// ===== МОДУЛЬ РАБОТЫ С КАРТАМИ =====

// Конфигурация локации (дефолт)
const MAP_CONFIG = {
    location: {
        coordinates: [55.817062, 37.383687],
        address: 'б-р Строителей, 7, Красногорск, Московская область, 143407',
        name: 'БЦ "Новатор"'
    },
    event: {
        title: 'Форум лабораторных инноваций',
        description: 'Лабораторная служба будущего: практика и перспективы',
        date: '2025-11-21',
        time: '11:00',
        duration: 7
    }
};

let yandexMap = null;

function getYandexMapUrl(cfg) {
    const c = cfg.location.coordinates;
    return `https://yandex.ru/maps/?pt=${c[1]},${c[0]}&z=17&l=map`;
}

function setExternalMapLink() {
    const link = document.getElementById('openExternalMap');
    if (!link) return;
    const cfg = getActiveMapConfig();
    link.href = getYandexMapUrl(cfg);
}

async function copyAddressToClipboard() {
    const cfg = getActiveMapConfig();
    const text = cfg.location.address || '';
    if (!text) return;

    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            // Fallback для небезопасного контекста / старых браузеров
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        if (window.showNotification) {
            window.showNotification('📋 Адрес скопирован в буфер обмена', 'success');
        }
    } catch (e) {
        if (window.showNotification) {
            window.showNotification('❌ Не удалось скопировать адрес', 'error');
        }
    }
}

/**
 * Возвращает активную конфигурацию карты:
 * - `window.PAGE_MAP_CONFIG` (если задана на странице)
 * - иначе дефолтная `MAP_CONFIG`
 */
function getActiveMapConfig() {
    return (window.PAGE_MAP_CONFIG && window.PAGE_MAP_CONFIG.location) ? window.PAGE_MAP_CONFIG : MAP_CONFIG;
}

/**
 * Инициализация функций карты
 */
function initMapFunctions() {
    const navBtn = document.getElementById('openNavigationMap');
    if (navBtn) {
        navBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openNavigation();
        });
    }

    const copyBtn = document.getElementById('copyMapAddress');
    if (copyBtn) {
        copyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            copyAddressToClipboard();
        });
    }

    // Обновляем ссылку "Открыть карту"
    setExternalMapLink();
    
    // Загружаем карту только если есть контейнер
    const mapContainer = document.getElementById('yandexMapFull');
    if (mapContainer) {
        // Защита от повторной инициализации (например, при SPA/повторном вызове)
        if (mapContainer.getAttribute('data-map-initialized') === 'true') {
            return;
        }

        // Проверяем, загружен ли API Яндекс.Карт
        if (typeof ymaps !== 'undefined') {
            initYandexMap();
        } else {
            // Если API еще не загружен (часто загружается async) — ждём и пробуем повторно.
            waitForYmapsAndInit({ attempts: 2, delayMs: 1600 });
        }
    }
}

function waitForYmapsAndInit({ attempts, delayMs }) {
    const mapContainer = document.getElementById('yandexMapFull');
    if (!mapContainer) return;

    let remaining = typeof attempts === 'number' ? attempts : 2;
    const delay = typeof delayMs === 'number' ? delayMs : 1600;

    const tick = () => {
        if (mapContainer.getAttribute('data-map-initialized') === 'true') return;

        if (typeof ymaps !== 'undefined') {
            initYandexMap();
            return;
        }

        if (remaining <= 0) {
            showMapError();
            return;
        }

        remaining -= 1;
        setTimeout(tick, delay);
    };

    setTimeout(tick, delay);
}

/**
 * Инициализация Яндекс.Карты
 */
function initYandexMap() {
    const mapContainer = document.getElementById('yandexMapFull');
    if (!mapContainer) return;
    
    if (typeof ymaps === 'undefined') {
        showMapError();
        return;
    }
    
    ymaps.ready(() => {
        try {
            const cfg = getActiveMapConfig();
            yandexMap = new ymaps.Map('yandexMapFull', {
                center: cfg.location.coordinates,
                zoom: 16,
                controls: ['zoomControl', 'fullscreenControl']
            });

            const placemark = new ymaps.Placemark(cfg.location.coordinates, {
                hintContent: cfg.location.name,
                balloonContent: `
                    <div class="map-balloon">
                        <h3>${cfg.location.name}</h3>
                        <p>${cfg.location.address}</p>
                        <p><strong>${cfg.event?.title || 'Событие'}</strong></p>
                        <p>${cfg.event?.date || ''}${cfg.event?.time ? `, ${cfg.event.time}` : ''}</p>
                    </div>
                `
            }, { preset: 'islands#blueIcon', iconColor: '#0d47a1' });

            yandexMap.geoObjects.add(placemark);
            mapContainer.setAttribute('data-map-initialized', 'true');
            setExternalMapLink();
        } catch (error) {
            console.error('Ошибка инициализации карты:', error);
            showMapError();
        }
    });
}

/**
 * Показывает сообщение об ошибке загрузки карты (без альтернативных карт)
 */
function showMapError() {
    const mapContainer = document.getElementById('yandexMapFull');
    if (!mapContainer) return;

    const cfg = getActiveMapConfig();
    
    // Очищаем контейнер
    mapContainer.textContent = '';
    
    const fallback = document.createElement('div');
    fallback.className = 'map-fallback';
    
    const content = document.createElement('div');
    content.className = 'fallback-content';
    
    const icon = document.createElement('span');
    icon.className = 'fallback-icon';
    icon.textContent = '⚠️';
    
    const h3 = document.createElement('h3');
    h3.textContent = 'Карта не загрузилась';
    
    const p = document.createElement('p');
    p.textContent = 'Проверьте подключение к интернету и попробуйте ещё раз.';
    
    const buttonsDiv = document.createElement('div');
    buttonsDiv.className = 'fallback-buttons';

    const retryBtn = document.createElement('button');
    retryBtn.className = 'fallback-btn secondary';
    retryBtn.textContent = 'Повторить';
    retryBtn.addEventListener('click', () => {
        mapContainer.removeAttribute('data-map-initialized');
        mapContainer.textContent = '';
        waitForYmapsAndInit({ attempts: 2, delayMs: 1200 });
    });
    
    const yandexLink = document.createElement('a');
    yandexLink.href = getYandexMapUrl(cfg);
    yandexLink.target = '_blank';
    yandexLink.rel = 'noopener noreferrer';
    yandexLink.className = 'fallback-btn';
    yandexLink.textContent = 'Открыть в Яндекс.Картах';

    buttonsDiv.appendChild(yandexLink);
    buttonsDiv.appendChild(retryBtn);
    
    content.appendChild(icon);
    content.appendChild(h3);
    content.appendChild(p);
    content.appendChild(buttonsDiv);
    fallback.appendChild(content);
    mapContainer.appendChild(fallback);
}

/**
 * Открывает навигацию в Яндекс.Картах
 */
function openNavigation() {
    const cfg = getActiveMapConfig();
    const url = getYandexMapUrl(cfg);
    window.open(url, '_blank', 'noopener,noreferrer');
}

/**
 * Инициализация кнопок календаря
 */
function initCalendarButtons() {
    const heroCalendarBtn = document.getElementById('addToCalendarHero');
    if (heroCalendarBtn) {
        heroCalendarBtn.addEventListener('click', (e) => {
            e.preventDefault();
            addToCalendar();
        });
    }
}

/**
 * Добавление события в календарь
 */
function addToCalendar() {
    try {
        const cfg = getActiveMapConfig();
        const startDate = new Date(`${cfg.event.date}T${cfg.event.time}`);
        const durationHours = (cfg.event && typeof cfg.event.duration === 'number') ? cfg.event.duration : MAP_CONFIG.event.duration;
        const endDate = new Date(startDate.getTime() + durationHours * 60 * 60 * 1000);
        
        const formatDate = (date) => date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';

        const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Форум лабораторных инноваций//Conference 2025//RU
BEGIN:VEVENT
UID:${Date.now()}@labforum2025.ru
DTSTAMP:${formatDate(new Date())}
DTSTART:${formatDate(startDate)}
DTEND:${formatDate(endDate)}
SUMMARY:${cfg.event.title}
DESCRIPTION:${cfg.event.description}\\\\n\\\\n📅 Дата: ${cfg.event.date}\\\\n⏰ Время: ${cfg.event.time}\\\\n📍 Место: ${cfg.location.address}
LOCATION:${cfg.location.address}
ORGANIZER;CN="Форум лабораторных инноваций":mailto:info@rclsmo.ru
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR`;

        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'Форум_лабораторных_инноваций_2025.ics';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        if (window.showNotification) {
            window.showNotification('📅 Файл календаря скачан! Импортируйте его в ваш календарь.', 'success');
        }
    } catch (error) {
        console.error('Ошибка создания календаря:', error);
        if (window.showNotification) {
            window.showNotification('❌ Произошла ошибка при создании файла календаря', 'error');
        }
    }
}

// Экспорт функций
window.initMapFunctions = initMapFunctions;
window.initYandexMap = initYandexMap;
window.openNavigation = openNavigation;
window.initCalendarButtons = initCalendarButtons;
window.addToCalendar = addToCalendar;
window.MAP_CONFIG = MAP_CONFIG;
