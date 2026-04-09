// ===== ОБЩИЙ МОДУЛЬ ДЛЯ ВСЕХ СТРАНИЦ =====

const DEBUG = false;
const SITE_TITLE = 'Референс-центр лабораторной службы Московской области';
const SITE_TITLE_SHORT = 'РЦЛСМО';
const MOBILE_NAV_BREAKPOINT = 968;

const SITE_NAV = [
    { id: 'home', href: 'index.html', label: 'Главная' },
    { id: 'about', href: 'about.html', label: 'О центре' },
    {
        id: 'events',
        label: 'Мероприятия',
        children: [
            { id: 'conf-mart-2026', href: 'conf_mart2026.html', label: 'Форум март 2026' },
            { id: 'conf-nov-2025', href: 'conf_nov2025.html', label: 'Форум ноябрь 2025' },
            { id: 'conf-sen-2025', href: 'conf_sen2025.html', label: 'Сентябрь 2025' }
        ]
    },
    { id: 'reports', href: 'reports.html', label: 'Отчеты' },
    { id: 'registration', href: 'registration.html', label: 'Регистрация' }
];

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function createSafeUrl(type, value) {
    switch (type) {
        case 'phone':
            return `tel:${encodeURIComponent(value)}`;
        case 'whatsapp':
            return `https://wa.me/${value.replace(/[^0-9]/g, '')}`;
        case 'telegram':
            return `https://t.me/${encodeURIComponent(value.replace(/^@/, ''))}`;
        case 'email':
            return `mailto:${encodeURIComponent(value)}`;
        default:
            return '#';
    }
}

function createElementWithText(tag, className, text) {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text) {
        element.textContent = text;
    }
    return element;
}

function debugLog(...args) {
    if (DEBUG) {
        console.log(...args);
    }
}

function getPageContext() {
    const { body } = document;
    const path = window.location.pathname;
    const explicit = body.dataset.page;

    if (explicit) {
        return explicit;
    }

    if (body.classList.contains('home-page')) return 'home';
    if (body.classList.contains('about-page')) return 'about';
    if (body.classList.contains('registration-page')) return 'registration';
    if (body.classList.contains('reports-page')) return 'reports';
    if (body.classList.contains('ask-page')) return 'feedback';
    if (body.classList.contains('privacy-page')) return 'privacy';
    if (path.includes('conf_mart2026')) return 'conf-mart-2026';
    if (path.includes('conf_nov2025')) return 'conf-nov-2025';
    if (path.includes('conf_sen2025')) return 'conf-sen-2025';
    return 'home';
}

function getDefaultImageFallback(img) {
    if (!(img instanceof HTMLImageElement)) {
        return 'images/hero-placeholder.svg';
    }

    if (img.dataset.fallbackSrc) {
        return img.dataset.fallbackSrc;
    }

    const classNames = `${img.className || ''} ${(img.parentElement && img.parentElement.className) || ''}`.toLowerCase();
    const alt = (img.alt || '').toLowerCase();

    if (classNames.includes('logo') || alt.includes('логотип')) {
        return 'images/logo.png';
    }

    if (classNames.includes('speaker') || classNames.includes('person') || classNames.includes('specialist') || alt.includes('иван') || alt.includes('анна') || alt.includes('ирина')) {
        return 'images/person-placeholder.svg';
    }

    return 'images/hero-placeholder.svg';
}

function applyImageFallback(img) {
    if (!(img instanceof HTMLImageElement) || img.dataset.fallbackApplied === 'true') {
        return;
    }

    const fallbackSrc = getDefaultImageFallback(img);
    if (!fallbackSrc || img.getAttribute('src') === fallbackSrc) {
        return;
    }

    img.dataset.fallbackApplied = 'true';
    img.src = fallbackSrc;
}

