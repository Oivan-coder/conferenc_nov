<?php
require __DIR__ . '/_bootstrap.php';

$obsMode = trim((string)($_GET['obs'] ?? ''));

if ($obsMode === 'state') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = qa_pdo();
        qa_ensure_schema($pdo);
        $session = qa_current_session($pdo);
        echo json_encode([
            'ok' => true,
            'session' => $session ? [
                'speaker_name' => (string)$session['speaker_name'],
                'title' => (string)$session['title'],
            ] : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

if ($obsMode === '1') {
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>OBS · текущий спикер</title>
<style>
:root{color-scheme:dark;--cyan:#66def1;--cyan-strong:#27c7de;--text:#edf8fb;--panel:rgba(7,25,43,.94);--line:rgba(102,222,241,.72)}
*{box-sizing:border-box}
html,body{width:100%;height:100%;margin:0;overflow:hidden;background:transparent}
body{font-family:Inter,Arial,sans-serif;color:var(--text)}
.speaker-card{position:absolute;right:54px;top:456px;width:448px;min-height:390px;padding:31px 32px 34px;border:2px solid var(--line);border-radius:22px;background:linear-gradient(145deg,rgba(13,38,61,.96),var(--panel));box-shadow:0 22px 65px rgba(0,0,0,.28),inset 0 1px 0 rgba(255,255,255,.035);transition:opacity .28s ease,transform .28s ease}
.speaker-card[hidden]{display:block;opacity:0;transform:translateY(8px)}
.speaker-label{display:inline-flex;align-items:center;gap:10px;padding:8px 13px;border:1px solid rgba(102,222,241,.24);border-radius:10px;background:rgba(102,222,241,.09);color:#d9f8fc;font-size:16px;font-weight:850;letter-spacing:.055em;text-transform:uppercase}
.speaker-label:before{content:"";width:7px;height:28px;border-radius:4px;background:linear-gradient(var(--cyan),var(--cyan-strong));box-shadow:0 0 16px rgba(102,222,241,.7)}
.speaker-name{margin:31px 0 0;font-size:34px;line-height:1.12;font-weight:900;letter-spacing:-.025em;overflow-wrap:anywhere}
.speaker-title{margin:17px 0 0;color:#b9d3df;font-size:22px;line-height:1.34;font-weight:600;overflow-wrap:anywhere}
.status{position:absolute;right:54px;bottom:30px;color:rgba(156,182,197,.72);font-size:13px;opacity:0}.status.visible{opacity:1}
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
    const endpoint = '/qa/live-preview.php?obs=state';
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
            const response = await fetch(endpoint + '&_=' + Date.now(), {cache:'no-store',credentials:'same-origin'});
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
<?php
    exit;
}

[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/live-preview.php');

if ($authorized) {
    try {
        $pdo = qa_pdo();
        $stmt = $pdo->prepare("SELECT online_token FROM participants WHERE participation_format = 'online' AND registration_status = 'confirmed' AND organization = 'Тестовая МО' AND online_token IS NOT NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $token = (string)($stmt->fetchColumn() ?: '');
        if (preg_match('/^[a-f0-9]{64}$/', $token)) {
            header('Location: /live/?t=' . $token);
            exit;
        }
        $error = 'Тестовый онлайн-участник не найден.';
    } catch (Throwable $e) {
        $error = 'Не удалось открыть тестовую персональную страницу.';
    }
}
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Открыть тестовую трансляцию</title><link rel="stylesheet" href="/qa/style.css?v=20260816-1"></head><body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Открыть тестовую трансляцию') ?>
<?php else: ?>
<div class="login-card"><div class="brand">Форум лабораторных инноваций Московской области — 2026</div><h1>Тестовая трансляция</h1><div class="notice"><?= qa_h($error ?? 'Не удалось открыть страницу.') ?></div><a class="btn" href="/qa/">Вернуться</a></div>
<?php endif; ?>
</body></html>
