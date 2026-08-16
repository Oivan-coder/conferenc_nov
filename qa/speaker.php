<?php
require __DIR__ . '/_bootstrap.php';
[$authorized, $pinConfigured, $loginError] = qa_process_auth('/qa/speaker.php');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Экран спикера — Q&A</title>
<link rel="stylesheet" href="/qa/style.css?v=20260816-1">
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Q&A · экран спикера') ?>
<?php else: ?>
<div class="speaker-shell">
<div class="speaker-nav"><a href="/qa/moderator.php">← к модератору</a></div>
<div class="speaker-status" id="connectionStatus">● подключение</div>
<main class="speaker-card">
<div class="speaker-kicker">Форум лабораторных инноваций 2026</div>
<div class="speaker-session" id="sessionLabel">Ожидаем текущий доклад</div>
<div id="questionBlock" hidden>
<div class="speaker-question" id="questionText"></div>
<div class="speaker-meta" id="questionMeta"></div>
</div>
<div class="speaker-empty" id="emptyState">Ожидаем вопрос от модератора</div>
</main>
</div>
<script>
const sessionLabel=document.getElementById('sessionLabel');
const questionBlock=document.getElementById('questionBlock');
const questionText=document.getElementById('questionText');
const questionMeta=document.getElementById('questionMeta');
const emptyState=document.getElementById('emptyState');
const connectionStatus=document.getElementById('connectionStatus');
let lastId=null;
async function loadState(){
 try{
  const r=await fetch('/qa/state.php',{cache:'no-store',credentials:'same-origin'});
  if(r.status===401){location.reload();return;}
  if(!r.ok)throw new Error('http');
  const d=await r.json();if(!d.ok)throw new Error('api');
  connectionStatus.textContent='● онлайн';
  if(d.session){sessionLabel.textContent=d.session.speaker_name+' — '+d.session.title;}else{sessionLabel.textContent='Текущий доклад не выбран';}
  if(d.question){
    questionBlock.hidden=false;emptyState.hidden=true;
    questionText.textContent=d.question.question_text;
    questionMeta.textContent=d.question.participant_name+' · '+d.question.organization;
    if(lastId!==null&&lastId!==d.question.id){questionBlock.animate([{opacity:.2,transform:'translateY(8px)'},{opacity:1,transform:'translateY(0)'}],{duration:280});}
    lastId=d.question.id;
  }else{
    questionBlock.hidden=true;emptyState.hidden=false;lastId=null;
  }
 }catch(e){connectionStatus.textContent='● нет связи';}
}
loadState();setInterval(loadState,2000);
document.addEventListener('keydown',e=>{if(e.key==='f'||e.key==='F'){if(!document.fullscreenElement){document.documentElement.requestFullscreen?.();}else{document.exitFullscreen?.();}}});
</script>
<?php endif; ?>
</body>
</html>
