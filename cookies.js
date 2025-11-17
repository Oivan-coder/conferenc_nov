// cookies.js - Простое рабочее решение
class CookieConsentManager {
    constructor() {
        this.consentCookieName = 'cookie_consent_accepted';
        this.metrikaLoaded = false;
        this.initialize();
    }

    initialize() {
        // Если согласие уже дано - запускаем метрику сразу
        if (this.getStoredCookie(this.consentCookieName) === 'true') {
            this.initializeYandexMetrika();
        } else {
            // Показываем баннер если согласия нет
            setTimeout(() => {
                this.displayConsentBanner();
            }, 2000);
        }
    }

    displayConsentBanner() {
        const bannerMarkup = `
            <div id="cookieConsentBanner" class="cookie-consent-banner">
                <div class="cookie-banner-content">
                    <div class="cookie-banner-text">
                        <h4>🍪 Использование cookies</h4>
                        <p>Мы используем файлы cookie для улучшения работы сайта. Продолжая использование, вы соглашаетесь с нашей <a href="privacy.html" target="_blank">Политикой конфиденциальности</a>.</p>
                    </div>
                    <div class="cookie-banner-buttons">
                        <button class="cookie-banner-btn cookie-accept-btn">Принять</button>
                        <button class="cookie-banner-btn cookie-info-btn">Подробнее</button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', bannerMarkup);
        this.injectBannerStyles();
        this.setupBannerEventHandlers();
    }

    injectBannerStyles() {
        const bannerStyles = `
            <style id="cookieConsentStyles">
                .cookie-consent-banner {
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
                    animation: cookieBannerSlideUp 0.5s ease-out;
                    backdrop-filter: blur(10px);
                }

                @keyframes cookieBannerSlideUp {
                    from {
                        opacity: 0;
                        transform: translateY(100px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .cookie-banner-content {
                    padding: 20px;
                }

                .cookie-banner-text h4 {
                    margin: 0 0 10px 0;
                    font-size: 16px;
                    font-weight: 600;
                    color: #64ffda;
                }

                .cookie-banner-text p {
                    margin: 0 0 15px 0;
                    font-size: 14px;
                    line-height: 1.5;
                    color: #ccd6f6;
                }

                .cookie-banner-text a {
                    color: #64ffda;
                    text-decoration: none;
                }

                .cookie-banner-text a:hover {
                    text-decoration: underline;
                }

                .cookie-banner-buttons {
                    display: flex;
                    gap: 10px;
                }

                .cookie-banner-btn {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    flex: 1;
                }

                .cookie-accept-btn {
                    background: #64ffda;
                    color: #0a192f;
                }

                .cookie-accept-btn:hover {
                    background: #45e6c4;
                    transform: translateY(-2px);
                }

                .cookie-info-btn {
                    background: transparent;
                    color: #64ffda;
                    border: 1px solid rgba(100, 255, 218, 0.3);
                }

                .cookie-info-btn:hover {
                    background: rgba(100, 255, 218, 0.1);
                }

                @media (max-width: 768px) {
                    .cookie-consent-banner {
                        left: 10px;
                        right: 10px;
                        bottom: 10px;
                    }
                    
                    .cookie-banner-buttons {
                        flex-direction: column;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', bannerStyles);
    }

    setupBannerEventHandlers() {
        const acceptButton = document.querySelector('.cookie-accept-btn');
        const infoButton = document.querySelector('.cookie-info-btn');
        const bannerElement = document.getElementById('cookieConsentBanner');

        if (acceptButton) {
            acceptButton.addEventListener('click', () => {
                this.processCookieConsent();
                this.hideConsentBanner(bannerElement);
            });
        }

        if (infoButton) {
            infoButton.addEventListener('click', () => {
                window.open('privacy.html', '_blank');
            });
        }
    }

    hideConsentBanner(bannerElement) {
        if (!bannerElement) return;
        
        bannerElement.style.opacity = '0';
        bannerElement.style.transform = 'translateY(100px)';
        
        setTimeout(() => {
            if (bannerElement.parentNode) {
                bannerElement.remove();
            }
        }, 500);
    }

    processCookieConsent() {
        // Сохраняем согласие на 1 год
        this.setCookieValue(this.consentCookieName, 'true', 365);
        
        // Запускаем Яндекс.Метрику
        this.initializeYandexMetrika();
        
        // Показываем уведомление
        this.displayConsentToast('Спасибо! Cookies приняты.');
    }

    initializeYandexMetrika() {
        // Проверяем не загружена ли уже метрика
        if (window.ym && window.ym.a) {
            console.log('✅ Яндекс.Метрика уже загружена');
            return;
        }

        if (this.metrikaLoaded) {
            console.log('✅ Яндекс.Метрика уже в процессе загрузки');
            return;
        }

        console.log('Загружаем Яндекс.Метрику...');
        this.metrikaLoaded = true;

        // Создаем скрипт Яндекс.Метрики
        const metrikaScriptElement = document.createElement('script');
        metrikaScriptElement.src = 'https://mc.yandex.ru/metrika/tag.js';
        metrikaScriptElement.async = true;
        
        // Создаем очередь для вызовов ym
        window.ymQueue = window.ymQueue || [];
        
        metrikaScriptElement.onload = () => {
            console.log('✅ Скрипт Яндекс.Метрики загружен');
            
            // Пробуем инициализировать несколько раз с интервалами
            this.tryInitializeMetrika(0);
        };

        metrikaScriptElement.onerror = () => {
            console.error('❌ Ошибка загрузки Яндекс.Метрики');
            this.metrikaLoaded = false;
        };

        document.head.appendChild(metrikaScriptElement);
    }

    tryInitializeMetrika(attempt) {
        const maxAttempts = 10;
        
        if (typeof window.ym === 'function') {
            console.log('✅ Функция ym доступна, инициализируем метрику');
            
            window.ym(105271987, 'init', {
                clickmap: true,
                trackLinks: true,
                accurateTrackBounce: true,
                webvisor: true
            });
            
            // Обрабатываем очередь вызовов
            if (window.ymQueue && window.ymQueue.length > 0) {
                console.log(`📋 Обрабатываем очередь из ${window.ymQueue.length} вызовов`);
                window.ymQueue.forEach(args => {
                    try {
                        window.ym.apply(null, args);
                    } catch (e) {
                        console.error('Ошибка при обработке очереди:', e);
                    }
                });
                window.ymQueue = [];
            }
            
            console.log('✅ Яндекс.Метрика успешно инициализирована');
        } else if (attempt < maxAttempts) {
            console.log(`🔄 Попытка ${attempt + 1}/${maxAttempts}: функция ym еще не доступна`);
            setTimeout(() => {
                this.tryInitializeMetrika(attempt + 1);
            }, 200);
        } else {
            console.error('❌ Не удалось инициализировать Яндекс.Метрику после всех попыток');
            this.metrikaLoaded = false;
        }
    }

    displayConsentToast(message) {
        const toastElement = document.createElement('div');
        toastElement.style.cssText = `
            position: fixed;
            bottom: 100px;
            right: 20px;
            background: #64ffda;
            color: #0a192f;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            z-index: 10001;
        `;
        
        toastElement.textContent = message;
        document.body.appendChild(toastElement);

        setTimeout(() => {
            if (toastElement.parentNode) {
                toastElement.remove();
            }
        }, 3000);
    }

    setCookieValue(cookieName, cookieValue, expirationDays) {
        const expirationDate = new Date();
        expirationDate.setTime(expirationDate.getTime() + (expirationDays * 24 * 60 * 60 * 1000));
        const expiresAttribute = "expires=" + expirationDate.toUTCString();
        document.cookie = cookieName + "=" + cookieValue + ";" + expiresAttribute + ";path=/;SameSite=Lax";
    }

    getStoredCookie(cookieName) {
        const nameWithEquals = cookieName + "=";
        const cookieArray = document.cookie.split(';');
        for (let i = 0; i < cookieArray.length; i++) {
            let cookieItem = cookieArray[i];
            while (cookieItem.charAt(0) === ' ') {
                cookieItem = cookieItem.substring(1, cookieItem.length);
            }
            if (cookieItem.indexOf(nameWithEquals) === 0) {
                return cookieItem.substring(nameWithEquals.length, cookieItem.length);
            }
        }
        return null;
    }
}

// Запускаем при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    new CookieConsentManager();
});
