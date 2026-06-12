import seedTransactions from '../data/sample-transactions.json';
import { DEFAULT_SETTINGS, calculateDashboard, toMoney } from './finance-model.js';
import {
  answerMonthlyReviewInSheets,
  appendTransactionToSheets,
  fetchBootstrapFromSheets,
  updateTransactionInSheets
} from './sheets-api.js';

const STORAGE_KEY = 'atlas-finance-state-v2';
const LEGACY_STORAGE_KEY = 'atlas-finance-state-v1';
const QUEUE_KEY = 'atlas-finance-sync-queue-v1';
const DEFAULT_TAB = 'today';
const BUSY_SYNC_STATES = new Set(['loading', 'saving', 'refreshing']);

let state = loadState();
let selectedBudgetGroupId = state.settings.categories[0]?.id || state.settings.categories[0]?.name || 'other';
let selectedCategoryId = null;
let selectedIncomeCategoryId = DEFAULT_SETTINGS.incomeCategories[0].id;
let transactionType = 'expense';
let activeTab = DEFAULT_TAB;
let syncState = {
  status: 'idle',
  firstBootstrapLoaded: false,
  lastUpdatedAt: null,
  message: 'Загружаю данные из Google Sheets…'
};
let savingTransaction = false;

const els = {
  cycleCaption: document.getElementById('cycleCaption'),
  syncStatus: document.getElementById('syncStatus'),
  syncBtn: document.getElementById('syncBtn'),
  dailyLimit: document.getElementById('dailyLimit'),
  daysLeft: document.getElementById('daysLeft'),
  limitRing: document.getElementById('limitRing'),
  limitPercent: document.getElementById('limitPercent'),
  freeMoney: document.getElementById('freeMoney'),
  totalSpent: document.getElementById('totalSpent'),
  burnRate: document.getElementById('burnRate'),
  forecastStatus: document.getElementById('forecastStatus'),
  quickSyncHint: document.getElementById('quickSyncHint'),
  operationTitle: document.getElementById('operationTitle'),
  categorySummary: document.getElementById('categorySummary'),
  categoryList: document.getElementById('categoryList'),
  categoryChips: document.getElementById('categoryChips'),
  detailChips: document.getElementById('detailChips'),
  form: document.getElementById('transactionForm'),
  amount: document.getElementById('amountInput'),
  description: document.getElementById('descriptionInput'),
  addTransactionBtn: document.getElementById('addTransactionBtn'),
  reviewCount: document.getElementById('reviewCount'),
  reviewList: document.getElementById('reviewList'),
  goalsSummary: document.getElementById('goalsSummary'),
  goalsList: document.getElementById('goalsList'),
  transactionList: document.getElementById('transactionList'),
  exportBtn: document.getElementById('exportBtn'),
  editDialog: document.getElementById('editDialog'),
  editForm: document.getElementById('editForm'),
  editId: document.getElementById('editIdInput'),
  editAmount: document.getElementById('editAmountInput'),
  editCategory: document.getElementById('editCategoryInput'),
  editDescription: document.getElementById('editDescriptionInput'),
  closeEditBtn: document.getElementById('closeEditBtn')
};

init();

function init() {
  normalizeState();
  bindEvents();
  render();
  syncFromSheets({ mode: 'loading' });
  if ('serviceWorker' in navigator) navigator.serviceWorker.register(`${import.meta.env.BASE_URL}service-worker.js`).catch(() => {});
}

function bindEvents() {
  els.form.addEventListener('submit', onSubmitTransaction);
  els.syncBtn.addEventListener('click', () => syncFromSheets({ mode: syncState.firstBootstrapLoaded ? 'refreshing' : 'loading' }));
  els.exportBtn.addEventListener('click', exportJson);
  els.editForm.addEventListener('submit', onSubmitEdit);
  els.closeEditBtn.addEventListener('click', () => els.editDialog.close());
  window.addEventListener('online', processSyncQueue);
  document.querySelectorAll('[data-tab]').forEach((button) => {
    button.addEventListener('click', () => setActiveTab(button.dataset.tab));
  });
  document.querySelectorAll('[data-transaction-type]').forEach((button) => {
    button.addEventListener('click', () => setTransactionType(button.dataset.transactionType));
  });
}

