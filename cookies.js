// cookies.js — управление cookies и общие корректировки интерфейса
class CookieConsentManager {
    constructor() {
        this.consentCookieName = 'cookie_consent_accepted';
        this.bannerId = 'cookieConsentBanner';
        this.metrikaId = 105271987;
        this.initialize();
    }

    initialize() {
        this.ensureCookieSettingsStyles();
        this.bindCookieSettingsControls();

        const consentState = this.getConsentState();
        if (consentState === 'accepted') {
            this.loadYandexMetrika();
            return;
        }

        if (consentState === 'unset') {
            this.showBanner();
        }
    }

    getConsentState() {
        const storedValue = this.getStoredCookie(this.consentCookieName);

        if (storedValue === 'accepted' || storedValue === 'true') return 'accepted';
        if (storedValue === 'declined' || storedValue === 'false') return 'declined';
        return 'unset';
    }

    bindCookieSettingsControls() {
        document.addEventListener('click', (event) => {
            const settingsButton = event.target.closest?.('[data-cookie-settings]');
            if (!settingsButton) return;

            event.preventDefault();
            this.showBanner({ settingsMode: true });
        });
    }

    ensureCookieSettingsStyles() {
        if (document.getElementById('cookie-settings-control-styles')) return;

        const style = document.createElement('style');
        style.id = 'cookie-settings-control-styles';
        style.textContent = `
            .footer-cookie-settings {
                appearance: none;
                border: 0;
                background: transparent;
                color: inherit;
                font: inherit;
                line-height: inherit;
                padding: 0;
                text-decoration: underline;
                text-underline-offset: 3px;
                cursor: pointer;
            }

            .footer-cookie-settings:hover {
                color: var(--accent-primary, #68ddff);
            }

            .footer-cookie-settings:focus-visible {
                outline: 2px solid var(--accent-primary, #68ddff);
                outline-offset: 4px;
                border-radius: 3px;
            }
        `;
        document.head.appendChild(style);
    }

    showBanner({ settingsMode = false } = {}) {
        const existingBanner = document.getElementById(this.bannerId);
        if (existingBanner) {
            existingBanner.querySelector('button')?.focus();
            return;
        }

        const banner = document.createElement('div');
        banner.id = this.bannerId;
        banner.className = 'cookie-consent-banner';
        banner.setAttribute('role', 'dialog');
        banner.setAttribute('aria-labelledby', 'cookieConsentTitle');
        banner.setAttribute('aria-describedby', 'cookieConsentDescription');

        const content = document.createElement('div');
        content.className = 'cookie-banner-content';

        const textBlock = document.createElement('div');
        textBlock.className = 'cookie-banner-text';

        const title = document.createElement('h4');
        title.id = 'cookieConsentTitle';
        title.textContent = settingsMode ? 'Настройки cookies' : 'Использование cookies';

        const description = document.createElement('p');
        description.id = 'cookieConsentDescription';

        const state = this.getConsentState();
        if (settingsMode && state === 'accepted') {
            description.appendChild(document.createTextNode('Сейчас аналитика включена. Вы можете сохранить согласие или отключить аналитические cookies. Подробнее — в '));
        } else if (settingsMode && state === 'declined') {
            description.appendChild(document.createTextNode('Сейчас аналитика отключена. Вы можете изменить выбор. Подробнее — в '));
        } else {
            description.appendChild(document.createTextNode('Мы используем Яндекс.Метрику только после вашего явного согласия. Подробнее — в '));
        }

        const privacyLink = document.createElement('a');
        privacyLink.href = '/privacy/';
        privacyLink.target = '_blank';
        privacyLink.rel = 'noopener noreferrer';
        privacyLink.textContent = 'Политике конфиденциальности';

        description.appendChild(privacyLink);
        description.appendChild(document.createTextNode('.'));
        textBlock.appendChild(title);
        textBlock.appendChild(description);

        const buttons = document.createElement('div');
        buttons.className = 'cookie-banner-buttons';

        const acceptBtn = document.createElement('button');
        acceptBtn.className = 'cookie-banner-btn cookie-accept-btn';
        acceptBtn.type = 'button';
        acceptBtn.textContent = settingsMode && state === 'accepted' ? 'Оставить включённой' : 'Разрешить аналитику';

        const declineBtn = document.createElement('button');
        declineBtn.className = 'cookie-banner-btn cookie-info-btn';
        declineBtn.type = 'button';
        declineBtn.textContent = 'Не разрешать';

        const infoBtn = document.createElement('button');
        infoBtn.className = 'cookie-banner-btn cookie-info-btn';
        infoBtn.type = 'button';
        infoBtn.textContent = 'Подробнее';

        buttons.appendChild(acceptBtn);
        buttons.appendChild(declineBtn);
        buttons.appendChild(infoBtn);
        content.appendChild(textBlock);
        content.appendChild(buttons);
        banner.appendChild(content);
        document.body.appendChild(banner);

        acceptBtn.addEventListener('click', () => {
            this.acceptCookies();
            this.hideBanner(banner);
        });

        declineBtn.addEventListener('click', () => {
            this.declineCookies();
            this.hideBanner(banner);
        });

        infoBtn.addEventListener('click', () => {
            window.open('/privacy/', '_blank', 'noopener,noreferrer');
        });
    }

