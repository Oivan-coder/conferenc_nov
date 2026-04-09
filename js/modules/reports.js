// ===== REPORTS HUB =====

function formatDisplayDate(dateString) {
    if (!dateString) {
        return '—';
    }

    const isoMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(dateString);
    if (isoMatch) {
        return `${isoMatch[3]}.${isoMatch[2]}.${isoMatch[1]}`;
    }

    return dateString;
}

function normalizeReports(rawReports) {
    return (Array.isArray(rawReports) ? rawReports : [])
        .map((item) => {
            const tags = Array.isArray(item.tags) ? item.tags.filter(Boolean) : [];
            const sortDate = item.sortDate || item.publishDate || '';
            return {
                title: item.title || 'Отчет без названия',
                period: item.period || 'Период уточняется',
                description: item.description || 'Описание отчета не указано.',
                file: item.file || '#',
                publishDate: item.publishDate || '',
                badge: item.badge || 'Отчет',
                year: item.year || '',
                tags,
                sortDate,
                featured: Boolean(item.featured)
            };
        })
        .sort((a, b) => String(b.sortDate).localeCompare(String(a.sortDate)));
}

function createReportCard(report) {
    const article = document.createElement('article');
    article.className = 'report-card';

    const top = document.createElement('div');
    top.className = 'report-card__top';

    const badge = document.createElement('span');
    badge.className = 'report-card__badge';
    badge.textContent = report.badge;

    const date = document.createElement('span');
    date.className = 'report-card__date';
    date.textContent = formatDisplayDate(report.publishDate);

    top.appendChild(badge);
    top.appendChild(date);

    const title = document.createElement('h3');
    title.className = 'report-card__title';
    title.textContent = report.title;

    const period = document.createElement('p');
    period.className = 'report-card__period';
    period.textContent = report.period;

    const description = document.createElement('p');
    description.className = 'report-card__desc';
    description.textContent = report.description;

    const tags = document.createElement('div');
    tags.className = 'report-card__tags';
    report.tags.forEach((tag) => {
        const chip = document.createElement('span');
        chip.textContent = tag;
        tags.appendChild(chip);
    });

    const actions = document.createElement('div');
    actions.className = 'report-card__actions';

    const link = document.createElement('a');
    link.className = 'cta-button primary';
    link.href = report.file;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Открыть отчет';

    actions.appendChild(link);
    article.appendChild(top);
    article.appendChild(title);
    article.appendChild(period);
    article.appendChild(description);
    if (report.tags.length) {
        article.appendChild(tags);
    }
    article.appendChild(actions);
    return article;
}

function renderFeaturedReport(report) {
    const host = document.getElementById('featuredReport');
    if (!host) {
        return;
    }

    if (!report) {
        host.classList.remove('is-visible');
        host.textContent = '';
        return;
    }

    host.classList.add('is-visible');
    host.textContent = '';

    const article = document.createElement('article');
    article.className = 'reports-featured-card';

    const meta = document.createElement('div');
    meta.className = 'reports-featured-card__meta';

    const badge = document.createElement('span');
    badge.className = 'reports-featured-card__badge';
    badge.textContent = report.badge || 'Свежий отчет';

    const date = document.createElement('span');
    date.textContent = formatDisplayDate(report.publishDate);

    meta.appendChild(badge);
    meta.appendChild(date);

    const title = document.createElement('h2');
    title.className = 'reports-featured-card__title';
    title.textContent = report.title;

    const description = document.createElement('p');
    description.className = 'reports-featured-card__desc';
    description.textContent = report.description;

    const link = document.createElement('a');
    link.className = 'cta-button primary';
    link.href = report.file;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Открыть актуальный отчет';

    article.appendChild(meta);
    article.appendChild(title);
    article.appendChild(description);
    article.appendChild(link);
    host.appendChild(article);
}

function setMetrics(reports) {
    const total = document.querySelector('[data-report-metric="total"]');
    const years = document.querySelector('[data-report-metric="years"]');
    const latest = document.querySelector('[data-report-metric="latest"]');

    const uniqueYears = new Set(reports.map((report) => String(report.year)).filter(Boolean));
    if (total) {
        total.textContent = String(reports.length);
    }
    if (years) {
        years.textContent = String(uniqueYears.size);
    }
    if (latest) {
        latest.textContent = reports[0] ? formatDisplayDate(reports[0].publishDate) : '—';
    }
}

