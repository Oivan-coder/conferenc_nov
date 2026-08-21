(function () {
    'use strict';

    const video = document.querySelector('[data-home-video]');
    const source = video?.querySelector('source[data-src]');

    if (!video || !source) return;

    const connection = navigator.connection;
    const prefersReducedMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const saveData = connection?.saveData === true;
    const slowConnection = ['slow-2g', '2g', '3g'].includes(connection?.effectiveType || '');

    // Уважаем системную настройку уменьшения движения и режим экономии трафика.
    if (prefersReducedMotion || saveData) return;

    const optimizedSrc = source.dataset.src;
    const highQualitySrc = 'videos/hero-video.mp4';
    const useHighQuality = !slowConnection && (
        window.innerWidth >= 900 ||
        (window.devicePixelRatio > 1.25 && window.innerWidth >= 720)
    );

    let currentSource = useHighQuality ? highQualitySrc : optimizedSrc;
    let fallbackTimer = null;
    let hasStartedPlaying = false;

    const markReady = () => {
        hasStartedPlaying = true;
        video.classList.add('is-ready');
        video.classList.remove('is-fallback');
        if (fallbackTimer) {
            window.clearTimeout(fallbackTimer);
            fallbackTimer = null;
        }
    };

    const tryPlay = () => {
        const playPromise = video.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(markReady).catch(() => {
                // Некоторые браузеры откладывают autoplay до первого взаимодействия.
                video.classList.remove('is-ready');
            });
        }
    };

    const loadSource = (src, quality) => {
        currentSource = src;
        source.src = src;
        video.dataset.quality = quality;
        video.preload = 'auto';
        video.autoplay = true;
        video.load();

        // loadeddata обычно наступает раньше canplay и позволяет убрать постер быстрее.
        video.addEventListener('loadeddata', tryPlay, { once: true });
        video.addEventListener('canplay', tryPlay, { once: true });
    };

    // Не ждём window.load/requestIdleCallback: hero-видео является содержимым первого экрана.
    loadSource(currentSource, useHighQuality ? 'high' : 'optimized');

    // Если тяжёлый HQ-файл действительно не успел начать воспроизводиться,
    // через несколько секунд переходим на лёгкую версию вместо вечного постера.
    if (useHighQuality) {
        fallbackTimer = window.setTimeout(() => {
            if (!hasStartedPlaying && video.readyState < 3) {
                loadSource(optimizedSrc, 'optimized-fallback');
            }
        }, 5500);
    }

    video.addEventListener('playing', markReady);
    video.addEventListener('error', () => {
        if (currentSource !== optimizedSrc) {
            loadSource(optimizedSrc, 'optimized-fallback');
        } else {
            video.classList.add('is-fallback');
        }
    });

    // Возвращаем воспроизведение после возврата на вкладку / из BFCache.
    const resume = () => {
        if (!document.hidden && video.paused && video.readyState >= 2) {
            tryPlay();
        }
    };

    document.addEventListener('visibilitychange', resume);
    window.addEventListener('pageshow', resume);

    // Резерв для браузеров с более жёсткой политикой autoplay.
    window.addEventListener('pointerdown', resume, { once: true, passive: true });
    window.addEventListener('touchstart', resume, { once: true, passive: true });
})();