function initMediaFallbacks() {
    const images = document.querySelectorAll('img');
    images.forEach((img) => {
        if (img.dataset.mediaFallbackReady === 'true') {
            return;
        }

        img.dataset.mediaFallbackReady = 'true';
        img.addEventListener('error', () => applyImageFallback(img));

        if (!img.getAttribute('src')) {
            applyImageFallback(img);
        }
    });

    const videos = document.querySelectorAll('video');
    videos.forEach((video) => {
        if (video.dataset.mediaFallbackReady === 'true') {
            return;
        }

        video.dataset.mediaFallbackReady = 'true';
        const markFallback = () => {
            video.classList.add('is-fallback');
        };

        video.addEventListener('error', markFallback);

        Array.from(video.querySelectorAll('source')).forEach((source) => {
            source.addEventListener('error', markFallback);
        });

        if (video.error) {
            markFallback();
        }
    });
}

function buildHeaderHtml(activePage) {
    const topLevelLinks = SITE_NAV.map((item) => {
        if (item.children) {
            const isActive = activePage.startsWith('conf-');
            const childLinks = item.children.map((child) => `
                <li>
                    <a href="${child.href}"${child.id === activePage ? ' class="active"' : ''}>${child.label}</a>
                </li>
            `).join('');

            return `
                <li class="menu-dropdown${isActive ? ' active' : ''}">
                    <button type="button" class="dropdown-toggle${isActive ? ' active' : ''}" aria-expanded="${isActive ? 'true' : 'false'}" aria-haspopup="true">
                        ${item.label}
                    </button>
                    <ul class="dropdown-menu">
                        ${childLinks}
                    </ul>
                </li>
            `;
        }

        return `
            <li>
                <a href="${item.href}"${item.id === activePage ? ' class="active"' : ''}>${item.label}</a>
            </li>
        `;
    }).join('');

    return `
        <header class="photo-header">
            <div class="container">
                <div class="header-content">
                    <a href="index.html" class="logo" aria-label="${SITE_TITLE}" title="${SITE_TITLE}">
                        <img src="images/logo.png" alt="Логотип РЦЛСМО" class="logo-image" data-fallback-src="images/logo.png">
                        <span class="logo-text">${SITE_TITLE_SHORT}</span>
                    </a>

                    <button class="burger-menu" id="burgerMenu" aria-label="Открыть меню" aria-expanded="false" aria-controls="navMenu" type="button">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>

                    <nav class="nav-menu" id="navMenu" aria-label="Основная навигация">
                        <ul class="nav-links">
                            ${topLevelLinks}
                            <li><a href="#contacts">Контакты</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </header>
    `;
}