function loadState() {
  const raw = localStorage.getItem(STORAGE_KEY) || localStorage.getItem(LEGACY_STORAGE_KEY);
  if (raw) {
    try {
      return JSON.parse(raw);
    } catch (error) {
      console.warn('State parse failed', error);
    }
  }
  return { settings: DEFAULT_SETTINGS, transactions: seedTransactions, tables: {} };
}

function normalizeState() {
  state.settings = { ...DEFAULT_SETTINGS, ...(state.settings || {}) };
  state.settings.accounts = state.settings.accounts || DEFAULT_SETTINGS.accounts;
  state.settings.categories = state.settings.categories || DEFAULT_SETTINGS.categories;
  state.settings.incomeCategories = state.settings.incomeCategories || DEFAULT_SETTINGS.incomeCategories;
  state.settings.obligations = state.settings.obligations || DEFAULT_SETTINGS.obligations;
  state.settings.goals = state.settings.goals || DEFAULT_SETTINGS.goals;
  state.transactions = Array.isArray(state.transactions) ? state.transactions : seedTransactions;
  state.tables = state.tables || {};
  if (!state.settings.categories.some((category) => category.id === selectedBudgetGroupId || category.name === selectedBudgetGroupId)) {
    selectedBudgetGroupId = state.settings.categories[0]?.id || state.settings.categories[0]?.name || 'other';
  }
  if (!state.settings.incomeCategories.some((category) => category.id === selectedIncomeCategoryId)) {
    selectedIncomeCategoryId = state.settings.incomeCategories[0]?.id || 'income_other';
  }
}

function saveState() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

function queueItems() {
  try {
    return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]');
  } catch {
    return [];
  }
}

function saveQueue(items) {
  localStorage.setItem(QUEUE_KEY, JSON.stringify(items));
}

function enqueue(item) {
  const items = queueItems();
  items.push({ ...item, queuedAt: new Date().toISOString() });
  saveQueue(items);
  renderSyncStatus();
}

async function processSyncQueue() {
  const items = queueItems();
  if (!items.length) {
    renderSyncStatus();
    return;
  }

  const remaining = [];
  for (const item of items) {
    try {
      if (item.action === 'appendTransaction') {
        const result = await appendTransactionToSheets(item.payload);
        if (result?.transaction) replaceTransaction(item.payload.id, result.transaction);
      } else if (item.action === 'updateTransaction') {
        const result = await updateTransactionInSheets(item.payload);
        if (result?.transaction) replaceTransaction(item.payload.id, result.transaction);
      } else if (item.action === 'answerMonthlyReview') {
        await answerMonthlyReviewInSheets(item.payload);
      }
    } catch (error) {
      remaining.push(item);
      console.warn('Queue sync failed', error);
    }
  }
  saveQueue(remaining);
  saveState();
  render();
}

async function syncFromSheets(options = {}) {
  if (BUSY_SYNC_STATES.has(syncState.status)) return;

  const mode = options.mode || (syncState.firstBootstrapLoaded ? 'refreshing' : 'loading');
  setSyncState(mode, {
    message: mode === 'refreshing' ? 'Обновляю данные из Google Sheets…' : 'Загружаю данные из Google Sheets…'
  });
  render();

  try {
    await processSyncQueue();
    await reloadBootstrap();
    setSyncState('success', {
      firstBootstrapLoaded: true,
      lastUpdatedAt: new Date(),
      message: `Обновлено ${formatTime(new Date())}`
    });
    render();
  } catch (error) {
    console.error('Bootstrap sync failed', error);
    setSyncState('error', { message: userSyncError(error) });
    render();
  }
}

async function reloadBootstrap() {
  const bootstrap = await fetchBootstrapFromSheets();
  if (!bootstrap?.transactions) throw new Error('Apps Script не вернул данные bootstrap');
  applyBootstrap(bootstrap);
  saveState();
}

