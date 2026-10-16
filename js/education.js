/* Образовательная библиотека. Источник карточек: /education/materials.json */
(function () {
  'use strict';
  const featuredRoot = document.getElementById('eduFeatured');
  const listRoot = document.getElementById('eduList');
  const visualsRoot = document.getElementById('eduVisuals');
  const resultCount = document.getElementById('eduResultCount');
  const emptyRoot = document.getElementById('eduEmpty');
  const searchInput = document.getElementById('eduSearch');
  const filterButtons = Array.from(document.querySelectorAll('[data-edu-filter]'));
  if (!featuredRoot || !listRoot || !visualsRoot) return;

  const CATEGORIES = {
    diagnostics: 'Лабораторные исследования',
    processes: 'Организация работы',
    digital: 'Цифровые инструменты'
  };
  let topics = [];
  let activeFilter = 'all';
  const escapeHtml = (str) => String(str == null ? '' : str).replace(/[&<>"']/g, c => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[c]));
  const normal = (str) => String(str || '').toLocaleLowerCase('ru-RU').replace(/ё/g, 'е');
  const formatSize = (bytes) => bytes > 1024 * 1024
    ? (bytes / 1024 / 1024).toLocaleString('ru-RU', { maximumFractionDigits: 1 }) + ' МБ'
    : Math.max(1, Math.round(bytes / 1024)) + ' КБ';
  const fileHref = name => '/education/files/' + encodeURIComponent(name);

  function fileLink(file) {
    const isPdf = (file.format || '').toUpperCase() === 'PDF';
    const label = isPdf ? 'Открыть PDF' : 'Скачать ' + (file.label || 'материал');
    return '<a class="edu-file-link" href="' + fileHref(file.name) + '"' +
      (isPdf ? ' target="_blank" rel="noopener noreferrer"' : ' download') +
      ' aria-label="' + escapeHtml(label) + '">' +
      '<span>' + escapeHtml(label) + '</span><b>' + escapeHtml(file.format) + '</b></a>';
  }

  function renderFeatured(topic) {
    featuredRoot.innerHTML = topic ? '<article class="edu-featured">' +
      '<div class="edu-featured__body">' +
      '<div class="edu-featured__meta"><span aria-hidden="true"></span> Комплект материалов</div>' +
      '<h3>' + escapeHtml(topic.title) + '</h3>' +
      '<p>' + escapeHtml(topic.subtitle) + '</p>' +
      '<div class="edu-file-actions">' + topic.files.map(fileLink).join('') + '</div>' +
      '</div><div class="edu-featured__aside" aria-hidden="true"><small>Учебный материал / РЦЛСМО</small>' +
      '<strong>' + String(topic.files.length).padStart(2, '0') + '</strong></div></article>' : '';
  }

  function renderTopic(topic, index) {
    const first = topic.files[0];
    const isPdf = (first.format || '').toUpperCase() === 'PDF';
    return '<article class="edu-topic">' +
      '<span class="edu-topic__index">' + String(index + 1).padStart(2, '0') + '</span>' +
      '<div class="edu-topic__heading"><span class="edu-topic__category">' +
      escapeHtml(CATEGORIES[topic.category] || 'Материалы') + '</span>' +
      '<h3>' + escapeHtml(topic.title) + '</h3>' +
      '<p class="edu-topic__subtitle">' + escapeHtml(topic.subtitle) + '</p></div>' +
      '<div class="edu-topic__action"><em>' + escapeHtml(first.format) + '</em>' +
      '<a href="' + fileHref(first.name) + '"' + (isPdf ? ' target="_blank" rel="noopener noreferrer"' : ' download') +
      '>' + (isPdf ? 'Открыть материал' : 'Скачать материал') + '</a></div>' +
      '<span class="edu-topic__arrow" aria-hidden="true">↗</span></article>';
  }

  function renderVisual(item) {
    const href = fileHref(item.name);
    return '<a class="edu-visual" href="' + href + '" target="_blank" rel="noopener noreferrer" ' +
      'aria-label="Открыть: ' + escapeHtml(item.title) + '">' +
      '<div class="edu-visual__image"><img src="' + href + '" alt="' + escapeHtml(item.title) +
      '" loading="lazy" decoding="async"></div>' +
      '<div class="edu-visual__footer"><div><strong>' + escapeHtml(item.title) +
      '</strong><small>' + escapeHtml(item.format) + ' · ' + formatSize(item.size) +
      '</small></div><span aria-hidden="true">↗</span></div></a>';
  }

  function update() {
    const q = normal(searchInput.value.trim());
    const visible = topics.filter(t => (activeFilter === 'all' || activeFilter === t.category) &&
      (!q || normal([t.title, t.subtitle, CATEGORIES[t.category], ...t.files.map(f => f.name)].join(' ')).includes(q)));
    const featured = visible.find(t => t.featured);
    const ordinary = visible.filter(t => t !== featured);
    renderFeatured(featured);
    listRoot.innerHTML = ordinary.map((item, index) => renderTopic(item, index)).join('');
    emptyRoot.hidden = visible.length !== 0;
    const fileCount = visible.reduce((count, t) => count + t.files.length, 0);
    resultCount.textContent = 'Тем: ' + visible.length + '  ·  Документов: ' + fileCount;
  }

  filterButtons.forEach(button => button.addEventListener('click', () => {
    activeFilter = button.dataset.eduFilter;
    filterButtons.forEach(other => {
      const current = other === button;
      other.classList.toggle('is-active', current);
      other.setAttribute('aria-pressed', String(current));
    });
    update();
  }));
  searchInput.addEventListener('input', update);

  fetch('/education/materials.json', { cache: 'no-store' })
    .then(response => {
      if (!response.ok) throw new Error('Catalog HTTP ' + response.status);
      return response.json();
    })
    .then(data => {
      topics = Array.isArray(data.topics) ? data.topics : [];
      const visuals = Array.isArray(data.visuals) ? data.visuals : [];
      visualsRoot.innerHTML = visuals.map(renderVisual).join('');
      if (!visuals.length) document.getElementById('handouts').hidden = true;
      update();
    })
    .catch(error => {
      console.error('Не удалось загрузить учебные материалы', error);
      resultCount.textContent = 'Не удалось загрузить каталог материалов. Обновите страницу.';
      emptyRoot.hidden = true;
      document.getElementById('handouts').hidden = true;
    });
})();
