function parseRuDate(value) {
  const match = String(value || '').match(/(\d{2})\.(\d{2})\.(\d{4})/);
  if (!match) return 0;
  const [, day, month, year] = match;
  return new Date(Number(year), Number(month) - 1, Number(day)).getTime();
}

function reorderTransactionsOldestFirst() {
  const list = document.getElementById('transactionList');
  if (!list || list.dataset.reordering === '1') return;

  const rows = Array.from(list.querySelectorAll('.tx-row'));
  if (rows.length < 2) return;

  const ordered = rows
    .map((row, index) => {
      const small = row.querySelector('small')?.textContent || '';
      return { row, index, time: parseRuDate(small) };
    })
    .sort((a, b) => (a.time - b.time) || (a.index - b.index))
    .map((item) => item.row);

  const changed = ordered.some((row, index) => row !== rows[index]);
  if (!changed) return;

  list.dataset.reordering = '1';
  ordered.forEach((row) => list.appendChild(row));
  delete list.dataset.reordering;
}

function patchNegativeDailyLimit() {
  const dailyLimit = document.getElementById('dailyLimit');
  const limitPercent = document.getElementById('limitPercent');
  const limitRing = document.getElementById('limitRing');
  const label = document.querySelector('.hero-copy .label');
  const text = dailyLimit?.textContent || '';
  const isNegative = text.trim().startsWith('-') || text.trim().startsWith('−');

  if (!dailyLimit || !limitPercent || !limitRing || !label) return;

  if (isNegative) {
    label.textContent = 'Нужно сократить';
    limitPercent.textContent = '!';
    limitRing.classList.add('danger-ring');
    return;
  }

  label.textContent = 'Можно сегодня';
}

function applyUiPatches() {
  reorderTransactionsOldestFirst();
  patchNegativeDailyLimit();
}

const observer = new MutationObserver(() => {
  requestAnimationFrame(applyUiPatches);
});

observer.observe(document.body, {
  childList: true,
  subtree: true,
  characterData: true
});

window.addEventListener('load', applyUiPatches);
requestAnimationFrame(applyUiPatches);