function applyBootstrap(bootstrap) {
  state = {
    ...state,
    settings: {
      ...DEFAULT_SETTINGS,
      ...bootstrap.settings
    },
    transactions: bootstrap.transactions,
    tables: bootstrap.tables || {}
  };
  normalizeState();
}

async function onSubmitTransaction(event) {
  event.preventDefault();
  if (savingTransaction || !syncState.firstBootstrapLoaded) return;

  const amount = parseAmount(els.amount.value);
  if (!amount || amount <= 0) return;

  const currentType = transactionType;
  const account = defaultCashflowAccount();
  const incomeCategory = selectedIncomeCategory();
  const group = currentType === 'income' ? null : selectedBudgetGroup();
  const detail = currentType === 'income' ? null : selectedDetailCategory() || defaultDetailForGroup(group.id);
  const tx = {
    id: crypto.randomUUID(),
    date: new Date().toISOString(),
    type: currentType,
    category: currentType === 'income' ? incomeCategory.name : group.name,
    categoryId: currentType === 'income' ? incomeCategory.id : detail?.category_id || group.defaultCategoryId || group.id,
    categoryDetail: currentType === 'income' ? '' : detail?.name || '',
    budgetGroupId: currentType === 'income' ? incomeCategory.budgetGroupId || 'income' : group.id,
    description: els.description.value.trim(),
    amount,
    account: account.name,
    accountId: account.id,
    currency: 'RUB',
    syncStatus: 'pending'
  };

  savingTransaction = true;
  let savedToSheets = false;
  setSyncState('saving', { message: currentType === 'income' ? 'Сохраняю доход в Google Sheets…' : 'Сохраняю расход в Google Sheets…' });
  renderTransactionFormState();
  renderSyncStatus();

  try {
    await appendTransactionToSheets(tx);
    savedToSheets = true;
    els.form.reset();
    if (currentType === 'expense') selectedCategoryId = detail?.category_id || null;
    await reloadBootstrap();
    setSyncState('success', {
      firstBootstrapLoaded: true,
      lastUpdatedAt: new Date(),
      message: `Обновлено ${formatTime(new Date())}`
    });
  } catch (error) {
    console.error(savedToSheets ? 'Bootstrap after append failed' : 'Sheets append failed', error);
    setSyncState('error', { message: savedToSheets ? userSavedButSyncError(error, currentType) : userSaveError(error, currentType) });
  } finally {
    savingTransaction = false;
    render();
  }
}

async function onSubmitEdit(event) {
  event.preventDefault();
  const id = els.editId.value;
  const tx = state.transactions.find((item) => item.id === id);
  if (!tx) return;

  const group = categoryById(els.editCategory.value) || selectedBudgetGroup();
  const patch = {
    ...tx,
    id,
    transaction_id: id,
    amount: parseAmount(els.editAmount.value),
    description: els.editDescription.value.trim(),
    category: group.name,
    categoryId: group.defaultCategoryId || group.id,
    budgetGroupId: group.id,
    syncStatus: 'pending'
  };

  replaceTransaction(id, patch);
  els.editDialog.close();
  saveState();
  render();

  try {
    const result = await updateTransactionInSheets(patch);
    if (result?.transaction) replaceTransaction(id, result.transaction);
  } catch (error) {
    enqueue({ action: 'updateTransaction', payload: patch });
    console.warn('Sheets update failed', error);
    setSyncState('error', { message: `Не удалось сохранить правку: ${error?.message || 'попробуйте еще раз'}` });
  } finally {
    saveState();
    render();
  }
}

async function answerReview(item, input) {
  const amount = parseAmount(input.value);
  const payload = {
    period: item.period,
    item_id: item.item_id,
    actual_amount: amount,
    status: 'done',
    notes: item.notes || ''
  };

  updateLocalReview(payload);
  saveState();
  render();

  try {
    await answerMonthlyReviewInSheets(payload);
  } catch (error) {
    enqueue({ action: 'answerMonthlyReview', payload });
    console.warn('Review sync failed', error);
    setSyncState('error', { message: `Не удалось сохранить ответ: ${error?.message || 'попробуйте еще раз'}` });
    renderSyncStatus();
  }
}

