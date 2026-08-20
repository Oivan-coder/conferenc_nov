(function () {
    'use strict';

    function updateEventState() {
        const root = document.querySelector('.conference-2026-page');
        if (!root) return;

        const eventStart = new Date('2026-10-07T09:30:00+03:00');
        const now = new Date();
        root.dataset.eventState = now < eventStart ? 'upcoming' : 'started';
    }

    function markAgendaOnScroll() {
        const items = Array.from(document.querySelectorAll('.c26-agenda__item'));
        if (!items.length || !('IntersectionObserver' in window)) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.12 });

        items.forEach((item) => observer.observe(item));
    }

    function initAgendaDisclosure() {
        const disclosure = document.querySelector('[data-agenda-disclosure]');
        const label = disclosure?.querySelector('[data-agenda-toggle-label]');
        const agenda = disclosure?.querySelector('.c26-agenda');
        if (!disclosure || !label || !agenda) return;

        const syncDisclosure = () => {
            const isOpen = disclosure.open;
            label.textContent = isOpen ? 'Свернуть расписание' : 'Показать полное расписание';

            // Legacy .c26-agenda styles force display:grid and override the browser's
            // native closed <details> state. Inline display fixes that reliably.
            if (isOpen) {
                agenda.style.removeProperty('display');
                agenda.querySelectorAll('.c26-agenda__item').forEach((item) => {
                    item.classList.add('is-visible');
                });
            } else {
                agenda.style.display = 'none';
            }
        };

        disclosure.addEventListener('toggle', syncDisclosure);
        syncDisclosure();
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateEventState();
        markAgendaOnScroll();
        initAgendaDisclosure();
    });
})();
