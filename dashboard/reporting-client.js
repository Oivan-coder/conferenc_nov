(() => {
  'use strict';

  const cfg = window.DASHBOARD_REPORTING_CONFIG || {};
  const speakerNames = Array.isArray(cfg.speakerNames) ? cfg.speakerNames : [];
  const aliases = cfg.organizationAliases && typeof cfg.organizationAliases === 'object' ? cfg.organizationAliases : {};
  const organizationMeta = cfg.organizationMeta && typeof cfg.organizationMeta === 'object' ? cfg.organizationMeta : {};

  const norm = (value) => String(value || '')
    .trim()
    .toLowerCase()
    .replace(/ё/g, 'е')
    .replace(/[«»“”"]/g, '')
    .replace(/\s+/g, ' ')
    .replace(/\s*([.,])\s*/g, '$1')
    .trim();

  const compactFallback = (value, maxLength = 52) => {
    let display = String(value || '').trim().replace(/\s+/g, ' ');
    if (!display) return '';
    display = display.replace(/^(?:ГБУЗ\s+МО|ГКУ\s+МО|СПБ\s+ГБУЗ|ФГАОУ\s+ВО|ФГБОУ\s+ВО|ФГБУЗ|ФГБУ|ФГАУ|ГБУЗС|ГБУЗ|ГКУЗ|ГКУ|ГБУ|ГАУЗ|ГАУ|ГУЗ|БУЗ|БУ|МУЗ|ООО|АО|ПАО|ЗАО)\s+/i, '');
    display = display.replace(/^[\s"«»]+|[\s"«»]+$/g, '');
    display = display.replace(/\s+Минздрава\s+(?:России|РФ)$/i, '').trim();
    if (display.length <= maxLength) return display;
    let cut = display.slice(0, Math.max(1, maxLength - 1));
    const lastSpace = cut.lastIndexOf(' ');
    if (lastSpace >= Math.floor(maxLength * 0.65)) cut = cut.slice(0, lastSpace);
    return cut.replace(/[ ,.;:\-–—]+$/g, '') + '…';
  };

  const inferCategory = (value) => {
    const raw = String(value || '').trim();
    const n = norm(raw);
    if (/^\s*(ооо|ао|пао|зао|ип)\b/i.test(raw)
      || /^\s*гк\s*[«" ]/i.test(raw)
      || n.includes('лабораторная служба хеликс')
      || /\bниармедик\b/i.test(raw)
      || n === 'формит') {
      return { key: 'private', label: 'Частная / коммерческая' };
    }
    if (/(^|[\s«"(])(?:гбузс|гбуз|гкуз|гку|гбу|гауз|гау|гуз|буз|муз|фгбу|фгбуз|фгау|фгаоу|фгбоу|фбун|фбуз|фкуз|фку|фгку|гнц|гну|бу)(?=$|[\s«"(\-])/i.test(raw)) {
      return { key: 'government', label: 'Государственная' };
    }
    for (const marker of ['министерство здравоохранения', 'минздрав', 'минздрава', 'фмба', 'рманпо', 'пспбгму', 'рниму', 'ростгму', 'нмиц', 'мкнц', 'рнпц', 'црб', 'гкб', 'сгб', 'бсмп', 'квд', 'в/ч']) {
      if (n.includes(marker)) return { key: 'government', label: 'Государственная' };
    }
    return { key: 'unknown', label: 'Не определено' };
  };

  const orgInfo = (value) => {
    const raw = String(value || '').trim();
    const key = norm(raw);
    const known = organizationMeta[key];
    if (known) {
      return {
        raw,
        display: String(known.display || raw),
        category: { key: String(known.category || 'unknown'), label: String(known.label || 'Не определено') },
        known: true,
      };
    }

    const aliased = aliases[key];
    if (aliased) {
      const meta = organizationMeta[norm(aliased)];
      if (meta) {
        return {
          raw,
          display: String(meta.display || aliased),
          category: { key: String(meta.category || 'unknown'), label: String(meta.label || 'Не определено') },
          known: true,
        };
      }
    }

    return {
      raw,
      display: compactFallback(raw),
      category: inferCategory(raw),
      known: false,
    };
  };

  const categoryTag = (category) => {
    const span = document.createElement('span');
    span.className = 'tag';
    span.textContent = category.label;
    if (category.key === 'government') {
      span.style.background = '#e8f2ff'; span.style.color = '#315c8f';
    } else if (category.key === 'private') {
      span.style.background = '#f3edf9'; span.style.color = '#6a4b86';
    } else if (category.key === 'organizer') {
      span.style.background = '#e5f4ea'; span.style.color = '#286345';
    } else {
      span.style.background = '#fff2d8'; span.style.color = '#85600b';
    }
    return span;
  };

  const reviewBadge = () => {
    const span = document.createElement('span');
    span.textContent = 'требует проверки';
    span.style.display = 'inline-block';
    span.style.marginTop = '5px';
    span.style.padding = '3px 6px';
    span.style.borderRadius = '999px';
    span.style.fontSize = '10px';
    span.style.fontWeight = '700';
    span.style.background = '#fff2d8';
    span.style.color = '#85600b';
    return span;
  };

  function enhancePeopleTable() {
    const table = document.getElementById('people');
    if (!table) return;

    const speakerSet = new Set(speakerNames.map(norm));
    const filters = table.closest('.panel')?.querySelector('.filters');

    let role = document.getElementById('role');
    if (filters && !role) {
      role = document.createElement('select');
      role.id = 'role';
      role.innerHTML = '<option value="">Все роли</option><option value="speaker">Докладчики</option><option value="participant">Не докладчики</option>';
      filters.appendChild(role);
    }

    let categoryFilter = document.getElementById('orgCategory');
    if (filters && !categoryFilter) {
      categoryFilter = document.createElement('select');
      categoryFilter.id = 'orgCategory';
      categoryFilter.innerHTML = '<option value="">Все категории</option><option value="organizer">Организаторы</option><option value="government">Государственные</option><option value="private">Частные / коммерческие</option><option value="unknown">Не определено</option>';
      filters.appendChild(categoryFilter);
    }

    let normalizationFilter = document.getElementById('orgNormalization');
    if (filters && !normalizationFilter) {
      normalizationFilter = document.createElement('select');
      normalizationFilter.id = 'orgNormalization';
      normalizationFilter.innerHTML = '<option value="">Все названия</option><option value="known">Нормализованы</option><option value="review">Требуют проверки</option>';
      filters.appendChild(normalizationFilter);
    }

    const head = table.querySelector('thead tr');
    if (head && !head.querySelector('[data-category-head]')) {
      const th = document.createElement('th');
      th.dataset.categoryHead = '1';
      th.textContent = 'Категория';
      head.insertBefore(th, head.children[2] || null);
    }
    if (head && !head.querySelector('[data-role-head]')) {
      const th = document.createElement('th');
      th.dataset.roleHead = '1';
      th.textContent = 'Роль';
      const source = [...head.children].find((x) => x.textContent.trim() === 'Источник');
      head.insertBefore(th, source || null);
    }

    const rows = [...table.querySelectorAll('tbody tr')];
    let speakers = 0;
    let others = 0;
    let invitedSpeakers = 0;

    rows.forEach((row) => {
      if (row.dataset.reportingEnhanced === '1') return;
      row.dataset.reportingEnhanced = '1';

      const name = row.querySelector('td strong')?.textContent || '';
      const isSpeaker = speakerSet.has(norm(name));
      row.dataset.role = isSpeaker ? 'speaker' : 'participant';
      if (row.dataset.status === 'confirmed') {
        if (isSpeaker) speakers += 1; else others += 1;
      }

      const orgCell = row.children[1];
      const orgStrong = orgCell?.querySelector('strong');
      const rawOrg = orgStrong?.textContent || '';
      const info = orgInfo(rawOrg);
      row.dataset.category = info.category.key;
      row.dataset.normalized = info.known ? 'known' : 'review';

      if (orgStrong && info.display) {
        orgStrong.textContent = info.display;
        orgStrong.title = info.display === rawOrg ? rawOrg : `Введено участником: ${rawOrg}`;
        row.dataset.search = `${row.dataset.search || ''} ${norm(info.display)} ${norm(rawOrg)}`.trim();
      }
      if (orgCell && !info.known) {
        orgCell.appendChild(document.createElement('br'));
        orgCell.appendChild(reviewBadge());
      }

      const categoryCell = document.createElement('td');
      categoryCell.appendChild(categoryTag(info.category));
      if (orgCell) orgCell.insertAdjacentElement('afterend', categoryCell);

      const cells = [...row.children];
      const sourceCell = cells.find((td) => /Публичная регистрация|По приглашению|Тест/.test(td.textContent));
      if (isSpeaker && sourceCell && /По приглашению/.test(sourceCell.textContent)) invitedSpeakers += 1;
      const roleCell = document.createElement('td');
      roleCell.innerHTML = isSpeaker
        ? '<span class="tag" style="background:#e9eefc;color:#3d5791">Докладчик</span>'
        : '<span class="muted">Участник</span>';
      if (sourceCell) row.insertBefore(roleCell, sourceCell); else row.appendChild(roleCell);
    });

    const panel = table.closest('.panel');
    const meta = panel?.querySelector('.panel-head .muted');
    const reviewCount = rows.filter((row) => row.dataset.normalized === 'review').length;
    if (meta) meta.textContent = `${rows.length} записей · докладчики ${speakers} · остальные ${others}${reviewCount ? ` · проверить МО ${reviewCount}` : ''}`;

    const q = document.getElementById('q');
    const fmt = document.getElementById('fmt');
    const status = document.getElementById('sts');

    const apply = () => {
      const text = (q?.value || '').trim().toLowerCase();
      rows.forEach((row) => {
        const okText = !text || (row.dataset.search || '').includes(text);
        const okF = !fmt?.value || row.dataset.format === fmt.value;
        const okS = !status?.value || row.dataset.status === status.value;
        const okR = !role?.value || row.dataset.role === role.value;
        const okC = !categoryFilter?.value || row.dataset.category === categoryFilter.value;
        const okN = !normalizationFilter?.value || row.dataset.normalized === normalizationFilter.value;
        row.hidden = !(okText && okF && okS && okR && okC && okN);
      });
    };

    [q, fmt, status, role, categoryFilter, normalizationFilter].filter(Boolean).forEach((el) => el.addEventListener('input', apply));

    const brief = document.getElementById('briefText');
    if (brief && speakers + others > 0) {
      brief.textContent += `\n\nРоли участников:\n• докладчики — ${speakers}${invitedSpeakers ? ` (по приглашению — ${invitedSpeakers})` : ''}\n• остальные участники — ${others}`;
      if (reviewCount) brief.textContent += `\n• названия организаций требуют проверки — ${reviewCount}`;
    }
  }

  function mergeOrganizationTables() {
    const table = document.querySelector('.org-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const groups = new Map();
    [...tbody.querySelectorAll('tr')].forEach((row) => {
      const cells = [...row.children];
      const raw = cells[0]?.querySelector('strong')?.textContent || cells[0]?.textContent || '';
      const info = orgInfo(raw);
      const key = norm(info.display);
      const values = cells.slice(1).map((cell) => Number.parseInt(cell.textContent.trim(), 10) || 0);
      if (!groups.has(key)) groups.set(key, { organization: info.display, category: info.category, known: info.known, values: new Array(values.length).fill(0) });
      const group = groups.get(key);
      values.forEach((value, index) => { group.values[index] += value; });
      group.known = group.known || info.known;
    });

    const entries = [...groups.values()].sort((a, b) => (b.values[0] - a.values[0]) || a.organization.localeCompare(b.organization, 'ru'));

    const head = table.querySelector('thead tr');
    if (head && !head.querySelector('[data-category-head]')) {
      const th = document.createElement('th');
      th.dataset.categoryHead = '1';
      th.textContent = 'Категория';
      head.insertBefore(th, head.children[1] || null);
    }

    tbody.innerHTML = '';
    entries.forEach((entry) => {
      const tr = document.createElement('tr');
      const orgTd = document.createElement('td');
      const strong = document.createElement('strong');
      strong.textContent = entry.organization;
      orgTd.appendChild(strong);
      if (!entry.known) {
        orgTd.appendChild(document.createElement('br'));
        orgTd.appendChild(reviewBadge());
      }
      tr.appendChild(orgTd);

      const categoryTd = document.createElement('td');
      categoryTd.appendChild(categoryTag(entry.category));
      tr.appendChild(categoryTd);

      entry.values.forEach((value) => {
        const td = document.createElement('td');
        td.textContent = String(value);
        tr.appendChild(td);
      });
      tbody.appendChild(tr);
    });

    const orgList = document.querySelector('.org-list');
    if (orgList) {
      const confirmed = entries.filter((entry) => entry.values[0] > 0).slice(0, 18);
      const max = Math.max(1, ...confirmed.map((entry) => entry.values[0]));
      orgList.innerHTML = '';
      confirmed.forEach((entry) => {
        const row = document.createElement('div');
        row.className = 'org-row';
        row.title = entry.organization;
        const name = document.createElement('div');
        name.className = 'org-name';
        name.textContent = entry.organization;
        const bar = document.createElement('div');
        bar.className = 'bar';
        const fill = document.createElement('i');
        fill.style.width = `${Math.round(entry.values[0] / max * 100)}%`;
        bar.appendChild(fill);
        const num = document.createElement('div');
        num.className = 'org-num';
        num.textContent = String(entry.values[0]);
        row.append(name, bar, num);
        orgList.appendChild(row);
      });
      const meta = orgList.closest('.panel')?.querySelector('.panel-head .muted');
      if (meta) meta.textContent = `${entries.filter((entry) => entry.values[0] > 0).length} организаций`;
    }
  }

  enhancePeopleTable();
  mergeOrganizationTables();
})();