function buildFooterHtml() {
    const currentYear = new Date().getFullYear();
    return `
        <footer class="main-footer" id="contacts">
            <div class="container">
                <div class="footer-main">
                    <div class="footer-block">
                        <div class="footer-info">
                            <span class="footer-label">Организатор</span>
                            <div class="footer-logo-wrapper">
                                <a href="https://mz.mosreg.ru/" target="_blank" rel="noopener noreferrer" class="footer-logo-link">
                                    <img src="images/mz-mosreg-logo.png" alt="Министерство здравоохранения Московской области" class="footer-logo">
                                    <span class="footer-org-name">Министерство здравоохранения Московской области</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="footer-block">
                        <div class="footer-info">
                            <span class="footer-label">Партнёр проекта</span>
                            <div class="footer-logo-wrapper">
                                <a href="https://cvimz.ru/" target="_blank" rel="noopener noreferrer" class="footer-logo-link">
                                    <img src="images/cvimz-logo.png" alt="Центр внедрения изменений и обеспечения деятельности МЗ" class="footer-logo">
                                    <span class="footer-org-name">Центр внедрения изменений и обеспечения деятельности МЗ</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="footer-block footer-contacts">
                        <div class="footer-info">
                            <span class="footer-label">Контакты для связи</span>
                            <div class="contact-items-wrapper">
                                <div class="contact-person-group">
                                    <div class="contact-header">
                                        <span class="contact-person">Иван Михайлович, заведующий референс-центром</span>
                                        <div class="contact-actions">
                                            <a href="tel:+79778290881" class="contact-phone-button" aria-label="Позвонить Ивану Михайловичу">
                                                <span class="phone-icon">📞</span>
                                            </a>
                                            <a href="https://t.me/ivan_goltsev" target="_blank" rel="noopener noreferrer" class="messenger-button tg-button" aria-label="Написать Ивану Михайловичу в Telegram">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.05 5.56-5.022c.242-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.654-.64.136-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                                                </svg>
                                            </a>
                                            <a href="https://wa.me/79778290881" target="_blank" rel="noopener noreferrer" class="messenger-button wa-button" aria-label="Написать Ивану Михайловичу в WhatsApp">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="contact-person-group">
                                    <div class="contact-header">
                                        <span class="contact-person">Ирина Владимировна, куратор проекта</span>
                                        <div class="contact-actions">
                                            <a href="tel:+79958832336" class="contact-phone-button" aria-label="Позвонить Ирине Владимировне">
                                                <span class="phone-icon">📞</span>
                                            </a>
                                            <a href="https://t.me/doc_kld" target="_blank" rel="noopener noreferrer" class="messenger-button tg-button" aria-label="Написать Ирине Владимировне в Telegram">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.05 5.56-5.022c.242-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.654-.64.136-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                                                </svg>
                                            </a>
                                            <a href="https://wa.me/79958832336" target="_blank" rel="noopener noreferrer" class="messenger-button wa-button" aria-label="Написать Ирине Владимировне в WhatsApp">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="footer-divider"></div>

                <div class="footer-copyright">
                    <p>© ${currentYear} ${SITE_TITLE}.</p>
                    <p class="copyright-secondary">
                        Все права защищены |
                        <a href="privacy.html">Политика конфиденциальности</a>
                    </p>
                </div>
            </div>
        </footer>
    `;
}

function renderSiteShell() {
    const activePage = getPageContext();
    const headerHost = document.querySelector('[data-site-shell="header"]') || document.querySelector('header.photo-header');
    const footerHost = document.querySelector('[data-site-shell="footer"]') || document.querySelector('footer.main-footer');

    if (headerHost) {
        headerHost.outerHTML = buildHeaderHtml(activePage);
    }

    if (footerHost) {
        footerHost.outerHTML = buildFooterHtml();
    }
}

function syncShellMetrics() {
    const header = document.querySelector('.photo-header');
    if (!header) {
        return;
    }

    const headerHeight = Math.ceil(header.getBoundingClientRect().height || 0);
    document.documentElement.style.setProperty('--site-header-height', `${headerHeight}px`);
}

function initHeaderState() {
    if (document.body.dataset.headerStateReady === 'true') {
        syncShellMetrics();
        return;
    }

    document.body.dataset.headerStateReady = 'true';
    const header = document.querySelector('.photo-header');
    if (!header) {
        return;
    }

    const updateHeaderState = () => {
        header.classList.toggle('scrolled', window.scrollY > 16);
        syncShellMetrics();
    };

    updateHeaderState();
    window.addEventListener('scroll', updateHeaderState, { passive: true });
    window.addEventListener('resize', updateHeaderState);
}

function closeBurgerMenu() {
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    document.body.classList.remove('menu-open');

    if (burgerMenu) {
        burgerMenu.classList.remove('active');
        burgerMenu.setAttribute('aria-expanded', 'false');
    }

    if (navMenu) {
        navMenu.classList.remove('active');
    }

    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const menuDropdown = document.querySelector('.menu-dropdown');
    if (dropdownToggle) {
        dropdownToggle.setAttribute('aria-expanded', 'false');
    }
    if (menuDropdown) {
        menuDropdown.classList.remove('active');
    }
}

