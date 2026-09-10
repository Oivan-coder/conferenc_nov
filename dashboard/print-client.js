(() => {
    'use strict';

    const BRIDGE_URL = 'http://127.0.0.1:5030/print';
    const BRIDGE_TIMEOUT_MS = 2200;
    const LAST_CODE_STORAGE_KEY = 'rclsmo_last_badge_code';

    const scannerPanel = document.querySelector('.panel.scanner');
    if (!scannerPanel) return;

    const panel = document.createElement('div');
    panel.className = 'panel';
    panel.id = 'badge-print-panel';
    panel.style.marginBottom = '18px';
    panel.innerHTML = `
        <div class="panel-head">
            <div>
                <h2>Печать бейджа</h2>
                <p class="muted" style="margin:5px 0 0">После первого успешного сканирования бейдж печатается автоматически. При повторном сканировании система предложит распечатать его ещё раз.</p>
            </div>
            <span class="pill" data-print-state>Ожидание сканирования</span>
        </div>
        <div data-print-message class="muted" style="font-size:13px;line-height:1.55">Если этикетку зажуёт или печать получится неудачной, повторно отсканируйте QR участника либо используйте кнопку повторной печати.</div>
        <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:12px">
            <button type="button" data-print-again style="display:none;border:0;border-radius:10px;background:#214f3b;color:#fff;padding:10px 14px;font:inherit;font-size:13px;font-weight:700;cursor:pointer">Напечатать ещё раз</button>
            <button type="button" data-print-retry style="display:none;border:0;border-radius:10px;background:#214f3b;color:#fff;padding:10px 14px;font:inherit;font-size:13px;font-weight:700;cursor:pointer">Повторить печать</button>
            <button type="button" data-print-test style="border:1px solid #cbd9d2;border-radius:10px;background:#fff;color:#214f3b;padding:10px 14px;font:inherit;font-size:13px;font-weight:700;cursor:pointer">Проверить сервис печати</button>
        </div>`;
    scannerPanel.insertAdjacentElement('afterend', panel);

    const stateEl = panel.querySelector('[data-print-state]');
    const messageEl = panel.querySelector('[data-print-message]');
    const printAgainButton = panel.querySelector('[data-print-again]');
    const retryButton = panel.querySelector('[data-print-retry]');
    const testButton = panel.querySelector('[data-print-test]');

    let lastCode = '';
    let printing = false;

    function validCode(value) {
        return /^LE[A-F0-9]{8}$/.test(String(value || '').trim().toUpperCase());
    }

    function rememberCode(code) {
        const normalized = String(code || '').trim().toUpperCase();
        if (!validCode(normalized)) return;
        lastCode = normalized;
        try {
            sessionStorage.setItem(LAST_CODE_STORAGE_KEY, normalized);
        } catch (error) {}
        printAgainButton.style.display = '';
        printAgainButton.textContent = `Напечатать ещё раз · ${normalized}`;
    }

    function restoreLastCode() {
        try {
            const stored = String(sessionStorage.getItem(LAST_CODE_STORAGE_KEY) || '').trim().toUpperCase();
            if (validCode(stored)) rememberCode(stored);
        } catch (error) {}
    }

    function setState(label, message, kind = 'neutral') {
        stateEl.textContent = label;
        stateEl.style.background = kind === 'ok' ? '#e6f6ec' : kind === 'warn' ? '#fff4d9' : kind === 'error' ? '#fff0ef' : '#e9f2ed';
        stateEl.style.color = kind === 'ok' ? '#175d36' : kind === 'warn' ? '#7b5a0c' : kind === 'error' ? '#8d302b' : '#214f3b';
        messageEl.textContent = message;
    }

    function downloadZpl(zpl, code) {
        const blob = new Blob([zpl], {type: 'text/plain;charset=utf-8'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `badge_${code}.zpl`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    async function fetchZpl(code) {
        const response = await fetch(`/dashboard/badge-zpl.php?code=${encodeURIComponent(code)}`, {
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!response.ok) throw new Error(`ZPL HTTP ${response.status}`);
        return response.text();
    }

    async function sendToBridge(zpl, code) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), BRIDGE_TIMEOUT_MS);
        try {
            const response = await fetch(BRIDGE_URL, {
                method: 'POST',
                mode: 'cors',
                cache: 'no-store',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({participant_id: code, zpl, action: 'print_badge'}),
                signal: controller.signal
            });
            if (!response.ok) throw new Error(`Bridge HTTP ${response.status}`);
            const result = await response.json().catch(() => ({}));
            if (result.status !== 'success') throw new Error(result.message || 'Print bridge error');
            return result;
        } finally {
            clearTimeout(timeout);
        }
    }

    async function printBadge(code, {fallbackDownload = true, reason = 'scan'} = {}) {
        code = String(code || '').trim().toUpperCase();
        if (printing || !validCode(code)) return;

        printing = true;
        rememberCode(code);
        retryButton.style.display = 'none';
        printAgainButton.disabled = true;
        printAgainButton.style.opacity = '.65';
        setState('Печать…', `Формируем ZPL для ${code} и передаём на принтер.`);

        try {
            const zpl = await fetchZpl(code);
            try {
                const result = await sendToBridge(zpl, code);
                const printer = result.printer ? ` · ${result.printer}` : '';
                const prefix = reason === 'repeat' ? 'Повторный бейдж' : 'Бейдж';
                setState('Напечатано', `${prefix} ${code} передан на принтер${printer}.`, 'ok');
            } catch (bridgeError) {
                if (fallbackDownload) {
                    downloadZpl(zpl, code);
                    setState('Сервис печати недоступен', `Автопечать не сработала. ZPL для ${code} скачан на компьютер как резервный файл. Запустите локальный сервис печати и нажмите «Повторить печать».`, 'warn');
                } else {
                    setState('Сервис печати недоступен', 'Не удалось подключиться к локальному сервису на 127.0.0.1:5030.', 'error');
                }
                retryButton.style.display = '';
            }
        } catch (error) {
            setState('Ошибка ZPL', 'Не удалось сформировать ZPL для этого участника. Отметка о приходе в базе при этом не меняется.', 'error');
            retryButton.style.display = '';
        } finally {
            printing = false;
            printAgainButton.disabled = false;
            printAgainButton.style.opacity = '1';
        }
    }

    async function testBridge() {
        setState('Проверка…', 'Проверяем локальный сервис печати на этом компьютере.');
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), BRIDGE_TIMEOUT_MS);
        try {
            const response = await fetch('http://127.0.0.1:5030/health', {
                mode: 'cors',
                cache: 'no-store',
                signal: controller.signal
            });
            if (!response.ok) throw new Error('offline');
            const result = await response.json().catch(() => ({}));
            const printer = result.printer ? ` Принтер: ${result.printer}.` : '';
            setState('Сервис готов', `Локальный сервис печати доступен.${printer}`, 'ok');
        } catch (error) {
            setState('Сервис не запущен', 'На этом компьютере не найден локальный сервис печати. Без него браузер не может напрямую отправить ZPL на USB/LAN-принтер.', 'warn');
        } finally {
            clearTimeout(timeout);
        }
    }

    printAgainButton.addEventListener('click', () => {
        if (!lastCode || printing) return;
        const confirmed = window.confirm(`Распечатать ещё один бейдж для ${lastCode}?\n\nОтметка о приходе участника не изменится.`);
        if (confirmed) printBadge(lastCode, {fallbackDownload: true, reason: 'repeat'});
    });

    retryButton.addEventListener('click', () => {
        if (lastCode) printBadge(lastCode, {fallbackDownload: false, reason: 'repeat'});
    });

    testButton.addEventListener('click', testBridge);

    restoreLastCode();

    const successfulScanCode = document.querySelector('.scan-status.success code')?.textContent?.trim().toUpperCase() || '';
    if (validCode(successfulScanCode)) {
        rememberCode(successfulScanCode);
        setTimeout(() => printBadge(successfulScanCode), 150);
        return;
    }

    const duplicateScanCode = document.querySelector('.scan-status.warning code')?.textContent?.trim().toUpperCase() || '';
    if (validCode(duplicateScanCode)) {
        rememberCode(duplicateScanCode);
        setState('Уже отмечен', `Участник ${duplicateScanCode} уже был отмечен ранее. Можно распечатать ещё один бейдж без изменения посещаемости.`, 'warn');
        setTimeout(() => {
            const confirmed = window.confirm(`Участник ${duplicateScanCode} уже был отмечен.\n\nРаспечатать ещё один бейдж?`);
            if (confirmed) printBadge(duplicateScanCode, {fallbackDownload: true, reason: 'repeat'});
        }, 120);
    }
})();
