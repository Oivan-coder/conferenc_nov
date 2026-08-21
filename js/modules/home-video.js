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
    const highQualitySrc = source.dataset.srcHq || optimizedSrc;
    const downlink = Number(connection?.downlink || 0);
    const hasComfortableBandwidth = !downlink || downlink >= 4;
    const useHighQuality = highQualitySrc !== optimizedSrc &&
        !slowConnection &&
        hasComfortableBandwidth &&
        window.innerWidth >= 1024;

    let currentSource = useHighQuality ? highQualitySrc : optimizedSrc;
    let fallbackTimer = null;
    let hasStartedPlaying = false;
    let fallbackUsed = false;

    const markReady = () => {
        hasStartedPlaying = true;
        video.classList.add('is-ready');
        video.classList.remove('is-fallback');
        if (fallbackTimer) {
            window.clearTimeout(fallbackTimer);
            fallbackTimer = null;
        }
    };

    const fallbackToOptimized = () => {
        if (currentSource === optimizedSrc || fallbackUsed) return;
        fallbackUsed = true;
        loadSource(optimizedSrc, 'optimized-fallback');
    };

    const tryPlay = () => {
        video.muted = true;
        video.defaultMuted = true;
        const playPromise = video.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.catch((error) => {
                // Некоторые браузеры откладывают autoplay до первого взаимодействия.
                video.classList.remove('is-ready');
                if (error?.name === 'NotSupportedError') {
                    fallbackToOptimized();
                }
            });
        }
    };

    const loadSource = (src, quality) => {
        currentSource = src;
        source.src = src;
        video.dataset.quality = quality;
        video.preload = 'auto';
        video.autoplay = true;
        video.muted = true;
        video.defaultMuted = true;
        video.load();

        if (fallbackTimer) {
            window.clearTimeout(fallbackTimer);
            fallbackTimer = null;
        }

        if (src !== optimizedSrc) {
            fallbackTimer = window.setTimeout(() => {
                if (!hasStartedPlaying && video.readyState < 2) {
                    fallbackToOptimized();
                }
            }, 6500);
        }
    };

    // Начинаем сетевую загрузку сразу после первого отображения постера и текста,
    // чтобы видео не конкурировало с критическими ресурсами первого экрана.
    window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
            loadSource(currentSource, useHighQuality ? 'high' : 'optimized');
        });
    });

    // loadeddata обычно наступает раньше canplay и позволяет убрать постер быстрее.
    video.addEventListener('loadeddata', tryPlay);
    video.addEventListener('canplay', tryPlay);
    video.addEventListener('playing', markReady);
    video.addEventListener('error', () => {
        if (currentSource !== optimizedSrc) {
            fallbackToOptimized();
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
