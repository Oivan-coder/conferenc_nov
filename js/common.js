// ===== ОБЩИЙ МОДУЛЬ ДЛЯ ВСЕХ СТРАНИЦ =====

const DEBUG = false;
const SITE_TITLE = 'Референс-центр лабораторной службы Московской области';
const SITE_TITLE_SHORT = 'РЦЛСМО';
const MOBILE_NAV_BREAKPOINT = 968;
const SITE_LOADER_STORAGE_KEY = 'rclsmo-loader-test';
const SITE_LOADER_SESSION_KEY = 'rclsmo-loader-full-shown';

const SITE_NAV = [
    { id: 'home', href: '/', label: 'Главная' },
    {
        id: 'events',
        label: 'Мероприятия',
        children: [
            { id: 'conference-2026', href: '/conference-2026/', label: 'Форум 7 октября 2026' },
            { id: 'conf-nov-2025', href: '/conf_nov2025', label: 'Форум ноябрь 2025' },
            { id: 'conf-sen-2025', href: '/conf_sen2025', label: 'Сентябрь 2025' }
        ]
    },
    { id: 'about', href: '/about', label: 'О центре' }
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
    if (body.classList.contains('ask-page')) return 'feedback';
    if (body.classList.contains('privacy-page')) return 'privacy';
    if (path.includes('conference-2026')) return 'conference-2026';
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
            const isCurrent = item.children.some((child) => child.id === activePage);
            const childLinks = item.children.map((child) => `
                <li>
                    <a href="${child.href}"${child.id === activePage ? ' class="active"' : ''}>${child.label}</a>
                </li>
            `).join('');

            return `
                <li class="menu-dropdown${isCurrent ? ' current' : ''}">
                    <button type="button" class="dropdown-toggle${isCurrent ? ' current' : ''}" aria-expanded="false" aria-haspopup="true">
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
                    <a href="/" class="logo" aria-label="${SITE_TITLE}" title="${SITE_TITLE}">
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

function buildFooterHtml(activePage) {
    const isConferencePage = activePage === 'conference-2026';
    const monikiRole = isConferencePage ? 'Организатор форума' : 'Референс-центр МОНИКИ';

    return `
        <footer class="main-footer institution-footer" id="contacts">
            <div class="container">
                <div class="institution-footer__panel" aria-label="Организации и контактная информация">
                    <section class="institution-footer__lead">
                        <span class="institution-footer__eyebrow">${monikiRole}</span>
                        <a class="institution-footer__brand" href="https://www.monikiweb.ru/" target="_blank" rel="noopener noreferrer">
                            <span class="institution-footer__brand-logo">
                                <img src="images/moniki-logo-preview.svg" alt="" loading="lazy" decoding="async">
                            </span>
                            <span>
                                <strong>ГБУЗ МО МОНИКИ</strong>
                                <small>им. М. Ф. Владимирского</small>
                            </span>
                        </a>
                        <a class="institution-footer__more" href="/about/#organization">
                            Подробнее о центре <span aria-hidden="true">→</span>
                        </a>
                    </section>

                    <div class="institution-footer__meta">
                        <div class="institution-footer__partners">
                            <a class="institution-footer__partner" href="https://mz.mosreg.ru/" target="_blank" rel="noopener noreferrer">
                                <span class="institution-footer__partner-label">Во взаимодействии с</span>
                                <span class="institution-footer__partner-body">
                                    <span class="institution-footer__partner-logo">
                                        <img src="images/mz-mosreg-logo.png" alt="" loading="lazy" decoding="async">
                                    </span>
                                    <strong>Министерство здравоохранения Московской области</strong>
                                </span>
                            </a>

                            <a class="institution-footer__partner" href="https://cvimz.ru/" target="_blank" rel="noopener noreferrer">
                                <span class="institution-footer__partner-label">Организационное сопровождение</span>
                                <span class="institution-footer__partner-body">
                                    <span class="institution-footer__partner-logo">
                                        <img src="images/cvimz-logo.png" alt="" loading="lazy" decoding="async">
                                    </span>
                                    <strong>Центр внедрения изменений и обеспечения деятельности МЗ</strong>
                                </span>
                            </a>
                        </div>

                        <a class="institution-footer__contact" href="mailto:info@rclsmo.ru">
                            <span class="institution-footer__contact-mark" aria-hidden="true">@</span>
                            <span class="institution-footer__contact-copy">
                                <small>Контакт центра</small>
                                <strong>info@rclsmo.ru</strong>
                                <span class="institution-footer__contact-note">Общие вопросы и обратная связь</span>
                            </span>
                        </a>
                    </div>
                </div>

                <div class="footer-divider"></div>

                <div class="footer-bottom">
                    <div class="footer-identity">
                        <p class="footer-title">Информационный ресурс Референс-центра лабораторной службы Московской области</p>
                        <p class="footer-disclaimer">Не является официальным интернет-порталом органа власти или медицинской организации</p>
                    </div>
                    <nav class="footer-links" aria-label="Правовая информация">
                        <a href="/privacy/">Политика конфиденциальности</a>
                        <button type="button" class="footer-cookie-settings" data-cookie-settings>Настройки cookies</button>
                    </nav>
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
        footerHost.outerHTML = buildFooterHtml(activePage);
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

function closeDropdownMenu() {
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

        const menuDropdown = navMenu.querySelector('.menu-dropdown');
        if (menuDropdown?.classList.contains('active') && !menuDropdown.contains(event.target)) {
            closeDropdownMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (navMenu.classList.contains('active')) {
                closeBurgerMenu();
            } else {
                closeDropdownMenu();
            }
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

function readStorage(storage, key) {
    try {
        return storage.getItem(key);
    } catch (error) {
        return null;
    }
}

function writeStorage(storage, key, value) {
    try {
        storage.setItem(key, value);
    } catch (error) {
        debugLog('Storage write skipped', error);
    }
}

function removeStorage(storage, key) {
    try {
        storage.removeItem(key);
    } catch (error) {
        debugLog('Storage remove skipped', error);
    }
}

function getSiteLoaderSetting() {
    const params = new URLSearchParams(window.location.search);
    const queryValue = (params.get('loader') || '').toLowerCase();

    if (['off', '0', 'false', 'no'].includes(queryValue)) {
        writeStorage(window.localStorage, SITE_LOADER_STORAGE_KEY, 'off');
        removeStorage(window.sessionStorage, SITE_LOADER_SESSION_KEY);
        return null;
    }

    if (['auto', 'full', 'quick', 'on', '1'].includes(queryValue)) {
        const normalized = ['on', '1'].includes(queryValue) ? 'auto' : queryValue;
        writeStorage(window.localStorage, SITE_LOADER_STORAGE_KEY, normalized);
        return normalized;
    }

    const storedValue = readStorage(window.localStorage, SITE_LOADER_STORAGE_KEY);
    if (storedValue === 'off') {
        return null;
    }

    return storedValue || 'auto';
}

function resolveSiteLoaderMode(setting) {
    if (setting === 'full' || setting === 'quick') {
        return setting;
    }

    if (setting === 'auto') {
        const fullShown = readStorage(window.sessionStorage, SITE_LOADER_SESSION_KEY) === 'true';
        if (!fullShown) {
            writeStorage(window.sessionStorage, SITE_LOADER_SESSION_KEY, 'true');
            return 'full';
        }
        return null;
    }

    return null;
}

function initSiteLoaderTest() {
    if (document.getElementById('rclsmoSiteLoader')) {
        return;
    }

    const mode = resolveSiteLoaderMode(getSiteLoaderSetting());
    if (!mode) {
        return;
    }

    const style = document.createElement('style');
    style.id = 'rclsmo-site-loader-style';
    style.textContent = `
        #rclsmoSiteLoader {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            background: #020817;
            opacity: 1;
            transform: translateZ(0);
            transition: opacity .55s ease, filter .55s ease;
        }
        #rclsmoSiteLoader.is-hiding {
            opacity: 0;
            filter: blur(3px);
            pointer-events: none;
        }
        #rclsmoSiteLoader iframe {
            display: block;
            width: 100%;
            height: 100%;
            border: 0;
        }
    `;
    document.head.appendChild(style);

    const overlay = document.createElement('div');
    overlay.id = 'rclsmoSiteLoader';
    overlay.setAttribute('aria-label', 'Загрузка сайта');
    overlay.dataset.mode = mode;

    const frame = document.createElement('iframe');
    frame.title = 'Загрузка РЦЛСМО';
    frame.src = `/loader/rclsmo-loader.html?mode=${encodeURIComponent(mode)}`;
    frame.setAttribute('aria-hidden', 'true');
    overlay.appendChild(frame);
    document.body.prepend(overlay);
    debugLog(`Loader test mode: ${mode}`);

    const startedAt = performance.now();
    const minDuration = mode === 'full' ? 3800 : 1250;
    const maxDuration = mode === 'full' ? 6200 : 2400;
    let closing = false;

    const closeLoader = () => {
        if (closing) {
            return;
        }
        closing = true;
        const elapsed = performance.now() - startedAt;
        const wait = Math.max(0, minDuration - elapsed);
        window.setTimeout(() => {
            overlay.classList.add('is-hiding');
            window.setTimeout(() => {
                overlay.remove();
            }, 650);
        }, wait);
    };

    if (document.readyState === 'complete') {
        closeLoader();
    } else {
        window.addEventListener('load', closeLoader, { once: true });
    }

    window.setTimeout(closeLoader, maxDuration);
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

initSiteLoaderTest();

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