function initBurgerMenu() {
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    const body = document.body;

    if (!burgerMenu || !navMenu) {
        return;
    }

    if (burgerMenu.hasAttribute('data-initialized')) {
        return;
    }
    burgerMenu.setAttribute('data-initialized', 'true');

    burgerMenu.addEventListener('click', (event) => {
        event.stopPropagation();
        const isExpanded = burgerMenu.classList.toggle('active');
        navMenu.classList.toggle('active', isExpanded);
        body.classList.toggle('menu-open', isExpanded);
        burgerMenu.setAttribute('aria-expanded', String(isExpanded));
    });

    document.addEventListener('click', (event) => {
        if (navMenu.classList.contains('active') && !navMenu.contains(event.target) && !burgerMenu.contains(event.target)) {
            closeBurgerMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && navMenu.classList.contains('active')) {
            closeBurgerMenu();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > MOBILE_NAV_BREAKPOINT && navMenu.classList.contains('active')) {
            closeBurgerMenu();
        }
        syncShellMetrics();
    });

    const navLinks = navMenu.querySelectorAll('a');
    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            closeBurgerMenu();
        });
    });

    const dropdownToggle = navMenu.querySelector('.dropdown-toggle');
    const dropdownMenu = navMenu.querySelector('.dropdown-menu');
    const menuDropdown = navMenu.querySelector('.menu-dropdown');

    if (dropdownToggle && dropdownMenu && menuDropdown) {
        dropdownToggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const isExpanded = menuDropdown.classList.toggle('active');
            dropdownToggle.setAttribute('aria-expanded', String(isExpanded));
        });
    }
}

function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function onAnchorClick(event) {
            const href = this.getAttribute('href');

            if (!href || href === '#' || href === '#top') {
                event.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            const targetElement = document.querySelector(href);
            if (!targetElement) {
                return;
            }

            event.preventDefault();
            const headerHeight = document.querySelector('.photo-header')?.offsetHeight || 80;
            const targetPosition = targetElement.offsetTop - headerHeight - 20;
            window.scrollTo({ top: targetPosition, behavior: 'smooth' });
        });
    });
}

function initBackToTop() {
    const backToTop = document.getElementById('backToTop');
    if (!backToTop || backToTop.hasAttribute('data-initialized')) {
        return;
    }
    backToTop.setAttribute('data-initialized', 'true');

    window.addEventListener('scroll', () => {
        backToTop.classList.toggle('visible', window.pageYOffset > 300);
    });

    backToTop.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;

    const content = document.createElement('div');
    content.className = 'notification-content';

    const messageSpan = document.createElement('span');
    messageSpan.className = 'notification-message';
    messageSpan.textContent = message;

    const closeBtn = document.createElement('button');
    closeBtn.className = 'notification-close';
    closeBtn.textContent = '×';
    closeBtn.setAttribute('aria-label', 'Закрыть уведомление');

    content.appendChild(messageSpan);
    content.appendChild(closeBtn);
    notification.appendChild(content);

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

    closeBtn.addEventListener('click', () => closeNotification(notification));
    window.setTimeout(() => closeNotification(notification), 5000);
}

function closeNotification(notification) {
    if (!notification || !notification.parentNode) {
        return;
    }

    notification.style.animation = 'slideOutRight 0.3s ease';
    window.setTimeout(() => {
        if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
        }
    }, 300);
}

if (!document.getElementById('notification-styles')) {
    const styles = document.createElement('style');
    styles.id = 'notification-styles';
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
    `;
    document.head.appendChild(styles);
}

document.addEventListener('DOMContentLoaded', () => {
    renderSiteShell();
    initHeaderState();
    initBurgerMenu();
    initSmoothScroll();
    initBackToTop();
    initMediaFallbacks();
    window.requestAnimationFrame(syncShellMetrics);
});

window.escapeHtml = escapeHtml;
window.createSafeUrl = createSafeUrl;
window.createElementWithText = createElementWithText;
window.debugLog = debugLog;
window.renderSiteShell = renderSiteShell;
window.syncShellMetrics = syncShellMetrics;
window.initHeaderState = initHeaderState;
window.initBurgerMenu = initBurgerMenu;
window.closeBurgerMenu = closeBurgerMenu;
window.initSmoothScroll = initSmoothScroll;
window.initBackToTop = initBackToTop;
window.showNotification = showNotification;
window.closeNotification = closeNotification;
window.initMediaFallbacks = initMediaFallbacks;
