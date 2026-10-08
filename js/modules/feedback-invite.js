(() => {
    'use strict';
    const dialog = document.getElementById('feedbackInvite');
    if (!dialog) return;
    const storageKey = 'forum-2026-feedback-invite';
    const remember = () => { try { sessionStorage.setItem(storageKey, 'shown'); } catch {} };
    const open = () => {
        if (dialog.open || document.hidden) return;
        dialog.showModal();
        document.documentElement.classList.add('feedback-modal-open');
        remember();
    };
    const close = () => dialog.close();
    dialog.querySelectorAll('[data-feedback-dismiss]').forEach(button => button.addEventListener('click', close));
    dialog.addEventListener('close', () => document.documentElement.classList.remove('feedback-modal-open'));
    dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
    dialog.querySelector('a').addEventListener('click', remember);
    let shown = false;
    try { shown = sessionStorage.getItem(storageKey) === 'shown'; } catch {}
    if (location.hash === '#feedback') open();
    else if (!shown) window.setTimeout(open, 1800);
    window.addEventListener('hashchange', () => { if (location.hash === '#feedback') open(); });
})();
