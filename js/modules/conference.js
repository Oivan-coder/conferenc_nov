// ===== МОДУЛЬ КОНФЕРЕНЦИЙ =====

function initConferenceScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    const animatedElements = document.querySelectorAll('.feature-card, .program-block, .partner-card, .speaker-slide, .stat-item, .session-item');
    animatedElements.forEach((element) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(element);
    });
}

function initConferencePlaceholderAnimations() {
    const placeholders = document.querySelectorAll('.program-placeholder, .speakers-placeholder');
    placeholders.forEach((placeholder) => {
        placeholder.style.opacity = '0';
        placeholder.style.transform = 'translateY(20px)';

        setTimeout(() => {
            placeholder.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            placeholder.style.opacity = '1';
            placeholder.style.transform = 'translateY(0)';
        }, 300);
    });
}

function initProgramAccordion() {
    const accordionBlocks = document.querySelectorAll('.accordion-block');
    accordionBlocks.forEach((block) => {
        const header = block.querySelector('.accordion-header');
        if (!header || header.dataset.initialized === 'true') {
            return;
        }

        header.dataset.initialized = 'true';
        header.addEventListener('click', () => {
            accordionBlocks.forEach((otherBlock) => {
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

function initProgramFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const timeBlocks = document.querySelectorAll('.time-block');

    filterButtons.forEach((button) => {
        if (button.dataset.initialized === 'true') {
            return;
        }

        button.dataset.initialized = 'true';
        button.addEventListener('click', function onFilterClick() {
            filterButtons.forEach((btn) => btn.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            timeBlocks.forEach((block) => {
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

function initProgramAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    const timeBlocks = document.querySelectorAll('.time-block');
    timeBlocks.forEach((block) => {
        block.style.opacity = '0';
        block.style.transform = 'translateY(30px)';
        block.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(block);
    });
}

function initProgramToggle() {
    const toggleBtn = document.getElementById('toggleProgram');
    const programFull = document.getElementById('programFull');

    if (!toggleBtn || !programFull || toggleBtn.dataset.initialized === 'true') {
        return;
    }

    toggleBtn.dataset.initialized = 'true';
    toggleBtn.addEventListener('click', () => {
        const isExpanded = programFull.style.display === 'block';

        if (isExpanded) {
            programFull.style.display = 'none';
            toggleBtn.classList.remove('expanded');
            const btnText = toggleBtn.querySelector('.btn-text');
            if (btnText) {
                btnText.textContent = 'Показать программу';
            }
            return;
        }

        programFull.style.display = 'block';
        toggleBtn.classList.add('expanded');
        const btnText = toggleBtn.querySelector('.btn-text');
        if (btnText) {
            btnText.textContent = 'Скрыть программу';
        }

        if (!window.programFiltersInitialized) {
            initProgramFilters();
            initProgramAnimations();
            window.programFiltersInitialized = true;
        }
    });
}

function initFeedbackPopup() {
    const popup = document.getElementById('feedbackPopup');
    const closeBtn = document.getElementById('popupClose');
    const laterBtn = document.getElementById('popupLater');

    if (!popup || !closeBtn || popup.dataset.initialized === 'true') {
        return;
    }

    popup.dataset.initialized = 'true';
    let popupShown = false;

    const lastShownTime = sessionStorage.getItem('feedbackPopupLastShown');
    if (lastShownTime) {
        const timePassed = Date.now() - parseInt(lastShownTime, 10);
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

    document.addEventListener('mouseout', (event) => {
        if (event.clientY < 50 && !popupShown) {
            showPopup();
        }
    });

    closeBtn.addEventListener('click', closePopup);
    if (laterBtn) {
        laterBtn.addEventListener('click', remindLater);
    }

    popup.addEventListener('click', (event) => {
        if (event.target === popup || event.target.classList.contains('popup-overlay')) {
            closePopup();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && popup.classList.contains('active')) {
            closePopup();
        }
    });
}

function initFeedbackSystem() {
    const popup = document.getElementById('feedbackPopup');
    if (!popup) {
        return;
    }

    const activationDate = popup.dataset.enableAfter || '2025-11-21';
    const conferenceDate = new Date(activationDate);
    const currentDate = new Date();

    if (currentDate < conferenceDate) {
        if (window.debugLog) {
            window.debugLog('📅 Конференция еще не прошла - отзывы отключены');
        }
        return;
    }

    if (window.debugLog) {
        window.debugLog('💬 Система отзывов активирована');
    }
    initFeedbackPopup();
}

function handleConferenceResize() {
    if (window.updateSpeakerCarousel) {
        window.updateSpeakerCarousel();
    }
}

function cleanupConference() {
    if (window.stopPhotoAutoSlide) {
        window.stopPhotoAutoSlide();
    }
    if (window.stopSpeakerAutoSlide) {
        window.stopSpeakerAutoSlide();
    }
}

function initConferencePage() {
    if (window.debugLog) {
        window.debugLog('🎯 Инициализация страницы конференции');
    }

    if (window.initSpeakersCarousel) {
        window.initSpeakersCarousel();
    }
    initConferenceScrollAnimations();
    initConferencePlaceholderAnimations();
    if (window.initCalendarButtons) {
        window.initCalendarButtons();
    }
    if (window.initMapFunctions) {
        window.initMapFunctions();
    }
    initProgramAccordion();
    initProgramToggle();
    initFeedbackSystem();
}

window.addEventListener('resize', handleConferenceResize);
window.addEventListener('beforeunload', cleanupConference);

window.initConferencePage = initConferencePage;
window.initProgramFilters = initProgramFilters;
window.initProgramAnimations = initProgramAnimations;

document.addEventListener('DOMContentLoaded', () => {
    const pageContext = document.body.dataset.page || '';
    if (pageContext.startsWith('conf-')) {
        initConferencePage();
    }
});
