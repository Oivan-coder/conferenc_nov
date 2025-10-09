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

// Инициализация карусели
let currentSlide = 0;
const slidesToShow = 3;

function initCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const carouselDots = document.getElementById('carouselDots');
    
    // Создаем слайды
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
        
        // Создаем точки
        const dot = document.createElement('button');
        dot.className = `carousel-dot ${index === 0 ? 'active' : ''}`;
        dot.addEventListener('click', () => goToSlide(index));
        carouselDots.appendChild(dot);
    });
    
    updateCarousel();
}

function moveSlide(direction) {
    const totalSlides = speakers.length;
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    updateCarousel();
}

function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
}

function updateCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const dots = document.querySelectorAll('.carousel-dot');
    const slideWidth = 320; // 300px + 20px gap
    
    // Обновляем позицию карусели
    carouselTrack.style.transform = `translateX(-${currentSlide * slideWidth}px)`;
    
    // Обновляем активную точку
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

// Функция добавления в календарь (простая как в предыдущей конфе)
function addToCalendar() {
    const icsContent = `BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
SUMMARY:LAB Evolution 2025
DESCRIPTION:Конференция "Современная лабораторная служба: от анализа к качеству"
DTSTART:20251121T110000
DTEND:20251121T180000
LOCATION:Москва, Конгресс-центр
END:VEVENT
END:VCALENDAR`;

    // Создаем и скачиваем файл
    const blob = new Blob([icsContent], { type: 'text/calendar' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'LAB_Evolution_2025.ics';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    
    // Простое уведомление
    alert('Файл календаря скачан. Импортируйте его в ваш календарь.');
}

// Плавная прокрутка и анимации
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем карусель
    initCarousel();
    
    // Плавная прокрутка для навигации
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const id = this.getAttribute('href');
            if (id.length < 2) return;
            const targetElement = document.querySelector(id);
            if (!targetElement) return;

            e.preventDefault();
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Обработчики для кнопок календаря
    document.querySelectorAll('#addToCalendar, #addToCalendarFooter').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            addToCalendar();
        });
    });

    // Анимация появления элементов при скролле
    const observer = new IntersectionObserver(function(entries) {
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

    // Наблюдаем за карточками и секциями
    document.querySelectorAll('.feature-card, .program-block, .partner-card, .speaker-slide').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
    
    // Автопрокрутка карусели (опционально)
    setInterval(() => {
        moveSlide(1);
    }, 5000);
});

// Обработка изменения размера окна
window.addEventListener('resize', function() {
    updateCarousel();
});
