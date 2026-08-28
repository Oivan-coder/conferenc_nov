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

    function normalizeRussianPhone(value) {
        const digits = String(value || '').replace(/\D+/g, '');
        if (!digits) return '';
        if (digits.length === 10) return `7${digits}`;
        if (digits.length === 11 && (digits[0] === '7' || digits[0] === '8')) return `7${digits.slice(1)}`;
        return null;
    }

    function formatRussianPhone(normalized) {
        if (!normalized || normalized.length !== 11) return '';
        return `+7 (${normalized.slice(1, 4)}) ${normalized.slice(4, 7)}-${normalized.slice(7, 9)}-${normalized.slice(9, 11)}`;
    }

    function isValidPersonName(value, required) {
        const cleaned = String(value || '').trim().replace(/\s+/g, ' ');
        if (!cleaned) return !required;
        if (cleaned.length < 2 || cleaned.length > 100) return false;
        return /^[\p{L}\p{M}][\p{L}\p{M}'’\- ]*$/u.test(cleaned);
    }

    function hasEnoughLetters(value, minimum = 2) {
        const letters = String(value || '').match(/\p{L}/gu) || [];
        return letters.length >= minimum;
    }

    function setFieldValidity(field, message) {
        if (!field) return;
        field.setCustomValidity(message || '');
    }

    function validateForm(form) {
        const lastName = form.elements.lastName;
        const firstName = form.elements.firstName;
        const middleName = form.elements.middleName;
        const position = form.elements.position;
        const organization = form.elements.organization;
        const email = form.elements.email;
        const phone = form.elements.phone;

        [lastName, firstName, middleName, position, organization, email, phone].forEach((field) => setFieldValidity(field, ''));

        if (!isValidPersonName(lastName?.value, true)) {
            setFieldValidity(lastName, 'Укажите фамилию буквами. Допустимы пробел, дефис и апостроф.');
        }
        if (!isValidPersonName(firstName?.value, true)) {
            setFieldValidity(firstName, 'Укажите имя буквами. Допустимы пробел, дефис и апостроф.');
        }
        if (!isValidPersonName(middleName?.value, false)) {
            setFieldValidity(middleName, 'Проверьте отчество: допустимы буквы, пробел, дефис и апостроф.');
        }

        if (position && (!hasEnoughLetters(position.value) || position.value.trim().length < 2)) {
            setFieldValidity(position, 'Укажите должность текстом.');
        }
        if (organization && (!hasEnoughLetters(organization.value) || organization.value.trim().length < 2)) {
            setFieldValidity(organization, 'Укажите наименование организации.');
        }

        if (email && email.value && !email.checkValidity()) {
            setFieldValidity(email, 'Проверьте адрес электронной почты, например name@example.ru.');
        }

        if (phone && phone.value.trim()) {
            const normalized = normalizeRussianPhone(phone.value);
            if (!normalized) {
                setFieldValidity(phone, 'Укажите российский номер: 10 цифр либо 11 цифр, начиная с +7 или 8.');
            } else {
                phone.value = formatRussianPhone(normalized);
            }
        }

        return form.reportValidity();
    }

    function applyServerValidation(form, fields) {
        if (!fields || typeof fields !== 'object') return false;

        const messages = {
            lastName: 'Проверьте фамилию: используйте буквы, пробел, дефис или апостроф.',
            firstName: 'Проверьте имя: используйте буквы, пробел, дефис или апостроф.',
            middleName: 'Проверьте отчество: используйте буквы, пробел, дефис или апостроф.',
            position: 'Проверьте должность.',
            organization: 'Проверьте наименование организации.',
            email: 'Проверьте адрес электронной почты.',
            phone: 'Проверьте телефон: нужен российский номер из 10 цифр либо 11 цифр, начиная с +7 или 8.',
            privacyConsent: 'Необходимо дать согласие на обработку персональных данных.',
            policyAcknowledged: 'Необходимо подтвердить ознакомление с Политикой обработки персональных данных.'
        };

        let firstInvalid = null;
        Object.keys(fields).forEach((name) => {
            const field = form.elements[name];
            if (!field) return;
            if (typeof field.setCustomValidity === 'function') {
                field.setCustomValidity(messages[name] || 'Проверьте значение поля.');
                if (!firstInvalid) firstInvalid = field;
            }
        });

        if (firstInvalid) {
            firstInvalid.reportValidity();
            firstInvalid.focus?.();
            return true;
        }
        return false;
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

        if (!validateForm(form) || !config.endpoint) return;

        submitButton.disabled = true;
        if (message) message.textContent = 'Проверяем данные…';

        const payload = Object.fromEntries(new FormData(form).entries());
        payload.eventId = config.eventId;

        try {
            let response = await sendRegistration(payload);
            let result = await response.json().catch(() => ({}));

            if (response.status === 422 && result.error === 'validation_failed') {
                applyServerValidation(form, result.fields);
                if (message) message.textContent = 'Проверьте выделенные поля формы.';
                return;
            }

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

    function wireValidation(form) {
        const phone = form.elements.phone;
        if (phone) {
            phone.addEventListener('blur', () => {
                setFieldValidity(phone, '');
                if (!phone.value.trim()) return;
                const normalized = normalizeRussianPhone(phone.value);
                if (!normalized) {
                    setFieldValidity(phone, 'Укажите российский номер: 10 цифр либо 11 цифр, начиная с +7 или 8.');
                    return;
                }
                phone.value = formatRussianPhone(normalized);
            });
        }

        form.addEventListener('input', (event) => {
            const field = event.target;
            if (field && typeof field.setCustomValidity === 'function') field.setCustomValidity('');
        });
        form.addEventListener('change', (event) => {
            const field = event.target;
            if (field && typeof field.setCustomValidity === 'function') field.setCustomValidity('');
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const isOpen = setRegistrationState();
        const form = document.querySelector('[data-registration-form]');
        if (isOpen && form) {
            wireValidation(form);
            form.addEventListener('submit', submitRegistration);
            await refreshAvailability();
        }
    });
})();
