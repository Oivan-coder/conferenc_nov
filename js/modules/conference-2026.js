(function () {
    'use strict';

    const VENUE_ADDRESS = 'б-р Строителей, 1, Красногорск, Московская область, 143407';
    const VENUE_ROUTE_URL = 'https://yandex.ru/maps/?rtext=~55.816085%2C37.380812&rtt=auto';

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

    function copyTextFallback(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {
            copied = false;
        }

        textarea.remove();
        return copied;
    }

    async function copyVenueAddress() {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(VENUE_ADDRESS);
                return true;
            } catch (error) {
                // Older browsers or restrictive clipboard permissions fall back below.
            }
        }

        return copyTextFallback(VENUE_ADDRESS);
    }

    function initLocationActions() {
        const routeButton = document.getElementById('openNavigationMap');
        const copyButton = document.getElementById('copyMapAddress');

        if (routeButton) {
            routeButton.addEventListener('click', () => {
                const routeWindow = window.open(VENUE_ROUTE_URL, '_blank');
                if (routeWindow) {
                    routeWindow.opener = null;
                } else {
                    window.location.href = VENUE_ROUTE_URL;
                }
            });
        }

        if (copyButton) {
            const originalLabel = copyButton.textContent;
            let resetTimer = null;

            copyButton.addEventListener('click', async () => {
                const copied = await copyVenueAddress();

                if (!copied) {
                    window.prompt('Скопируйте адрес:', VENUE_ADDRESS);
                    return;
                }

                copyButton.textContent = 'Адрес скопирован';
                copyButton.setAttribute('aria-live', 'polite');

                window.clearTimeout(resetTimer);
                resetTimer = window.setTimeout(() => {
                    copyButton.textContent = originalLabel;
                }, 1800);
            });
        }
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
        initLocationActions();
    });
})();
