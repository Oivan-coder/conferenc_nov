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

    function initMobileMenuLayerFix() {
        if (document.getElementById('c26-mobile-menu-layer-fix')) return;

        const style = document.createElement('style');
        style.id = 'c26-mobile-menu-layer-fix';
        style.textContent = `
            @media (max-width: 968px) {
                .conference-2026-page .skip-link {
                    display: none !important;
                }

                .conference-2026-page .c26-hero {
                    isolation: auto;
                }

                .conference-2026-page .c26-hero__media {
                    z-index: 0;
                }

                .conference-2026-page .c26-hero__overlay {
                    z-index: 1;
                }

                .conference-2026-page .c26-hero__content {
                    z-index: 2;
                }

                .conference-2026-page .c26-hero .photo-header {
                    z-index: 1000;
                }
            }
        `;
        document.head.appendChild(style);
    }

    document.addEventListener('DOMContentLoaded', () => {
        initMobileMenuLayerFix();
        updateEventState();
        markAgendaOnScroll();
        initAgendaDisclosure();
    });
})();
