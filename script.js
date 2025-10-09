// Конфигурация приложения
const CONFIG = {
    // Замените на ваш API ключ Яндекс.Карт
    yandexMapsApiKey: '3dd59f10-9dfb-4db3-b374-9e344bee9e00',
    
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

// Данные спикеров
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
let currentPhotoSlide = 0;
let currentSpeakerSlide = 0;
let autoSlideInterval;
let yandexMap = null;

// ==================== ИНИЦИАЛИЗАЦИЯ ПРИЛОЖЕНИЯ ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Инициализация LAB Evolution 2025');
    
    // Инициализация всех модулей
    initBurgerMenu();
    initPhotoSlider();
    initSpeakersCarousel();
    initBackToTop();
    initSmoothScroll();
    initScrollAnimations();
    initCalendarButtons();
    initMapFunctions();
    
    addCustomStyles();
});

// ==================== БУРГЕР-МЕНЮ ====================

function initBurgerMenu() {
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    const body = document.body;
    
    if (!burgerMenu || !navMenu) {
        console.warn('Бургер-меню не найдено в DOM');
        return;
    }
    
    // Функция переключения меню
    function toggleMenu() {
        const isActive = burgerMenu.classList.contains('active');
        
        if (!isActive) {
            // Открываем меню
            burgerMenu.classList.add('active');
            navMenu.classList.add('active');
            body.classList.add('menu-open');
            burgerMenu.setAttribute('aria-expanded', 'true');
            navMenu.setAttribute('aria-hidden', 'false');
        } else {
            // Закрываем меню
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
            body.classList.remove('menu-open');
            burgerMenu.setAttribute('aria-expanded', 'false');
            navMenu.setAttribute('aria-hidden', 'true');
        }
    }
    
    // Обработчик клика по бургеру
    burgerMenu.addEventListener('click', toggleMenu);
    
    // Закрываем меню при клике на ссылку
    const navLinks = document.querySelectorAll('.nav-links a, .nav-register-btn');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (burgerMenu.classList.contains('active')) {
                toggleMenu();
            }
        });
    });
    
    // Закрываем меню при клике вне его области
    document.addEventListener('click', function(event) {
        const isClickInsideMenu = navMenu.contains(event.target);
        const isClickOnBurger = burgerMenu.contains(event.target);
        
        if (!isClickInsideMenu && !isClickOnBurger && navMenu.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    // Закрываем меню при нажатии Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && navMenu.classList.contains('active')) {
            toggleMenu();
        }
    });
    
    console.log('✅ Бургер-меню инициализировано');
}

// ==================== СЛАЙДЕР ФОТОГРАФИЙ ====================

