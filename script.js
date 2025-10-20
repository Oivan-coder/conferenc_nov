// ==================== КОНФИГУРАЦИЯ ПРИЛОЖЕНИЯ ====================
const CONFIG = {
    location: {
        coordinates: [55.817062, 37.383687],
        address: 'б-р Строителей, 7, Красногорск, Московская область, 143407',
        name: 'БЦ "Новатор"'
    },
    event: {
        title: 'LAB Evolution 2025',
        description: 'Конференция "Современная лабораторная служба: от анализа к качеству"',
        date: '2025-11-21',
        time: '11:00',
        duration: 7
    }
};

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

let currentPhotoSlide = 0;
let currentSpeakerSlide = 0;
let photoAutoSlideInterval;
let speakerAutoSlideInterval;
let yandexMap = null;

// ==================== ИНИЦИАЛИЗАЦИЯ ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Инициализация LAB Evolution 2025');
    
    const isAboutPage = document.body.classList.contains('about-page');
    const isRegistrationPage = document.body.classList.contains('registration-page');
    
    initBurgerMenu();
    initSmoothScroll();
    initBackToTop();
    
    // 🔥 ДОБАВИЛ ЭТУ СТРОКУ - проверяем дату и включаем отзывы если нужно
    initFeedbackSystem();
    
    if (isAboutPage) {
        initAboutPageAnimations();
        initAboutPageContacts();
    } else if (isRegistrationPage) {
        initCustomSelects();
        initRegistrationForm();
    } else {
        initPhotoSlider();
        initSpeakersCarousel();
        initScrollAnimations();
        initCalendarButtons();
        initMapFunctions();
        initProgramAccordion();
    }
    
    addCustomStyles();
});

// ==================== СИСТЕМА ОТЗЫВОВ ====================

function initFeedbackSystem() {
    // Проверяем дату - отзывы только после 21 ноября 2025
    const conferenceDate = new Date('2025-01-20');
    const currentDate = new Date();
    
    if (currentDate < conferenceDate) {
        console.log('📅 Конференция еще не прошла - отзывы отключены');
        // Скрываем кнопку отзыва
        const floatingBtn = document.querySelector('.floating-feedback-btn');
        if (floatingBtn) {
            floatingBtn.style.display = 'none';
        }
        return;
    }
    
    console.log('💬 Система отзывов активирована');
    initFeedbackPopup();
}

function initFeedbackPopup() {
    const popup = document.getElementById('feedbackPopup');
    const closeBtn = document.getElementById('popupClose');
    const laterBtn = document.getElementById('popupLater');
    
    if (!popup || !closeBtn) return;
    
    let popupShown = false;

    // Проверяем, показывали ли уже попап в этой сессии
    if (sessionStorage.getItem('feedbackPopupShown')) {
        return;
    }

    function showPopup() {
        if (!popupShown) {
            popup.classList.add('active');
            popupShown = true;
            sessionStorage.setItem('feedbackPopupShown', 'true');
        }
    }

    function closePopup() {
        popup.classList.remove('active');
    }

    // Показ через 10 секунд
    setTimeout(showPopup, 10000);

    // Показ при уходе курсора за верхний край
    document.addEventListener('mouseout', (e) => {
        if (e.clientY < 50 && !popupShown) {
            showPopup();
        }
    });

    // Обработчики кнопок
    closeBtn.addEventListener('click', closePopup);
    if (laterBtn) {
        laterBtn.addEventListener('click', closePopup);
    }

    // Закрытие по клику на оверлей
    popup.addEventListener('click', (e) => {
        if (e.target === popup) {
            closePopup();
        }
    });

    // Закрытие по Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            closePopup();
        }
    });
}

// ==================== ОСНОВНЫЕ ФУНКЦИИ ====================

