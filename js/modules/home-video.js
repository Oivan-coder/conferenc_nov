(function () {
    'use strict';

    const video = document.querySelector('[data-home-video]');
    const source = video?.querySelector('source[data-src]');

    if (!video || !source) return;

    const connection = navigator.connection;
    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const saveData = connection?.saveData === true;
    const slowConnection = ['slow-2g', '2g', '3g'].includes(connection?.effectiveType || '');

    if (prefersReducedMotion || saveData) return;

    let started = false;

    const startVideo = () => {
        if (started) return;
        started = true;

        const useHighQuality = window.innerWidth >= 1180 && !slowConnection;
        source.src = useHighQuality ? 'videos/hero-video.mp4' : source.dataset.src;
        video.dataset.quality = useHighQuality ? 'high' : 'optimized';
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