function initPhotoSlider() {
    console.log('🔄 Инициализация фото-слайдера...');
    
    const photoSlider = document.getElementById('photoSlider');
    const photoDots = document.getElementById('photoDots');
    
    if (!photoSlider || !photoDots) {
        console.error('❌ Не найден photoSlider или photoDots');
        return;
    }

    // Очищаем слайдер
    photoSlider.innerHTML = '';
    photoDots.innerHTML = '';

    const photos = [
        'images/hero/1.jpg',
        'images/hero/2.jpg', 
        'images/hero/3.jpg',
        'images/hero/4.jpg',
        'images/hero/5.jpg',
        'images/hero/6.jpg',
        'images/hero/7.jpg',
        'images/hero/8.jpg',
        'images/hero/9.jpg',
        'images/hero/10.jpg',
        'images/hero/11.jpg',
        'images/hero/12.jpg',
        'images/hero/13.jpg',
        'images/hero/14.jpg',
        'images/hero/15.jpg'
    ];


    console.log('📸 Загружаем фото из images/hero/:', photos);

    // Создаем слайды
    photos.forEach((photoPath, index) => {
        const slideElement = document.createElement('div');
        slideElement.className = `photo-slide ${index === 0 ? 'active' : ''}`;
        
        slideElement.innerHTML = `
            <img src="${photoPath}" 
                 alt="Фото с конференции LAB Evolution ${index + 1}" 
                 style="width: 100%; height: 100%; object-fit: cover;"
                 onload="console.log('✅ Фото ${index + 1} загружено')"
                 onerror="console.error('❌ Ошибка загрузки фото ${index + 1}:', this.src)">
        `;
        
        photoSlider.appendChild(slideElement);

        // Создаем точки навигации
        const dot = document.createElement('button');
        dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Перейти к фото ${index + 1}`);
        dot.addEventListener('click', () => goToPhotoSlide(index));
        photoDots.appendChild(dot);
    });

    // Обработчики кнопок навигации
    const prevBtn = document.getElementById('photoPrev');
    const nextBtn = document.getElementById('photoNext');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => movePhotoSlide(-1));
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => movePhotoSlide(1));
    }
    
    // Автопрокрутка
    startPhotoAutoSlide();
    
    console.log(`✅ Создано ${photos.length} слайдов`);
    console.log('🔍 Проверяем элементы в DOM:', document.querySelectorAll('.photo-slide').length);
}

// ДОБАВЛЯЕМ ВСЕ НЕОБХОДИМЫЕ ФУНКЦИИ ДЛЯ СЛАЙДЕРА:

function movePhotoSlide(direction) {
    const slides = document.querySelectorAll('.photo-slide');
    const totalSlides = slides.length;
    
    if (totalSlides === 0) return;
    
    currentPhotoSlide = (currentPhotoSlide + direction + totalSlides) % totalSlides;
    updatePhotoSlides();
    resetPhotoAutoSlide();
}

function goToPhotoSlide(index) {
    const slides = document.querySelectorAll('.photo-slide');
    const totalSlides = slides.length;
    
    if (index >= 0 && index < totalSlides) {
        currentPhotoSlide = index;
        updatePhotoSlides();
        resetPhotoAutoSlide();
    }
}

function updatePhotoSlides() {
    const slides = document.querySelectorAll('.photo-slide');
    const dots = document.querySelectorAll('.slider-dot');
    
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentPhotoSlide);
    });
    
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentPhotoSlide);
    });
}

function startPhotoAutoSlide() {
    autoSlideInterval = setInterval(() => {
        movePhotoSlide(1);
    }, 5000);
}

function stopPhotoAutoSlide() {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
}

function resetPhotoAutoSlide() {
    stopPhotoAutoSlide();
    startPhotoAutoSlide();
}

// ==================== КАРУСЕЛЬ СПИКЕРОВ ====================

function initSpeakersCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const carouselDots = document.getElementById('carouselDots');
    const prevBtn = document.querySelector('.carousel-btn-prev');
    const nextBtn = document.querySelector('.carousel-btn-next');
    
    if (!carouselTrack || !carouselDots) {
        console.warn('Элементы карусели спикеров не найдены');
        return;
    }
    
    // Очищаем существующие элементы
    carouselTrack.innerHTML = '';
    carouselDots.innerHTML = '';
    
    // Создаем слайды спикеров
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
        dot.addEventListener('click', () => goToSpeakerSlide(index));
        carouselDots.appendChild(dot);
    });
    
    // Обработчики кнопок навигации
    if (prevBtn) {
        prevBtn.addEventListener('click', () => moveSpeakerSlide(-1));
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => moveSpeakerSlide(1));
    }
    
    // Останавливаем автопрокрутку при наведении
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', stopSpeakerAutoSlide);
        carouselContainer.addEventListener('mouseleave', startSpeakerAutoSlide);
    }
    
    updateSpeakerCarousel();
    startSpeakerAutoSlide();
    
    console.log('✅ Карусель спикеров инициализирована');
}

function moveSpeakerSlide(direction) {
    const totalSlides = speakers.length;
    currentSpeakerSlide = (currentSpeakerSlide + direction + totalSlides) % totalSlides;
    updateSpeakerCarousel();
    resetSpeakerAutoSlide();
}

function goToSpeakerSlide(index) {
    currentSpeakerSlide = index;
    updateSpeakerCarousel();
    resetSpeakerAutoSlide();
}

function updateSpeakerCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const dots = document.querySelectorAll('.carousel-dot');
    const slideWidth = 320; // Ширина слайда + gap
    
    if (carouselTrack) {
        carouselTrack.style.transform = `translateX(-${currentSpeakerSlide * slideWidth}px)`;
    }
    
    // Обновляем точки
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSpeakerSlide);
        dot.setAttribute('aria-current', index === currentSpeakerSlide ? 'true' : 'false');
    });
}

function startSpeakerAutoSlide() {
    autoSlideInterval = setInterval(() => {
        moveSpeakerSlide(1);
    }, 4000);
}

function stopSpeakerAutoSlide() {
    if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
    }
}

function resetSpeakerAutoSlide() {
    stopSpeakerAutoSlide();
    startSpeakerAutoSlide();
}

// ==================== КАРТА ====================

function initMapFunctions() {
    const navBtn = document.getElementById('openNavigationMap');
    
    if (navBtn) {
        navBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openNavigation();
        });
    }
    
    // Инициализируем Яндекс.Карты
    initYandexMap();
    
    console.log('✅ Функции карты инициализированы');
}

function initYandexMap() {
    const mapContainer = document.getElementById('yandexMapFull');
    
    if (!mapContainer) {
        console.warn('Контейнер карты не найден');
        return;
    }
    
    // Проверяем, загружена ли библиотека Яндекс.Карт
    if (typeof ymaps === 'undefined') {
        console.warn('Библиотека Яндекс.Карт не загружена');
        showMapFallback();
        return;
    }
    
    try {
        // Инициализируем карту
        ymaps.ready(() => {
            yandexMap = new ymaps.Map('yandexMapFull', {
                center: CONFIG.location.coordinates,
                zoom: 16,
                controls: ['zoomControl', 'fullscreenControl']
            });

            // Создаем метку
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
        });
        
        console.log('✅ Яндекс.Карта инициализирована');
    } catch (error) {
        console.error('Ошибка создания карты:', error);
        showMapFallback();
    }
}

function showMapFallback() {
    const mapContainer = document.getElementById('yandexMapFull');
    if (mapContainer) {
        mapContainer.innerHTML = `
            <div class="map-fallback">
                <div class="fallback-content">
                    <span class="fallback-icon">🗺️</span>
                    <h3>Интерактивная карта</h3>
                    <p>БЦ "Новатор", б-р Строителей, 7, Красногорск</p>
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

// ==================== КАЛЕНДАРЬ ====================

function initCalendarButtons() {
    const heroCalendarBtn = document.getElementById('addToCalendarHero');
    
    if (heroCalendarBtn) {
        heroCalendarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addToCalendar();
        });
    }
    
    console.log('✅ Кнопки календаря инициализированы');
}

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

// ==================== НАВИГАЦИЯ ====================

function openNavigation() {
    const url = `https://yandex.ru/maps/?pt=${CONFIG.location.coordinates[1]},${CONFIG.location.coordinates[0]}&z=17&l=map`;
    window.open(url, '_blank');
}

// ==================== ПЛАВНАЯ ПРОКРУТКА ====================

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
                const headerHeight = document.querySelector('.photo-header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    });
    
    console.log('✅ Плавная прокрутка инициализирована');
}

// ==================== КНОПКА "НАВЕРХ" ====================

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
    
    console.log('✅ Кнопка "Наверх" инициализирована');
}