function initBurgerMenu() {
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    
    if (!burgerMenu || !navMenu) return;
    
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
        document.body.classList.toggle('menu-open');
    });
    
    const navLinks = navMenu.querySelectorAll('a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        });
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
                const headerHeight = document.querySelector('.photo-header')?.offsetHeight || 80;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    });
}

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

// ==================== КАРТОЧКИ СПЕЦИАЛИСТОВ ====================

function initSpecialistsCards() {
    const cards = document.querySelectorAll('.specialist-card');
    const grid = document.querySelector('.specialists-grid');
    
    if (!grid || cards.length === 0) return;
    
    console.log('🎯 Инициализация карточек специалистов...');
    
    const isMobile = window.innerWidth <= 768;
    
    cards.forEach((card, index) => {
        card.style.setProperty('--shift-direction', index % 2 === 0 ? '1' : '-1');
        card.setAttribute('tabindex', '0');
        card.setAttribute('role', 'button');
        card.setAttribute('aria-expanded', 'false');
        
        if (!isMobile) {
            card.addEventListener('mouseenter', handleCardHover);
            card.addEventListener('mouseleave', handleCardLeave);
        }
        
        card.addEventListener('click', handleCardClick);
        card.addEventListener('keydown', handleCardKeydown);
        card.style.animationDelay = `${0.1 + index * 0.1}s`;
    });
    
    document.addEventListener('click', handleOutsideClick);
    document.addEventListener('keydown', handleEscapeKey);
}

function handleCardHover(e) {
    const card = e.currentTarget;
    const allCards = document.querySelectorAll('.specialist-card');
    
    allCards.forEach(c => c.classList.remove('card-active'));
    card.classList.add('card-hover');
    
    allCards.forEach(otherCard => {
        if (otherCard !== card) {
            const rect = otherCard.getBoundingClientRect();
            const cardRect = card.getBoundingClientRect();
            const direction = rect.left < cardRect.left ? -1 : 1;
            otherCard.style.setProperty('--neighbor-shift', direction);
        }
    });
}

function handleCardLeave(e) {
    const card = e.currentTarget;
    setTimeout(() => {
        if (!card.matches(':hover')) {
            card.classList.remove('card-hover');
        }
    }, 100);
}

function handleCardClick(e) {
    e.stopPropagation();
    const card = e.currentTarget;
    const allCards = document.querySelectorAll('.specialist-card');
    const wasActive = card.classList.contains('card-active');
    
    allCards.forEach(c => {
        c.classList.remove('card-active', 'card-hover');
        c.setAttribute('aria-expanded', 'false');
    });
    
    if (!wasActive) {
        card.classList.add('card-active');
        card.setAttribute('aria-expanded', 'true');
        
        allCards.forEach(otherCard => {
            if (otherCard !== card) {
                const rect = otherCard.getBoundingClientRect();
                const cardRect = card.getBoundingClientRect();
                const direction = rect.left < cardRect.left ? -1 : 1;
                otherCard.style.setProperty('--neighbor-shift', direction);
            }
        });
        
        if (window.innerWidth <= 768) {
            setTimeout(() => {
                card.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
            }, 300);
        }
    }
}

function handleCardKeydown(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        e.currentTarget.click();
    }
}

function handleOutsideClick(e) {
    if (!e.target.closest('.specialist-card')) {
        const allCards = document.querySelectorAll('.specialist-card');
        allCards.forEach(card => {
            card.classList.remove('card-active', 'card-hover');
            card.setAttribute('aria-expanded', 'false');
        });
    }
}

function handleEscapeKey(e) {
    if (e.key === 'Escape') {
        const allCards = document.querySelectorAll('.specialist-card');
        allCards.forEach(card => {
            card.classList.remove('card-active', 'card-hover');
            card.setAttribute('aria-expanded', 'false');
        });
    }
}

function initResponsiveContacts() {
    const contactLinks = document.querySelectorAll('.contact-link');
    
    contactLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        link.addEventListener('focus', function() {
            this.style.outline = '2px solid var(--accent-primary)';
            this.style.outlineOffset = '2px';
        });
        
        link.addEventListener('blur', function() {
            this.style.outline = 'none';
        });
    });
}

