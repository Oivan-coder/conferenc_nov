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

    async function submitRegistration(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const message = form.querySelector('[data-registration-message]');
        const submitButton = form.querySelector('button[type="submit"]');

        if (!form.reportValidity() || !config.endpoint) return;

        submitButton.disabled = true;
        if (message) message.textContent = 'Отправляем заявку…';

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.eventId = config.eventId;

        try {
            const response = await fetch(config.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error('Registration request failed');
            form.reset();
            if (message) message.textContent = 'Заявка принята. Подтверждение будет направлено на указанную почту.';
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
