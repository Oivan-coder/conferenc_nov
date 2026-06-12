const BOOTSTRAP_ACTION = 'action=bootstrap';
const MAX_WAIT_MS = 12000;
let released = false;
let bootstrapStarted = false;

function setLoading(message = 'Загружаю данные из Google Sheets…') {
  document.documentElement.classList.add('is-loading-data');
  document.documentElement.classList.remove('is-data-ready', 'is-data-error');
  const overlayText = document.querySelector('[data-loading-text]');
  if (overlayText) overlayText.textContent = message;
  setFormDisabled(true);
}

function setReady(message = 'синхронизировано') {
  if (released) return;
  released = true;
  document.documentElement.classList.remove('is-loading-data', 'is-data-error');
  document.documentElement.classList.add('is-data-ready');
  setFormDisabled(false);
  const hint = document.getElementById('quickSyncHint');
  if (hint) hint.textContent = message;
}

function setError(message = 'Не удалось загрузить данные. Нажми ↻ для повторной синхронизации.') {
  if (released) return;
  document.documentElement.classList.remove('is-loading-data');
  document.documentElement.classList.add('is-data-error');
  setFormDisabled(true);
  const overlayText = document.querySelector('[data-loading-text]');
  if (overlayText) overlayText.textContent = message;
  const hint = document.getElementById('quickSyncHint');
  if (hint) hint.textContent = 'синхронизация не готова';
}

function setFormDisabled(disabled) {
  const selectors = [
    '#amountInput',
    '#descriptionInput',
    '#transactionForm button',
    '#transactionForm .chip',
    '#transactionForm .detail-chip'
  ];
  selectors.forEach((selector) => {
    document.querySelectorAll(selector).forEach((element) => {
      if ('disabled' in element) element.disabled = disabled;
      element.setAttribute('aria-disabled', String(disabled));
    });
  });
}

function isBootstrapRequest(input) {
  const url = typeof input === 'string' ? input : input?.url || '';
  return url.includes('/exec') && url.includes(BOOTSTRAP_ACTION);
}

function installFetchGuard() {
  const originalFetch = window.fetch.bind(window);
  window.fetch = async (input, init) => {
    const isBootstrap = isBootstrapRequest(input);
    if (isBootstrap) {
      bootstrapStarted = true;
      setLoading();
    }

    try {
      const response = await originalFetch(input, init);
      if (isBootstrap) {
        if (response.ok) {
          setReady();
        } else {
          setError(`Ошибка синхронизации: ${response.status}`);
        }
      }
      return response;
    } catch (error) {
      if (isBootstrap) setError('Нет связи с Google Sheets. Нажми ↻ после восстановления сети.');
      throw error;
    }
  };
}

function installDomObserver() {
  const observer = new MutationObserver(() => {
    if (!released) setFormDisabled(true);
  });
  observer.observe(document.body, { childList: true, subtree: true });
}

setLoading();
installFetchGuard();

window.addEventListener('DOMContentLoaded', () => {
  setFormDisabled(true);
  installDomObserver();
});

window.setTimeout(() => {
  if (!released && !bootstrapStarted) {
    setError('Интеграция не стартовала. Проверь подключение Google Sheets и нажми ↻.');
  } else if (!released) {
    setError('Google Sheets долго отвечает. Нажми ↻ для повторной загрузки.');
  }
}, MAX_WAIT_MS);
