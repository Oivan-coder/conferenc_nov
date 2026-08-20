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
        if (!disclosure || !label) return;

        const syncLabel = () => {
            label.textContent = disclosure.open ? 'Свернуть расписание' : 'Показать полное расписание';
        };

        disclosure.addEventListener('toggle', () => {
            syncLabel();
            if (disclosure.open) {
                disclosure.querySelectorAll('.c26-agenda__item').forEach((item) => {
                    item.classList.add('is-visible');
                });
            }
        });

        syncLabel();
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateEventState();
        markAgendaOnScroll();
        initAgendaDisclosure();
    });
})();
