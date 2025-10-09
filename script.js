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

// Глобальные переменные для карусели
let currentSlide = 0;
let autoSlideInterval;

// Инициализация карусели
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

// Перемещение слайдов
function moveSlide(direction) {
    const totalSlides = speakers.length;
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    updateCarousel();
    resetAutoSlide();
}

// Переход к конкретному слайду
function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
    resetAutoSlide();
}

// Обновление отображения карусели
function updateCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const dots = document.querySelectorAll('.carousel-dot');
    const slideWidth = 320; // 300px + 20px gap
    
    if (carouselTrack) {
        carouselTrack.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
    }
    
    // Обновляем активную точку
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
        dot.setAttribute('aria-current', index === currentSlide ? 'true' : 'false');
    });
}

// Автопрокрутка карусели
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

// Функция добавления в календарь
function addToCalendar() {
    try {
        const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//LAB Evolution//Conference 2025//RU
BEGIN:VEVENT
UID:${Date.now()}@labevolution2025.ru
DTSTAMP:${new Date().toISOString().replace(/[-:]/g, '').split('.')[0]}Z
DTSTART:20251121T110000
DTEND:20251121T180000
SUMMARY:LAB Evolution 2025
DESCRIPTION:Конференция "Современная лабораторная служба: от анализа к качеству"\\n\\n📅 Дата: 21 ноября 2025 г.\\n⏰ Время: 11:00\\n📍 Место: Москва, Конгресс-центр\\n\\nОрганизатор: Минздрав МО
LOCATION:Москва, Конгресс-центр
ORGANIZER;CN="LAB Evolution":mailto:info@labevolution.ru
STATUS:CONFIRMED
END:VEVENT
END:VCALENDAR`;

        // Создаем и скачиваем файл
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
        
        // Показываем уведомление
        showNotification('📅 Файл календаря скачан! Импортируйте его в ваш календарь.', 'success');
        
    } catch (error) {
        console.error('Ошибка при создании календаря:', error);
        showNotification('❌ Произошла ошибка при создании файла календаря', 'error');
    }
}

// Уведомления
function showNotification(message, type = 'info') {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" aria-label="Закрыть уведомление">×</button>
        </div>
    `;
    
    // Добавляем стили
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
    
    // Обработчик закрытия
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        closeNotification(notification);
    });
    
    // Автоматическое закрытие
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

function closeNotification(notification) {
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

// Кнопка "Наверх"
function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    
    if (!backToTop) return;
    
    // Показываем/скрываем кнопку при скролле
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
    
    // Плавная прокрутка наверх
    backToTop.addEventListener('click', (e) => {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Плавная прокрутка для навигации
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Обработка ссылки наверх (логотип)
            if (href === '#' || href === '#top') {
                e.preventDefault();
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
                return;
            }
            
            // Обработка якорных ссылок
            const targetElement = document.querySelector(href);
            if (targetElement) {
                e.preventDefault();
                const headerHeight = document.querySelector('header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight - 20;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Анимация появления элементов при скролле
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                entry.target.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            }
        });
    }, observerOptions);

    // Наблюдаем за элементами
    const animatedElements = document.querySelectorAll(
        '.feature-card, .program-block, .partner-card, .speaker-slide, .stat-item, .session-item'
    );
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        observer.observe(el);
    });
}

// Обработчики для кнопок календаря
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

// Обработчики для карусели
function initCarouselEvents() {
    // Кнопки вперед/назад
    const prevBtn = document.querySelector('.carousel-btn-prev');
    const nextBtn = document.querySelector('.carousel-btn-next');
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => moveSlide(-1));
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => moveSlide(1));
    }
    
    // Пауза автопрокрутки при наведении
    const carousel = document.querySelector('.carousel-container');
    if (carousel) {
        carousel.addEventListener('mouseenter', stopAutoSlide);
        carousel.addEventListener('mouseleave', startAutoSlide);
        carousel.addEventListener('touchstart', stopAutoSlide);
        carousel.addEventListener('touchend', startAutoSlide);
    }
}

// Адаптация карусели при изменении размера окна
function handleResize() {
    updateCarousel();
}

// Основная инициализация
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем все компоненты
    initCarousel();
    initBackToTop();
    initSmoothScroll();
    initScrollAnimations();
    initCalendarButtons();
    initCarouselEvents();
    
    // Обработчик изменения размера окна
    window.addEventListener('resize', handleResize);
    
    // Добавляем CSS анимации
    addNotificationStyles();
});

// Добавление стилей для анимаций
function addNotificationStyles() {
    if (document.getElementById('notification-styles')) return;
    
    const styles = document.createElement('style');
    styles.id = 'notification-styles';
    styles.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
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
    `;
    
    document.head.appendChild(styles);
}

// Очистка при разгрузке страницы
window.addEventListener('beforeunload', () => {
    stopAutoSlide();
});