// ==================== СТРАНИЦА "О НАС" ====================

function initAboutPageAnimations() {
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
        '.specialist-card, .department-card, .value-card'
    );
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    initSpecialistsCards();
    initResponsiveContacts();
}

function initAboutPageContacts() {
    const contactsButton = document.querySelector('.contacts-button');
    if (contactsButton) {
        contactsButton.addEventListener('click', function(e) {
            e.preventDefault();
            const specialistsSection = document.querySelector('.specialists-section');
            if (specialistsSection) {
                const headerHeight = document.querySelector('.photo-header')?.offsetHeight || 80;
                const targetPosition = specialistsSection.offsetTop - headerHeight - 20;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    }
}

// ==================== ОСТАЛЬНЫЕ ФУНКЦИИ ====================

function initPhotoSlider() {
    const photoSlider = document.getElementById('photoSlider');
    const photoDots = document.getElementById('photoDots');
    if (!photoSlider || !photoDots) return;

    const photos = [
        'images/hero/1.jpg','images/hero/2.jpg','images/hero/3.jpg','images/hero/4.jpg','images/hero/5.jpg',
        'images/hero/6.jpg','images/hero/7.jpg','images/hero/8.jpg','images/hero/9.jpg','images/hero/10.jpg',
        'images/hero/11.jpg','images/hero/12.jpg','images/hero/13.jpg','images/hero/14.jpg','images/hero/15.jpg'
    ];

    photos.forEach((photoPath, index) => {
        const slideElement = document.createElement('div');
        slideElement.className = `photo-slide ${index === 0 ? 'active' : ''}`;
        slideElement.innerHTML = `
            <img src="${photoPath}" alt="Фото с конференции LAB Evolution ${index + 1}"
                 style="width: 100%; height: 100%; object-fit: cover;"
                 onerror="this.style.display='none';">
        `;
        photoSlider.appendChild(slideElement);

        const dot = document.createElement('button');
        dot.className = `slider-dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToPhotoSlide(index));
        photoDots.appendChild(dot);
    });

    const prevBtn = document.getElementById('photoPrev');
    const nextBtn = document.getElementById('photoNext');
    if (prevBtn) prevBtn.addEventListener('click', () => movePhotoSlide(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => movePhotoSlide(1));
    
    startPhotoAutoSlide();
}

function movePhotoSlide(direction) {
    const slides = document.querySelectorAll('.photo-slide');
    if (slides.length === 0) return;
    currentPhotoSlide = (currentPhotoSlide + direction + slides.length) % slides.length;
    updatePhotoSlides();
    resetPhotoAutoSlide();
}

function goToPhotoSlide(index) {
    const slides = document.querySelectorAll('.photo-slide');
    if (index >= 0 && index < slides.length) {
        currentPhotoSlide = index;
        updatePhotoSlides();
        resetPhotoAutoSlide();
    }
}

function updatePhotoSlides() {
    const slides = document.querySelectorAll('.photo-slide');
    const dots = document.querySelectorAll('.slider-dot');
    slides.forEach((slide, index) => slide.classList.toggle('active', index === currentPhotoSlide));
    dots.forEach((dot, index) => dot.classList.toggle('active', index === currentPhotoSlide));
}

function startPhotoAutoSlide() {
    photoAutoSlideInterval = setInterval(() => movePhotoSlide(1), 5000);
}

function stopPhotoAutoSlide() {
    if (photoAutoSlideInterval) clearInterval(photoAutoSlideInterval);
}

function resetPhotoAutoSlide() {
    stopPhotoAutoSlide();
    startPhotoAutoSlide();
}

function initSpeakersCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const carouselDots = document.getElementById('carouselDots');
    if (!carouselTrack || !carouselDots) return;
    
    speakers.forEach((speaker, index) => {
        const slide = document.createElement('div');
        slide.className = 'speaker-slide';
        slide.innerHTML = `
            <div class="speaker-photo">${speaker.photo}</div>
            <div class="speaker-name">${speaker.name}</div>
            <div class="speaker-role">${speaker.role}</div>
            <div class="speaker-topic">${speaker.topic}</div>
        `;
        carouselTrack.appendChild(slide);
        
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToSpeakerSlide(index));
        carouselDots.appendChild(dot);
    });
    
    const prevBtn = document.querySelector('.carousel-btn-prev');
    const nextBtn = document.querySelector('.carousel-btn-next');
    if (prevBtn) prevBtn.addEventListener('click', () => moveSpeakerSlide(-1));
    if (nextBtn) nextBtn.addEventListener('click', () => moveSpeakerSlide(1));
    
    updateSpeakerCarousel();
    startSpeakerAutoSlide();
}

function moveSpeakerSlide(direction) {
    currentSpeakerSlide = (currentSpeakerSlide + direction + speakers.length) % speakers.length;
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
    if (carouselTrack) carouselTrack.style.transform = `translateX(-${currentSpeakerSlide * 320}px)`;
    dots.forEach((dot, index) => dot.classList.toggle('active', index === currentSpeakerSlide));
}

function startSpeakerAutoSlide() {
    speakerAutoSlideInterval = setInterval(() => moveSpeakerSlide(1), 4000);
}

function stopSpeakerAutoSlide() {
    if (speakerAutoSlideInterval) clearInterval(speakerAutoSlideInterval);
}

function resetSpeakerAutoSlide() {
    stopSpeakerAutoSlide();
    startSpeakerAutoSlide();
}

function initMapFunctions() {
    const navBtn = document.getElementById('openNavigationMap');
    if (navBtn) navBtn.addEventListener('click', (e) => { e.preventDefault(); openNavigation(); });
    initYandexMap();
}

function initYandexMap() {
    const mapContainer = document.getElementById('yandexMapFull');
    if (!mapContainer) return;
    
    if (typeof ymaps === 'undefined') {
        showMapFallback();
        return;
    }
    
    ymaps.ready(() => {
        try {
            yandexMap = new ymaps.Map('yandexMapFull', {
                center: CONFIG.location.coordinates,
                zoom: 16,
                controls: ['zoomControl', 'fullscreenControl']
            });

            const placemark = new ymaps.Placemark(CONFIG.location.coordinates, {
                hintContent: CONFIG.location.name,
                balloonContent: `
                    <div class="map-balloon">
                        <h3>${CONFIG.location.name}</h3>
                        <p>${CONFIG.location.address}</p>
                        <p><strong>LAB Evolution 2025</strong></p>
                        <p>21 ноября, 11:00</p>
                    </div>
                `
            }, { preset: 'islands#blueIcon', iconColor: '#0d47a1' });

            yandexMap.geoObjects.add(placemark);
        } catch (error) {
            showMapFallback();
        }
    });
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
                           target="_blank" class="fallback-btn">Открыть в Яндекс.Картах</a>
                        <button class="fallback-btn secondary" onclick="openNavigation()">Проложить маршрут</button>
                    </div>
                </div>
            </div>
        `;
    }
}

function initCalendarButtons() {
    const heroCalendarBtn = document.getElementById('addToCalendarHero');
    if (heroCalendarBtn) heroCalendarBtn.addEventListener('click', (e) => { e.preventDefault(); addToCalendar(); });
}

function addToCalendar() {
    try {
        const startDate = new Date(`${CONFIG.event.date}T${CONFIG.event.time}`);
        const endDate = new Date(startDate.getTime() + CONFIG.event.duration * 60 * 60 * 1000);
        
        const formatDate = (date) => date.toISOString().replace(/[-:]/g, '').split('.')[0] + 'Z';

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

        const blob = new Blob([icsContent], { type: 'text/calendar;charset=utf-8' });
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
        showNotification('❌ Произошла ошибка при создании файла календаря', 'error');
    }
}

function openNavigation() {
    const url = `https://yandex.ru/maps/?pt=${CONFIG.location.coordinates[1]},${CONFIG.location.coordinates[0]}&z=17&l=map`;
    window.open(url, '_blank');
}

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    const animatedElements = document.querySelectorAll('.feature-card, .program-block, .partner-card, .speaker-slide, .stat-item, .session-item');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
}

function initProgramAccordion() {
    const accordionBlocks = document.querySelectorAll('.accordion-block');
    accordionBlocks.forEach((block, index) => {
        const header = block.querySelector('.accordion-header');
        if (!header) return;
        
        header.addEventListener('click', () => {
            accordionBlocks.forEach(otherBlock => {
                if (otherBlock !== block && otherBlock.classList.contains('active')) {
                    otherBlock.classList.remove('active');
                }
            });
            block.classList.toggle('active');
        });
    });
    
    const blocksToAutoOpen = document.querySelectorAll('.accordion-block:not([data-auto-open="false"])');
    if (blocksToAutoOpen[0]) {
        blocksToAutoOpen[0].classList.add('active');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close">×</button>
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
    `;
    
    document.body.appendChild(notification);
    
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => closeNotification(notification));
    setTimeout(() => closeNotification(notification), 5000);
}

function closeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
        if (notification.parentNode) notification.parentNode.removeChild(notification);
    }, 300);
}

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
        }
        .map-fallback {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: #f8f9fa;
            text-align: center;
        }
        .fallback-content { padding: 2rem; }
        .fallback-icon { font-size: 3rem; margin-bottom: 1rem; display: block; }
        .fallback-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }
        .fallback-btn.secondary { background: var(--text-light); }
        .map-balloon { padding: 15px; max-width: 250px; }
        .photo-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: linear-gradient(135deg, #0d47a1, #08306b);
            color: white;
        }
    `;
    document.head.appendChild(styles);
}

function initCustomSelects() {
    const customSelects = document.querySelectorAll('.custom-select');
    customSelects.forEach(select => {
        const selected = select.querySelector('.select-selected');
        const items = select.querySelector('.select-items');
        const hiddenInput = select.querySelector('input[type="hidden"]');
        const options = items.querySelectorAll('div');
        
        selected.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('.select-items.active').forEach(other => other.classList.remove('active'));
            document.querySelectorAll('.select-selected.active').forEach(other => other.classList.remove('active'));
            items.classList.toggle('active');
            selected.classList.toggle('active');
        });
        
        options.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent;
                selected.querySelector('.select-text').textContent = text;
                hiddenInput.value = value;
                options.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                items.classList.remove('active');
                selected.classList.remove('active');
                if (value) hiddenInput.setCustomValidity('');
            });
        });
        
        document.addEventListener('click', function() {
            items.classList.remove('active');
            selected.classList.remove('active');
        });
        
        select.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
}

function initRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.style.borderColor = 'var(--error)';
            } else {
                field.style.borderColor = '';
            }
        });
        
        if (isValid) {
            const successMessage = document.querySelector('.success-message');
            if (successMessage) {
                form.style.display = 'none';
                successMessage.style.display = 'block';
            }
            showNotification('✅ Регистрация прошла успешно!', 'success');
        } else {
            showNotification('❌ Пожалуйста, заполните все обязательные поля', 'error');
        }
    });
}

function handleResize() {
    if (typeof updateSpeakerCarousel === 'function') updateSpeakerCarousel();
}

function cleanup() {
    stopPhotoAutoSlide();
    stopSpeakerAutoSlide();
}

window.addEventListener('resize', handleResize);
window.addEventListener('beforeunload', cleanup);
window.openNavigation = openNavigation;
window.addToCalendar = addToCalendar;

console.log('🎉 Все модули LAB Evolution 2025 успешно загружены!');