function updateLocalReview(payload) {
  const rows = state.tables.monthlyReview || [];
  const index = rows.findIndex((row) => row.period === payload.period && row.item_id === payload.item_id);
  let updated = null;
  if (index >= 0) {
    rows[index] = { ...rows[index], actual_amount: payload.actual_amount, status: payload.status };
    updated = rows[index];
  }
  state.tables.monthlyReview = rows;
  if (!updated) return;

  if (updated.item_type === 'obligation') {
    state.settings.obligations = state.settings.obligations.map((item) => item.id === updated.target_id
      ? { ...item, amount: payload.actual_amount, active: payload.actual_amount > 0 }
      : item);
  }

  if (updated.item_type === 'goal' || updated.item_type === 'investment') {
    state.settings.goals = state.settings.goals.map((item) => item.id === updated.target_id
      ? { ...item, monthlyPlan: payload.actual_amount }
      : item);
  }

  if (updated.item_type === 'account') {
    state.settings.accounts = state.settings.accounts.map((item) => item.id === updated.target_id
      ? { ...item, balance: payload.actual_amount }
      : item);
  }
}

function replaceTransaction(oldId, next) {
  state.transactions = state.transactions.map((item) => item.id === oldId ? { ...next, syncStatus: next.syncStatus || 'synced' } : item);
}

function render() {
  normalizeState();
  if (!syncState.firstBootstrapLoaded) {
    renderLoadingState();
    return;
  }

  const model = calculateDashboard(state.transactions, state.settings, new Date());
  renderTabs();
  renderDashboard(model);
  renderQuickForm();
  renderCategories(model.byCategory);
  renderReview();
  renderGoals();
  renderTransactions();
  renderSyncStatus();
  renderTransactionFormState();
}

function renderLoadingState() {
  renderTabs();
  els.cycleCaption.textContent = syncState.status === 'error' ? 'Синхронизация не завершена' : 'Финансовый цикл загружается';
  els.dailyLimit.textContent = syncState.status === 'error' ? '—' : 'Загружаю…';
  els.daysLeft.textContent = syncState.status === 'error' ? 'Нажмите «Повторить»' : 'Жду Google Sheets';
  els.freeMoney.textContent = syncState.status === 'error' ? '—' : 'Загружаю…';
  els.totalSpent.textContent = syncState.status === 'error' ? '—' : 'Загружаю…';
  els.burnRate.textContent = syncState.status === 'error' ? '—' : 'Загружаю…';
  els.forecastStatus.textContent = syncState.status === 'error' ? '—' : 'Загружаю…';
  els.forecastStatus.classList.remove('danger');
  els.limitPercent.textContent = syncState.status === 'error' ? '!' : '…';
  els.limitRing.style.setProperty('--ring', '12%');
  els.limitRing.classList.toggle('danger-ring', syncState.status === 'error');
  els.quickSyncHint.textContent = syncState.status === 'error' ? 'ошибка синхронизации' : 'загружаю данные';
  els.categorySummary.textContent = syncState.status === 'error' ? 'нет данных' : 'загружаю';
  els.categoryList.innerHTML = loadingRows(3);
  els.categoryChips.innerHTML = loadingChips(4);
  els.detailChips.innerHTML = '';
  els.reviewCount.textContent = '—';
  els.reviewList.innerHTML = loadingRows(2);
  els.goalsSummary.textContent = '—';
  els.goalsList.innerHTML = loadingRows(2);
  els.transactionList.innerHTML = loadingRows(4);
  renderSyncStatus();
  renderTransactionFormState();
}

function renderTabs() {
  document.querySelectorAll('[data-tab]').forEach((button) => {
    button.classList.toggle('active', button.dataset.tab === activeTab);
  });
  document.querySelectorAll('[data-panel]').forEach((panel) => {
    panel.classList.toggle('active', panel.dataset.panel === activeTab);
  });
}

function setActiveTab(tab) {
  activeTab = tab || DEFAULT_TAB;
  renderTabs();
}

