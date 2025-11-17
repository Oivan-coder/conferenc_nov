// ==================== КОНФИГУРАЦИЯ ПРИЛОЖЕНИЯ ====================
const CONFIG = {
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
// ===== ДАННЫЕ СПЕЦИАЛИСТОВ ДЛЯ МОДАЛЬНЫХ ОКОН =====
const specialistsData = {
    1: {
        name: "Щеблыкина-Монастырёва Ирина Владимировна",
        role: "Куратор референс-центра",
        bio: "Главный внештатный специалист по клинической лабораторной диагностике. Руководитель проекта «Централизация лабораторной службы» Московской области.",
        photo: "images/team/irina-scheblykina.jpg",
        contacts: [
            { type: "phone", value: "+79958832336", label: "Телефон", icon: "📞" },
            { type: "whatsapp", value: "+79958832336", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "doc_kld", label: "Telegram", icon: "✈️" },
            { type: "email", value: "ScheblykinaIV@mosreg.ru", label: "Email", icon: "📧" }
        ]
    },
    2: {
        name: "Денисова Екатерина Анатольевна",
        role: "Заведующий референс-центром",
        bio: "Заведующий референс-центром лабораторной службы МО",
        photo: "images/team/ekaterina-denisova.jpg",
        contacts: [
            { type: "phone", value: "+79933521997", label: "Телефон", icon: "📞" },
            { type: "whatsapp", value: "+79933521997", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "lab_docc", label: "Telegram", icon: "✈️" }
        ]
    },
    3: {
        name: "Гольцев Иван Михайлович",
        role: "Ведущий специалист",
        bio: "Специалист по лабораторному оборудованию и технической поддержке",
        photo: "images/team/ivan-goltsev.jpg",
        contacts: [
            { type: "whatsapp", value: "+79778290881", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "ivan_goltsev", label: "Telegram", icon: "✈️" }
        ]
    },
    4: {
        name: "Кондратов Устин Сергеевич",
        role: "Ведущий экономист",
        bio: "Специалист по экономическому анализу и оптимизации лабораторной службы",
        photo: "images/team/ustin-kondratov.jpg",
        contacts: [
            { type: "whatsapp", value: "+79670252747", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "ssorf7", label: "Telegram", icon: "✈️" }
        ]
    },
    5: {
        name: "Каплина Анна Валерьвна",
        role: "Аналитик",
        bio: "Специалист по анализу данных и статистической обработке результатов",
        photo: "images/team/anna-kaplina.jpg",
        contacts: [
            { type: "whatsapp", value: "+79637516163", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "Annkey", label: "Telegram", icon: "✈️" }
        ]
    }
};

// ===== ФУНКЦИИ МОДАЛЬНЫХ ОКОН ДЛЯ СПЕЦИАЛИСТОВ =====
function openSpecialistModal(specialistId) {
    const specialist = specialistsData[specialistId];
    if (!specialist) return;
    
    const modal = document.getElementById('specialistModal');
    const modalBody = document.getElementById('specialistModalBody');
    
    let contactsHTML = '';
    specialist.contacts.forEach(contact => {
        let href = '';
        switch(contact.type) {
            case 'phone':
                href = `tel:${contact.value}`;
                break;
            case 'whatsapp':
                href = `https://wa.me/${contact.value.replace('+', '')}`;
                break;
            case 'telegram':
                href = `https://t.me/${contact.value}`;
                break;
            case 'email':
                href = `mailto:${contact.value}`;
                break;
        }
        
        contactsHTML += `
            <a href="${href}" class="modal-contact-link ${contact.type}"
               ${contact.type === 'whatsapp' || contact.type === 'telegram' ? 'target="_blank"' : ''}>
                <span>${contact.icon}</span>
                ${contact.label}
            </a>
        `;
    });
    
    modalBody.innerHTML = `
        <div class="modal-photo">
            <img src="${specialist.photo}" alt="${specialist.name}"
                 onerror="this.style.display='none'; this.parentElement.innerHTML='${specialist.photo.includes('irina') ? '👩‍⚕️' : specialist.photo.includes('ekaterina') ? '👨‍💼' : specialist.photo.includes('ivan') ? '🔧' : specialist.photo.includes('ustin') ? '💼' : '📊'}';">
        </div>
        <h2 class="modal-name">${specialist.name}</h2>
        <div class="modal-role">${specialist.role}</div>
        <div class="modal-bio">${specialist.bio}</div>
        <div class="modal-contacts">
            ${contactsHTML}
        </div>
    `;
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSpecialistModal() {
    const modal = document.getElementById('specialistModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ===== ОБНОВЛЕННАЯ ИНИЦИАЛИЗАЦИЯ ДЛЯ СТРАНИЦЫ "О НАС" =====
function initAboutPageAnimations() {
    console.log('🎯 Инициализация модальных окон специалистов');
    
    // Закрытие по ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const specialistModal = document.getElementById('specialistModal');
            if (specialistModal.classList.contains('active')) {
                closeSpecialistModal();
            }
        }
    });
    
    // Анимация появления карточек
    const cards = document.querySelectorAll('.specialist-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
}

function initAboutPageContacts() {
    console.log('📞 Контакты в модальных окнах');
    // Не нужно для новой версии
}


const speakers = [
    {
        id: 1,
        name: "Вербенкин Александр Валерьевич",
        role: "Главный врач клиники Вербенкина, врач-эндокринолог",
        photo: "images/speakers/verbenkin.jpg",
        topic: "Анализы под вопросом: кто отвечает за неверный результат?",
    },
    {
        id: 2,
        name: "Жилина Светлана Владимировна",
        role: "Кандидат медицинских наук, врач-бактериолог высшей квалификационной категории, член комитета по микробиологии Ассоциации «Федерация лабораторной медицины», заведующий микробиологической лабораторией Клиническая больница №1 АО Группа компаний МЕДСИ",
        photo: "images/speakers/zhilina.jpg",
        topic: "Синтез классики и новых технологий в комплексной микробиологической диагностике гастроэнтеритов. Возможности микробиологических лабораторий",
    },
    {
        id: 3,
        name: "Лешкина Гульнара Витальевна",
        role: "Врач-цитолог КДЛ ФБУН ЦНИИ эпидемиологии Роспотребнадзора, врач-цитолог КДЛ ФБУН ЦНИИ эпидемиологии Роспотребнадзора",
        photo: "images/speakers/leshkina.jpg",
        topic: "Возможности цитологического исследования в современной лабораторной диагностике: практика, качество, обучение + Мастер-класс",
    },
    {
        id: 4,
        name: "Денисов Дмитрий Геннадьевич",
        role: "Медицинский директор Лабораторной службы «Хеликс»",
        photo: "images/speakers/denisov.jpg",
        topic: "Персональный медицинский ИИ-навигатор: как современные сервисы лаборатории помогают заботиться о здоровье",
    },
    {
        id: 5,
        name: "Станкевич Любовь Ивановна",
        role: "Кандидат медицинских наук, Директор по лабораторной медицине и производству ЛабКвест,  доцент кафедры клинической лабораторной диагностики и патологической анатомии Академии постдипломного образования ФГБУ ФНКЦ ФМБА России, вице-президент Российской ассоциации медицинской лабораторной диагностики (РАМЛД)",
        photo: "images/speakers/stankevich.jpg",
        topic: "Практические аспекты скрининга рака шейки матки: нюансы рекомендаций и новые тренды",
        details: [
                  "Дополнительный доклад: Повреждения печени: молчаливая сага и лабораторные решения при поддержке искусственного интеллекта для полной диагностики"]
    },
    {
        id: 6,
        name: "Карасев Александр Владимирович",
        role: "Врач-педиатр, специалист клинической лабораторной диагностики, Медицинский директор Большая тройка-Медицина",
        photo: "images/speakers/karasev.jpg",
        topic: "ИИ и результаты лабораторных исследований - внимание на ошибки",
        details: [
            "Врач-педиатр",
            "Специалист клинической лабораторной диагностики",
            "Медицинский директор Большая тройка-Медицина",
            "Член Федерации Лабораторной Медицины",
            "Член European Society Human Genetics"
        ]
    },
    {
        id: 7,
        name: "Попов Владимир Александрович",
        role: "Руководитель отдела региональных продаж АО Вектор-Бест-Европа",
        photo: "images/speakers/popov.jpg",
        topic: "Технологические решения для лабораторной диагностики от ведущего российского производителя",
        details: []
    },
    {
        id: 8,
        name: "Буллих Артем Владимирович",
        role: "Кандидат медицинских наук, Главный специалист по направлению Патоморфологические исследования, АО ГК Медси, заведующий ЦПАО",
        photo: "images/speakers/bullikh.jpg",
        topic: "Опыт применения цифровой патологии при централизации гистологических исследований. Перспектива развития.",
        details: []
    },
    // УДАЛЕН: Деркач Владислав Игоревич (id: 9)
    {
        id: 9, // Новый id вместо удаленного
        name: "Шадрина Екатерина Станиславовна",
        role: "Руководитель направления сестринской деятельности Хадасса Медикал Москва",
        photo: "images/speakers/shadrina.jpg",
        topic: "Взаимодействие Медсестра-пациент на преаналитическом этапе. Ключевые факторы качества и безопасности по стандартам JCI и ISO",
        details: []
    },
    {
        id: 10,
        name: "Сорока Александр Евгеньевич",
        role: "Старший специалист по продукции ПЦР, КБМ АО Вектор-Бест-Европа",
        photo: "images/speakers/soroka.jpg",
        topic: "Технологические решения в области ПЦР-диагностики для централизованных лабораторий",
        details: []
    },
    // НОВЫЕ СПИКЕРЫ
    {
        id: 11,
        name: "Тиванова Елена Валерьевна",
        role: "Руководитель направления лабораторной медицины ОМДиЭ ФБУН ЦНИИ Эпидемиологии Роспотребнадзора",
        photo: "images/speakers/tivanova.jpg", // нужно добавить фото
        topic: "Эффективные подходы к скринингу для реализации Национального плана по борьбе с вирусным гепатитом С",
        details: []
    },
    {
        id: 12,
        name: "Абсаликов Артем Ринатович",
        role: "Специалист по планированию лаборатории в г. Москва",
        photo: "images/speakers/absalikov.jpg", // нужно добавить фото
        topic: "Концепция Lean и потери в лабораторном производстве",
        details: []
    },
    {
    id: 13,
    name: "Овчинникова Лиана Григорьевна",
    role: "директор по развитию АО «БиоХимМак»",
    photo: "images/speakers/ovchinnikova.jpg", // нужно добавить фото
    topic: "Новейшие подходы к диагностике кала: от преаналитики до полной автоматизации",
    details: []
}
];
let currentPhotoSlide = 0;
let currentSpeakerSlide = 0;
let photoAutoSlideInterval;
let speakerAutoSlideInterval;
let yandexMap = null;

// ==================== ИНИЦИАЛИЗАЦИЯ ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Инициализация Форума лабораторных инноваций');
    
    const isAboutPage = document.body.classList.contains('about-page');
    const isRegistrationPage = document.body.classList.contains('registration-page');
    
    initBurgerMenu();
    initSmoothScroll();
    initBackToTop();
    
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

function initSpeakersCarousel() {
    const speakersSection = document.getElementById('speakers');
    if (!speakersSection) {
        console.log('❌ Не найдена секция спикеров');
        return;
    }
    
    console.log('🎯 Инициализация карусели спикеров...');
    
    // Создаем структуру карусели
    const carouselHTML = `
        <div class="carousel-wrapper">
            <button class="carousel-btn carousel-btn-prev" aria-label="Предыдущий спикер">‹</button>
            <div class="carousel-track" id="carouselTrack">
                <!-- Слайды будут добавлены через JavaScript -->
            </div>
            <button class="carousel-btn carousel-btn-next" aria-label="Следующий спикер">›</button>
        </div>
        <div class="carousel-dots" id="carouselDots">
            <!-- Точки навигации будут добавлены через JavaScript -->
        </div>
    `;
    
    speakersSection.querySelector('.container').insertAdjacentHTML('beforeend', carouselHTML);
    
    const carouselTrack = document.getElementById('carouselTrack');
    const carouselDots = document.getElementById('carouselDots');
    
    // Создаем слайды
    speakers.forEach((speaker, index) => {
        const slide = document.createElement('div');
        slide.className = `speaker-slide`; // Убираем active класс здесь
        slide.setAttribute('data-speaker-id', speaker.id);
        slide.setAttribute('tabindex', '0');
        slide.setAttribute('role', 'button');
        slide.setAttribute('aria-label', `Подробнее о спикере ${speaker.name}`);
        
        slide.innerHTML = `
            <div class="speaker-photo" style="background-image: url('${speaker.photo}')"></div>
            <h3 class="speaker-name">${speaker.name}</h3>
            <div class="speaker-role">${speaker.role}</div>
            <div class="speaker-more">Нажмите для подробностей</div>
        `;
        
        slide.addEventListener('click', () => openSpeakerModal(speaker));
        slide.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openSpeakerModal(speaker);
            }
        });
        
        carouselTrack.appendChild(slide);
        
        // Создаем точки навигации
        const dot = document.createElement('button');
        dot.className = `carousel-dot`; // Убираем active класс здесь
        dot.setAttribute('aria-label', `Перейти к слайду ${index + 1}`);
        dot.addEventListener('click', () => goToSpeakerSlide(index));
        carouselDots.appendChild(dot);
    });
    
    // Устанавливаем начальный слайд как центральный
    currentSpeakerSlide = Math.floor(speakers.length / 2);
    
    // Добавляем обработчики событий
    const prevBtn = document.querySelector('.carousel-btn-prev');
    const nextBtn = document.querySelector('.carousel-btn-next');
    
    prevBtn.addEventListener('click', () => moveSpeakerSlide(-1));
    nextBtn.addEventListener('click', () => moveSpeakerSlide(1));
    
    // Инициализируем модальное окно
    initSpeakerModal();
    
    // Обновляем карусель и запускаем автопрокрутку
    updateSpeakerCarousel();
    startSpeakerAutoSlide();
    
    console.log('✅ Карусель спикеров создана, начинаем с центрального слайда:', currentSpeakerSlide);
}

