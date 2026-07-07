(function initCkdlMapModule() {
    const root = document.querySelector('[data-ckdl-map]');
    if (!root) return;

    const dataUrls = {
        geopolygons: '/data/geopolygons.json',
        clusters: '/data/ckdl_clusters.json',
        metrics: '/data/ckdl_metrics.json'
    };

    const els = {
        svg: root.querySelector('.ckdl-map__svg'),
        mapPanel: root.querySelector('.ckdl-map__map-panel'),
        base: root.querySelector('[data-map-base]'),
        regions: root.querySelector('[data-map-regions]'),
        hubs: root.querySelector('[data-map-hubs]'),
        tooltip: root.querySelector('[data-map-tooltip]'),
        fallback: root.querySelector('[data-map-fallback]'),
        loadStatus: root.querySelector('[data-map-load-status] b'),
        summaryClusters: root.querySelector('[data-summary-clusters]'),
        summaryTerritories: root.querySelector('[data-summary-territories]'),
        summaryLis: root.querySelector('[data-summary-lis]'),
        factRoutes: root.querySelector('[data-fact-routes]'),
        factLis: root.querySelector('[data-fact-lis]'),
        factTerritories: root.querySelector('[data-fact-territories]'),
        panelStatus: root.querySelector('[data-panel-status]'),
        panelTitle: root.querySelector('[data-panel-title]'),
        panelSubtitle: root.querySelector('[data-panel-subtitle]'),
        panelLisText: root.querySelector('[data-panel-lis-text]'),
        panelLisBar: root.querySelector('[data-panel-lis-bar]'),
        panelPerf: root.querySelector('[data-panel-perf]'),
        panelTat: root.querySelector('[data-panel-tat]'),
        panelDelivery: root.querySelector('[data-panel-delivery]'),
        panelExport: root.querySelector('[data-panel-export]'),
        panelListTitle: root.querySelector('[data-panel-list-title]'),
        panelList: root.querySelector('[data-panel-list]'),
        panelNote: root.querySelector('[data-panel-note]'),
        expandToggle: root.querySelector('[data-map-expand-toggle]')
    };

    const state = {
        geopolygons: null,
        clusters: null,
        metrics: null,
        regionNodes: [],
        hubRings: new Map(),
        assignments: new Map(),
        hoverKey: null,
        locked: false
    };
    const networkMode = root.dataset.mapMode === 'network';

    const svgNS = 'http://www.w3.org/2000/svg';

    function svgEl(tag, attrs = {}) {
        const node = document.createElementNS(svgNS, tag);
        Object.entries(attrs).forEach(([key, value]) => node.setAttribute(key, value));
        return node;
    }

    function pct(done, total) {
        return total > 0 ? Math.round((done / total) * 100) : 0;
    }

    function clusterColor(clusterName) {
        return state.clusters?.clusters?.[clusterName]?.color || state.clusters?.hubs?.[clusterName]?.color || '#48c9ff';
    }

    function hexToRgba(hex, alpha) {
        const clean = String(hex || '#48c9ff').replace('#', '');
        const value = parseInt(clean.length === 3 ? clean.split('').map((x) => x + x).join('') : clean, 16);
        const r = (value >> 16) & 255;
        const g = (value >> 8) & 255;
        const b = value & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    async function fetchJson(url) {
        const response = await fetch(url, { cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Failed to load ${url}: ${response.status}`);
        }
        return response.json();
    }

    function setLoadError() {
        if (els.fallback) els.fallback.hidden = false;
        if (els.loadStatus) els.loadStatus.textContent = 'Данные недоступны';
        root.classList.add('is-error');
    }

    function setLoadedStatus() {
        if (els.fallback) els.fallback.hidden = true;
        if (els.loadStatus) els.loadStatus.textContent = 'Данные загружены';
    }

    function renderSummary() {
        const clusterCount = Object.keys(state.clusters.clusters).length;
        const territoryCount = state.geopolygons.territories.length;
        const lisDone = state.metrics.summary.lisDone || 0;
        const lisTotal = state.metrics.summary.lisTotal || 0;
        const lisPercent = pct(lisDone, lisTotal);
        const sourceMedorgs = state.metrics.summary.sourceMedorgs || territoryCount;

        els.summaryClusters.textContent = clusterCount;
        els.summaryTerritories.textContent = territoryCount;
        els.summaryLis.textContent = networkMode ? sourceMedorgs : lisTotal;
        els.factRoutes.textContent = networkMode ? `${clusterCount} кустов ЦКДЛ` : `${clusterCount} кустов`;
        els.factLis.textContent = networkMode ? `${sourceMedorgs} медорганизация` : `${lisPercent}%`;
        els.factTerritories.textContent = networkMode ? `${territoryCount} территорий` : `${territoryCount} МО`;
    }

    function renderMap() {
        const fragmentBase = document.createDocumentFragment();
        const fragmentRegions = document.createDocumentFragment();

        state.geopolygons.territories.forEach((territory) => {
            const cluster = state.assignments.get(territory.name);
            const color = clusterColor(cluster);

            territory.paths.forEach((pathData) => {
                fragmentBase.appendChild(svgEl('path', { d: pathData }));
            });

            const group = svgEl('g', {
                class: 'ckdl-map__region',
                'data-cluster': cluster,
                'data-name': territory.name,
                style: [
                    `--region-fill:${hexToRgba(color, networkMode ? 0.18 : 0.34)}`,
                    `--region-cluster-fill:${hexToRgba(color, networkMode ? 0.38 : 0.58)}`,
                    `--region-active-fill:${hexToRgba(color, networkMode ? 0.58 : 0.82)}`,
                    `--region-stroke:${hexToRgba(color, networkMode ? 0.22 : 0.48)}`,
                    `--region-cluster-stroke:${hexToRgba(color, networkMode ? 0.48 : 0.88)}`
                ].join(';'),
                tabindex: '0',
                role: 'button',
                'aria-label': `${territory.name}, куст ${cluster}`
            });

            territory.paths.forEach((pathData) => {
                group.appendChild(svgEl('path', { d: pathData }));
            });

            group.addEventListener('focus', (event) => activate(cluster, territory.name, event, false));
            group.addEventListener('blur', clearHover);
            group.addEventListener('click', (event) => {
                state.locked = true;
                activate(cluster, territory.name, event, true);
            });

            state.regionNodes.push(group);
            fragmentRegions.appendChild(group);
        });

        els.base.appendChild(fragmentBase);
        els.regions.appendChild(fragmentRegions);
        renderHubs();
        fitNetworkViewBox();
    }

    function fitNetworkViewBox() {
        if (!networkMode || !els.svg) return;

        const hubVisuals = els.hubs
            ? Array.from(els.hubs.querySelectorAll('.ckdl-map__hub, .ckdl-map__hub-label'))
            : [];

        const boxes = [els.base, els.regions, ...hubVisuals]
            .filter(Boolean)
            .map((node) => {
                try {
                    return node.getBBox();
                } catch (error) {
                    return null;
                }
            })
            .filter((box) => box && box.width > 0 && box.height > 0);

        if (!boxes.length) return;

        const minX = Math.min(...boxes.map((box) => box.x));
        const minY = Math.min(...boxes.map((box) => box.y));
        const maxX = Math.max(...boxes.map((box) => box.x + box.width));
        const maxY = Math.max(...boxes.map((box) => box.y + box.height));
        const width = maxX - minX;
        const height = maxY - minY;
        const padX = width * 0.012;
        const padY = height * 0.018;

        els.svg.setAttribute('viewBox', [
            Math.round(minX - padX),
            Math.round(minY - padY),
            Math.round(width + padX * 2),
            Math.round(height + padY * 2)
        ].join(' '));
        els.svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    }

    function renderHubs() {
        const fragment = document.createDocumentFragment();

        Object.entries(state.clusters.hubPoints).forEach(([clusterName, point]) => {
            const color = clusterColor(clusterName);
            const group = svgEl('g', {
                class: 'ckdl-map__hub-node',
                style: `color:${color}`,
                tabindex: '0',
                role: 'button',
                'aria-label': `${clusterName}, ЦКДЛ`
            });

            const ring = svgEl('circle', {
                class: 'ckdl-map__hub-ring',
                cx: point[0],
                cy: point[1],
                r: 82,
                stroke: color
            });
            group.appendChild(ring);
            state.hubRings.set(clusterName, ring);

            group.appendChild(svgEl('circle', {
                class: 'ckdl-map__hub',
                cx: point[0],
                cy: point[1],
                r: networkMode ? 8 : 9,
                stroke: color
            }));

            const label = svgEl('text', {
                class: 'ckdl-map__hub-label',
                x: point[0] + 14,
                y: point[1] - 12
            });
            label.textContent = clusterName;
            group.appendChild(label);

            group.addEventListener('focus', (event) => activate(clusterName, null, event, false));
            group.addEventListener('blur', clearHover);
            group.addEventListener('click', (event) => {
                state.locked = true;
                activate(clusterName, null, event, true);
            });

            fragment.appendChild(group);
        });

        els.hubs.appendChild(fragment);
    }

    function setupSvgPointerTracking() {
        const trackingSurface = els.mapPanel || els.svg;
        if (!trackingSurface || trackingSurface.dataset.pointerTrackingReady === 'true') return;
        trackingSurface.dataset.pointerTrackingReady = 'true';

        trackingSurface.addEventListener('pointerover', handleSvgPointerOver);
        trackingSurface.addEventListener('pointermove', handleSvgPointerMove);
        trackingSurface.addEventListener('pointerleave', handleSvgPointerLeave);
    }

    function setupNetworkExpand() {
        if (!networkMode || !els.expandToggle || els.expandToggle.dataset.expandReady === 'true') return;
        els.expandToggle.dataset.expandReady = 'true';

        const showcase = root.closest('[data-about-showcase]') || root.closest('.about-network-section');

        els.expandToggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const expanded = !root.classList.contains('is-expanded');

            root.classList.toggle('is-expanded', expanded);
            if (showcase) {
                showcase.classList.toggle('is-map-expanded', expanded);
            }

            els.expandToggle.setAttribute('aria-expanded', String(expanded));
            els.expandToggle.setAttribute('aria-label', expanded ? 'Свернуть карту' : 'Развернуть карту');
            els.expandToggle.textContent = expanded ? 'Свернуть' : 'Развернуть';
        });
    }

    function getPointerTarget(event) {
        const source = event.target;
        if (!(source instanceof Element)) return null;

        const region = source.closest('.ckdl-map__region');
        if (region) {
            return {
                key: `region:${region.dataset.name}`,
                cluster: region.dataset.cluster,
                territory: region.dataset.name
            };
        }

        const hub = source.closest('.ckdl-map__hub-node');
        const cluster = hub?.getAttribute('aria-label')?.replace(', ЦКДЛ', '');
        if (hub && cluster) {
            return {
                key: `hub:${cluster}`,
                cluster,
                territory: null
            };
        }

        return null;
    }

    function activateHover(target, event) {
        if (target.key === state.hoverKey) {
            moveTooltip(event);
            return;
        }

        state.hoverKey = target.key;
        activate(target.cluster, target.territory, event);
    }

    function handleSvgPointerOver(event) {
        if (state.locked) return;
        const target = getPointerTarget(event);
        if (!target || target.key === state.hoverKey) return;

        activateHover(target, event);
    }

    function handleSvgPointerMove(event) {
        if (!state.locked) {
            const target = getPointerTarget(event);
            if (target) {
                activateHover(target, event);
            }
        }
        moveTooltip(event);
    }

    function handleSvgPointerLeave() {
        if (state.locked) return;
        state.hoverKey = null;
        clearHover();
    }

    function activate(cluster, territoryName, event) {
        state.regionNodes.forEach((node) => {
            const sameCluster = node.dataset.cluster === cluster;
            const sameTerritory = territoryName && node.dataset.name === territoryName;
            node.classList.toggle('is-dimmed', !sameCluster);
            node.classList.toggle('is-cluster', sameCluster && !sameTerritory);
            node.classList.toggle('is-active', Boolean(sameTerritory));
        });

        state.hubRings.forEach((ring, ringCluster) => {
            ring.classList.toggle('is-visible', ringCluster === cluster);
        });

        renderPanel(cluster, territoryName);
        showTooltip(cluster, territoryName, event);
    }

    function clearHover() {
        if (state.locked) return;
        state.hoverKey = null;
        state.regionNodes.forEach((node) => node.classList.remove('is-dimmed', 'is-cluster', 'is-active'));
        state.hubRings.forEach((ring) => ring.classList.remove('is-visible'));
        hideTooltip();
        renderDefaultPanel();
    }

    function clearLockedSelection(event) {
        if (!state.locked) return;
        const target = event.target;
        if (target.closest('.ckdl-map__region') || target.closest('.ckdl-map__hub-node')) return;
        state.locked = false;
        clearHover();
    }

    function renderPanel(cluster, territoryName) {
        const clusterData = state.clusters.clusters[cluster];
        if (!clusterData) return;

        if (networkMode) {
            els.panelStatus.textContent = territoryName ? 'Территория маршрута' : 'ЦКДЛ';
            els.panelTitle.textContent = territoryName || clusterData.title || cluster;
            els.panelSubtitle.textContent = territoryName
                ? `Относится к ЦКДЛ: ${cluster}`
                : `${clusterData.sourceCount || clusterData.count} медорганизаций в кусте`;
            els.panelLisText.textContent = `${clusterData.count} МО`;
            els.panelLisBar.style.width = '100%';
            els.panelPerf.textContent = '—';
            els.panelTat.textContent = '—';
            els.panelDelivery.textContent = '—';
            els.panelExport.textContent = '—';
            els.panelListTitle.textContent = territoryName ? 'Состав куста' : 'Медорганизации куста';
            els.panelNote.textContent = territoryName
                ? `Цвет территории совпадает с цветом ЦКДЛ «${cluster}».`
                : `Подсвечен весь куст «${cluster}».`;

            const rows = clusterData.sourceItems
                ? clusterData.sourceItems.map((item) => ({
                    label: item.name,
                    status: getSourceItemStatus(item, clusterData.hubMo)
                }))
                : clusterData.items;
            renderList(rows, clusterData.hubMo);
            return;
        }

        const clusterMetrics = state.metrics.clusters[cluster];
        const territoryMetrics = territoryName ? state.metrics.territories[territoryName] : null;
        const activeMetrics = territoryMetrics || clusterMetrics;

        if (!activeMetrics) return;

        els.panelStatus.textContent = territoryMetrics?.status || clusterMetrics.status || 'Сеть активна';
        els.panelTitle.textContent = territoryName || clusterData.title || cluster;
        els.panelSubtitle.textContent = territoryName
            ? `${territoryName} · куст ${cluster}`
            : `${clusterData.hubMo} · ${clusterData.sourceCount || clusterData.count} медорг. · ${clusterData.count} полигонов`;
        els.panelLisText.textContent = `${activeMetrics.lisDone} / ${activeMetrics.lisTotal}`;
        els.panelLisBar.style.width = `${pct(activeMetrics.lisDone, activeMetrics.lisTotal)}%`;
        els.panelPerf.textContent = `${activeMetrics.perf}%`;
        els.panelTat.textContent = `${activeMetrics.tat} ч`;
        els.panelDelivery.textContent = `${activeMetrics.delivery} ч`;
        els.panelExport.textContent = `${activeMetrics.export} ч`;
        els.panelListTitle.textContent = clusterData.sourceItems ? 'Медорганизации куста' : (territoryName ? 'Куст ЦКДЛ' : 'Территории куста');
        els.panelNote.textContent = territoryName
            ? `Выбрана территория «${territoryName}». Подсвечен весь куст ${cluster}.`
            : `Подсвечен куст ${cluster}. Показаны агрегированные показатели ЦКДЛ.`;

        const rows = clusterData.sourceItems
            ? clusterData.sourceItems.map((item) => ({
                label: item.name,
                status: getSourceItemStatus(item, clusterData.hubMo)
            }))
            : (territoryName
                ? [clusterData.hubMo, ...clusterData.items.filter((name) => name !== clusterData.hubMo && name !== territoryName)]
                : clusterData.items);
        renderList(rows.slice(0, 14), clusterData.hubMo);
    }

    function renderDefaultPanel() {
        const lisDone = state.metrics.summary.lisDone || 0;
        const lisTotal = state.metrics.summary.lisTotal || 0;

        els.panelStatus.textContent = networkMode ? 'Инфографика сети' : 'Сеть активна';
        els.panelTitle.textContent = networkMode ? 'Структура кустов ЦКДЛ' : 'Карта кустов ЦКДЛ';
        els.panelSubtitle.textContent = networkMode
            ? 'Выберите территорию на карте, чтобы увидеть ее ЦКДЛ и состав куста.'
            : 'Сводка по Московской области';
        els.panelLisText.textContent = `${lisDone} / ${lisTotal}`;
        els.panelLisBar.style.width = `${pct(lisDone, lisTotal)}%`;
        els.panelPerf.textContent = '—';
        els.panelTat.textContent = '—';
        els.panelDelivery.textContent = '—';
        els.panelExport.textContent = '—';
        els.panelListTitle.textContent = networkMode ? 'Легенда ЦКДЛ' : 'ЦКДЛ';
        els.panelNote.textContent = networkMode
            ? 'Наведение подсвечивает весь куст. Цвет территории совпадает с цветом точки ЦКДЛ.'
            : state.clusters.assignmentNotice;
        renderList(Object.keys(state.clusters.clusters), null, true);
    }

    function getSourceItemStatus(item, hubName) {
        if (!item.hasPolygon) return 'нет полигона';
        if (item.name === hubName) return 'ЦКДЛ';
        if (item.mapName && item.mapName !== item.name) return 'схлопнуто';
        return 'маршрут';
    }

    function renderList(rows, hubName, clusterList = false) {
        els.panelList.textContent = '';
        const fragment = document.createDocumentFragment();

        rows.forEach((rowItem) => {
            const isStructured = typeof rowItem === 'object' && rowItem !== null;
            const name = isStructured ? rowItem.label : rowItem;
            const row = document.createElement('div');
            row.className = 'ckdl-map__list-row';
            if (clusterList && state.clusters.clusters[name]) {
                row.style.setProperty('--row-color', clusterColor(name));
            } else if (hubName) {
                row.style.setProperty('--row-color', clusterColor(hubName));
            }

            const left = document.createElement('span');
            left.textContent = name;

            const right = document.createElement('span');
            right.textContent = isStructured
                ? rowItem.status
                : (clusterList
                    ? `${state.clusters.clusters[name].sourceCount || state.clusters.clusters[name].count} медорг.`
                    : (name === hubName ? 'ЦКДЛ' : 'маршрут'));

            row.append(left, right);
            fragment.appendChild(row);
        });

        els.panelList.appendChild(fragment);
    }

    function showTooltip(cluster, territoryName, event) {
        const clusterData = state.clusters.clusters[cluster];
        const metrics = territoryName ? state.metrics.territories[territoryName] : state.metrics.clusters[cluster];
        if (!clusterData) return;

        if (networkMode) {
            els.tooltip.innerHTML = territoryName
                ? `<b>${territoryName}</b><div>ЦКДЛ: <strong>${cluster}</strong></div><div>${clusterData.sourceCount || clusterData.count} медорганизаций в кусте</div>`
                : `<b>${cluster} — ЦКДЛ</b><div>${clusterData.sourceCount || clusterData.count} медорганизаций</div><div>${clusterData.count} территорий на карте</div>`;
            els.tooltip.style.display = 'block';
            moveTooltip(event);
            return;
        }

        if (!metrics) return;

        els.tooltip.innerHTML = territoryName
            ? `<b>${territoryName}</b><div>Куст: <strong>${cluster}</strong></div><div>Статус: ${metrics.status}</div><div>ТАТ: <strong>${metrics.tat} ч</strong> · ЛИС: <strong>${metrics.lisDone}/${metrics.lisTotal}</strong></div>`
            : `<b>${cluster} — ЦКДЛ</b><div>${clusterData.count} территорий</div><div>Средний ТАТ: <strong>${metrics.tat} ч</strong></div>`;
        els.tooltip.style.display = 'block';
        moveTooltip(event);
    }

    function moveTooltip(event) {
        if (!event || !els.tooltip || els.tooltip.style.display !== 'block') return;

        if (window.innerWidth <= 720) {
            els.tooltip.style.display = 'block';
            return;
        }

        const margin = 12;
        const gap = 16;
        const rect = els.tooltip.getBoundingClientRect();
        let left = event.clientX + gap;
        let top = event.clientY + gap;

        if (left + rect.width + margin > window.innerWidth) {
            left = event.clientX - rect.width - gap;
        }

        if (top + rect.height + margin > window.innerHeight) {
            top = event.clientY - rect.height - gap;
        }

        els.tooltip.style.left = `${Math.max(margin, left)}px`;
        els.tooltip.style.top = `${Math.max(margin, top)}px`;
    }

    function hideTooltip() {
        if (els.tooltip) {
            els.tooltip.style.display = 'none';
        }
    }

    async function init() {
        try {
            const [geopolygons, clusters, metrics] = await Promise.all([
                fetchJson(dataUrls.geopolygons),
                fetchJson(dataUrls.clusters),
                fetchJson(dataUrls.metrics)
            ]);

            state.geopolygons = geopolygons;
            state.clusters = clusters;
            state.metrics = metrics;
            state.assignments = new Map(clusters.territories.map((item) => [item.name, item.cluster]));

            renderSummary();
            renderMap();
            setupSvgPointerTracking();
            setupNetworkExpand();
            renderDefaultPanel();
            setLoadedStatus();
        } catch (error) {
            console.error('CKDL map load error:', error);
            setLoadError();
        }
    }

    document.addEventListener('click', clearLockedSelection);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            state.locked = false;
            clearHover();
        }
    });

    init();
})();
