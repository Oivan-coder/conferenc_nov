// cookies.js - Простое рабочее решение
class CookieConsent {
    constructor() {
        this.cookieName = 'cookie_consent_accepted';
        this.init();
    }

    init() {
        // Если согласие уже дано - запускаем метрику сразу
        if (this.getCookie(this.cookieName) === 'true') {
            this.loadYandexMetrika();
        } else {
            // Показываем баннер если согласия нет
            setTimeout(() => {
                this.createBanner();
            }, 2000);
        }
    }

    createBanner() {
        const bannerHTML = `
            <div id="cookieConsent" class="cookie-consent">
                <div class="cookie-content">
                    <div class="cookie-text">
                        <h4>🍪 Использование cookies</h4>
                        <p>Мы используем файлы cookie для улучшения работы сайта. Продолжая использование, вы соглашаетесь с нашей <a href="privacy.html" target="_blank">Политикой конфиденциальности</a>.</p>
                    </div>
                    <div class="cookie-buttons">
                        <button class="cookie-btn cookie-accept">Принять</button>
                        <button class="cookie-btn cookie-settings">Подробнее</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', bannerHTML);
        this.addStyles();
        this.attachEventHandlers();
    }

    addStyles() {
        const styles = `
            <style>
                .cookie-consent {
                    position: fixed;
                    bottom: 20px;
                    left: 20px;
                    right: 20px;
                    max-width: 500px;
                    background: rgba(23, 42, 70, 0.95);
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
                    border: 1px solid rgba(100, 255, 218, 0.3);
                    z-index: 10000;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    animation: cookieSlideUp 0.5s ease-out;
                    backdrop-filter: blur(10px);
                }

                @keyframes cookieSlideUp {
                    from {
                        opacity: 0;
                        transform: translateY(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .cookie-content {
                    padding: 20px;
                }

                .cookie-text h4 {
                    margin: 0 0 10px 0;
                    font-size: 16px;
                    font-weight: 600;
                    color: #64ffda;
                }

                .cookie-text p {
                    margin: 0 0 15px 0;
                    font-size: 14px;
                    line-height: 1.5;
                    color: #ccd6f6;
                }

                .cookie-text a {
                    color: #64ffda;
                    text-decoration: none;
                }

                .cookie-text a:hover {
                    text-decoration: underline;
                }

                .cookie-buttons {
                    display: flex;
                    gap: 10px;
                }

                .cookie-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    flex: 1;
                }

                .cookie-accept {
                    background: #64ffda;
                    color: #0a192f;
                }

                .cookie-accept:hover {
                    background: #45e6c4;
                    transform: translateY(-2px);
                }

                .cookie-settings {
                    background: transparent;
                    color: #64ffda;
                    border: 1px solid rgba(100, 255, 218, 0.3);
                }

                .cookie-settings:hover {
                    background: rgba(100, 255, 218, 0.1);
                }

                @media (max-width: 768px) {
                    .cookie-consent {
                        left: 10px;
                        right: 10px;
                        bottom: 10px;
                    }
                    
                    .cookie-buttons {
                        flex-direction: column;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    attachEventHandlers() {
        const acceptBtn = document.querySelector('.cookie-accept');
        const settingsBtn = document.querySelector('.cookie-settings');
        const banner = document.getElementById('cookieConsent');

        acceptBtn.addEventListener('click', () => {
            this.acceptCookies();
            this.hideBanner(banner);
        });

        settingsBtn.addEventListener('click', () => {
            window.open('privacy.html', '_blank');
        });
    }

    hideBanner(banner) {
        banner.style.opacity = '0';
        banner.style.transform = 'translateY(100px)';
        
        setTimeout(() => {
            banner.remove();
        }, 500);
    }

    acceptCookies() {
        // Сохраняем согласие на 1 год
        this.setCookie(this.cookieName, 'true', 365);
        
        // Запускаем Яндекс.Метрику
        this.loadYandexMetrika();
        
        // Показываем уведомление
        this.showToast('Спасибо! Cookies приняты.');
    }

    loadYandexMetrika() {
        // Проверяем не загружена ли уже метрика
        if (window.ym && window.ym.a) {
            return;
        }

        console.log('Загружаем Яндекс.Метрику...');

        
        // Создаем скрипт Яндекс.Метрики
        const script = document.createElement('script');
        script.src = 'https://mc.yandex.ru/metrika/tag.js';
        script.async = true;
        
        script.onload = () => {
            // Ждем чтобы ym функция точно была доступна
            setTimeout(() => {
                if (typeof window.ym === 'function') {
                    window.ym(105271987, 'init', {
                        clickmap: true,
                        trackLinks: true,
                        accurateTrackBounce: true,
                        webvisor: true
                    });
                    console.log('✅ Яндекс.Метрика успешно инициализирована');
                } else {
                    console.error('❌ Функция ym не доступна после загрузки скрипта');
                }
            }, 100);
        };

        script.onerror = () => {
            console.error('Ошибка загрузки Яндекс.Метрики');
        };

        document.head.appendChild(script);
    }

    showToast(message) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #64ffda;
            color: #0a192f;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 10001;
            animation: toastSlide 0.3s ease-out;
        `;
        
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    setCookie(name, value, days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        const expires = "expires=" + date.toUTCString();
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
    }

    getCookie(name) {
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
    new CookieConsent();
});
