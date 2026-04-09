// js/url-cleaner.js
// Сайт использует стабильные `.html`-маршруты.
// Этот модуль больше не переписывает URL, чтобы не создавать
// несуществующие clean-path адреса при прямом открытии страниц.

(function() {
    'use strict';

    document.documentElement.dataset.urlMode = 'html';
})();