function moveSpeakerSlide(direction) {
    const slides = document.querySelectorAll('.speaker-slide');
    if (slides.length === 0) return;
    
    currentSpeakerSlide = (currentSpeakerSlide + direction + slides.length) % slides.length;
    updateSpeakerCarousel();
    resetSpeakerAutoSlide();
}

function goToSpeakerSlide(index) {
    const slides = document.querySelectorAll('.speaker-slide');
    if (index >= 0 && index < slides.length) {
        currentSpeakerSlide = index;
        updateSpeakerCarousel();
        resetSpeakerAutoSlide();
    }
}

function updateSpeakerCarousel() {
    const carouselTrack = document.getElementById('carouselTrack');
    const slides = document.querySelectorAll('.speaker-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    
    if (!carouselTrack || slides.length === 0) return;
    
    // Рассчитываем смещение для центрирования активного слайда
    const slideWidth = slides[0].offsetWidth + 24; // + gap
    const containerWidth = carouselTrack.parentElement.offsetWidth;
    const offset = (containerWidth / 2) - (slideWidth / 2) - (currentSpeakerSlide * slideWidth);
    
    carouselTrack.style.transform = `translateX(${offset}px)`;
    
    // Обновляем активные классы
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentSpeakerSlide);
    });
    
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSpeakerSlide);
    });
}