function renderDashboard(model) {
  const start = model.cycle.start.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
  const end = model.cycle.end.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
  const daily = Math.max(0, model.dailyLimit);
  const burn = Math.max(0, model.burnRate);
  const pacePercent = daily > 0 ? Math.min(140, Math.round((burn / daily) * 100)) : 100;
  const ringPercent = Math.max(6, Math.min(100, 100 - Math.min(100, pacePercent)));

  els.cycleCaption.textContent = `${start} → ${end}`;
  els.dailyLimit.textContent = toMoney(model.dailyLimit);
  els.daysLeft.textContent = `${model.cycle.daysLeft} дн. до выплаты`;
  els.freeMoney.textContent = toMoney(model.freeMoney);
  els.totalSpent.textContent = toMoney(model.totalSpent);
  els.burnRate.textContent = `${toMoney(model.burnRate)}/д`;
  els.forecastStatus.textContent = model.forecastBalance >= 0 ? toMoney(model.forecastBalance) : `−${toMoney(Math.abs(model.forecastBalance))}`;
  els.forecastStatus.classList.toggle('danger', model.forecastBalance < 0);
  els.limitPercent.textContent = pacePercent <= 100 ? 'OK' : '!';
  els.limitRing.style.setProperty('--ring', `${ringPercent}%`);
  els.limitRing.classList.toggle('danger-ring', pacePercent > 100);
}

function renderQuickForm() {
  renderTransactionTypeToggle();
  els.operationTitle.textContent = transactionType === 'income' ? 'Доход' : 'Расход';

  if (transactionType === 'income') {
    const categories = state.settings.incomeCategories || DEFAULT_SETTINGS.incomeCategories;
    els.categoryChips.innerHTML = categories.map((category) => {
      const active = category.id === selectedIncomeCategoryId;
      return `<button class="chip income-chip ${active ? 'active' : ''}" type="button" data-income-category="${escapeHtml(category.id)}">${escapeHtml(category.name)}</button>`;
    }).join('');
    els.detailChips.innerHTML = '';
    els.categoryChips.querySelectorAll('[data-income-category]').forEach((button) => {
      button.addEventListener('click', () => {
        selectedIncomeCategoryId = button.dataset.incomeCategory;
        renderQuickForm();
      });
    });
    renderTransactionFormState();
    return;
  }

  const categories = state.settings.categories;
  els.categoryChips.innerHTML = categories.map((category) => {
    const id = category.id || category.name;
    const active = id === selectedBudgetGroupId;
    return `<button class="chip ${active ? 'active' : ''}" type="button" data-category="${escapeHtml(id)}">${escapeHtml(shortName(category.name))}</button>`;
  }).join('');

  els.categoryChips.querySelectorAll('[data-category]').forEach((button) => {
    button.addEventListener('click', () => {
      selectedBudgetGroupId = button.dataset.category;
      selectedCategoryId = null;
      render();
    });
  });

  const details = detailCategoriesForGroup(selectedBudgetGroupId);
  if (!details.length) {
    els.detailChips.innerHTML = '';
    renderTransactionFormState();
    return;
  }
  selectedCategoryId = selectedCategoryId || details[0]?.category_id;
  els.detailChips.innerHTML = details.map((detail) => {
    const active = detail.category_id === selectedCategoryId;
    return `<button class="detail-chip ${active ? 'active' : ''}" type="button" data-detail="${escapeHtml(detail.category_id)}">${escapeHtml(detail.name)}</button>`;
  }).join('');
  els.detailChips.querySelectorAll('[data-detail]').forEach((button) => {
    button.addEventListener('click', () => {
      selectedCategoryId = button.dataset.detail;
      renderQuickForm();
      renderTransactionFormState();
    });
  });
  renderTransactionFormState();
}

function renderTransactionTypeToggle() {
  document.querySelectorAll('[data-transaction-type]').forEach((button) => {
    button.classList.toggle('active', button.dataset.transactionType === transactionType);
  });
}

