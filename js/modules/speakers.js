// ===== МОДУЛЬ РАБОТЫ СО СПИКЕРАМИ =====

// Данные спикеров
const speakers = [
    {
        id: 1,
        name: "Вербенкин Александр Валерьевич",
        role: "Главный врач клиники Вербенкина, врач-эндокринолог",
        photo: "images/person-placeholder.svg",
        topic: "Анализы под вопросом: кто отвечает за неверный результат?",
    },
    {
        id: 2,
        name: "Жилина Светлана Владимировна",
        role: "Кандидат медицинских наук, врач-бактериолог высшей квалификационной категории, член комитета по микробиологии Ассоциации «Федерация лабораторной медицины», заведующий микробиологической лабораторией Клиническая больница №1 АО Группа компаний МЕДСИ",
        photo: "images/person-placeholder.svg",
        topic: "Синтез классики и новых технологий в комплексной микробиологической диагностике гастроэнтеритов. Возможности микробиологических лабораторий",
    },
    {
        id: 3,
        name: "Лешкина Гульнара Витальевна",
        role: "Врач-цитолог КДЛ ФБУН ЦНИИ эпидемиологии Роспотребнадзора, врач-цитолог КДЛ ФБУН ЦНИИ эпидемиологии Роспотребнадзора",
        photo: "images/person-placeholder.svg",
        topic: "Возможности цитологического исследования в современной лабораторной диагностике: практика, качество, обучение + Мастер-класс",
    },
    {
        id: 4,
        name: "Денисов Дмитрий Геннадьевич",
        role: "Медицинский директор Лабораторной службы «Хеликс»",
        photo: "images/person-placeholder.svg",
        topic: "Персональный медицинский ИИ-навигатор: как современные сервисы лаборатории помогают заботиться о здоровье",
    },
    {
        id: 5,
        name: "Станкевич Любовь Ивановна",
        role: "Кандидат медицинских наук, Директор по лабораторной медицине и производству ЛабКвест,  доцент кафедры клинической лабораторной диагностики и патологической анатомии Академии постдипломного образования ФГБУ ФНКЦ ФМБА России, вице-президент Российской ассоциации медицинской лабораторной диагностики (РАМЛД)",
        photo: "images/person-placeholder.svg",
        topic: "Практические аспекты скрининга рака шейки матки: нюансы рекомендаций и новые тренды",
        details: [
            "Дополнительный доклад: Повреждения печени: молчаливая сага и лабораторные решения при поддержке искусственного интеллекта для полной диагностики"]
    },
    {
        id: 6,
        name: "Карасев Александр Владимирович",
        role: "Врач-педиатр, специалист клинической лабораторной диагностики, Медицинский директор Большая тройка-Медицина",
        photo: "images/person-placeholder.svg",
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
        photo: "images/person-placeholder.svg",
        topic: "Технологические решения для лабораторной диагностики от ведущего российского производителя",
        details: []
    },
    {
        id: 8,
        name: "Буллих Артем Владимирович",
        role: "Кандидат медицинских наук, Главный специалист по направлению Патоморфологические исследования, АО ГК Медси, заведующий ЦПАО",
        photo: "images/person-placeholder.svg",
        topic: "Опыт применения цифровой патологии при централизации гистологических исследований. Перспектива развития.",
        details: []
    },
    {
        id: 9,
        name: "Шадрина Екатерина Станиславовна",
        role: "Руководитель направления системы менеджмента качества,  департамента качества и безопасности медицинской деятельности АО Медскан. Внештатный специалист по сестринскому делу ГК Медскан.",
        photo: "images/person-placeholder.svg",
        topic: "Взаимодействие Медсестра-пациент на преаналитическом этапе. Ключевые факторы качества и безопасности по стандартам JCI и ISO",
        details: []
    },
    {
        id: 10,
        name: "Сорока Александр Евгеньевич",
        role: "Старший специалист по продукции ПЦР, КБМ АО Вектор-Бест-Европа",
        photo: "images/person-placeholder.svg",
        topic: "Технологические решения в области ПЦР-диагностики для централизованных лабораторий",
        details: []
    },
    {
        id: 12,
        name: "Абсаликов Артем Ринатович",
        role: "Специалист по планированию лаборатории в г. Красногорск",
        photo: "images/person-placeholder.svg",
        topic: "Концепция Lean и потери в лабораторном производстве",
        details: []
    },
    {
        id: 13,
        name: "Овчинникова Лиана Григорьевна",
        role: "директор по развитию АО «БиоХимМак»",
        photo: "images/person-placeholder.svg",
        topic: "Новейшие подходы к диагностике кала: от преаналитики до полной автоматизации",
        details: []
    }
];