function startSpeakerAutoSlide() {
    speakerAutoSlideInterval = setInterval(() => {
        moveSpeakerSlide(1);
    }, 5000);
}

function stopSpeakerAutoSlide() {
    if (speakerAutoSlideInterval) {
        clearInterval(speakerAutoSlideInterval);
    }
}

function resetSpeakerAutoSlide() {
    stopSpeakerAutoSlide();
    startSpeakerAutoSlide();
}

function initSpeakerModal() {
    // Создаем модальное окно если его нет
    if (!document.getElementById('speakerModal')) {
        const modalHTML = `
            <div id="speakerModal" class="speaker-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <button class="modal-close" id="modalClose" aria-label="Закрыть окно">×</button>
                    <div class="modal-body" id="modalBody">
                        <!-- Контент будет заполнен динамически -->
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    const modal = document.getElementById('speakerModal');
    const closeBtn = document.getElementById('modalClose');
    const overlay = modal.querySelector('.modal-overlay');
    
    // Обработчики закрытия
    closeBtn.addEventListener('click', closeSpeakerModal);
    overlay.addEventListener('click', closeSpeakerModal);
    
    // Закрытие по Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeSpeakerModal();
        }
    });
    
    // Предотвращаем закрытие при клике на контент
    modal.querySelector('.modal-content').addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

function openSpeakerModal(speaker) {
    const modal = document.getElementById('speakerModal');
    const modalBody = document.getElementById('modalBody');
    
    if (!modal || !modalBody) return;
    
    // Создаем HTML для модального окна
    let modalHTML = `
        <div class="modal-speaker-header">
            <div class="modal-speaker-photo" style="background-image: url('${speaker.photo}')"></div>
            <h2 class="modal-speaker-name">${speaker.name}</h2>
            <div class="modal-speaker-role">${speaker.role}</div>
        </div>
    `;
    
    // Основной доклад
    modalHTML += `
        <div class="modal-speaker-topic">
            <div class="modal-topic-title">Тема выступления:</div>
            <div class="modal-topic-text">${speaker.topic}</div>
    `;
    
    // Второй доклад если есть - ОСТАВЛЯЕМ ЭТОТ БЛОК
    if (speaker.details) {
        const secondSpeech = speaker.details.find(detail => detail.includes('Дополнительный доклад:'));
        if (secondSpeech) {
            modalHTML += `
                <div class="modal-topic-desc" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(0, 229, 255, 0.2);">
                    <div class="modal-topic-title" style="font-size: 1rem;">Вторая тема выступления:</div>
                    <div class="modal-topic-text">${secondSpeech.replace('Дополнительный доклад: ', '')}</div>
                </div>
            `;
        }
    }
    
    modalHTML += `</div>`;
    
    modalBody.innerHTML = modalHTML;
    
    // Показываем модальное окно
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Фокусируемся на кнопке закрытия для доступности
    setTimeout(() => {
        document.getElementById('modalClose').focus();
    }, 100);
}

function closeSpeakerModal() {
    const modal = document.getElementById('speakerModal');
    if (!modal) return;
    
    modal.classList.remove('active');
    document.body.style.overflow = '';
    
    // Возвращаем фокус на активный слайд
    const activeSlide = document.querySelector('.speaker-slide.active');
    if (activeSlide) {
        activeSlide.focus();
    }
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

// ==================== СЛАЙДЕР ФОТО ====================

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
            <img src="${photoPath}" alt="Фото с конференции ${index + 1}"
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

// ==================== КАРТЫ И НАВИГАЦИЯ ====================

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
                        <p><strong>Форум лабораторных инноваций</strong></p>
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
PRODID:-//Форум лабораторных инноваций//Conference 2025//RU
BEGIN:VEVENT
UID:${Date.now()}@labforum2025.ru
DTSTAMP:${formatDate(new Date())}
DTSTART:${formatDate(startDate)}
DTEND:${formatDate(endDate)}
SUMMARY:${CONFIG.event.title}
DESCRIPTION:${CONFIG.event.description}\\\\n\\\\n📅 Дата: 21 ноября 2025 г.\\\\n⏰ Время: 11:00\\\\n📍 Место: ${CONFIG.location.address}
LOCATION:${CONFIG.location.address}
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
        
        showNotification('📅 Файл календаря скачан! Импортируйте его в ваш календарь.', 'success');
    } catch (error) {
        showNotification('❌ Произошла ошибка при создании файла календаря', 'error');
    }
}

function openNavigation() {
    const url = `https://yandex.ru/maps/?pt=${CONFIG.location.coordinates[1]},${CONFIG.location.coordinates[0]}&z=17&l=map`;
    window.open(url, '_blank');
}

// ==================== АНИМАЦИИ И УТИЛИТЫ ====================

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

// ==================== СИСТЕМА ОТЗЫВОВ ====================

function initFeedbackSystem() {
    const conferenceDate = new Date('2025-11-21');
    const currentDate = new Date();
    
    if (currentDate < conferenceDate) {
        console.log('📅 Конференция еще не прошла - отзывы отключены');
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

    const lastShownTime = sessionStorage.getItem('feedbackPopupLastShown');
    if (lastShownTime) {
        const timePassed = Date.now() - parseInt(lastShownTime);
        const oneHour = 60 * 60 * 1000;
        if (timePassed < oneHour) {
            return;
        }
    }

    function showPopup() {
        if (!popupShown) {
            popup.classList.add('active');
            popupShown = true;
            sessionStorage.setItem('feedbackPopupLastShown', Date.now().toString());
        }
    }

    function closePopup() {
        popup.classList.remove('active');
    }

    function remindLater() {
        closePopup();
        setTimeout(() => {
            sessionStorage.removeItem('feedbackPopupLastShown');
        }, 60 * 60 * 1000);
    }

    setTimeout(showPopup, 10000);

    document.addEventListener('mouseout', (e) => {
        if (e.clientY < 50 && !popupShown) {
            showPopup();
        }
    });

    closeBtn.addEventListener('click', closePopup);
    if (laterBtn) {
        laterBtn.addEventListener('click', remindLater);
    }

    popup.addEventListener('click', (e) => {
        if (e.target === popup) {
            closePopup();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            closePopup();
        }
    });
}
// Функционал фильтрации программы
function initProgramFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const timeBlocks = document.querySelectorAll('.time-block');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Убираем активный класс у всех кнопок
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Добавляем активный класс текущей кнопке
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            timeBlocks.forEach(block => {
                if (filter === 'all' || block.getAttribute('data-block') === filter) {
                    block.style.display = 'block';
                    setTimeout(() => {
                        block.style.opacity = '1';
                        block.style.transform = 'translateY(0)';
                    }, 50);
                } else {
                    block.style.opacity = '0';
                    block.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        block.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
}

// Анимация появления блоков
function initProgramAnimations() {
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
    
    const timeBlocks = document.querySelectorAll('.time-block');
    timeBlocks.forEach(block => {
        block.style.opacity = '0';
        block.style.transform = 'translateY(30px)';
        block.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(block);
    });
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    initProgramFilters();
    initProgramAnimations();
});
// Переключение программы
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleProgram');
    const programFull = document.getElementById('programFull');
    
    if (toggleBtn && programFull) {
        toggleBtn.addEventListener('click', function() {
            const isExpanded = programFull.style.display === 'block';
            
            if (isExpanded) {
                programFull.style.display = 'none';
                toggleBtn.classList.remove('expanded');
                toggleBtn.querySelector('.btn-text').textContent = 'Показать программу';
            } else {
                programFull.style.display = 'block';
                toggleBtn.classList.add('expanded');
                toggleBtn.querySelector('.btn-text').textContent = 'Скрыть программу';
                
                // Инициализируем фильтры при первом открытии
                if (!window.programFiltersInitialized) {
                    initProgramFilters();
                    initProgramAnimations();
                    window.programFiltersInitialized = true;
                }
            }
        });
    }
});
// ==================== ОБРАБОТЧИКИ СОБЫТИЙ ====================

function handleResize() {
    updateSpeakerCarousel();
}

function cleanup() {
    stopPhotoAutoSlide();
    stopSpeakerAutoSlide();
}

window.addEventListener('resize', handleResize);
window.addEventListener('beforeunload', cleanup);
window.openNavigation = openNavigation;
window.addToCalendar = addToCalendar;

console.log('🎉 Все модули Форума лабораторных инноваций успешно загружены!');