function renderCategories(categories) {
  const totalLimit = categories.reduce((sum, category) => sum + Number(category.monthlyLimit || 0), 0);
  const totalSpent = categories.reduce((sum, category) => sum + Number(category.spent || 0), 0);
  els.categorySummary.textContent = totalLimit ? `${Math.round((totalSpent / totalLimit) * 100)}% лимита` : 'без лимита';
  els.categoryList.innerHTML = categories.map((category) => {
    const percent = category.monthlyLimit ? Math.min(120, (category.spent / category.monthlyLimit) * 100) : 0;
    const over = category.monthlyLimit && category.spent > category.monthlyLimit;
    return `<div class="category-row">
      <div class="row-head">
        <strong>${escapeHtml(category.name)}</strong>
        <span>${toMoney(category.spent)}</span>
      </div>
      <div class="bar"><i class="${over ? 'over' : ''}" style="width:${Math.min(percent, 100)}%"></i></div>
      <small>${category.monthlyLimit ? `${toMoney(Math.max(0, category.monthlyLimit - category.spent))} осталось из ${toMoney(category.monthlyLimit)}` : 'лимит не задан'}</small>
    </div>`;
  }).join('');
}

function renderReview() {
  const rows = state.tables.monthlyReview || [];
  const visible = rows.filter((row) => row.status !== 'done');
  els.reviewCount.textContent = String(visible.length);
  els.reviewList.innerHTML = visible.map((item) => {
    const expected = Number(item.expected_amount || 0);
    return `<article class="review-card" data-review="${escapeHtml(item.item_id)}">
      <div>
        <strong>${escapeHtml(item.prompt)}</strong>
        <small>${expected ? `ориентир ${toMoney(expected)}` : 'без ориентира'}</small>
      </div>
      <div class="review-action">
        <input inputmode="decimal" placeholder="0" value="${item.actual_amount ?? ''}" />
        <button class="icon-button small" type="button" aria-label="Сохранить">✓</button>
      </div>
    </article>`;
  }).join('');

  els.reviewList.querySelectorAll('.review-card').forEach((card) => {
    const item = visible.find((row) => row.item_id === card.dataset.review);
    const input = card.querySelector('input');
    const button = card.querySelector('button');
    button.addEventListener('click', () => answerReview(item, input));
  });
}

function renderGoals() {
  const goals = state.settings.goals || [];
  const planned = goals.reduce((sum, goal) => sum + Number(goal.monthlyPlan || 0), 0);
  els.goalsSummary.textContent = planned ? `${toMoney(planned)} в плане` : 'без плана';
  els.goalsList.innerHTML = goals.map((goal) => {
    const percent = goal.target ? Math.min(100, (Number(goal.current || 0) / Number(goal.target || 1)) * 100) : 0;
    return `<div class="goal-row">
      <div class="row-head">
        <strong>${escapeHtml(goal.name)}</strong>
        <span>${goal.target ? `${Math.round(percent)}%` : toMoney(goal.current)}</span>
      </div>
      ${goal.target ? `<div class="bar soft"><i style="width:${percent}%"></i></div>` : ''}
      <small>${goal.target ? `${toMoney(goal.current)} из ${toMoney(goal.target)}` : 'гибкая цель'}</small>
    </div>`;
  }).join('');
}

function renderTransactions() {
  const items = state.transactions.slice(0, 30);
  els.transactionList.innerHTML = items.map((transaction) => {
    const pending = transaction.syncStatus === 'pending';
    const isIncome = transaction.type === 'income';
    return `<article class="tx-row ${transaction.reviewNeeded ? 'needs-review' : ''} ${isIncome ? 'income' : ''}" data-id="${escapeHtml(transaction.id)}">
      <div>
        <strong>${escapeHtml(transaction.description || transaction.category)}</strong>
        <small>${formatTransactionDate(transaction.date)} · ${escapeHtml(transaction.category)}${isIncome ? ' · доход' : ''}${pending ? ' · очередь' : ''}</small>
      </div>
      <div class="tx-side">
        <b>${formatTransactionAmount(transaction)}</b>
        <button class="text-button" type="button">Править</button>
      </div>
    </article>`;
  }).join('');

  els.transactionList.querySelectorAll('.tx-row').forEach((row) => {
    row.querySelector('button').addEventListener('click', () => openEdit(row.dataset.id));
  });
}

