// cookies.js - РАБОЧАЯ версия с красивыми стилями
class CookieConsentManager {
    constructor() {
        this.consentCookieName = 'cookie_consent_accepted';
        this.bannerId = 'cookieConsentBanner';
        this.initialize();
    }

    initialize() {
        if (this.getStoredCookie(this.consentCookieName) === 'true') {
            this.loadYandexMetrika();
        } else {
            setTimeout(() => {
                this.showBanner();
            }, 2000);
        }
    }

    showBanner() {
        if (document.getElementById(this.bannerId)) {
            return;
        }

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
        description.appendChild(document.createTextNode('Мы используем файлы cookie для улучшения работы сайта. Продолжая использование, вы соглашаетесь с нашей '));

        const privacyLink = document.createElement('a');
        privacyLink.href = 'privacy.html';
        privacyLink.target = '_blank';
        privacyLink.rel = 'noopener noreferrer';
        privacyLink.textContent = 'Политикой конфиденциальности';

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

        const infoBtn = document.createElement('button');
        infoBtn.className = 'cookie-banner-btn cookie-info-btn';
        infoBtn.type = 'button';
        infoBtn.textContent = 'Подробнее';

        buttons.appendChild(acceptBtn);
        buttons.appendChild(infoBtn);
        content.appendChild(textBlock);
        content.appendChild(buttons);
        banner.appendChild(content);
        document.body.appendChild(banner);

        this.setupEventHandlers(banner);
    }

    setupEventHandlers(banner) {
        const acceptBtn = banner?.querySelector('.cookie-accept-btn');
        const infoBtn = banner?.querySelector('.cookie-info-btn');
        if (!acceptBtn || !infoBtn || !banner) {
            return;
        }

        acceptBtn.addEventListener('click', () => {
            this.acceptCookies();
            this.hideBanner(banner);
        });

        infoBtn.addEventListener('click', () => {
            window.open('privacy.html', '_blank');
        });
    }

    hideBanner(banner) {
        if (!banner) return;
        
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(100px)';
        
        setTimeout(() => {
            if (banner.parentNode) {
                banner.remove();
            }
        }, 500);
    }

    acceptCookies() {
        // Сохраняем согласие на 1 год
        this.setCookieValue(this.consentCookieName, 'true', 365);
        
        // Запускаем Яндекс.Метрику
        this.loadYandexMetrika();
        
        // Показываем уведомление
        this.showToast('Спасибо! Cookies приняты.');
    }

    loadYandexMetrika() {
        // Проверяем, не загружена ли уже Метрика
        if (window.ym || document.querySelector('script[data-analytics="yandex-metrika"]')) {
            return;
        }
        
        // Безопасная загрузка Яндекс.Метрики через внешний скрипт
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = 'https://mc.yandex.ru/metrika/tag.js';
        script.setAttribute('data-analytics', 'yandex-metrika');
        
        script.onload = function() {
            // Инициализация после загрузки скрипта
            if (window.ym) {
                window.ym(105271987, 'init', {
                    clickmap: true,
                    trackLinks: true,
                    accurateTrackBounce: true,
                    webvisor: true
                });
            }
        };
        
        // Не создаем inline-fallback: это ухудшает CSP-совместимость и
        // дублирует внешнюю загрузку.
        script.onerror = function() {
            if (window.debugLog) {
                window.debugLog('Не удалось загрузить Яндекс.Метрику');
            }
        };
        
        document.head.appendChild(script);
    }

    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'cookie-toast';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }

    setCookieValue(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        const secure = window.location.protocol === 'https:' ? ';Secure' : '';
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax" + secure;
    }

    getStoredCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
}

// Запускаем при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    new CookieConsentManager();
});
