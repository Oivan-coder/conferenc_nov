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
 $insert=$pdo->prepare('INSERT INTO participants (event_id,participant_code,qr_token,online_token,last_name,first_name,middle_name,full_name,position,organization,email,email_normalized,phone,phone_normalized,participation_format,registration_status,registration_source,privacy_consent,consent_version,consent_at,created_at) VALUES (?,?,?,NULL,?,?,?,?,?,?,?, ?,NULL,NULL,"offline","confirmed","invited",0,"organizer-volunteer-list-2026-10-06",NULL,NOW())');
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
<h1>Волонтёры — бейджи форума</h1><p><?=vh($org)?></p>
<p>7 волонтёров, очное участие. Технические email для бейджей; письма не отправляются. Согласие участников не отмечается.</p>
<p><?=vh($notice)?></p>
<?php if(count($results)<7): ?><ul><?php foreach($names as $n):?><li><?=vh(trim(implode(' ',$n)))?></li><?php endforeach?></ul>
<form method="post"><input type="hidden" name="csrf" value="<?=vh($_SESSION['volunteer_import_csrf'])?>"><button type="submit">Добавить 7 волонтёров для бейджей</button></form><?php endif?>
<h2>В регистрации: <?=count($results)?> из 7</h2><table><tr><th>ФИО</th><th>Должность</th><th>Формат</th><th>Код бейджа</th></tr><?php foreach($results as $r):?><tr><td><?=vh($r['full_name'])?></td><td><?=vh($r['position'])?></td><td><?=vh($r['participation_format']==='offline'?'Очно':$r['participation_format'])?></td><td><?=vh($r['participant_code'])?></td></tr><?php endforeach?></table><p><a href="/dashboard/">Дашборд и печать бейджей</a></p></html>