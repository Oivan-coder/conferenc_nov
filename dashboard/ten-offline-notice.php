<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
const TEN_EVENT='forum-lab-innovations-2026-10-07';
const TEN_LOG='/home/c/cx314477/public_html/.private/ten-offline-20261006.json';
function th($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function tenRow(PDO $pdo,bool $lock=false):array {
 $q=$pdo->prepare('SELECT id,participant_code,full_name,email,participation_format,registration_status,qr_token FROM participants WHERE event_id=? AND participant_code=?'.($lock?' FOR UPDATE':''));
 $q->execute([TEN_EVENT,'LE1D2DEA66']);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
 if(count($rows)!==1)throw new RuntimeException('Регистрация не найдена однозначно');
 $r=$rows[0];
 if($r['full_name']!=='Тен Евгения Александровна'||mb_strtolower(trim($r['email']))!=='ferleo@mail.ru'||$r['registration_status']!=='confirmed')throw new RuntimeException('Данные изменились: требуется сверка');
 return $r;
}
function tenSave(array $log):void{
 $tmp=TEN_LOG.'.tmp';
 if(file_put_contents($tmp,json_encode($log,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new RuntimeException('Не удалось сохранить журнал');
 chmod($tmp,0600);
 if(!rename($tmp,TEN_LOG))throw new RuntimeException('Не удалось обновить журнал');
}
if(empty($_SESSION['ten_csrf']))$_SESSION['ten_csrf']=bin2hex(random_bytes(32));
$notice='';$r=null;$log=[];
try {
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $r=tenRow($pdo);
 if(is_readable(TEN_LOG)){$log=json_decode(file_get_contents(TEN_LOG),true);if(!is_array($log))throw new RuntimeException('Журнал повреждён');}
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($_SESSION['ten_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Обновите страницу');
  $lock=fopen(TEN_LOG.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Операция уже выполняется');
  try{
   $log=is_readable(TEN_LOG)?json_decode(file_get_contents(TEN_LOG),true):[];
   if(!is_array($log))throw new RuntimeException('Журнал повреждён');
   $action=(string)($_POST['action']??'');
   if($action==='change'){
    $pdo->beginTransaction();$r=tenRow($pdo,true);
    if($r['participation_format']!=='offline'){
     if($r['participation_format']!=='online')throw new RuntimeException('Неизвестный формат');
     $log['before']=$r;$log['changed_at']=date('c');tenSave($log);
     $token=$r['qr_token']?:bin2hex(random_bytes(32));
     if(!preg_match('/^[a-f0-9]{64}$/',$token))throw new RuntimeException('Некорректный QR-токен');
     $pdo->prepare('UPDATE participants SET participation_format="offline",qr_token=? WHERE id=? AND event_id=?')->execute([$token,$r['id'],TEN_EVENT]);
    }
    $pdo->commit();$notice='Тен Евгения Александровна переведена в очный формат. Код участника сохранён.';
   }elseif($action==='send'){
    $r=tenRow($pdo);
    if($r['participation_format']!=='offline'||!preg_match('/^[a-f0-9]{64}$/',(string)$r['qr_token']))throw new RuntimeException('Сначала переведите регистрацию в очный формат');
    if(isset($log['mail_status']))throw new RuntimeException('Попытка отправки уже зарегистрирована: '.$log['mail_status'].'. Повторная отправка заблокирована.');
    require_once SMTP_AUTOLOAD_PATH;
    if((new ReflectionFunction('sendConfiguredMail'))->getNumberOfParameters()<5)throw new RuntimeException('Почтовый модуль не поддерживает встроенный QR');
    $url='https://rclsmo.ru/participant.php?t='.rawurlencode($r['qr_token']);
    $qr=Endroid\QrCode\QrCode::create($url)->setSize(320)->setMargin(12);
    $png=(new Endroid\QrCode\Writer\PngWriter())->write($qr)->getString();
    if(substr($png,0,8)!=="\x89PNG\r\n\x1a\n")throw new RuntimeException('Не удалось сформировать QR');
    $body='<p>Уважаемая Евгения Александровна!</p><p>По Вашей просьбе формат участия в Форуме лабораторных инноваций Московской области изменён на <strong>очный</strong>. Ваша регистрация подтверждена. Повторно регистрироваться не нужно.</p><p><strong>Дата:</strong> 7 октября 2026 года.<br><strong>Начало программы:</strong> 10:00 (московское время).<br><strong>Место проведения:</strong> Дом Правительства Московской области, Красногорск, бульвар Строителей, 1.</p><p><strong>Пожалуйста, возьмите паспорт для прохода на территорию.</strong></p><p>На регистрации покажите персональный QR-код:</p><p><img src="cid:participant_qr" alt="QR-код участника" width="280" height="280"></p><p><a href="'.th($url).'">Открыть Ваш билет</a></p><p>Код участника: <strong>LE1D2DEA66</strong>. Билет можно сохранить на телефоне или распечатать.</p><p>До встречи на форуме!</p><p>С уважением,<br>Организационный комитет Форума лабораторных инноваций Московской области<br>Референс-центр лабораторной службы МО<br>info@rclsmo.ru</p>';
    $log['mail_status']='sending';$log['attempted_at']=date('c');$log['to']=$r['email'];tenSave($log);
    $ok=sendConfiguredMail($r['email'],'Очное участие подтверждено — Ваш билет на Форум 7 октября',$body,[],['participant_qr'=>['data'=>$png]]);
    $log['mail_status']=$ok?'accepted':'failed';$log['finished_at']=date('c');tenSave($log);
    $notice=$ok?'Письмо с QR-кодом и ссылкой на билет принято SMTP-сервером.':'Не удалось отправить письмо.';
   }else{throw new RuntimeException('Неизвестная операция');}
  }finally{flock($lock,LOCK_UN);fclose($lock);}
  $r=tenRow($pdo);
 }
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$notice=$e instanceof PDOException?'Ошибка базы. Изменения отменены.':$e->getMessage();}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Тен — очное участие и билет</title><style>body{font:17px/1.5 Arial;margin:36px;color:#173126}main{max-width:800px}button{font:inherit;background:#214f3b;color:white;border:0;border-radius:8px;padding:14px;margin:8px}p{margin:14px 0}</style><main><a href="/dashboard/">Дашборд</a><h1>Тен Евгения Александровна</h1><p><?=th($notice)?></p><?php if($r):?><p>Адрес: <?=th($r['email'])?><br>Код: <?=th($r['participant_code'])?><br>Формат: <?=$r['participation_format']==='offline'?'Очно':'Онлайн'?><br>Регистрация: подтверждена<br>Письмо: <?=th($log['mail_status']??'не отправлено')?></p><form method="post"><input type="hidden" name="csrf" value="<?=th($_SESSION['ten_csrf'])?>"><?php if($r['participation_format']!=='offline'):?><button name="action" value="change">Перевести в очный формат</button><?php elseif(!isset($log['mail_status'])):?><button name="action" value="send">Отправить билет и QR на ferleo@mail.ru</button><?php endif?></form><p>Отправитель: info@rclsmo.ru. Письмо содержит подтверждение очного участия, дату и место форума, напоминание о паспорте, встроенный QR и персональную ссылку на билет.</p><?php endif?></main></html>