function readEmbeddedReports() {
    const node = document.getElementById('reportsData');
    if (!node) {
        return [];
    }

    try {
        return JSON.parse(node.textContent || '[]');
    } catch (error) {
        if (window.debugLog) {
            window.debugLog('Не удалось разобрать встроенные данные отчетов', error);
        }
        return [];
    }
}

function fillSelect(select, values, defaultLabel) {
    if (!select) {
        return;
    }

    select.textContent = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = 'all';
    defaultOption.textContent = defaultLabel;
    select.appendChild(defaultOption);

    values.forEach((value) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    });
}

function renderReportsPage(reports, state) {
    const normalized = normalizeReports(reports);
    const uniqueYears = [...new Set(normalized.map((report) => String(report.year)).filter(Boolean))].sort((a, b) => b.localeCompare(a));
    const uniqueTags = [...new Set(normalized.flatMap((report) => report.tags))].sort((a, b) => a.localeCompare(b, 'ru'));

    fillSelect(state.yearFilter, uniqueYears, 'Все годы');
    fillSelect(state.tagFilter, uniqueTags, 'Все теги');
    renderFeaturedReport(normalized.find((report) => report.featured) || normalized[0]);
    setMetrics(normalized);

    function render() {
        const query = (state.search?.value || '').trim().toLowerCase();
        const selectedYear = state.yearFilter?.value || 'all';
        const selectedTag = state.tagFilter?.value || 'all';

        const filtered = normalized.filter((report) => {
            const haystack = [report.title, report.period, report.description, ...report.tags].join(' ').toLowerCase();
            const matchesQuery = !query || haystack.includes(query);
            const matchesYear = selectedYear === 'all' || String(report.year) === selectedYear;
            const matchesTag = selectedTag === 'all' || report.tags.includes(selectedTag);
            return matchesQuery && matchesYear && matchesTag;
        });

        state.grid.textContent = '';
        if (state.status) {
            state.status.textContent = `Показано отчетов: ${filtered.length} из ${normalized.length}.`;
        }

        if (!filtered.length) {
            const empty = document.createElement('div');
            empty.className = 'reports-empty';
            empty.textContent = 'По текущим фильтрам отчеты не найдены.';
            state.grid.appendChild(empty);
            return;
        }

        filtered.forEach((report) => {
            state.grid.appendChild(createReportCard(report));
        });
    }

    if (!state.bound) {
        if (state.search) {
            state.search.addEventListener('input', render);
        }
        if (state.yearFilter) {
            state.yearFilter.addEventListener('change', render);
        }
        if (state.tagFilter) {
            state.tagFilter.addEventListener('change', render);
        }
        state.bound = true;
    }

    state.render = render;
    render();
}

function initReportsPage() {
    const grid = document.getElementById('reportsGrid');
    if (!grid) {
        return;
    }

    const state = {
        grid,
        status: document.getElementById('reportsStatus'),
        search: document.getElementById('reportSearch'),
        yearFilter: document.getElementById('reportYearFilter'),
        tagFilter: document.getElementById('reportTagFilter'),
        bound: false,
        render: null
    };

    const embeddedReports = readEmbeddedReports();
    if (embeddedReports.length) {
        renderReportsPage(embeddedReports, state);
    }

    fetch('reports/reports.json', { cache: 'no-store' })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then((payload) => {
            renderReportsPage(payload, state);
        })
        .catch((error) => {
            if (window.debugLog) {
                window.debugLog('Ошибка загрузки reports/reports.json', error);
            }

            if (!embeddedReports.length) {
                if (state.status) {
                    state.status.textContent = 'Не удалось загрузить индекс отчетов.';
                }
                state.grid.textContent = '';
                const empty = document.createElement('div');
                empty.className = 'reports-empty';
                empty.textContent = 'Индекс отчетов недоступен. Проверьте файл reports/reports.json.';
                state.grid.appendChild(empty);
                return;
            }

            if (state.status) {
                state.status.textContent = `Показано отчетов: ${embeddedReports.length} из ${embeddedReports.length}.`;
            }
        });
}

document.addEventListener('DOMContentLoaded', initReportsPage);
