(() => {
    'use strict';

    const config = window.CONFERENCE_CHAT_CONFIG || {};
    const sessionMode = config.authMode === 'session';
    if (!config.enabled || (!sessionMode && !config.token)) return;

    const endpoint = config.endpoint || '/api/conference-chat.php';
    const list = document.querySelector('[data-chat-list]');
    const form = document.querySelector('[data-chat-form]');
    const input = document.querySelector('[data-chat-input]');
    const status = document.querySelector('[data-chat-status]');
    const sessionLabel = document.querySelector('[data-chat-session]');
    const replyBanner = document.querySelector('[data-reply-banner]');
    const replyLabel = document.querySelector('[data-reply-label]');
    const replyCancel = document.querySelector('[data-reply-cancel]');

    if (!list || !form || !input) return;

    let replyToId = null;
    let replyToName = '';
    let sending = false;
    let lastSignature = '';

    const timeFormatter = new Intl.DateTimeFormat('ru-RU', {hour: '2-digit', minute: '2-digit'});

    function setStatus(text, isError = false) {
        if (!status) return;
        status.textContent = text || '';
        status.classList.toggle('error', Boolean(isError));
    }

    function formatTime(value) {
        if (!value) return '';
        const parsed = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return '';
        return timeFormatter.format(parsed);
    }

    function clearReply() {
        replyToId = null;
        replyToName = '';
        if (replyBanner) replyBanner.hidden = true;
        if (replyLabel) replyLabel.textContent = '';
    }

    function setReply(message) {
        replyToId = Number(message.id);
        replyToName = message.participant_name || '';
        if (replyLabel) replyLabel.textContent = `Ответ для: ${replyToName}`;
        if (replyBanner) replyBanner.hidden = false;
        input.focus();
    }

    function makeButton(text, className = '') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.textContent = text;
        return button;
    }

    function renderMessage(message) {
        const article = document.createElement('article');
        article.className = 'chat-message' + (message.message_type === 'question' ? ' is-question' : '');

        const head = document.createElement('div');
        head.className = 'chat-message__head';

        const who = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = message.participant_name || 'Участник';
        const org = document.createElement('span');
        org.textContent = message.organization ? ` · ${message.organization}` : '';
        who.append(name, org);

        const right = document.createElement('div');
        right.className = 'chat-message__right';
        if (message.message_type === 'question') {
            const badge = document.createElement('span');
            badge.className = 'chat-question-badge';
            badge.textContent = 'Вопрос спикеру';
            right.appendChild(badge);
        }
        const time = document.createElement('time');
        time.textContent = formatTime(message.created_at);
        right.appendChild(time);
        head.append(who, right);
        article.appendChild(head);

        if (message.reply_to_id && (message.reply_name || message.reply_text)) {
            const quote = document.createElement('div');
            quote.className = 'chat-reply-quote';
            const quoteName = document.createElement('strong');
            quoteName.textContent = message.reply_name || 'Сообщение';
            const quoteText = document.createElement('span');
            quoteText.textContent = message.reply_text || '';
            quote.append(quoteName, quoteText);
            article.appendChild(quote);
        }

        const text = document.createElement('div');
        text.className = 'chat-message__text';
        text.textContent = message.message_text || '';
        article.appendChild(text);

        if (message.message_type === 'question' && ['on_air', 'answered'].includes(message.status)) {
            const marker = document.createElement('div');
            marker.className = 'chat-question-status ' + (message.status === 'answered' ? 'answered' : 'on-air');
            marker.textContent = message.status === 'answered' ? '✓ Отвечено спикером' : '● Передано спикеру';
            article.appendChild(marker);
        }

        const actions = document.createElement('div');
        actions.className = 'chat-message__actions';

        const replyButton = makeButton('Ответить', 'chat-action');
        replyButton.addEventListener('click', () => setReply(message));
        actions.appendChild(replyButton);

        if (message.message_type === 'question') {
            const liked = Boolean(Number(message.liked_by_me));
            const voteButton = makeButton(`👍 ${Number(message.votes || 0)}`, 'chat-action vote' + (liked ? ' active' : ''));
            voteButton.setAttribute('aria-label', liked ? 'Убрать поддержку вопроса' : 'Поддержать вопрос');
            voteButton.addEventListener('click', async () => {
                voteButton.disabled = true;
                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        credentials: 'same-origin',
                        body: JSON.stringify({action: 'vote', token: sessionMode ? '' : config.token, message_id: Number(message.id)})
                    });
                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data?.ok) throw new Error('vote');
                    voteButton.textContent = `👍 ${Number(data.votes || 0)}`;
                    voteButton.classList.toggle('active', Boolean(data.liked));
                } catch (_) {
                    setStatus('Не удалось обновить реакцию.', true);
                } finally {
                    voteButton.disabled = false;
                }
            });
            actions.appendChild(voteButton);
        }

        article.appendChild(actions);
        return article;
    }

    function render(data) {
        const messages = Array.isArray(data.messages) ? data.messages : [];
        const signature = messages.map((m) => `${m.id}:${m.status}:${m.votes}:${m.liked_by_me}`).join('|');
        if (signature === lastSignature) {
            if (sessionLabel) {
                sessionLabel.textContent = data.session ? `${data.session.speaker_name} — ${data.session.title}` : 'Текущий спикер пока не выбран';
            }
            return;
        }

        const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 120;
        list.innerHTML = '';

        if (!messages.length) {
            const empty = document.createElement('div');
            empty.className = 'chat-empty';
            empty.innerHTML = '<strong>Пока тихо</strong><span>Напишите первое сообщение или задайте вопрос спикеру.</span>';
            list.appendChild(empty);
        } else {
            messages.forEach((message) => list.appendChild(renderMessage(message)));
        }

        if (nearBottom || lastSignature === '') list.scrollTop = list.scrollHeight;
        lastSignature = signature;

        if (sessionLabel) {
            sessionLabel.textContent = data.session ? `${data.session.speaker_name} — ${data.session.title}` : 'Текущий спикер пока не выбран';
        }
    }

    async function loadMessages() {
        try {
            const url = sessionMode ? endpoint : `${endpoint}?t=${encodeURIComponent(config.token)}`;
            const response = await fetch(url, {cache: 'no-store', credentials: 'same-origin'});
            if (response.status === 401 && sessionMode) {
                location.reload();
                return;
            }
            if (!response.ok) throw new Error('load');
            const data = await response.json();
            if (!data?.ok) throw new Error('api');
            render(data);
        } catch (_) {
            setStatus('Не удалось обновить обсуждение. Повторим автоматически.', true);
        }
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sending) return;
        const text = input.value.trim();
        if (!text) return;

        const selectedType = form.querySelector('input[name="messageType"]:checked')?.value || 'chat';
        sending = true;
        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.disabled = true;
        setStatus('Отправляем…');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'same-origin',
                body: JSON.stringify({
                    action: 'send',
                    token: sessionMode ? '' : config.token,
                    message_type: selectedType,
                    message_text: text,
                    reply_to_id: replyToId
                })
            });
            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.ok) {
                throw new Error(data?.message || 'send');
            }
            input.value = '';
            clearReply();
            setStatus(selectedType === 'question' ? 'Вопрос отправлен спикеру и опубликован в обсуждении.' : 'Сообщение отправлено.');
            lastSignature = '';
            await loadMessages();
        } catch (error) {
            const message = error instanceof Error && error.message !== 'send' ? error.message : 'Не удалось отправить сообщение.';
            setStatus(message, true);
        } finally {
            sending = false;
            if (submit) submit.disabled = false;
        }
    });

    replyCancel?.addEventListener('click', clearReply);

    form.querySelectorAll('input[name="messageType"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            input.placeholder = radio.value === 'question'
                ? 'Напишите вопрос текущему спикеру…'
                : 'Напишите сообщение участникам…';
        });
    });

    loadMessages();
    setInterval(loadMessages, 2500);
})();
