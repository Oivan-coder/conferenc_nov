<?php

declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow,noarchive', true);
header('X-Content-Type-Options: nosniff');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>OBS · текущий спикер</title>
<style>
:root{color-scheme:dark;--cyan:#66def1;--cyan-strong:#27c7de;--text:#edf8fb;--muted:#9cb6c5;--panel:rgba(7,25,43,.94);--line:rgba(102,222,241,.72)}
*{box-sizing:border-box}
html,body{width:100%;height:100%;margin:0;overflow:hidden;background:transparent}
body{font-family:Inter,Arial,sans-serif;color:var(--text)}
.speaker-card{position:absolute;right:54px;top:456px;width:448px;min-height:390px;padding:31px 32px 34px;border:2px solid var(--line);border-radius:22px;background:linear-gradient(145deg,rgba(13,38,61,.96),var(--panel));box-shadow:0 22px 65px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.035);transition:opacity .28s ease,transform .28s ease}
.speaker-card[hidden]{display:block;opacity:0;transform:translateY(8px)}
.speaker-label{display:inline-flex;align-items:center;gap:10px;padding:8px 13px;border:1px solid rgba(102,222,241,.24);border-radius:10px;background:rgba(102,222,241,.09);color:#d9f8fc;font-size:16px;font-weight:850;letter-spacing:.055em;text-transform:uppercase}
.speaker-label:before{content:"";width:7px;height:28px;border-radius:4px;background:linear-gradient(var(--cyan),var(--cyan-strong));box-shadow:0 0 16px rgba(102,222,241,.7)}
.speaker-name{margin:31px 0 0;font-size:34px;line-height:1.12;font-weight:900;letter-spacing:-.025em;overflow-wrap:anywhere}
.speaker-title{margin:17px 0 0;color:#b9d3df;font-size:22px;line-height:1.34;font-weight:600;overflow-wrap:anywhere}
.status{position:absolute;right:54px;bottom:30px;color:rgba(156,182,197,.72);font-size:13px;opacity:0}
.status.visible{opacity:1}
</style>
</head>
<body>
<section class="speaker-card" data-card hidden aria-live="polite">
    <div class="speaker-label">Спикер</div>
    <h1 class="speaker-name" data-speaker></h1>
    <p class="speaker-title" data-title></p>
</section>
<div class="status" data-status></div>
<script>
(() => {
    const endpoint = '/api/current-session.php';
    const card = document.querySelector('[data-card]');
    const speaker = document.querySelector('[data-speaker]');
    const title = document.querySelector('[data-title]');
    const status = document.querySelector('[data-status]');
    let signature = '';

    function showStatus(message) {
        status.textContent = message;
        status.classList.toggle('visible', Boolean(message));
    }

    async function refresh() {
        try {
            const response = await fetch(endpoint + '?_=' + Date.now(), {cache:'no-store',credentials:'same-origin'});
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'invalid_response');

            if (!data.session) {
                signature = '';
                card.hidden = true;
                showStatus('Текущий спикер не выбран');
                return;
            }

            const nextSignature = data.session.speaker_name + '\n' + data.session.title;
            if (nextSignature !== signature) {
                speaker.textContent = data.session.speaker_name;
                title.textContent = data.session.title;
                signature = nextSignature;
            }
            card.hidden = false;
            showStatus('');
        } catch (_) {
            showStatus('Нет связи с данными доклада');
        }
    }

    refresh();
    setInterval(refresh, 3000);
})();
</script>
</body>
</html>
