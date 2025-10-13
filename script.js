<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- 🔥 SEO МЕТА-ТЕГИ -->
    <title>Регистрация - LAB Evolution 2025</title>
    <meta name="description" content="Регистрация на конференцию LAB Evolution 2025. Заполните форму для участия в мероприятии по лабораторной диагностике.">
    <meta name="keywords" content="регистрация на конференцию, LAB Evolution 2025, заявка на участие, медицинская конференция">
    <link rel="canonical" href="https://rclsmo.ru/registration.html" />
    
    <!-- Open Graph для соцсетей -->
    <meta property="og:title" content="Регистрация - LAB Evolution 2025">
    <meta property="og:description" content="Зарегистрируйтесь на конференцию по лабораторной диагностике LAB Evolution 2025">
    <meta property="og:url" content="https://rclsmo.ru/registration.html">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚕️</text></svg>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="styles.css">
</head>
<body class="registration-page">
    <!-- Хедер из основного стиля -->
    <header class="photo-header">
        <div class="container">
            <div class="header-content">
                <a href="index.html" class="logo">
                    <div class="logo-icon">⚕️</div>
                    <div>LAB Evolution 2025</div>
                </a>
                
                <button class="burger-menu" id="burgerMenu" aria-label="Открыть меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-links">
                        <li><a href="about.html">О нас</a></li>
                        <li class="menu-dropdown">
                            <a href="index.html#about" class="dropdown-toggle">Конференция</a>
                            <ul class="dropdown-menu">
                                <li><a href="index.html#about">О событии</a></li>
                                <li><a href="index.html#program">Программа</a></li>
                                <li><a href="index.html#speakers">Эксперты</a></li>
                                <li><a href="index.html#partners">Технологии</a></li>
                            </ul>
                        </li>
                        <li><a href="index.html#location">Место</a></li>
                        <li><a href="index.html#contacts">Контакты</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="registration-main">
        <div class="container">
            <div class="registration-header">
                <div class="logo-section">
                    <div class="logo-icon">⚕️</div>
                    <h1>LAB Evolution 2025</h1>
                </div>
                
                <h2 class="section-title">Регистрация на конференцию</h2>
                <p class="section-content">Заполните форму для участия в мероприятии</p>
            </div>
            
            <div class="registration-content">
                <form id="visible-form" class="registration-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Электронная почта *</label>
                            <input type="email" id="email" name="email" placeholder="example@mail.ru" required>
                        </div>

                        <div class="form-group">
                            <label for="name">ФИО *</label>
                            <input type="text" id="name" name="name" placeholder="Введите ваше полное имя" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="position">Должность *</label>
                            <div class="custom-select">
                                <div class="select-selected">
                                    <span class="select-text">-- Выберите должность --</span>
                                    <span class="select-arrow">▼</span>
                                </div>
                                <div class="select-items">
                                    <div data-value="Главный врач">Главный врач</div>
                                    <div data-value="Заведующий лабораторией">Заведующий лабораторией</div>
                                    <div data-value="Врач-лаборант">Врач-лаборант</div>
                                    <div data-value="Медицинский технолог">Медицинский технолог</div>
                                    <div data-value="Руководитель ЛПУ">Руководитель ЛПУ</div>
                                    <div data-value="Специалист по качеству">Специалист по качеству</div>
                                    <div data-value="Гость">Гость</div>
                                    <div data-value="Другое">Другое</div>
                                </div>
                                <input type="hidden" id="position" name="position" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="organization">Организация *</label>
                            <input type="text" id="organization" name="organization" placeholder="Название медицинского учреждения" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" placeholder="+7 (XXX) XXX-XX-XX" required>
                        </div>

                        <div class="form-group full-width">
                            <div class="checkbox-group">
                                <input type="checkbox" id="agreement" required>
                                <label for="agreement">
                                    Даю согласие на обработку персональных данных в соответствии с Федеральным законом № 152-ФЗ
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="cta-button primary submit-btn">Зарегистрироваться</button>
                </form>

                <div id="success-message" class="success-message">
                    <div class="success-icon">✅</div>
                    <h3>Спасибо за регистрацию!</h3>
                    <p>Ваша заявка принята. На указанную почту будет отправлено подтверждение участия.</p>
                    <p><strong>Ждем вас на конференции LAB Evolution 2025!</strong></p>
                    <a href="index.html" class="cta-button primary back-home-btn">Вернуться на главную</a>
                </div>
            </div>

            <div class="back-link">
                <a href="index.html">← Вернуться на главную страницу</a>
            </div>
        </div>
    </main>

    <!-- Кнопка "Наверх" -->
    <button id="backToTop" class="back-to-top">↑</button>

    <script>
        // ==================== ОСНОВНАЯ ИНИЦИАЛИЗАЦИЯ ====================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Инициализация страницы регистрации');
            
            initBurgerMenu();
            initBackToTop();
            initCustomSelects();
            initRegistrationForm();
            
            // Скрываем успешное сообщение по умолчанию
            document.getElementById('success-message').style.display = 'none';
        });

        // ==================== БУРГЕР-МЕНЮ ====================
        function initBurgerMenu() {
            const burgerMenu = document.getElementById('burgerMenu');
            const navMenu = document.getElementById('navMenu');
            const body = document.body;
            
            if (!burgerMenu || !navMenu) return;
            
            burgerMenu.addEventListener('click', function() {
                burgerMenu.classList.toggle('active');
                navMenu.classList.toggle('active');
                body.classList.toggle('menu-open');
                
                const isExpanded = burgerMenu.classList.contains('active');
                burgerMenu.setAttribute('aria-expanded', isExpanded);
                navMenu.setAttribute('aria-hidden', !isExpanded);
            });
            
            const navLinks = document.querySelectorAll('.nav-links a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    burgerMenu.classList.remove('active');
                    navMenu.classList.remove('active');
                    body.classList.remove('menu-open');
                    burgerMenu.setAttribute('aria-expanded', 'false');
                    navMenu.setAttribute('aria-hidden', 'true');
                });
            });
        }

        // ==================== КНОПКА "НАВЕРХ" ====================
        function initBackToTop() {
            const backToTop = document.getElementById('backToTop');
            
            if (!backToTop) return;
            
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    backToTop.classList.add('visible');
                } else {
                    backToTop.classList.remove('visible');
                }
            });
            
            backToTop.addEventListener('click', (e) => {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        // ==================== ВЫПАДАЮЩИЙ СПИСОК ДОЛЖНОСТЕЙ ====================
        function initCustomSelects() {
            const customSelects = document.querySelectorAll('.custom-select');
            
            customSelects.forEach(select => {
                const selected = select.querySelector('.select-selected');
                const items = select.querySelector('.select-items');
                const hiddenInput = select.querySelector('input[type="hidden"]');
                const options = items.querySelectorAll('div');
                
                // Обработчик клика по выбранному элементу
                selected.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Закрываем другие открытые select
                    document.querySelectorAll('.select-items.active').forEach(other => {
                        if (other !== items) other.classList.remove('active');
                    });
                    document.querySelectorAll('.select-selected.active').forEach(other => {
                        if (other !== selected) other.classList.remove('active');
                    });
                    
                    // Переключаем текущий
                    items.classList.toggle('active');
                    selected.classList.toggle('active');
                });
                
                // Обработчик выбора опции
                options.forEach(option => {
                    option.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        
                        // Обновляем отображаемый текст
                        selected.querySelector('.select-text').textContent = text;
                        hiddenInput.value = value;
                        
                        // Помечаем выбранную опцию
                        options.forEach(opt => opt.classList.remove('selected'));
                        this.classList.add('selected');
                        
                        // Закрываем список
                        items.classList.remove('active');
                        selected.classList.remove('active');
                        
                        // Валидация
                        if (value) {
                            hiddenInput.setCustomValidity('');
                        }
                    });
                });
                
                // Закрытие при клике вне select
                document.addEventListener('click', function() {
                    items.classList.remove('active');
                    selected.classList.remove('active');
                });
                
                // Предотвращаем закрытие при клике внутри select
                select.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            });
            
            console.log('✅ Кастомные select инициализированы');
        }

        // ==================== ФОРМА РЕГИСТРАЦИИ ====================
        function initRegistrationForm() {
            const form = document.getElementById('visible-form');
            const submittedData = new Set();

            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Проверка согласия
                if (!document.getElementById('agreement').checked) {
                    showNotification('❌ Необходимо дать согласие на обработку персональных данных', 'error');
                    return;
                }

                // Проверка выбора должности
                const positionInput = document.getElementById('position');
                if (!positionInput.value) {
                    showNotification('❌ Пожалуйста, выберите должность', 'error');
                    return;
                }
                
                const email = document.getElementById('email').value;
                const phone = document.getElementById('phone').value;
                
                // Быстрая локальная проверка
                if (submittedData.has(email + phone)) {
                    showNotification('❌ Вы уже отправляли эту форму', 'error');
                    return;
                }
                
                // Блокируем кнопку
                const submitBtn = this.querySelector('.submit-btn');
                submitBtn.textContent = 'Отправляем...';
                submitBtn.disabled = true;
                
                try {
                    // 🔥 ОТПРАВКА В GOOGLE FORMS
                    await submitToGoogleForms();
                    submittedData.add(email + phone);
                    
                    // ПОКАЗЫВАЕМ УСПЕХ
                    document.getElementById('visible-form').style.display = 'none';
                    document.getElementById('success-message').style.display = 'block';
                    showNotification('✅ Регистрация прошла успешно!', 'success');
                    
                } catch (error) {
                    console.error('Ошибка:', error);
                    showNotification('❌ Ошибка отправки. Попробуйте еще раз.', 'error');
                    submitBtn.textContent = 'Зарегистрироваться';
                    submitBtn.disabled = false;
                }
            });
        }

        // 🔥 ОТПРАВКА В GOOGLE FORMS
        function submitToGoogleForms() {
            return new Promise((resolve) => {
                const iframe = document.createElement('iframe');
                iframe.name = 'google-form-' + Date.now();
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
                
                const tempForm = document.createElement('form');
                tempForm.action = 'https://docs.google.com/forms/u/0/d/e/1FAIpQLSc7urE88w0Y_zM7EBGbhQN8Uw87Uw6eFRBm2d9GvqJT7Q4S0A/formResponse';
                tempForm.method = 'POST';
                tempForm.target = iframe.name;
                tempForm.style.display = 'none';
                
                const fields = [
                    { name: 'entry.1277724311', value: document.getElementById('email').value },
                    { name: 'entry.74922399', value: document.getElementById('name').value },
                    { name: 'entry.827511450', value: document.getElementById('position').value },
                    { name: 'entry.1066367767', value: document.getElementById('organization').value },
                    { name: 'entry.362036752', value: document.getElementById('phone').value }
                ];
                
                fields.forEach(field => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = field.name;
                    input.value = field.value;
                    tempForm.appendChild(input);
                });
                
                document.body.appendChild(tempForm);
                tempForm.submit();
                
                setTimeout(() => resolve(true), 3000);
            });
        }

        // ==================== УВЕДОМЛЕНИЯ ====================
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" aria-label="Закрыть уведомление">×</button>
                </div>
            `;
            
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
                min-width: 300px;
            `;
            
            document.body.appendChild(notification);
            
            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.addEventListener('click', () => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // ==================== CSS АНИМАЦИИ ====================
        const style = document.createElement('style');
        style.textContent = `
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
                opacity: 0.8;
                transition: opacity 0.3s;
            }
            
            .notification-close:hover { 
                opacity: 1; 
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            
            .notification-message { 
                flex: 1; 
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
