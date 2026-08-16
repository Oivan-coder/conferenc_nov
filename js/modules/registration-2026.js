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

    async function refreshAvailability() {
        if (!config.availabilityEndpoint) return;

        const offlineRadio = document.querySelector('input[name="participationFormat"][value="offline"]');
        const onlineRadio = document.querySelector('input[name="participationFormat"][value="online"]');
        const note = document.querySelector('[data-offline-availability]');

        try {
            const response = await fetch(config.availabilityEndpoint, { cache: 'no-store' });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok) return;

            if (offlineRadio) offlineRadio.disabled = !result.offline?.available;
            if (onlineRadio) onlineRadio.disabled = !result.online?.available;

            if (note) {
                if (result.offline?.state === 'full') {
                    note.textContent = 'Свободные места для очного участия закончились. Доступны онлайн-участие и лист ожидания.';
                } else if (result.offline?.state === 'limited') {
                    note.textContent = 'Количество мест для очного участия ограничено.';
                } else {
                    note.textContent = 'Очное участие доступно. Количество мест ограничено.';
                }
            }
        } catch (error) {
            if (note) note.textContent = 'Количество мест для очного участия ограничено.';
        }
    }

    async function processDuplicate(response, result, payload, message) {
        if (response.status !== 409 || result.error !== 'possible_duplicate') return { response, result };

        const reasons = Array.isArray(result.reasons) ? result.reasons : [];
        const confirmed = window.confirm(duplicateMessage(reasons));
        if (!confirmed) {
            if (message) message.textContent = 'Регистрация не отправлена. Проверьте данные участника.';
            return { cancelled: true };
        }

        payload.confirmDuplicate = true;
        if (message) message.textContent = 'Отправляем регистрацию…';
        const retryResponse = await sendRegistration(payload);
        const retryResult = await retryResponse.json().catch(() => ({}));
        return { response: retryResponse, result: retryResult };
    }

    async function processOfflineFull(response, result, payload, form, message) {
        if (response.status !== 409 || result.error !== 'offline_full') return { response, result };

        if (result.can_switch_online) {
            const switchOnline = window.confirm('Свободные места для очного участия закончились. Перейти на онлайн-участие?');
            if (switchOnline) {
                payload.participationFormat = 'online';
                const onlineRadio = form.querySelector('input[name="participationFormat"][value="online"]');
                if (onlineRadio) onlineRadio.checked = true;
                if (message) message.textContent = 'Оформляем онлайн-регистрацию…';
                const retryResponse = await sendRegistration(payload);
                const retryResult = await retryResponse.json().catch(() => ({}));
                return { response: retryResponse, result: retryResult };
            }
        }

        if (result.can_join_waitlist) {
            const joinWaitlist = window.confirm('Добавить заявку в лист ожидания на очное участие?');
            if (joinWaitlist) {
                payload.waitlistIfFull = true;
                if (message) message.textContent = 'Добавляем в лист ожидания…';
                const retryResponse = await sendRegistration(payload);
                const retryResult = await retryResponse.json().catch(() => ({}));
                return { response: retryResponse, result: retryResult };
            }
        }

        if (message) message.textContent = 'Очная регистрация не отправлена.';
        return { cancelled: true };
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

            let processed = await processDuplicate(response, result, payload, message);
            if (processed.cancelled) return;
            response = processed.response;
            result = processed.result;

            processed = await processOfflineFull(response, result, payload, form, message);
            if (processed.cancelled) return;
            response = processed.response;
            result = processed.result;

            if (!response.ok || !result.ok) {
                if (result.error === 'offline_closed') throw new Error('offline_closed');
                if (result.error === 'online_closed') throw new Error('online_closed');
                throw new Error(result.error || 'Registration request failed');
            }

            form.reset();
            if (message) {
                if (result.registration_status === 'waitlist') {
                    message.textContent = `Заявка добавлена в лист ожидания. Код: ${result.participant_code}. Подтверждение направлено на почту.`;
                } else if (result.participation_format === 'online') {
                    message.textContent = `Онлайн-регистрация подтверждена. Код участника: ${result.participant_code}. Персональная ссылка направлена на почту.`;
                } else {
                    message.textContent = `Очная регистрация подтверждена. Код участника: ${result.participant_code}. QR-билет направлен на почту.`;
                }
            }

            await refreshAvailability();
        } catch (error) {
            if (message) {
                if (error.message === 'offline_closed') {
                    message.textContent = 'Очная регистрация сейчас закрыта. Выберите онлайн-участие.';
                } else if (error.message === 'online_closed') {
                    message.textContent = 'Онлайн-регистрация сейчас закрыта.';
                } else {
                    message.textContent = 'Не удалось отправить заявку. Попробуйте ещё раз или свяжитесь с организаторами.';
                }
            }
        } finally {
            submitButton.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const isOpen = setRegistrationState();
        const form = document.querySelector('[data-registration-form]');
        if (isOpen && form) {
            form.addEventListener('submit', submitRegistration);
            await refreshAvailability();
        }
    });
})();
