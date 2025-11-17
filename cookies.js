// cookies.js - РАБОЧАЯ версия с красивыми стилями
class CookieConsentManager {
    constructor() {
        this.consentCookieName = 'cookie_consent_accepted';
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
        const bannerHTML = `
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

        document.body.insertAdjacentHTML('beforeend', bannerHTML);
        this.addStyles();
        this.setupEventHandlers();
    }

    addStyles() {
        const styles = `
            <style>
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

                .cookie-toast {
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
                }

                @keyframes toastSlide {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    setupEventHandlers() {
        const acceptBtn = document.querySelector('.cookie-accept-btn');
        const infoBtn = document.querySelector('.cookie-info-btn');
        const banner = document.getElementById('cookieConsentBanner');

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
        console.log('🚀 Загружаем Яндекс.Метрику...');
        
        // Стандартный код Яндекс.Метрики
        const metrikaCode = `
            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            
            ym(105271987, "init", {
                clickmap:true,
                trackLinks:true,
                accurateTrackBounce:true,
                webvisor:true
            });
        `;
        
        const script = document.createElement('script');
        script.innerHTML = metrikaCode;
        document.head.appendChild(script);
        
        console.log('✅ Яндекс.Метрика загружена');
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
        document.cookie = name + "=" + value + ";" + expires + ";path=/;SameSite=Lax";
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