function renderSyncStatus() {
  const pending = queueItems().length;
  renderSyncButton();

  if (syncState.status === 'error') {
    els.syncStatus.hidden = false;
    els.syncStatus.innerHTML = `<span>${escapeHtml(syncState.message || 'Ошибка синхронизации')}</span><button class="text-button" type="button" data-sync-retry>Повторить</button>`;
    els.syncStatus.querySelector('[data-sync-retry]').addEventListener('click', () => syncFromSheets({ mode: syncState.firstBootstrapLoaded ? 'refreshing' : 'loading' }));
    els.quickSyncHint.textContent = 'ошибка синхронизации';
    return;
  }

  if (BUSY_SYNC_STATES.has(syncState.status)) {
    els.syncStatus.hidden = false;
    els.syncStatus.textContent = syncState.message || syncStatusText(syncState.status);
    els.quickSyncHint.textContent = syncStatusText(syncState.status).toLowerCase();
    return;
  }

  if (pending) {
    els.syncStatus.hidden = false;
    els.syncStatus.textContent = `${pending} в очереди`;
    els.quickSyncHint.textContent = `${pending} в очереди`;
    return;
  }

  els.syncStatus.hidden = true;
  els.quickSyncHint.textContent = syncState.lastUpdatedAt ? `Обновлено ${formatTime(syncState.lastUpdatedAt)}` : 'синхронизировано';
}

function renderSyncButton() {
  const busy = BUSY_SYNC_STATES.has(syncState.status);
  els.syncBtn.disabled = busy;
  els.syncBtn.classList.toggle('is-loading', busy);

  if (busy) {
    const label = syncState.status === 'refreshing' ? 'Обновляю…' : syncState.status === 'saving' ? 'Сохраняю…' : syncStatusText(syncState.status);
    els.syncBtn.innerHTML = `<span class="button-spinner" aria-hidden="true"></span><span>${escapeHtml(label)}</span>`;
    els.syncBtn.title = label;
    els.syncBtn.setAttribute('aria-label', label);
    return;
  }

  if (syncState.status === 'error') {
    els.syncBtn.textContent = 'Повторить';
    els.syncBtn.title = syncState.message || 'Повторить синхронизацию';
    els.syncBtn.setAttribute('aria-label', 'Повторить синхронизацию');
    return;
  }

  if (syncState.lastUpdatedAt) {
    const label = `Обновлено ${formatTime(syncState.lastUpdatedAt)}`;
    els.syncBtn.textContent = label;
    els.syncBtn.title = 'Обновить данные';
    els.syncBtn.setAttribute('aria-label', 'Обновить данные');
    return;
  }

  els.syncBtn.textContent = '↻';
  els.syncBtn.title = 'Синхронизация';
  els.syncBtn.setAttribute('aria-label', 'Синхронизация');
}

function renderTransactionFormState() {
  const disabled = savingTransaction || !syncState.firstBootstrapLoaded;
  [els.amount, els.description, els.addTransactionBtn].forEach((element) => {
    element.disabled = disabled;
    element.setAttribute('aria-disabled', String(disabled));
  });
  els.categoryChips.querySelectorAll('button').forEach((button) => {
    button.disabled = disabled;
    button.setAttribute('aria-disabled', String(disabled));
  });
  els.detailChips.querySelectorAll('button').forEach((button) => {
    button.disabled = disabled;
    button.setAttribute('aria-disabled', String(disabled));
  });
  document.querySelectorAll('[data-transaction-type]').forEach((button) => {
    button.disabled = disabled;
    button.setAttribute('aria-disabled', String(disabled));
  });
  els.addTransactionBtn.textContent = savingTransaction ? 'Сохраняю…' : transactionType === 'income' ? 'Добавить доход' : 'Добавить';
}

function setSyncState(status, patch = {}) {
  syncState = { ...syncState, status, ...patch };
}

function syncStatusText(status) {
  return {
    idle: 'Синхронизировано',
    loading: 'Загружаю данные',
    saving: 'Сохраняю расход',
    refreshing: 'Обновляю данные',
    success: 'Обновлено',
    error: 'Ошибка синхронизации'
  }[status] || 'Синхронизация';
}

