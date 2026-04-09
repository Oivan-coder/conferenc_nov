async function loadReports() {
  const container = document.getElementById('reports');
  const searchInput = document.getElementById('searchInput');
  const yearFilter = document.getElementById('yearFilter');
  const template = document.getElementById('reportCardTemplate');

  try {
    const response = await fetch('./reports.json', { cache: 'no-store' });
    const reports = await response.json();

    const years = [...new Set(reports.map(item => String(item.year)))].sort((a, b) => b.localeCompare(a));
    years.forEach(year => {
      const option = document.createElement('option');
      option.value = year;
      option.textContent = year;
      yearFilter.appendChild(option);
    });

    function render() {
      const query = searchInput.value.trim().toLowerCase();
      const year = yearFilter.value;

      const filtered = reports.filter(item => {
        const haystack = [
          item.title,
          item.period,
          item.description,
          ...(item.tags || [])
        ].join(' ').toLowerCase();

        const queryMatch = !query || haystack.includes(query);
        const yearMatch = year === 'all' || String(item.year) === year;
        return queryMatch && yearMatch;
      });

      container.innerHTML = '';

      if (!filtered.length) {
        container.innerHTML = '<div class="empty-state">По вашему запросу отчеты не найдены.</div>';
        return;
      }

      filtered.forEach(item => {
        const node = template.content.firstElementChild.cloneNode(true);
        node.querySelector('.report-card__badge').textContent = item.badge || 'Отчет';
        node.querySelector('.report-card__date').textContent = item.publishDate || '';
        node.querySelector('.report-card__title').textContent = item.title;
        node.querySelector('.report-card__period').textContent = item.period;
        node.querySelector('.report-card__desc').textContent = item.description;

        const link = node.querySelector('.report-link');
        link.href = item.file;

        const tagsWrap = node.querySelector('.report-card__tags');
        (item.tags || []).forEach(tag => {
          const tagNode = document.createElement('span');
          tagNode.textContent = tag;
          tagsWrap.appendChild(tagNode);
        });

        container.appendChild(node);
      });
    }

    searchInput.addEventListener('input', render);
    yearFilter.addEventListener('change', render);
    render();
  } catch (error) {
    console.error('Не удалось загрузить reports.json', error);
    container.innerHTML = '<div class="empty-state">Не удалось загрузить список отчетов. Проверьте файл <code>reports.json</code>.</div>';
  }
}

loadReports();
