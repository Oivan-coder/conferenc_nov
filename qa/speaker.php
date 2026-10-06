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
<title>Экран спикера — все вопросы</title>
<link rel="stylesheet" href="/qa/style.css?v=20261006-questions2">
<style>
.speaker-shell{display:block;padding:90px 24px 40px}.speaker-card{margin:auto;text-align:left}.speaker-session{font-size:22px;margin:12px 0 24px}.speaker-controls{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}.speaker-controls button{border:1px solid #527365;border-radius:999px;background:#193e30;color:#e6f2ec;padding:10px 14px;font:inherit;cursor:pointer}.speaker-controls button[aria-pressed="true"]{background:#d5eddf;color:#163c2a}.speaker-question-item{border:1px solid #496d5b;background:#173b2e;border-radius:18px;padding:22px;margin:14px 0}.speaker-question-item.status-on_air{border:2px solid #a3dfb5;background:#214d38}.speaker-question-item.status-answered{background:#183228}.speaker-question-head{display:flex;align-items:center;gap:12px;justify-content:space-between;color:#bad8c8;font-size:14px;margin-bottom:14px}.speaker-question-body{white-space:pre-wrap;overflow-wrap:anywhere;font-size:clamp(24px,2.7vw,40px);line-height:1.35;font-weight:700}.speaker-question-meta{color:#bad3c6;font-size:16px;line-height:1.5;margin-top:16px}.speaker-empty{font-size:24px}.speaker-status{background:#102c23;z-index:2}.speaker-nav{z-index:2}.speaker-count{color:#bad3c6;margin:0 0 16px}.speaker-question-item[hidden]{display:none}@media(max-width:760px){.speaker-shell{padding:76px 14px 24px}.speaker-question-item{padding:18px}.speaker-status{top:14px;right:14px}.speaker-nav{top:18px;left:14px}}
</style>
</head>
<body>
<?php if (!$authorized): ?>
<?= qa_login_markup($pinConfigured, $loginError, 'Q&A · экран спикера') ?>
<?php else: ?>
<div class="speaker-shell">
<div class="speaker-nav"><a href="/qa/moderator.php">← к модератору</a></div>
<div class="speaker-status" id="connectionStatus" role="status">● подключение</div>
<main class="speaker-card">
<div class="speaker-kicker">Форум лабораторных инноваций Московской области — 2026</div>
<h1>Вопросы участников</h1>
<div class="speaker-session" id="sessionLabel">Ожидаем текущий доклад</div>
<div class="speaker-controls" id="speakerFilters" aria-label="Фильтр вопросов">
<button type="button" data-filter="all" aria-pressed="true">Все вопросы</button>
<button type="button" data-filter="new" aria-pressed="false">Новые</button>
<button type="button" data-filter="on_air" aria-pressed="false">Выбраны модератором</button>
<button type="button" data-filter="answered" aria-pressed="false">Отвечены</button>
</div>
<p class="speaker-count" id="questionCount">Вопросы обновляются автоматически. Скрытые модератором вопросы не выводятся.</p>
<div id="speakerQuestions"></div>
<div class="speaker-empty" id="emptyState">Пока вопросов нет</div>
</main>
</div>
<script>
const sessionLabel=document.getElementById('sessionLabel');
const questionList=document.getElementById('speakerQuestions');
const emptyState=document.getElementById('emptyState');
const connectionStatus=document.getElementById('connectionStatus');
const questionCount=document.getElementById('questionCount');
const statusLabels={new:'Новый',on_air:'Выбран модератором',answered:'Отвечен'};
let activeFilter='all';
let loading=false;
let questions=[];
function applyFilter(){
 let visible=0;
 questionList.querySelectorAll('[data-question-id]').forEach(el=>{el.hidden=activeFilter!=='all'&&el.dataset.status!==activeFilter;if(!el.hidden)visible++;});
 document.querySelectorAll('[data-filter]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.filter===activeFilter)));
 questionCount.textContent='Показано '+visible+' из '+questions.length+' · порядок поступления';
 emptyState.hidden=visible>0;
 emptyState.textContent=questions.length?'В этой категории вопросов нет':'Пока вопросов нет';
}
function renderQuestions(items){
 questions=(Array.isArray(items)?items:[]).filter(q=>q.status!=='hidden').sort((a,b)=>Number(a.id)-Number(b.id));
 const existing=new Map(Array.from(questionList.querySelectorAll('[data-question-id]')).map(el=>[el.dataset.questionId,el]));
 const present=new Set(questions.map(q=>String(q.id)));
 for(const q of questions){
  const id=String(q.id),signature=JSON.stringify(q);let card=existing.get(id);
  if(card?.dataset.signature===signature)continue;
  if(!card){card=document.createElement('article');card.dataset.questionId=id;questionList.appendChild(card);}
  card.dataset.signature=signature;card.dataset.status=q.status;card.className='speaker-question-item status-'+q.status;card.replaceChildren();
  const head=document.createElement('div');head.className='speaker-question-head';
  const status=document.createElement('span');status.textContent=statusLabels[q.status]||q.status;
  const number=document.createElement('strong');number.textContent='#'+id;head.append(status,number);
  const text=document.createElement('div');text.className='speaker-question-body';text.textContent=q.question_text;
  const meta=document.createElement('div');meta.className='speaker-question-meta';meta.textContent=[q.participant_name,q.organization,q.created_at].filter(Boolean).join(' · ');
  if(q.speaker_name||q.session_title){const session=document.createElement('div');session.textContent=[q.speaker_name,q.session_title].filter(Boolean).join(' — ');meta.appendChild(session);}
  card.append(head,text,meta);
 }
 existing.forEach((el,id)=>{if(!present.has(id))el.remove();});
 // Status and votes never move a card or reset the page scroll.
 let previous=null;
 for(const q of questions){const card=questionList.querySelector('[data-question-id="'+Number(q.id)+'"]');const next=previous?previous.nextElementSibling:questionList.firstElementChild;if(next!==card)questionList.insertBefore(card,next);previous=card;}
 applyFilter();
}
document.getElementById('speakerFilters').addEventListener('click',e=>{const button=e.target.closest('[data-filter]');if(!button)return;activeFilter=button.dataset.filter;applyFilter();});
async function loadState(){
 if(loading)return;loading=true;
 try{
  const r=await fetch('/qa/state.php',{cache:'no-store',credentials:'same-origin',signal:AbortSignal.timeout(10000)});
  if(r.status===401){location.reload();return;}
  if(!r.ok)throw new Error('http');
  const d=await r.json();if(!d.ok)throw new Error('api');
  connectionStatus.textContent='● онлайн';
  sessionLabel.textContent=d.session?d.session.speaker_name+' — '+d.session.title:'Текущий доклад не выбран';
  renderQuestions(d.questions);
 }catch(e){connectionStatus.textContent='● нет связи · повторяем';}
 finally{loading=false;}
}
loadState();setInterval(loadState,2000);
document.addEventListener('visibilitychange',()=>{if(!document.hidden)loadState();});
document.addEventListener('keydown',e=>{if(e.key==='f'||e.key==='F'||e.key==='а'||e.key==='А'){if(!document.fullscreenElement){document.documentElement.requestFullscreen?.();}else{document.exitFullscreen?.();}}});
</script>
<?php endif; ?>
</body>
</html>