    hideBanner(banner) {
        if (!banner) return;
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(100px)';
        window.setTimeout(() => banner.remove(), 500);
    }

    acceptCookies() {
        this.setCookieValue(this.consentCookieName, 'accepted', 365);
        this.loadYandexMetrika();
        this.showToast('Аналитические cookies включены.');
    }

    declineCookies() {
        this.setCookieValue(this.consentCookieName, 'declined', 365);
        this.disableYandexMetrika();
        this.showToast('Аналитические cookies отключены.');
    }

    loadYandexMetrika() {
        if (window.__rclsmoMetrikaInitialized) return;

        window.ym = window.ym || function yandexMetrikaQueue() {
            (window.ym.a = window.ym.a || []).push(arguments);
        };
        window.ym.l = window.ym.l || Date.now();

        window.ym(this.metrikaId, 'init', {
            clickmap: true,
            trackLinks: true,
            accurateTrackBounce: true,
            webvisor: false
        });
        window.__rclsmoMetrikaInitialized = true;

        if (document.querySelector('script[data-analytics="yandex-metrika"]')) return;

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = `https://mc.yandex.ru/metrika/tag.js?id=${this.metrikaId}`;
        script.setAttribute('data-analytics', 'yandex-metrika');

        script.onerror = () => {
            window.__rclsmoMetrikaInitialized = false;
            script.remove();
            if (window.debugLog) window.debugLog('Не удалось загрузить Яндекс.Метрику');
        };

        document.head.appendChild(script);
    }

    disableYandexMetrika() {
        if (window.__rclsmoMetrikaInitialized && typeof window.ym === 'function') {
            try {
                window.ym(this.metrikaId, 'destruct');
            } catch (error) {
                if (window.debugLog) window.debugLog('Не удалось остановить Яндекс.Метрику в текущей вкладке', error);
            }
        }

        window.__rclsmoMetrikaInitialized = false;
        this.removeMetrikaCookies();
    }