// Состояние карусели
let currentSpeakerSlide = 0;
let speakerAutoSlideInterval;

function setSpeakerPhotoBackground(element, source) {
    const fallback = 'images/person-placeholder.svg';
    const safeFallback = window.escapeHtml ? window.escapeHtml(fallback) : fallback;
    const safeSource = window.escapeHtml ? window.escapeHtml(source || fallback) : (source || fallback);

    element.style.backgroundImage = `url('${safeFallback}')`;

    const probe = new Image();
    probe.onload = () => {
        element.style.backgroundImage = `url('${safeSource}')`;
    };
    probe.onerror = () => {
        element.style.backgroundImage = `url('${safeFallback}')`;
    };
    probe.src = source || fallback;
}

// Инициализация карусели спикеров
function initSpeakersCarousel() {
    const speakersSection = document.getElementById('speakers');
    if (!speakersSection) return;
    
    const container = speakersSection.querySelector('.container');
    if (!container) return;
    
    if (typeof window.debugLog === 'function') {
        window.debugLog('🎯 Инициализация карусели спикеров...');
    }
    
    // Создаем структуру карусели безопасно
    const carouselWrapper = document.createElement('div');
    carouselWrapper.className = 'carousel-wrapper';
    
    const prevBtn = document.createElement('button');
    prevBtn.className = 'carousel-btn carousel-btn-prev';
    prevBtn.setAttribute('aria-label', 'Предыдущий спикер');
    prevBtn.textContent = '‹';
    
    const carouselTrack = document.createElement('div');
    carouselTrack.className = 'carousel-track';
    carouselTrack.id = 'carouselTrack';
    
    const nextBtn = document.createElement('button');
    nextBtn.className = 'carousel-btn carousel-btn-next';
    nextBtn.setAttribute('aria-label', 'Следующий спикер');
    nextBtn.textContent = '›';
    
    carouselWrapper.appendChild(prevBtn);
    carouselWrapper.appendChild(carouselTrack);
    carouselWrapper.appendChild(nextBtn);
    
    const carouselDots = document.createElement('div');
    carouselDots.className = 'carousel-dots';
    carouselDots.id = 'carouselDots';
    
    container.appendChild(carouselWrapper);
    container.appendChild(carouselDots);
    
    // Создаем слайды
    speakers.forEach((speaker, index) => {
        const slide = document.createElement('div');
        slide.className = 'speaker-slide';
        slide.setAttribute('data-speaker-id', speaker.id);
        slide.setAttribute('tabindex', '0');
        slide.setAttribute('role', 'button');
        slide.setAttribute('aria-label', `Подробнее о спикере ${speaker.name}`);
        
        // Создаем элементы безопасно
        const photoDiv = document.createElement('div');
        photoDiv.className = 'speaker-photo';
        setSpeakerPhotoBackground(photoDiv, speaker.photo);
        
        const nameH3 = document.createElement('h3');
        nameH3.className = 'speaker-name';
        nameH3.textContent = speaker.name;
        
        const roleDiv = document.createElement('div');
        roleDiv.className = 'speaker-role';
        roleDiv.textContent = speaker.role;
        
        const moreDiv = document.createElement('div');
        moreDiv.className = 'speaker-more';
        moreDiv.textContent = 'Нажмите для подробностей';
        
        slide.appendChild(photoDiv);
        slide.appendChild(nameH3);
        slide.appendChild(roleDiv);
        slide.appendChild(moreDiv);
        
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
        dot.className = 'carousel-dot';
        dot.setAttribute('aria-label', `Перейти к слайду ${index + 1}`);
        dot.addEventListener('click', () => goToSpeakerSlide(index));
        carouselDots.appendChild(dot);
    });
    
    // Устанавливаем начальный слайд как центральный
    currentSpeakerSlide = Math.floor(speakers.length / 2);
    
    // Добавляем обработчики событий
    const prevBtnEl = document.querySelector('.carousel-btn-prev');
    const nextBtnEl = document.querySelector('.carousel-btn-next');
    
    if (prevBtnEl) prevBtnEl.addEventListener('click', () => moveSpeakerSlide(-1));
    if (nextBtnEl) nextBtnEl.addEventListener('click', () => moveSpeakerSlide(1));
    
    // Инициализируем модальное окно
    initSpeakerModal();
    
    // Обновляем карусель и запускаем автопрокрутку
    updateSpeakerCarousel();
    startSpeakerAutoSlide();
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
        const modal = document.createElement('div');
        modal.id = 'speakerModal';
        modal.className = 'speaker-modal';
        
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        const content = document.createElement('div');
        content.className = 'modal-content';
        
        const closeBtn = document.createElement('button');
        closeBtn.className = 'modal-close';
        closeBtn.id = 'modalClose';
        closeBtn.setAttribute('aria-label', 'Закрыть окно');
        closeBtn.textContent = '×';
        
        const body = document.createElement('div');
        body.className = 'modal-body';
        body.id = 'modalBody';
        
        content.appendChild(closeBtn);
        content.appendChild(body);
        modal.appendChild(overlay);
        modal.appendChild(content);
        document.body.appendChild(modal);
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
    
    // Безопасное создание HTML
    const header = document.createElement('div');
    header.className = 'modal-speaker-header';
    
    const photoDiv = document.createElement('div');
    photoDiv.className = 'modal-speaker-photo';
    setSpeakerPhotoBackground(photoDiv, speaker.photo);
    
    const nameH2 = document.createElement('h2');
    nameH2.className = 'modal-speaker-name';
    nameH2.textContent = speaker.name;
    
    const roleDiv = document.createElement('div');
    roleDiv.className = 'modal-speaker-role';
    roleDiv.textContent = speaker.role;
    
    header.appendChild(photoDiv);
    header.appendChild(nameH2);
    header.appendChild(roleDiv);
    
    const topicDiv = document.createElement('div');
    topicDiv.className = 'modal-speaker-topic';
    
    const topicTitle = document.createElement('div');
    topicTitle.className = 'modal-topic-title';
    topicTitle.textContent = 'Тема выступления:';
    
    const topicText = document.createElement('div');
    topicText.className = 'modal-topic-text';
    topicText.textContent = speaker.topic;
    
    topicDiv.appendChild(topicTitle);
    topicDiv.appendChild(topicText);
    
    // Второй доклад если есть
    if (speaker.details && speaker.details.length > 0) {
        const secondSpeech = speaker.details.find(detail => detail.includes('Дополнительный доклад:'));
        if (secondSpeech) {
            const descDiv = document.createElement('div');
            descDiv.className = 'modal-topic-desc';
            descDiv.style.marginTop = '15px';
            descDiv.style.paddingTop = '15px';
            descDiv.style.borderTop = '1px solid rgba(0, 229, 255, 0.2)';
            
            const secondTitle = document.createElement('div');
            secondTitle.className = 'modal-topic-title';
            secondTitle.style.fontSize = '1rem';
            secondTitle.textContent = 'Вторая тема выступления:';
            
            const secondText = document.createElement('div');
            secondText.className = 'modal-topic-text';
            secondText.textContent = secondSpeech.replace('Дополнительный доклад: ', '');
            
            descDiv.appendChild(secondTitle);
            descDiv.appendChild(secondText);
            topicDiv.appendChild(descDiv);
        }
    }
    
    // Очищаем и заполняем модальное окно
    modalBody.textContent = '';
    modalBody.appendChild(header);
    modalBody.appendChild(topicDiv);
    
    // Показываем модальное окно
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Фокусируемся на кнопке закрытия для доступности
    setTimeout(() => {
        const closeBtn = document.getElementById('modalClose');
        if (closeBtn) closeBtn.focus();
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

// Экспорт функций
window.initSpeakersCarousel = initSpeakersCarousel;
window.stopSpeakerAutoSlide = stopSpeakerAutoSlide;
window.updateSpeakerCarousel = updateSpeakerCarousel;
