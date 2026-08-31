<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const TEST_EMBED_URL = 'https://www.youtube-nocookie.com/embed/aqz-KE-bpKQ';

function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['conference_dashboard_auth'])) {
    http_response_code(404);
    exit;
}

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$token = strtolower(trim((string)($_GET['t'] ?? '')));
$participant = null;

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');

    if (preg_match('/^LE[A-F0-9]{8}$/', $code)) {
        $stmt = $pdo->prepare(
            'SELECT id, participant_code, full_name, position, organization, online_token, online_watch_seconds
             FROM participants
             WHERE participant_code = :code
               AND participation_format = "online"
               AND registration_status = "confirmed"
             LIMIT 1'
        );
        $stmt->execute([':code' => $code]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($participant) $token = strtolower((string)$participant['online_token']);
    } elseif (preg_match('/^[a-f0-9]{64}$/', $token)) {
        $stmt = $pdo->prepare(
            'SELECT id, participant_code, full_name, position, organization, online_token, online_watch_seconds
             FROM participants
             WHERE online_token = :token
               AND participation_format = "online"
               AND registration_status = "confirmed"
             LIMIT 1'
        );
        $stmt->execute([':token' => $token]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
} catch (Throwable $e) {
    $participant = null;
}

if (!$participant) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Тест онлайн-трансляции — Форум 2026</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6f4;color:#173126;font-family:Arial,sans-serif}.wrap{max-width:1100px;margin:0 auto;padding:24px 16px 44px}.top{background:#214f3b;color:#fff;border-radius:18px;padding:28px;margin-bottom:20px}.eyebrow{font-size:12px;letter-spacing:.06em;text-transform:uppercase;opacity:.8}.top h1{margin:8px 0 0;font-size:30px;line-height:1.2}.grid{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px}.card{background:#fff;border:1px solid #dfe8e2;border-radius:16px;overflow:hidden}.player{aspect-ratio:16/9;background:#13221c}.player iframe{width:100%;height:100%;border:0}.body{padding:22px}.body h2{margin:0 0 8px;font-size:22px}.muted{color:#62756c;line-height:1.55}.info{padding:22px}.info p{margin:7px 0;line-height:1.5}.badge{display:inline-block;padding:7px 11px;border-radius:999px;background:#eef5f1;color:#214f3b;font-size:13px;font-weight:700}.test{margin-top:14px;padding:12px;border-radius:10px;background:#fff6d9;color:#695400;font-size:13px;line-height:1.5}.error{max-width:680px;margin:80px auto;background:#fff;border:1px solid #dfe8e2;border-radius:16px;padding:36px;text-align:center}.small{font-size:13px;color:#6f8078;line-height:1.5;margin-top:14px}.discussion{margin-top:20px}.discussion-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;padding:22px 22px 16px;border-bottom:1px solid #e5ece8}.discussion-head h2{margin:0 0 5px;font-size:23px}.discussion-head p{margin:0}.session-now{max-width:430px;text-align:right;font-size:13px;color:#62756c;line-height:1.45}.session-now strong{display:block;color:#214f3b;margin-bottom:3px}.chat-list{height:430px;overflow-y:auto;padding:8px 18px;background:#f8faf9}.chat-message{padding:15px 4px;border-bottom:1px solid #e3ebe7}.chat-message:last-child{border-bottom:0}.chat-message.is-question{margin:9px 0;padding:14px;border:1px solid #b9d8ca;border-radius:13px;background:#f1f8f4}.chat-message__head{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;font-size:13px;color:#6b7d74}.chat-message__head strong{color:#173126;font-size:14px}.chat-message__right{display:flex;align-items:center;gap:8px;flex-shrink:0}.chat-question-badge{padding:5px 8px;border-radius:999px;background:#214f3b;color:#fff;font-size:11px;font-weight:700}.chat-message__text{font-size:16px;line-height:1.5;margin-top:8px;white-space:pre-wrap;overflow-wrap:anywhere}.chat-reply-quote{margin-top:9px;border-left:3px solid #91b8a6;padding:7px 10px;background:#edf3f0;border-radius:0 8px 8px 0;display:flex;flex-direction:column;gap:2px;font-size:12px;color:#62756c}.chat-reply-quote strong{color:#365548}.chat-question-status{display:inline-block;margin-top:10px;font-size:12px;font-weight:700}.chat-question-status.on-air{color:#a25b00}.chat-question-status.answered{color:#1d6d46}.chat-message__actions{display:flex;gap:8px;margin-top:9px}.chat-action{border:0;background:transparent;padding:4px 3px;color:#537066;font-size:12px;font-weight:700;cursor:pointer}.chat-action.vote{padding:5px 8px;border-radius:8px;background:#edf3f0}.chat-action.vote.active{background:#d7ebe1;color:#174f35}.chat-compose{padding:16px 18px 18px;border-top:1px solid #e5ece8}.chat-modes{display:flex;gap:8px;margin-bottom:10px}.chat-mode input{position:absolute;opacity:0;pointer-events:none}.chat-mode span{display:block;padding:8px 11px;border-radius:9px;background:#edf3f0;color:#526d61;font-size:13px;font-weight:700;cursor:pointer}.chat-mode input:checked+span{background:#214f3b;color:#fff}.chat-compose textarea{width:100%;min-height:82px;resize:vertical;border:1px solid #cbd9d2;border-radius:11px;padding:12px 13px;font:inherit;color:#173126;outline:none}.chat-compose-bottom{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:9px}.chat-submit{border:0;border-radius:10px;padding:10px 15px;background:#214f3b;color:#fff;font-weight:700;cursor:pointer}.chat-status{font-size:12px;color:#61766b;line-height:1.4}.chat-status.error{color:#a13a32}.chat-reply-banner{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:9px;padding:8px 10px;border-radius:9px;background:#edf3f0;color:#476458;font-size:12px}.chat-reply-banner button{border:0;background:transparent;color:#476458;font-weight:700;cursor:pointer}.chat-empty{min-height:360px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:7px;text-align:center;color:#71847a}.chat-empty strong{color:#365548;font-size:18px}@media(max-width:800px){.grid{grid-template-columns:1fr}.top h1{font-size:25px}.wrap{padding-top:14px}.top{padding:22px}.discussion-head{display:block}.session-now{text-align:left;margin-top:10px}.chat-list{height:390px}.chat-message__head{display:block}.chat-message__right{margin-top:5px}.chat-compose-bottom{align-items:flex-start;flex-direction:column}.chat-submit{width:100%}}
</style>
</head>
<body>
<?php if (!$participant): ?>
<div class="error"><h1>Онлайн-участник не найден</h1><p class="muted">Откройте тест с кодом подтверждённого онлайн-участника.</p></div>
<?php else: ?>
<div class="wrap">
<div class="top"><div class="eyebrow">Закрытый тест · только для организатора</div><h1>Форум лабораторных инноваций Московской области — 2026</h1></div>
<div class="grid">
<section class="card"><div class="player"><iframe src="<?= h(TEST_EMBED_URL) ?>" allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen title="Тестовый видеоплеер"></iframe></div><div class="body"><h2><?= h($participant['full_name']) ?></h2><div class="muted"><?= h($participant['organization']) ?> · <?= h($participant['position']) ?></div><div class="small">Это тестовый режим. В боевой персональной ссылке до 7 октября трансляция остаётся закрытой.</div></div></section>
<aside class="card info"><span class="badge">Онлайн-участие · ТЕСТ</span><p><strong>Код:</strong> <?= h($participant['participant_code']) ?></p><p><strong>Дата:</strong> 7 октября 2026 года</p><p><strong>Учёт присутствия:</strong> тестовый режим активен</p><p class="small">Счётчик ниже пишет реальные тестовые секунды в БД этого участника.</p><div class="test">Накоплено <strong data-watch-seconds><?= (int)$participant['online_watch_seconds'] ?></strong> сек. После 900 сек. дашборд отметит онлайн-факт ≥15 мин.</div></aside>
</div>
<section class="card discussion" aria-labelledby="discussionTitle"><div class="discussion-head"><div><h2 id="discussionTitle">Обсуждение</h2><p class="muted">Тест общего чата и вопросов текущему спикеру.</p></div><div class="session-now"><strong>Текущий доклад</strong><span data-chat-session>Текущий спикер пока не выбран</span></div></div><div class="chat-list" data-chat-list><div class="chat-empty"><strong>Загружаем обсуждение…</strong><span>Сообщения появятся здесь.</span></div></div><form class="chat-compose" data-chat-form autocomplete="off"><div class="chat-reply-banner" data-reply-banner hidden><span data-reply-label></span><button type="button" data-reply-cancel>Отмена</button></div><div class="chat-modes" role="radiogroup" aria-label="Тип сообщения"><label class="chat-mode"><input type="radio" name="messageType" value="chat" checked><span>Сообщение в чат</span></label><label class="chat-mode"><input type="radio" name="messageType" value="question"><span>Вопрос спикеру</span></label></div><textarea data-chat-input maxlength="1000" placeholder="Напишите тестовое сообщение…" required></textarea><div class="chat-compose-bottom"><div class="chat-status" data-chat-status>Вопрос спикеру появится в общем чате и у модератора.</div><button class="chat-submit" type="submit">Отправить</button></div></form></section>
</div>
<script>
window.CONFERENCE_CHAT_CONFIG={enabled:true,token:<?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>,endpoint:'/api/conference-chat.php'};
</script>
<script src="/js/modules/live-chat.js?v=20260816-1" defer></script>
<script>
(() => {
 const token=<?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;
 const endpoint='/api/live-heartbeat.php';
 const watchEl=document.querySelector('[data-watch-seconds]');
 let timer=null;
 async function heartbeat(){
   if(document.visibilityState!=='visible') return;
   try{
     const response=await fetch(endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token,test_mode:true}),credentials:'same-origin',keepalive:true});
     const data=await response.json().catch(()=>null);
     if(watchEl&&data&&data.tracking_active) watchEl.textContent=String(data.watch_seconds||0);
   }catch(_){}
 }
 heartbeat();
 timer=setInterval(heartbeat,30000);
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='visible') heartbeat();});
 window.addEventListener('pagehide',()=>{if(timer)clearInterval(timer);try{navigator.sendBeacon(endpoint,JSON.stringify({token,test_mode:true}));}catch(_){}});
})();
</script>
<?php endif; ?>
</body>
</html>
