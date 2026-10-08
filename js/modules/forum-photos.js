(() => {
  const grid = document.querySelector('[data-photo-gallery]');
  const preview = document.querySelector('[data-photo-preview]');
  if (!grid && !preview) return;
  let photos = [], shown = 0, active = 0;
  const dialog = document.getElementById('photoViewer');
  const status = document.getElementById('photoStatus');
  const more = document.getElementById('photoMore');
  function openPhoto(index) {
    active = (index + photos.length) % photos.length;
    document.getElementById('photoFull').src = photos[active].src;
    document.getElementById('photoFull').alt = `Фото с форума — ${active + 1}`;
    document.getElementById('photoDownload').href = photos[active].download;
    document.getElementById('photoPosition').textContent = `${active + 1} / ${photos.length}`;
    if (!dialog.open) { dialog.showModal(); document.documentElement.classList.add('photo-viewer-open'); }
  }
  function tile(photo, index, isPreview) {
    const el = document.createElement(isPreview ? 'a' : 'button');
    el.className = 'photo-tile';
    if (isPreview) el.href = '/conference-2026/photos/';
    else { el.type = 'button'; el.addEventListener('click', () => openPhoto(index)); }
    el.setAttribute('aria-label', `Открыть фотографию ${index + 1}`);
    const img = document.createElement('img'); img.src = photo.thumb; img.alt = `Форум лабораторных инноваций — фото ${index + 1}`; img.loading = 'lazy'; img.decoding = 'async';
    el.append(img);
    const label = document.createElement('span'); label.textContent = isPreview ? 'Смотреть ↗' : 'Открыть ↗'; el.append(label);
    return el;
  }
  function renderMore() {
    const limit = Math.min(shown + 24, photos.length);
    while (shown < limit) { grid.append(tile(photos[shown], shown, false)); shown++; }
    more.hidden = shown >= photos.length;
  }
  if (dialog) {
    document.getElementById('photoClose').onclick = () => dialog.close();
    document.getElementById('photoPrev').onclick = () => openPhoto(active - 1);
    document.getElementById('photoNext').onclick = () => openPhoto(active + 1);
    dialog.addEventListener('close', () => document.documentElement.classList.remove('photo-viewer-open'));
    dialog.addEventListener('click', e => { if(e.target === dialog) { const r=dialog.getBoundingClientRect(); if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom) dialog.close(); } });
    dialog.addEventListener('keydown', e => { if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {e.preventDefault();openPhoto(active + (e.key === 'ArrowLeft' ? -1 : 1));} });
    let startX = null;
    const image = document.getElementById('photoFull');
    image.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, {passive:true});
    image.addEventListener('touchend', e => { if(startX !== null) { const dx=e.changedTouches[0].clientX-startX; if(Math.abs(dx)>60) openPhoto(active+(dx<0?1:-1));startX=null; } }, {passive:true});
    more.onclick = renderMore;
  }
  fetch('/api/forum-photos.php', {credentials:'omit'}).then(r => {if(!r.ok) throw new Error();return r.json();}).then(data => {
    photos = data.photos;
    if (!Array.isArray(photos)) throw new Error();
    if (preview) photos.slice(0,3).forEach((p,i) => preview.append(tile(p,i,true)));
    if (grid) {
      document.getElementById('photoCount').textContent = `${photos.length} фото`;
      status.textContent = photos.length ? '' : 'Фотографии скоро появятся. Загляните чуть позже.';
      renderMore();
    }
  }).catch(() => { if(status) status.textContent = 'Не удалось загрузить фотографии. Обновите страницу чуть позже.'; });
})();
