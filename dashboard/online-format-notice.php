<?php
session_start();
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['conference_dashboard_auth'])) { http_response_code(403); exit('Требуется вход организатора'); }
require_once dirname(__DIR__).'/api/smtp-mailer.php';
$event='forum-lab-innovations-2026-10-07';
$targets=['LE2806A37D'=>['Царикаева Ангелина Артуровна','czarikaeva.lina@mail.ru'],'LE985C924B'=>['Лазарева Наталья Вячеславовна','kore79@mail.ru']];
if(empty($_SESSION['format_notice_csrf'])) $_SESSION['format_notice_csrf']=bin2hex(random_bytes(32));
function fnH($s) { return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$notice='';$rows=[];$receiptPath='/home/c/cx314477/public_html/.private/online-format-notice-20261006.json';
try {
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $receipts=is_readable($receiptPath)?json_decode(file_get_contents($receiptPath),true):[]; if(!is_array($receipts)) $receipts=[];
 $select=$pdo->prepare('SELECT id,participant_code,full_name,email,participation_format,registration_status,online_token FROM participants WHERE event_id=? AND participant_code=?');
 foreach($targets as $code=>$expected) {
  $select->execute([$event,$code]);$r=$select->fetch(PDO::FETCH_ASSOC);
  if(!$r || $r['full_name']!==$expected[0] || mb_strtolower($r['email'])!==$expected[1]) throw new RuntimeException('Данные адресата изменились: требуется сверка');
  $rows[]=$r;
 }
 if($_SERVER['REQUEST_METHOD']==='POST') {
  if(!hash_equals($_SESSION['format_notice_csrf'],(string)($_POST['csrf']??''))) throw new RuntimeException('Обновите страницу');
  $action=(string)($_POST['action']??'');
  if($action==='change') {
   $pdo->beginTransaction();
   $update=$pdo->prepare('UPDATE participants SET participation_format="online",online_token=:token WHERE id=:id AND event_id=:event');
   foreach($rows as $r) {
    $token=$r['online_token']?:bin2hex(random_bytes(32));
    $update->execute([':token'=>$token,':id'=>$r['id'],':event'=>$event]);
   }
   $pdo->commit();$notice='Обе регистрации переведены в онлайн. Персональные ссылки сохранены.';
  } elseif($action==='send') {
   foreach($rows as $r) {
    if($r['participation_format']!=='online'||!$r['online_token']) throw new RuntimeException('Сначала измените формат');
    if(!empty($receipts[$r['participant_code']]['sent'])) continue;
    $live='https://rclsmo.ru/live/?t='.rawurlencode($r['online_token']);
    $body='<p>Здравствуйте, '.fnH($r['full_name']).'!</p><p>Ваша регистрация на Форум лабораторных инноваций Московской области переведена в онлайн-формат. Повторно регистрироваться не нужно.</p><p>Форум состоится 7 октября 2026 года. Начало программы — в 10:00 (московское время).</p><p><a href="'.fnH($live).'">Открыть онлайн-трансляцию</a></p><p>Это Ваша персональная ссылка для подключения. Трансляция будет доступна в день форума.</p>';
    if($r['participant_code']==='LE2806A37D') $body.='<p>Сертификат участника по итогам форума не предусмотрен.</p>';
    $body.='<p>С уважением,<br>Организационный комитет Форума лабораторных инноваций Московской области<br>Референс-центр лабораторной службы МО<br>info@rclsmo.ru</p>';
    $ok=sendConfiguredMail($r['email'],'Онлайн-участие — Форум лабораторных инноваций 7 октября 2026',$body,['info@rclsmo.ru']);
    $receipts[$r['participant_code']]=['sent'=>$ok,'at'=>date('c'),'to'=>$r['email'],'cc'=>'info@rclsmo.ru'];
    if(file_put_contents($receiptPath,json_encode($receipts,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false) throw new RuntimeException('Не удалось сохранить результат отправки; не повторяйте отправку автоматически');
    chmod($receiptPath,0600);
   }
   $notice='Отправка завершена. Результаты ниже. Статус означает приём писем SMTP-сервером, а не подтверждение прочтения.';
  }
  $rows=[];foreach($targets as $code=>$expected){$select->execute([$event,$code]);$rows[]=$select->fetch(PDO::FETCH_ASSOC);}
 }
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$notice=$e instanceof PDOException?'Ошибка базы; изменения отменены.':$e->getMessage();}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Онлайн-участие и письма</title><style>body{font:16px Arial;margin:40px;color:#173126}td,th{padding:14px;border-bottom:1px solid #ddd;text-align:left}button{padding:14px;background:#214f3b;color:white;border:0;border-radius:8px;margin:12px 8px 0 0}</style>
<h1>Онлайн-участие — Царикаева и Лазарева</h1><p>Отправитель и копия: info@rclsmo.ru. Каждой участнице — отдельная персональная ссылка.</p><p><?=fnH($notice)?></p>
<table><tr><th>ФИО</th><th>Email</th><th>Формат</th><th>Письмо с копией организаторам</th></tr><?php foreach($rows as $r):?><tr><td><?=fnH($r['full_name'])?></td><td><?=fnH($r['email'])?></td><td><?=fnH($r['participation_format']==='online'?'Онлайн':'Очно')?></td><td><?=!empty($receipts[$r['participant_code']]['sent'])?'Принято SMTP-сервером, копия info@rclsmo.ru':(isset($receipts[$r['participant_code']])?'Не удалось отправить':'Не отправлено')?></td></tr><?php endforeach?></table>
<form method="post"><input type="hidden" name="csrf" value="<?=fnH($_SESSION['format_notice_csrf'])?>"><button name="action" value="change">Перевести обе регистрации в онлайн</button><button name="action" value="send">Отправить ссылки с копией info@rclsmo.ru</button></form>
<p>В письме: онлайн-формат подтверждён, повторная регистрация не нужна, 7 октября в 10:00 МСК, персональная ссылка на трансляцию. Для Царикаевой — ответ, что сертификат не предусмотрен.</p>
</html>