(function () {
    'use strict';

    const config = window.REGISTRATION_CONFIG || { state: 'closed', endpoint: '' };

    function setRegistrationState() {
        const form = document.querySelector('[data-registration-form]');
        const closed = document.querySelector('[data-registration-closed]');
        const canOpen = config.state === 'open' && typeof config.endpoint === 'string' && config.endpoint.length > 0;

        document.body.dataset.registrationState = canOpen ? 'open' : 'closed';
        if (form) form.hidden = !canOpen;
        if (closed) closed.hidden = canOpen;
        return canOpen;
    }

    function duplicateMessage(reasons) {
        const lines = [];
        if (reasons.includes('same_person')) lines.push('Участник с таким ФИО и медицинской организацией уже зарегистрирован.');
        if (reasons.includes('email')) lines.push('На этот email уже есть регистрация.');
        if (reasons.includes('phone')) lines.push('На этот номер телефона уже есть регистрация.');
        lines.push('', 'Если это действительно другой участник или повторная регистрация нужна, нажмите «ОК».');
        return lines.join('\n');
    }

    async function sendRegistration(payload) {
        return fetch(config.endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
    }

    async function submitRegistration(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const message = form.querySelector('[data-registration-message]');
        const submitButton = form.querySelector('button[type="submit"]');

        if (!form.reportValidity() || !config.endpoint) return;

        submitButton.disabled = true;
        if (message) message.textContent = 'Проверяем данные…';

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.eventId = config.eventId;

        try {
            let response = await sendRegistration(payload);
            let result = await response.json().catch(() => ({}));

            if (response.status === 409 && result.error === 'possible_duplicate') {
                const reasons = Array.isArray(result.reasons) ? result.reasons : [];
                const confirmed = window.confirm(duplicateMessage(reasons));

                if (!confirmed) {
                    if (message) message.textContent = 'Регистрация не отправлена. Проверьте данные участника.';
                    return;
                }

                payload.confirmDuplicate = true;
                if (message) message.textContent = 'Отправляем регистрацию…';
                response = await sendRegistration(payload);
                result = await response.json().catch(() => ({}));
            }

            if (!response.ok || !result.ok) {
                throw new Error(result.error || 'Registration request failed');
            }

            form.reset();
            if (message) {
                message.textContent = `Регистрация принята. Код участника: ${result.participant_code}. Подтверждение будет направлено на указанную почту.`;
            }
        } catch (error) {
            if (message) message.textContent = 'Не удалось отправить заявку. Попробуйте ещё раз или свяжитесь с организаторами.';
        } finally {
            submitButton.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const isOpen = setRegistrationState();
        const form = document.querySelector('[data-registration-form]');
        if (isOpen && form) form.addEventListener('submit', submitRegistration);
    });
})();
