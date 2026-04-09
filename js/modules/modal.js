// ===== МОДУЛЬ МОДАЛЬНЫХ ОКОН ДЛЯ СПЕЦИАЛИСТОВ =====

// Данные специалистов
const specialistsData = {
    1: {
        name: "Щеблыкина-Монастырёва Ирина Владимировна",
        role: "Куратор референс-центра",
        bio: "Главный внештатный специалист по клинической лабораторной диагностике. Руководитель проекта «Централизация лабораторной службы» Московской области.",
        photo: "images/person-placeholder.svg",
        contacts: [
            { type: "phone", value: "+79958832336", label: "Телефон", icon: "📞" },
            { type: "whatsapp", value: "+79958832336", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "doc_kld", label: "Telegram", icon: "✈️" },
            { type: "email", value: "ScheblykinaIV@mosreg.ru", label: "Email", icon: "📧" }
        ]
    },
    2: {
        name: "Гольцев Иван Михайлович",
        role: "Заведующий референс-центром",
        bio: "Заведующий референс-центром лабораторной службы Московской области. Курирует организационные вопросы и развитие направления.",
        photo: "images/person-placeholder.svg",
        contacts: [
            { type: "phone", value: "+79778290881", label: "Телефон", icon: "📞" },
            { type: "whatsapp", value: "+79778290881", label: "WhatsApp", icon: "💬" },
            { type: "telegram", value: "ivan_goltsev", label: "Telegram", icon: "✈️" }
        ]
    },
    3: {
        name: "Довгаль Лада Алексеевна",
        role: "Ведущий специалист",
        bio: "Ведущий специалист референс-центра лабораторной службы Московской области.",
        photo: "images/person-placeholder.svg",
        contacts: []
    },
    4: {
        name: "Шоль Елизавета Викторовна",
        role: "Врач КЛД",
        bio: "Врач клинической лабораторной диагностики, участвующий в методическом и практическом сопровождении работы центра.",
        photo: "images/person-placeholder.svg",
        contacts: []
    },
    5: {
        name: "Манвелов Эдуард Витальевич",
        role: "Главный специалист ГКУ ЦВИОД",
        bio: "Главный специалист ГКУ ЦВИОД, участвующий в реализации задач и проектов референс-центра.",
        photo: "images/person-placeholder.svg",
        contacts: []
    }
};

/**
 * Открывает модальное окно специалиста
 * @param {number} specialistId - ID специалиста
 */
function openSpecialistModal(specialistId) {
    const specialist = specialistsData[specialistId];
    if (!specialist) return;
    
    const modal = document.getElementById('specialistModal');
    const modalBody = document.getElementById('specialistModalBody');
    
    if (!modal || !modalBody) return;
    
    // Очищаем содержимое
    modalBody.textContent = '';
    
    // Создаем элементы безопасно
    const photoContainer = document.createElement('div');
    photoContainer.className = 'modal-photo';
    
    const img = document.createElement('img');
    img.src = specialist.photo;
    img.alt = specialist.name;
    img.loading = 'lazy';
    
    // Обработка ошибки загрузки изображения
    img.addEventListener('error', function() {
        this.style.display = 'none';
        const emoji = specialist.photo.includes('irina') ? '👩‍⚕️' : 
                     specialist.photo.includes('ekaterina') ? '👨‍💼' : 
                     specialist.photo.includes('ivan') ? '🔧' : 
                     specialist.photo.includes('ustin') ? '💼' : '📊';
        photoContainer.textContent = emoji;
        photoContainer.style.fontSize = '4rem';
        photoContainer.style.textAlign = 'center';
        photoContainer.style.padding = '2rem';
    });
    
    photoContainer.appendChild(img);
    
    const name = document.createElement('h2');
    name.className = 'modal-name';
    name.textContent = specialist.name;
    
    const role = document.createElement('div');
    role.className = 'modal-role';
    role.textContent = specialist.role;
    
    const bio = document.createElement('div');
    bio.className = 'modal-bio';
    bio.textContent = specialist.bio;
    
    const contactsContainer = document.createElement('div');
    contactsContainer.className = 'modal-contacts';
    
    if (Array.isArray(specialist.contacts) && specialist.contacts.length) {
        specialist.contacts.forEach(contact => {
            const link = document.createElement('a');
            const safeUrl = window.createSafeUrl ? window.createSafeUrl(contact.type, contact.value) : '#';
            link.href = safeUrl;
            link.className = `modal-contact-link ${contact.type}`;
            
            if (contact.type === 'whatsapp' || contact.type === 'telegram') {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            }
            
            const icon = document.createElement('span');
            icon.textContent = contact.icon;
            link.appendChild(icon);
            
            const label = document.createTextNode(contact.label);
            link.appendChild(label);
            
            contactsContainer.appendChild(link);
        });
    }
    
    // Собираем все элементы
    modalBody.appendChild(photoContainer);
    modalBody.appendChild(name);
    modalBody.appendChild(role);
    modalBody.appendChild(bio);
    if (contactsContainer.childElementCount > 0) {
        modalBody.appendChild(contactsContainer);
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

/**
 * Закрывает модальное окно специалиста
 */
function closeSpecialistModal() {
    const modal = document.getElementById('specialistModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Инициализация модальных окон для страницы "О нас"
 */
function initAboutPageAnimations() {
    // Закрытие по ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const specialistModal = document.getElementById('specialistModal');
            if (specialistModal && specialistModal.classList.contains('active')) {
                closeSpecialistModal();
            }
        }
    });
    
    // Обработчики для кнопок закрытия модального окна
    const modalOverlay = document.getElementById('modalOverlay');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    
    if (modalOverlay) {
        modalOverlay.addEventListener('click', closeSpecialistModal);
    }
    
    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeSpecialistModal);
    }

    const triggerButtons = document.querySelectorAll('[data-specialist-trigger]');
    triggerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const specialistId = Number(button.getAttribute('data-specialist-trigger'));
            if (specialistId) {
                openSpecialistModal(specialistId);
            }
        });
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

    if (window.initMediaFallbacks) {
        window.initMediaFallbacks();
    }
}

// Экспорт функций
window.openSpecialistModal = openSpecialistModal;
window.closeSpecialistModal = closeSpecialistModal;
window.initAboutPageAnimations = initAboutPageAnimations;

document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('about-page')) {
        initAboutPageAnimations();
    }
});