// ==================== АНИМАЦИИ ПРИ ПРОКРУТКЕ ====================

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { 
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });

    const animatedElements = document.querySelectorAll(
        '.feature-card, .program-block, .partner-card, .speaker-slide, .stat-item, .session-item'
    );
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    console.log('✅ Анимации прокрутки инициализированы');
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
        background: ${type === 'success' ? '#2e7d32' : type === 'error' ? '#c62828' : '#0d47a1'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
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

// ==================== ДОПОЛНИТЕЛЬНЫЕ СТИЛИ ====================

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
        
        .notification-close:hover { 
            opacity: 1; 
        }
        
        .notification-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .notification-message { 
            flex: 1; 
        }
        
        .map-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #f8f9fa;
            text-align: center;
        }
        
        .fallback-content { 
            padding: 2rem; 
        }
        
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

        /* Стили для placeholder'ов фото */
        .photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: linear-gradient(135deg, #0d47a1, #08306b);
            color: white;
        }

        .placeholder-content {
            text-align: center;
        }

        .placeholder-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.7;
            display: block;
        }
    `;
    
    document.head.appendChild(styles);
    
    console.log('✅ Кастомные стили добавлены');
}

// ==================== ОБРАБОТЧИКИ СОБЫТИЙ ====================

function handleResize() {
    updateSpeakerCarousel();
}

function cleanup() {
    stopPhotoAutoSlide();
    stopSpeakerAutoSlide();
}

// Обработчики событий
window.addEventListener('resize', handleResize);
window.addEventListener('beforeunload', cleanup);

// Экспортируем функции для глобального использования
window.openNavigation = openNavigation;
window.addToCalendar = addToCalendar;

console.log('🎉 Все модули LAB Evolution 2025 успешно загружены!');
