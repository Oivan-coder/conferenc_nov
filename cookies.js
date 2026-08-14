// cookies.js — управление cookies и общие корректировки интерфейса
class CookieConsentManager {
    constructor() {
        this.consentCookieName = 'cookie_consent_accepted';
        this.bannerId = 'cookieConsentBanner';
        this.initialize();
    }

    initialize() {
        if (this.getStoredCookie(this.consentCookieName) === 'true') {
            this.loadYandexMetrika();
            return;
        }

        window.setTimeout(() => this.showBanner(), 2000);
    }

    showBanner() {
        if (document.getElementById(this.bannerId)) return;

        const banner = document.createElement('div');
        banner.id = this.bannerId;
        banner.className = 'cookie-consent-banner';

        const content = document.createElement('div');
        content.className = 'cookie-banner-content';

        const textBlock = document.createElement('div');
        textBlock.className = 'cookie-banner-text';

        const title = document.createElement('h4');
        title.textContent = 'Использование cookies';

        const description = document.createElement('p');
        description.appendChild(document.createTextNode('Мы используем аналитические cookies только после вашего согласия. Подробнее — в '));

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
        acceptBtn.textContent = 'Принять';

        const declineBtn = document.createElement('button');
        declineBtn.className = 'cookie-banner-btn cookie-info-btn';
        declineBtn.type = 'button';
        declineBtn.textContent = 'Отклонить';

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
            this.setCookieValue(this.consentCookieName, 'false', 365);
            this.hideBanner(banner);
            this.showToast('Аналитические cookies отключены.');
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
        this.setCookieValue(this.consentCookieName, 'true', 365);
        this.loadYandexMetrika();
        this.showToast('Аналитические cookies включены.');
    }

    loadYandexMetrika() {
        if (window.ym || document.querySelector('script[data-analytics="yandex-metrika"]')) return;

        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = 'https://mc.yandex.ru/metrika/tag.js';
        script.setAttribute('data-analytics', 'yandex-metrika');

        script.onload = () => {
            if (window.ym) {
                window.ym(105271987, 'init', {
                    clickmap: true,
                    trackLinks: true,
                    accurateTrackBounce: true,
                    webvisor: false
                });
            }
        };

        script.onerror = () => {
            if (window.debugLog) window.debugLog('Не удалось загрузить Яндекс.Метрику');
        };

        document.head.appendChild(script);
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
