(() => {
    'use strict';
    const form = document.getElementById('conferenceFeedbackForm');
    if (!form) return;
    const status = document.getElementById('feedbackStatus');
    const button = form.querySelector('button[type="submit"]');
    const success = document.getElementById('feedbackSuccess');
    let pending = false;
    // Random key for this submission only; never derived from a person or ticket.
    const randomKey = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), b => b.toString(16).padStart(2, '0')).join('');
    let key = randomKey();
    let previousPayload = '';
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending || !form.reportValidity()) return;
        const values = new FormData(form);
        const payload = {
            overall_rating: Number(values.get('overall_rating')),
            program_rating: Number(values.get('program_rating')),
            organization_rating: Number(values.get('organization_rating')),
            participation_format: values.get('participation_format'),
            liked_text: values.get('liked_text').trim(),
            improvements_text: values.get('improvements_text').trim(),
            website: values.get('website')
        };
        const signature = JSON.stringify(payload);
        if (previousPayload && previousPayload !== signature) key = randomKey();
        previousPayload = signature;
        payload.submission_key = key;
        pending = true;
        button.disabled = true;
        button.textContent = 'Отправляем…';
        status.textContent = '';
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch('/api/conference-feedback.php', {
                method: 'POST', credentials: 'omit', referrerPolicy: 'no-referrer',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload), signal: controller.signal
            });
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || 'Не удалось отправить отзыв. Попробуйте ещё раз.');
            form.hidden = true;
            success.hidden = false;
            success.focus();
        } catch (error) {
            status.textContent = error.name === 'AbortError' || error instanceof TypeError
                ? 'Не удалось получить ответ сервера. Ваш текст остался в форме — попробуйте отправить ещё раз.'
                : error instanceof SyntaxError ? 'Приём отзывов временно недоступен. Ваш текст остался в форме — попробуйте позже.' : error.message;
        } finally {
            window.clearTimeout(timeout);
            pending = false;
            button.disabled = false;
            button.textContent = 'Отправить отзыв';
        }
    });
})();
