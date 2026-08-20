(function () {
    'use strict';

    const video = document.querySelector('[data-home-video]');
    const source = video?.querySelector('source[data-src]');

    if (!video || !source) return;

    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const saveData = navigator.connection?.saveData === true;

    if (prefersReducedMotion || saveData) return;

    let started = false;

    const startVideo = () => {
        if (started) return;
        started = true;

        source.src = source.dataset.src;
        video.load();

        video.addEventListener('canplay', () => {
            video.classList.add('is-ready');
            video.play().catch(() => {
                video.classList.remove('is-ready');
            });
        }, { once: true });

        video.addEventListener('error', () => {
            video.classList.add('is-fallback');
        }, { once: true });
    };

    const scheduleVideo = () => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(startVideo, { timeout: 1200 });
        } else {
            window.setTimeout(startVideo, 450);
        }
    };

    if (document.readyState === 'complete') {
        scheduleVideo();
    } else {
        window.addEventListener('load', scheduleVideo, { once: true });
    }
})();
