// Конфигурация приложения
const CONFIG = {
    // Замените на ваш API ключ Яндекс.Карт
    yandexMapsApiKey: 'YOUR_YANDEX_MAPS_API_KEY',
    
    // Координаты БЦ "Новатор"
    location: {
        coordinates: [55.817062, 37.383687],
        address: 'б-р Строителей, 7, Красногорск, Московская область, 143407',
        name: 'БЦ "Новатор"'
    },
    
    // Данные события для календаря
    event: {
        title: 'LAB Evolution 2025',
        description: 'Конференция "Современная лабораторная служба: от анализа к качеству"',
        date: '2025-11-21',
        time: '11:00',
        duration: 7 // часов
    }
};

// Данные спикеров для карусели
const speakers = [
    {
        name: "Фаниль Самуилович Билалов",
        role: "Эксперт по лабораторной онкодиагностике",
        topic: "Современные подходы в лабораторной онкодиагностике",
        photo: "🧑‍⚕️"
    },
    {
        name: "Гульнара Витальевна Лешкина",
        role: "Специалист по цитологической диагностике", 
        topic: "Цитология в современной лабораторной диагностике",
        photo: "👩‍⚕️"
    },
    {
        name: "Мария Георгиевна Ламбакахар",
        role: "РМАНПО, эксперт по кадровому развитию",
        topic: "Развитие кадрового потенциала лабораторной службы",
        photo: "👩‍🏫"
    },
    {
        name: "Любовь Ивановна Станкевич",
        role: "LabQuest, управление лабораторной сетью",
        topic: "Стандартизация и цифровизация лабораторных процессов",
        photo: "👨‍💼"
    },
    {
        name: "Представители Hadassah",
        role: "Клиника Hadassah, международные стандарты", 
        topic: "JCI, ISO и пациент-ориентированный подход",
        photo: "🏥"
    },
    {
        name: "Главные внештатные специалисты",
        role: "По ВИЧ и дерматовенерологии",
        topic: "Маршрутизация пациентов с ВИЧ и сифилисом",
        photo: "👨‍🔬"
    }
];

// Глобальные переменные
let currentSlide = 0;
let autoSlideInterval;
let yandexMap = null;

// ==================== КАРУСЕЛЬ СПИКЕРОВ ====================

function initCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const carouselDots = document.getElementById('carouselDots');
    
    if (!carouselTrack || !carouselDots) return;
    
    // Очищаем существующие элементы
    carouselTrack.innerHTML = '';
    carouselDots.innerHTML = '';
    
    // Создаем слайды
    speakers.forEach((speaker, index) => {
        const slide = document.createElement('div');
        slide.className = 'speaker-slide';
        slide.setAttribute('data-slide-index', index);
        slide.innerHTML = `
            <div class="speaker-photo" aria-label="Фото спикера">${speaker.photo}</div>
            <div class="speaker-name">${speaker.name}</div>
            <div class="speaker-role">${speaker.role}</div>
            <div class="speaker-topic">${speaker.topic}</div>
        `;
        carouselTrack.appendChild(slide);
        
        // Создаем точки навигации
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${index === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Перейти к слайду ${index + 1}`);
        dot.addEventListener('click', () => goToSlide(index));
        carouselDots.appendChild(dot);
    });
    
    updateCarousel();
    startAutoSlide();
}

function moveSlide(direction) {
    const totalSlides = speakers.length;
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    updateCarousel();
    resetAutoSlide();
}

function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
    resetAutoSlide();
}

function updateCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const dots = document.querySelectorAll('.carousel-dot');
    const slideWidth = 320;
    
    if (carouselTrack) {
        carouselTrack.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
    }
    
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
        dot.setAttribute('aria-current', index === currentSlide ? 'true' : 'false');
    });
}

function startAutoSlide() {
    autoSlideInterval = setInterval(() => {
        moveSlide(1);
    }, 5000);
}

function stopAutoSlide() {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
}

function resetAutoSlide() {
    stopAutoSlide();
    startAutoSlide();
}

function initCarouselEvents() {
    const prevBtn = document.querySelector('.carousel-btn-prev');
    const nextBtn = document.querySelector('.carousel-btn-next');
    const carousel = document.querySelector('.carousel-container');
    
    if (prevBtn) prevBtn.addEventListener('click', () => moveSlide(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => moveSlide(1));
    
    if (carousel) {
        carousel.addEventListener('mouseenter', stopAutoSlide);
        carousel.addEventListener('mouseleave', startAutoSlide);
        carousel.addEventListener('touchstart', stopAutoSlide);
        carousel.addEventListener('touchend', startAutoSlide);
    }
}

// ==================== ИНТЕРАКТИВНАЯ КАРТА ====================

function initYandexMap() {
    const mapContainer = document.getElementById('yandexMap');
    
    if (!mapContainer) return;
    
    if (window.ymaps) {
        createMap();
    } else {
        loadYandexMapsAPI();
    }
}

function loadYandexMapsAPI() {
    // Если API ключ не указан, используем фолбэк
    if (!CONFIG.yandexMapsApiKey || CONFIG.yandexMapsApiKey === 'YOUR_YANDEX_MAPS_API_KEY') {
        showMapFallback();
        return;
    }
    
    const script = document.createElement('script');
    script.src = `https://api-maps.yandex.ru/2.1/?apikey=${CONFIG.yandexMapsApiKey}&lang=ru_RU`;
    script.onload = () => ymaps.ready(createMap);
    script.onerror = () => {
        console.error('Ошибка загрузки Яндекс Карт');
        showMapFallback();
    };
    document.head.appendChild(script);
}

function createMap() {
    try {
        yandexMap = new ymaps.Map('yandexMap', {
            center: CONFIG.location.coordinates,
            zoom: 16,
            controls: ['zoomControl', 'fullscreenControl', 'typeSelector']
        });

        // Создаем метку с кастомным контентом
        const placemark = new ymaps.Placemark(
            CONFIG.location.coordinates,
            {
                hintContent: CONFIG.location.name,
                balloonContent: `
                    <div class="map-balloon">
                        <h3>${CONFIG.location.name}</h3>
                        <p>${CONFIG.location.address}</p>
                        <p><strong>LAB Evolution 2025</strong></p>
                        <p>21 ноября, 11:00</p>
                        <button onclick="openNavigation()" style="
                            background: var(--primary); 
                            color: white; 
                            border: none; 
                            padding: 8px 16px; 
                            border-radius: 4px; 
                            cursor: pointer; 
                            margin-top: 10px;
                        ">Проложить маршрут</button>
                    </div>
                `
            },
            {
                preset: 'islands#blueIcon',
                iconColor: '#0d47a1'
            }
        );

        yandexMap.geoObjects.add(placemark);

        // Оптимизация для мобильных
        if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
            yandexMap.behaviors.disable('scrollZoom');
        }

        // Открываем балун при клике на метку
        placemark.events.add('click', function() {
            yandexMap.balloon.open(CONFIG.location.coordinates, {
                content: `
                    <div class="map-balloon">
                        <h3>${CONFIG.location.name}</h3>
                        <p>${CONFIG.location.address}</p>
                        <p><strong>Конференция LAB Evolution 2025</strong></p>
                        <p>📅 21 ноября 2025, 11:00</p>
                        <p>🚇 Метро "Мякинино" - 5 минут</p>
                        <p>🚗 Парковка для участников</p>
                    </div>
                `
            });
        });

    } catch (error) {
        console.error('Ошибка создания карты:', error);
        showMapFallback();
    }
}

function showMapFallback() {
    const mapContainer = document.getElementById('yandexMap');
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div class="map-fallback">
                <div class="fallback-content">
                    <span class="fallback-icon">🗺️</span>
                    <h3>Интерактивная карта</h3>
                    <p>Для отображения карты необходим API ключ Яндекс.Карт</p>
                    <div class="fallback-buttons">
                        <a href="https://yandex.ru/maps/org/bc_novator/1125366325/?ll=37.383687%2C55.817062&z=17" 
                           target="_blank" class="fallback-btn">
                            Открыть в Яндекс.Картах
                        </a>
                        <button class="fallback-btn secondary" onclick="openNavigation()">
                            Проложить маршрут
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
}

function openNavigation() {
    const { coordinates, address } = CONFIG.location;
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    
    if (isMobile) {
        // Пытаемся открыть в Яндекс.Навигаторе
        window.location.href = `yandexnavi://build_route_on_map?lat_to=${coordinates[0]}&lon_to=${coordinates[1]}`;
        
        // Фолбэк через 1 секунду
        setTimeout(() => {
            window.open(`https://yandex.ru/maps/?pt=${coordinates[1]},${coordinates[0]}&z=16&l=map`, '_blank');
        }, 1000);
    } else {
        window.open(`https://yandex.ru/maps/?pt=${coordinates[1]},${coordinates[0]}&z=16&l=map&rtext=~${coordinates[0]},${coordinates[1]}`, '_blank');
    }
    
    showNotification('🗺️ Открываю навигацию до БЦ "Новатор"...', 'info');
}

function initMapFunctions() {
    const navBtn = document.getElementById('openNavigation');
    if (navBtn) {
        navBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openNavigation();
        });
    }
    
    initYandexMap();
}

