// Данные спикеров для карусели
const speakers = [
    {
        name: "Фаниль Самуилович Билалов",
        role: "Эксперт по лабораторной онкодиагностике",
        topic: "Современные подходы в лабораторной онкодиагностике",
        photo: "https://via.placeholder.com/150/0d47a1/ffffff?text=ФСБ"
    },
    {
        name: "Гульнара Витальевна Лешкина",
        role: "Специалист по цитологической диагностике",
        topic: "Цитология в современной лабораторной диагностике",
        photo: "https://via.placeholder.com/150/00695c/ffffff?text=ГВЛ"
    },
    {
        name: "Мария Георгиевна Ламбакахар",
        role: "РМАНПО, эксперт по кадровому развитию",
        topic: "Развитие кадрового потенциала лабораторной службы",
        photo: "https://via.placeholder.com/150/00b0ff/ffffff?text=МГЛ"
    },
    {
        name: "Любовь Ивановна Станкевич",
        role: "LabQuest, управление лабораторной сетью",
        topic: "Стандартизация и цифровизация лабораторных процессов",
        photo: "https://via.placeholder.com/150/08306b/ffffff?text=ЛИС"
    },
    {
        name: "Представители Hadassah",
        role: "Клиника Hadassah, международные стандарты",
        topic: "JCI, ISO и пациент-ориентированный подход",
        photo: "https://via.placeholder.com/150/546e7a/ffffff?text=HAD"
    },
    {
        name: "Главные внештатные специалисты",
        role: "По ВИЧ и дерматовенерологии",
        topic: "Маршрутизация пациентов с ВИЧ и сифилисом",
        photo: "https://via.placeholder.com/150/2e7d32/ffffff?text=ВРА"
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
            <img src="${speaker.photo}" alt="${speaker.name}" class="speaker-photo">
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

// Плавная прокрутка и анимации
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем карусель
    initCarousel();
    
    // Плавная прокрутка для навигации
    document.querySelectorAll('nav a, .cta-button').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Анимация появления элементов при скролле
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

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