    removeMetrikaCookies() {
        document.cookie
            .split(';')
            .map((item) => item.trim().split('=')[0])
            .filter((name) => name.startsWith('_ym'))
            .forEach((name) => this.deleteCookieValue(name));
    }

    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'cookie-toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        window.setTimeout(() => toast.remove(), 3000);
    }

    setCookieValue(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        const secure = window.location.protocol === 'https:' ? ';Secure' : '';
        document.cookie = `${name}=${value};expires=${date.toUTCString()};path=/;SameSite=Lax${secure}`;
    }

    deleteCookieValue(name) {
        const secure = window.location.protocol === 'https:' ? ';Secure' : '';
        document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax${secure}`;
    }

    getStoredCookie(name) {
        const prefix = `${name}=`;
        return document.cookie
            .split(';')
            .map((item) => item.trim())
            .find((item) => item.startsWith(prefix))
            ?.slice(prefix.length) || null;
    }
}

function ensureFooterContactRoleStyles() {
    if (document.getElementById('footer-contact-role-styles')) return;

    const style = document.createElement('style');
    style.id = 'footer-contact-role-styles';
    style.textContent = `
        .contact-person .contact-name {
            display: block;
            color: #ffffff;
            font-weight: 800;
            font-size: 1.05em;
            line-height: 1.25;
            margin-bottom: 8px;
            letter-spacing: 0.01em;
        }

        .contact-person .contact-role {
            display: block;
            color: rgba(226, 241, 255, 0.86);
            font-weight: 500;
            line-height: 1.5;
        }
    `;
    document.head.appendChild(style);
}

function setContactPersonMarkup(element, name, role) {
    element.innerHTML = '';

    const nameNode = document.createElement('span');
    nameNode.className = 'contact-name';
    nameNode.textContent = name;

    const roleNode = document.createElement('span');
    roleNode.className = 'contact-role';
    roleNode.textContent = role;

    element.appendChild(nameNode);
    element.appendChild(roleNode);
}

function updateFooterContactRoles() {
    ensureFooterContactRoleStyles();

    document.querySelectorAll('.contact-person').forEach((element) => {
        const text = element.textContent.trim().replace(/\s+/g, ' ');

        if (
            text === 'Иван Михайлович, заведующий референс-центром' ||
            text === 'Иван Михайлович, заведующий Референс-центром лабораторной службы МО, специалист проекта «Централизация лабораторной службы» МЗ МО'
        ) {
            setContactPersonMarkup(
                element,
                'Иван Михайлович',
                'заведующий Референс-центром лабораторной службы МО, специалист проекта «Централизация лабораторной службы» МЗ МО'
            );
        }

        if (
            text === 'Ирина Владимировна, куратор проекта' ||
            text === 'Ирина Владимировна, куратор референс-центра' ||
            text === 'Ирина Владимировна, руководитель проекта централизации лабораторной службы МО, главный внештатный специалист по лабораторной диагностике' ||
            text === 'Ирина Владимировна, руководитель проекта централизации лабораторной службы МО, главный внештатный специалист по клинической лабораторной диагностике'
        ) {
            setContactPersonMarkup(
                element,
                'Ирина Владимировна',
                'руководитель проекта централизации лабораторной службы МО, главный внештатный специалист по клинической лабораторной диагностике'
            );
        }
    });
}

function updateFooterInstitutionLabels() {
    const applyLabels = () => {
        document.querySelectorAll('.footer-block').forEach((block) => {
            const organization = block.querySelector('.footer-org-name')?.textContent.trim();
            const label = block.querySelector('.footer-label');
            if (!organization || !label) return;

            if (
                organization === 'Министерство здравоохранения Московской области' &&
                label.textContent !== 'Во взаимодействии с'
            ) {
                label.textContent = 'Во взаимодействии с';
            }

            if (
                organization === 'Центр внедрения изменений и обеспечения деятельности МЗ' &&
                label.textContent !== 'Организационное сопровождение'
            ) {
                label.textContent = 'Организационное сопровождение';
            }
        });
    };

    applyLabels();
    const observer = new MutationObserver(applyLabels);
    observer.observe(document.documentElement, { childList: true, subtree: true });
    window.setTimeout(() => observer.disconnect(), 5000);
}

document.addEventListener('DOMContentLoaded', () => {
    new CookieConsentManager();
    updateFooterContactRoles();
    updateFooterInstitutionLabels();
});