// ==================== КАЛЕНДАРЬ ====================

function addToCalendar() {
    try {
        const startDate = new Date(`${CONFIG.event.date}T${CONFIG.event.time}`);
        const endDate = new Date(startDate.getTime() + CONFIG.event.duration * 60 * 60 * 1000);
        
        const formatDate = (date) => {
            return date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';
        };

        const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//LAB Evolution//Conference 2025//RU
BEGIN:VEVENT
UID:${Date.now()}@labevolution2025.ru
DTSTAMP:${formatDate(new Date())}
DTSTART:${formatDate(startDate)}
DTEND:${formatDate(endDate)}
SUMMARY:${CONFIG.event.title}
DESCRIPTION:${CONFIG.event.description}\\\\n\\\\n📅 Дата: 21 ноября 2025 г.\\\\n⏰ Время: 11:00\\\\n📍 Место: ${CONFIG.location.address}
LOCATION:${CONFIG.location.address}
ORGANIZER;CN="LAB Evolution":mailto:info@labevolution.ru
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR`;

        const blob = new Blob([icsContent], { 
            type: 'text/calendar;charset=utf-8' 
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'LAB_Evolution_2025.ics';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        showNotification('📅 Файл календаря скачан! Импортируйте его в ваш календарь.', 'success');
        
    } catch (error) {
        console.error('Ошибка при создании календаря:', error);
        showNotification('❌ Произошла ошибка при создании файла календаря', 'error');
    }
}

function initCalendarButtons() {
    document.querySelectorAll('#addToCalendar, #addToCalendarFooter, .cta-button').forEach(button => {
        if (button.textContent.includes('календарь') || button.id.includes('Calendar')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                addToCalendar();
            });
        }
    });
}

// ==================== УВЕДОМЛЕНИЯ ====================

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" aria-label="Закрыть уведомление">×</button>
        </div>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--success)' : type === 'error' ? 'var(--error)' : 'var(--primary)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: var(--shadow-hover);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
        min-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => closeNotification(notification));
    
    setTimeout(() => closeNotification(notification), 5000);
}

function closeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// ==================== НАВИГАЦИЯ И АНИМАЦИИ ====================

function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    
    if (!backToTop) return;
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    backToTop.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#' || href === '#top') {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            
            const targetElement = document.querySelector(href);
            if (targetElement) {
                e.preventDefault();
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    });
}

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    const animatedElements = document.querySelectorAll(
        '.feature-card, .program-block, .partner-card, .speaker-slide, .stat-item, .session-item, .location-card'
    );
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        observer.observe(el);
    });
}

// ==================== СТИЛИ И УТИЛИТЫ ====================

function addCustomStyles() {
    if (document.getElementById('custom-styles')) return;
    
    const styles = document.createElement('style');
    styles.id = 'custom-styles';
    styles.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0;
            margin-left: 1rem;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .notification-close:hover { opacity: 1; }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-message { flex: 1; }
        
        .map-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #f8f9fa;
            text-align: center;
        }
        
        .fallback-content { padding: 2rem; }
        
        .fallback-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .fallback-content h3 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        
        .fallback-content p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }
        
        .fallback-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .fallback-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .fallback-btn.secondary {
            background: var(--text-light);
        }
        
        .fallback-btn:hover {
            background: var(--primary-dark);
        }
        
        .fallback-btn.secondary:hover {
            background: var(--text);
        }
        
        .map-balloon {
            padding: 15px;
            max-width: 250px;
        }
        
        .map-balloon h3 {
            margin: 0 0 10px 0;
            color: var(--primary-dark);
            font-size: 1.2rem;
        }
        
        .map-balloon p {
            margin: 5px 0;
            font-size: 0.9rem;
            color: var(--text);
        }
    `;
    
    document.head.appendChild(styles);
}

// ==================== ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ ====================

function handleResize() {
    updateCarousel();
}

function cleanup() {
    stopAutoSlide();
}

document.addEventListener('DOMContentLoaded', function() {
    // Добавляем кастомные стили
    addCustomStyles();
    
    // Инициализируем все модули
    initCarousel();
    initCarouselEvents();
    initBackToTop();
    initSmoothScroll();
    initScrollAnimations();
    initCalendarButtons();
    initMapFunctions();
    
    // Обработчики событий
    window.addEventListener('resize', handleResize);
    window.addEventListener('beforeunload', cleanup);
});

// Экспортируем функции для глобального использования
window.openNavigation = openNavigation;
window.addToCalendar = addToCalendar;
