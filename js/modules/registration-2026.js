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

    function isPhonePrefixOnly(value) {
        const digits = String(value || '').replace(/\D+/g, '');
        return digits === '' || digits === '7';
    }

    function formatRussianPhoneInput(value) {
        let digits = String(value || '').replace(/\D+/g, '');
        if (!digits || digits === '7') return '+7 ';

        if (digits.length > 11 && (digits.startsWith('77') || digits.startsWith('78'))) {
            digits = digits.slice(-11);
        }
        if (digits.length === 10) digits = `7${digits}`;
        if (digits.length >= 11 && digits[0] === '8') digits = `7${digits.slice(1)}`;
        if (digits[0] !== '7') digits = `7${digits}`;

        const subscriber = digits.slice(1, 11);
        let result = '+7';
        if (!subscriber.length) return '+7 ';
        result += ` (${subscriber.slice(0, 3)}`;
        if (subscriber.length >= 3) result += ')';
        if (subscriber.length > 3) result += ` ${subscriber.slice(3, 6)}`;
        if (subscriber.length > 6) result += `-${subscriber.slice(6, 8)}`;
        if (subscriber.length > 8) result += `-${subscriber.slice(8, 10)}`;
        return result;
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

    function setMessage(element, text, state = '') {
        if (!element) return;
        element.textContent = text || '';
        if (state) element.dataset.state = state;
        else delete element.dataset.state;
    }

    function setMessageAction(element, text, label, url, state = 'success') {
        setMessage(element, text, state);
        if (!element || !url) return;
        const br = document.createElement('br');
        const link = document.createElement('a');
        link.href = url;
        link.textContent = label;
        link.style.display = 'inline-block';
        link.style.marginTop = '8px';
        link.style.fontWeight = '700';
        element.append(br, link);
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

        if (phone && !isPhonePrefixOnly(phone.value)) {
            const normalized = normalizeRussianPhone(phone.value);
            if (!normalized) {
                setFieldValidity(phone, 'Введите 10 цифр номера после +7.');
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
            phone: 'Проверьте телефон: введите 10 цифр номера после +7.',
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
        const submitButton = document.querySelector('[data-registration-form] button[type="submit"]');

        try {
            const response = await fetch(config.availabilityEndpoint, { cache: 'no-store' });
            const result = await response.json().catch(() => ({}));
            if (!response.ok || !result.ok) return;

            if (offlineRadio) offlineRadio.disabled = !result.offline?.available;
            if (onlineRadio) onlineRadio.disabled = !result.online?.available;
            if (offlineRadio?.disabled && offlineRadio.checked) offlineRadio.checked = false;
            if (onlineRadio?.disabled && onlineRadio.checked) onlineRadio.checked = false;
            if (submitButton) submitButton.disabled = !result.offline?.available && !result.online?.available;

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
            if (note) note.textContent = 'Не удалось проверить остаток очных мест. Повторим проверку при отправке формы.';
        }
    }

    async function processDuplicate(response, result, payload, message) {
        if (response.status !== 409 || result.error !== 'possible_duplicate') return { response, result };

        const reasons = Array.isArray(result.reasons) ? result.reasons : [];
        const exactDuplicate = ['same_person', 'email', 'phone'].every((reason) => reasons.includes(reason));

        if (exactDuplicate) {
            window.alert('Вы уже зарегистрированы на мероприятие. Повторная регистрация не требуется.\n\nЕсли необходимо изменить данные или вы не получили письмо с подтверждением, свяжитесь с организаторами: info@rclsmo.ru.');
            setMessage(message, 'Вы уже зарегистрированы. Если необходимо изменить данные или повторно получить подтверждение, напишите на info@rclsmo.ru.', 'warning');
            return { cancelled: true };
        }

        const confirmed = window.confirm(duplicateMessage(reasons));
        if (!confirmed) {
            setMessage(message, 'Регистрация не отправлена. Проверьте данные участника.', 'warning');
            return { cancelled: true };
        }

        payload.confirmDuplicate = true;
        setMessage(message, 'Отправляем регистрацию…');
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
                setMessage(message, 'Оформляем онлайн-регистрацию…');
                const retryResponse = await sendRegistration(payload);
                const retryResult = await retryResponse.json().catch(() => ({}));
                return { response: retryResponse, result: retryResult };
            }
        }

        if (result.can_join_waitlist) {
            const joinWaitlist = window.confirm('Добавить заявку в лист ожидания на очное участие?');
            if (joinWaitlist) {
                payload.waitlistIfFull = true;
                setMessage(message, 'Добавляем в лист ожидания…');
                const retryResponse = await sendRegistration(payload);
                const retryResult = await retryResponse.json().catch(() => ({}));
                return { response: retryResponse, result: retryResult };
            }
        }

        setMessage(message, 'Очная регистрация не отправлена.', 'warning');
        return { cancelled: true };
    }

    async function submitRegistration(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const message = form.querySelector('[data-registration-message]');
        const submitButton = form.querySelector('button[type="submit"]');

        if (!validateForm(form) || !config.endpoint) return;

        submitButton.disabled = true;
        setMessage(message, 'Проверяем данные…');

        const payload = Object.fromEntries(new FormData(form).entries());
        if (payload.phone && isPhonePrefixOnly(payload.phone)) payload.phone = '';
        payload.eventId = config.eventId;

        try {
            let response = await sendRegistration(payload);
            let result = await response.json().catch(() => ({}));

            if (response.status === 422 && result.error === 'validation_failed') {
                applyServerValidation(form, result.fields);
                setMessage(message, 'Проверьте выделенные поля формы.', 'error');
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
            if (form.elements.phone) form.elements.phone.value = '+7 ';
            const code = result.participant_code;
            if (result.email_pending) {
                setMessage(message, `Очная регистрация подтверждена. Код участника: ${code}. QR-билет формируется и будет направлен на почту.`, 'success');
            } else if (!result.email_sent) {
                setMessage(message, `Регистрация сохранена. Код участника: ${code}. Письмо пока не отправлено — сохраните код и напишите на info@rclsmo.ru.`, 'warning');
            } else if (result.registration_status === 'waitlist') {
                setMessage(message, `Заявка добавлена в лист ожидания. Код: ${code}. Подтверждение направлено на почту.`, 'success');
            } else if (result.participation_format === 'online') {
                setMessageAction(message, `Онлайн-регистрация подтверждена. Код участника: ${code}. Персональная ссылка направлена на почту.`, 'Открыть мой билет', result.participant_url, 'success');
            } else {
                setMessageAction(message, `Очная регистрация подтверждена. Код участника: ${code}. QR-билет направлен на почту.`, 'Открыть мой билет', result.participant_url, 'success');
            }
            message?.focus({ preventScroll: true });

        } catch (error) {
            if (error.message === 'offline_closed') {
                setMessage(message, 'Очная регистрация сейчас закрыта. Выберите онлайн-участие.', 'warning');
            } else if (error.message === 'online_closed') {
                setMessage(message, 'Онлайн-регистрация сейчас закрыта.', 'warning');
            } else {
                setMessage(message, 'Не удалось отправить заявку. Данные не потеряны — попробуйте ещё раз или напишите на info@rclsmo.ru.', 'error');
            }
        } finally {
            submitButton.disabled = false;
            refreshAvailability();
        }
    }

    function wireValidation(form) {
        const phone = form.elements.phone;
        if (phone) {
            phone.value = formatRussianPhoneInput(phone.value);

            phone.addEventListener('focus', () => {
                if (isPhonePrefixOnly(phone.value)) phone.value = '+7 ';
                window.setTimeout(() => phone.setSelectionRange(phone.value.length, phone.value.length), 0);
            });

            phone.addEventListener('input', () => {
                setFieldValidity(phone, '');
                phone.value = formatRussianPhoneInput(phone.value);
                phone.setSelectionRange(phone.value.length, phone.value.length);
            });

            phone.addEventListener('blur', () => {
                setFieldValidity(phone, '');
                if (isPhonePrefixOnly(phone.value)) {
                    phone.value = '+7 ';
                    return;
                }
                const normalized = normalizeRussianPhone(phone.value);
                if (!normalized) {
                    setFieldValidity(phone, 'Введите 10 цифр номера после +7.');
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