function setTransactionType(type) {
  transactionType = type === 'income' ? 'income' : 'expense';
  renderQuickForm();
}

function openEdit(id) {
  const tx = state.transactions.find((item) => item.id === id);
  if (!tx) return;
  els.editId.value = tx.id;
  els.editAmount.value = String(tx.amount || '');
  els.editDescription.value = tx.description || '';
  els.editCategory.innerHTML = state.settings.categories.map((category) => {
    const idValue = category.id || category.name;
    return `<option value="${escapeHtml(idValue)}">${escapeHtml(category.name)}</option>`;
  }).join('');
  els.editCategory.value = tx.budgetGroupId || categoryIdFromName(tx.category) || selectedBudgetGroupId;
  els.editDialog.showModal();
}

function selectedBudgetGroup() {
  return categoryById(selectedBudgetGroupId) || state.settings.categories[0] || { id: 'other', name: 'Прочее' };
}

function selectedIncomeCategory() {
  return (state.settings.incomeCategories || DEFAULT_SETTINGS.incomeCategories).find((category) => category.id === selectedIncomeCategoryId)
    || DEFAULT_SETTINGS.incomeCategories.find((category) => category.id === 'income_other')
    || { id: 'income_other', name: 'Прочий доход', budgetGroupId: 'income' };
}

function selectedDetailCategory() {
  return detailCategoriesForGroup(selectedBudgetGroupId).find((detail) => detail.category_id === selectedCategoryId);
}

function defaultDetailForGroup(groupId) {
  return detailCategoriesForGroup(groupId)[0] || null;
}

function detailCategoriesForGroup(groupId) {
  const rows = state.tables.categories || [];
  return rows.filter((category) => category.budget_group_id === groupId && category.active !== false);
}

function categoryById(id) {
  return state.settings.categories.find((category) => category.id === id || category.name === id);
}

function categoryIdFromName(name) {
  return state.settings.categories.find((category) => category.name === name)?.id;
}

function defaultCashflowAccount() {
  return state.settings.accounts.find((account) => account.includeInCashflow !== false)
    || state.settings.accounts[0]
    || { id: 'vtb_main', name: 'ВТБ' };
}

function shortName(name) {
  return String(name)
    .replace('Еда вне дома и досуг', 'Еда вне дома')
    .replace('Бензин и платные дороги', 'Авто / дороги')
    .replace('Одежда/техника/хобби/подарки', 'Покупки')
    .replace('Связь/подписки', 'Связь')
    .replace('Текущие расходы авто', 'Авто')
    .replace('Переводы людям', 'Переводы')
    .replace('Резерв месяца', 'Резерв');
}

function parseAmount(value) {
  return Number(String(value || '').replace(/\s/g, '').replace(',', '.'));
}

function formatTransactionDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatTransactionAmount(transaction) {
  const amount = toMoney(transaction.amount);
  return transaction.type === 'income' ? `+${amount}` : amount;
}

function formatTime(value) {
  const date = value instanceof Date ? value : new Date(value);
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

function userSyncError(error) {
  return `Ошибка синхронизации: ${error?.message || 'не удалось получить данные'}`;
}

function userSaveError(error, type = 'expense') {
  return `Не удалось сохранить ${type === 'income' ? 'доход' : 'расход'}: ${error?.message || 'попробуйте еще раз'}`;
}

function userSavedButSyncError(error, type = 'expense') {
  return `${type === 'income' ? 'Доход' : 'Расход'} сохранен, но данные не обновились: ${error?.message || 'нажмите «Повторить»'}`;
}

function loadingRows(count) {
  return Array.from({ length: count }, () => '<div class="skeleton-row" aria-hidden="true"></div>').join('');
}

function loadingChips(count) {
  return Array.from({ length: count }, () => '<span class="skeleton-chip" aria-hidden="true"></span>').join('');
}

function exportJson() {
  const blob = new Blob([JSON.stringify(state, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `atlas-finance-backup-${new Date().toISOString().slice(0, 10)}.json`;
  a.click();
  URL.revokeObjectURL(url);
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"]/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[m]));
}
