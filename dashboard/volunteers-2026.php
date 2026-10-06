<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
if (empty($_SESSION['conference_dashboard_auth'])) { http_response_code(403); exit('Требуется вход организатора'); }
$event='forum-lab-innovations-2026-10-07';
$org='ГБПОУ МО «Московский областной медицинский колледж»';
$names=[
['Николаева','Милана','Дмитриевна'],
['Тошпуланова','Нозанин','Хусанбойевна'],
['Знамова','Ксения','Михайловна'],
['Погосян','Цолак','Жанович'],
['Овчинникова','Виктория','Сергеевна'],
['Шиловски','София',''],
['Тошпулатов','Боймухаммад','Нурмухаммадович'],
];
if (empty($_SESSION['volunteer_import_csrf'])) $_SESSION['volunteer_import_csrf']=bin2hex(random_bytes(32));
function vh($s) { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$notice=''; $results=[];
try {
$pdo=require '/home/c/cx314477/public_html/.private/db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
if ($_SERVER['REQUEST_METHOD']==='POST') {
 if (!hash_equals($_SESSION['volunteer_import_csrf'],(string)($_POST['csrf']??''))) throw new RuntimeException('Обновите страницу');
 $pdo->beginTransaction();
 $find=$pdo->prepare('SELECT participant_code FROM participants WHERE event_id=? AND full_name=? LIMIT 1 FOR UPDATE');
 $insert=$pdo->prepare('INSERT INTO participants (event_id,participant_code,qr_token,online_token,last_name,first_name,middle_name,full_name,position,organization,email,email_normalized,phone,phone_normalized,participation_format,registration_status,registration_source,privacy_consent,consent_version,consent_at,created_at) VALUES (?,?,?,NULL,?,?,?,?,?,?,?, ?,NULL,NULL,"offline","confirmed","invited",0,"organizer-list-2026-10-06",NOW(),NOW())');
 foreach($names as $n) {
  $full=trim(implode(' ',$n)); $find->execute([$event,$full]);
  if ($find->fetchColumn()) continue;
  $code='';
  for($i=0;$i<20;$i++) {
   $candidate='LE'.strtoupper(bin2hex(random_bytes(4)));
   $check=$pdo->prepare('SELECT 1 FROM participants WHERE participant_code=?'); $check->execute([$candidate]);
   if (!$check->fetchColumn()) { $code=$candidate; break; }
  }
  if (!$code) throw new RuntimeException('Не удалось создать код');
  $email='volunteer-'.substr(hash('sha256',$event.$full),0,20).'@badge.invalid';
  $insert->execute([$event,$code,bin2hex(random_bytes(32)),$n[0],$n[1],$n[2],$full,'Волонтёр',$org,$email,$email]);
 }
 $pdo->commit(); $notice='Волонтёры добавлены. Повторный запуск не создаёт дубли.';
}
$lookup=$pdo->prepare('SELECT participant_code, full_name, position, organization, participation_format FROM participants WHERE event_id=? AND full_name=?');
foreach($names as $n) { $lookup->execute([$event,trim(implode(' ',$n))]); $r=$lookup->fetch(PDO::FETCH_ASSOC); if($r) $results[]=$r; }
} catch(Throwable $e) { if(isset($pdo)&&$pdo->inTransaction()) $pdo->rollBack(); $notice=$e instanceof PDOException?'Не удалось добавить записи. Изменения отменены.':$e->getMessage(); }
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Регистрация волонтёров</title>
<style>body{font:16px Arial;margin:40px;color:#173126}table{border-collapse:collapse}td,th{padding:12px;border-bottom:1px solid #ccc;text-align:left}button{padding:14px;background:#214f3b;color:white;border:0;border-radius:8px}</style>
<nav style="display:flex;gap:16px;margin-bottom:24px"><a href="/dashboard/">Регистрация</a><strong>Волонтёры</strong></nav><h1>Волонтёры — бейджи форума</h1><p><?=vh($org)?></p>
<p>7 волонтёров, очное участие. Технические email для бейджей; письма не отправляются. Согласие участников не отмечается.</p>
<p><?=vh($notice)?></p>
<?php if(count($results)<7): ?><ul><?php foreach($names as $n):?><li><?=vh(trim(implode(' ',$n)))?></li><?php endforeach?></ul>
<form method="post"><input type="hidden" name="csrf" value="<?=vh($_SESSION['volunteer_import_csrf'])?>"><button type="submit">Добавить 7 волонтёров для бейджей</button></form><?php endif?>
<p><button type="button" id="print-all"<?= !$results ? ' disabled' : '' ?>>Напечатать все этикетки</button> <button type="button" id="check-printer">Проверить сервис печати</button></p><p id="print-status" role="status" aria-live="polite">Печать через сервис на этом компьютере.</p><h2>В регистрации: <?=count($results)?> из 7</h2><table><tr><th>ФИО</th><th>Должность</th><th>Формат</th><th>Код бейджа</th><th>Печать</th></tr><?php foreach($results as $r):?><tr><td><?=vh($r['full_name'])?></td><td><?=vh($r['position'])?></td><td><?=vh($r['participation_format']==='offline'?'Очно':$r['participation_format'])?></td><td><?=vh($r['participant_code'])?></td><td><button type="button" data-print-code="<?=vh($r['participant_code'])?>">Напечатать</button><span data-print-result style="display:block;margin-top:8px"></span></td></tr><?php endforeach?></table><p><a href="/dashboard/">Дашборд и печать бейджей</a></p>
<script>
(() => {
 const bridge='http://127.0.0.1:5030';
 const status=document.getElementById('print-status');
 const buttons=[...document.querySelectorAll('[data-print-code]')];
 const all=document.getElementById('print-all');
 const check=document.getElementById('check-printer');
 const sent=new Set(); let busy=false;
 function lock(value){busy=value;buttons.forEach(b=>b.disabled=value);all.disabled=value||!buttons.length;check.disabled=value;}
 async function request(url,options={}){
  const controller=new AbortController();const timer=setTimeout(()=>controller.abort(),20000);
  try {return await fetch(url,{...options,signal:controller.signal,cache:'no-store'});}
  finally {clearTimeout(timer);}
 }
 async function health(){
  const r=await request(bridge+'/health',{mode:'cors'});const data=await r.json();
  if(!r.ok||data.status!=='success')throw Error('Сервис печати недоступен');
  return data;
 }
 async function print(b){
  const code=b.dataset.printCode;
  const result=b.parentElement.querySelector('[data-print-result]');
  status.textContent='Печать '+code+'…';result.textContent='Подготовка…';
  const r=await request('/dashboard/badge-zpl.php?code='+encodeURIComponent(code),{credentials:'same-origin'});
  const zpl=await r.text();
  if(!r.ok||!zpl.startsWith('^XA')||!zpl.trimEnd().endsWith('^XZ')){
   result.textContent='Не удалось сформировать этикетку';throw Error('Ошибка этикетки '+code);
  }
  result.textContent='Отправка…';
  try{
   const response=await request(bridge+'/print',{method:'POST',mode:'cors',headers:{'Content-Type':'application/json'},body:JSON.stringify({participant_id:code,zpl,action:'print_badge'})});
   const data=await response.json();
   if(!response.ok||data.status!=='success')throw Error(data.message||'Ошибка сервиса печати');
  }catch(e){result.textContent='Проверьте принтер перед повторной печатью';throw e;}
  sent.add(code);result.textContent='Передано на принтер';b.textContent='Напечатать ещё раз';
 }
 async function run(queue){
  if(busy)return;lock(true);let count=0;
  try{
   await health();
   for(const b of queue){await print(b);count++;}
   status.textContent='Передано на принтер: '+count+' этикеток.';
  }catch(e){status.textContent='Печать остановлена. Передано в этом запуске: '+count+'. '+(e.name==='AbortError'?'Сервис не ответил вовремя. Проверьте очередь принтера.':e.message);}
  finally{lock(false);}
 }
 buttons.forEach(b=>b.addEventListener('click',()=>run([b])));
 all.addEventListener('click',()=>{
  const queue=buttons.filter(b=>!sent.has(b.dataset.printCode));
  if(!queue.length){status.textContent='Все этикетки уже переданы. Для повторной печати используйте кнопку рядом с ФИО.';return;}
  run(queue);
 });
 check.addEventListener('click',async()=>{
  if(busy)return;lock(true);status.textContent='Проверка сервиса…';
  try{const data=await health();status.textContent='Сервис готов. Принтер: '+(data.printer||'по умолчанию');}
  catch(e){status.textContent='Сервис печати недоступен на этом компьютере.';}
  finally{lock(false);}
 });
})();
</script></html>