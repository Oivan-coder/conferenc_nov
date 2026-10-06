<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
const PROD_EVENT='forum-lab-innovations-2026-10-07';
const PROD_FILE='/home/c/cx314477/public_html/.private/reminder-production-20261006.json';
if(empty($_SESSION['prod_reminder_csrf']))$_SESSION['prod_reminder_csrf']=bin2hex(random_bytes(32));
function rh($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function reminderBody(array $r,string $format,string $token):string{
 $p='<p>Здравствуйте, <strong>'.rh($r['full_name']).'</strong>!</p><p>Уже завтра, <strong>7 октября</strong>, состоится Форум лабораторных инноваций Московской области.</p><p><strong>Начало программы — в 10:00 по московскому времени.</strong></p>';
 if($format==='offline'){
  $url='https://rclsmo.ru/participant.php?t='.rawurlencode($token);
  $p.='<p><strong>Место проведения:</strong> Дом Правительства Московской области, Красногорск, бульвар Строителей, 1.</p><p style="padding:16px;background:#fff2d8;border-radius:8px"><strong>Обязательно возьмите паспорт: он необходим для прохода на территорию.</strong></p><p>На регистрации покажите ваш QR-код:</p><p style="text-align:center"><img src="cid:participant_qr" alt="Персональный QR-код для регистрации" width="280" height="280"></p><p style="text-align:center"><a style="display:inline-block;background:#214f3b;color:white;padding:14px 24px;text-decoration:none;border-radius:8px" href="'.rh($url).'">Открыть мой билет</a></p><p>Код участника: <strong>'.rh($r['participant_code']).'</strong>. Сохраните билет заранее, чтобы быстро показать его на входе.</p>';
 }else{
  $url='https://rclsmo.ru/live/?t='.rawurlencode($token);
  $p.='<p>Подключайтесь к онлайн-трансляции по кнопке ниже. Повторная регистрация не нужна.</p><p style="text-align:center"><a style="display:inline-block;background:#214f3b;color:white;padding:14px 24px;text-decoration:none;border-radius:8px" href="'.rh($url).'">Открыть трансляцию</a></p><p>Для участников трансляция будет доступна в день форума. Ссылка персональная, не пересылайте её другим людям.</p>';
 }
 return '<!doctype html><html lang="ru"><body style="margin:0;background:#f3f6f4;font:16px/1.6 Arial;color:#173126"><table role="presentation" width="100%"><tr><td align="center" style="padding:24px 12px"><table role="presentation" width="620" style="max-width:100%;background:white;border:1px solid #dbe6df"><tr><td style="background:#214f3b;color:white;padding:24px"><div style="font-size:12px">Референс-центр лабораторной службы МО</div><h1 style="font-size:25px;margin:8px 0">Уже завтра — Форум лабораторных инноваций</h1></td></tr><tr><td style="padding:24px">'.$p.'<p>До встречи на форуме!</p></td></tr><tr><td style="padding:18px 24px;background:#f8faf9">Организационный комитет<br>Референс-центр лабораторной службы МО<br><a href="mailto:info@rclsmo.ru">info@rclsmo.ru</a></td></tr></table></td></tr></table></body></html>';
}
function prodRows(PDO $pdo):array{
 $q=$pdo->prepare('SELECT participant_code,full_name,email,organization,position,participation_format,registration_status,registration_source,qr_token,online_token FROM participants WHERE event_id=? ORDER BY id');$q->execute([PROD_EVENT]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function prodKey(array $r):string{return hash('sha256',json_encode([$r['participant_code'],$r['full_name'],$r['email'],$r['participation_format'],$r['registration_status'],$r['qr_token'],$r['online_token']],JSON_UNESCAPED_UNICODE));}
function prodPlan(array $rows):array{
 $jobs=[];$excluded=[];$issues=[];$emails=[];$names=[];$tokens=[];$counts=['offline'=>0,'online'=>0];
 foreach($rows as $r){
  $org=mb_strtolower(trim($r['organization']));$email=mb_strtolower(trim($r['email']));
  if($r['registration_source']==='test'||in_array($org,['тестовая мо','тест','ovan','oivan'],true)||mb_strtolower(trim($r['full_name']))==='тест тест'){$excluded[]=$r['full_name'].' — тестовая запись';continue;}
  if($r['registration_status']!=='confirmed'){$excluded[]=$r['full_name'].' — статус '.$r['registration_status'];continue;}
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||str_ends_with($email,'.invalid')){$excluded[]=$r['full_name'].' — нет действующего email';continue;}
  $format=$r['participation_format'];if(!isset($counts[$format])){$issues[]=$r['full_name'].' — неизвестный формат';continue;}
  $token=$format==='offline'?$r['qr_token']:$r['online_token'];
  if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/',$token)){$issues[]=$r['full_name'].' — нет персональной ссылки';continue;}
  $name=preg_replace('/\s+/u',' ',str_replace('ё','е',mb_strtolower(trim($r['full_name']))));
  foreach([['email',$email,&$emails],['ФИО',$name,&$names],['токен',$token,&$tokens]] as &$check){
   if(isset($check[2][$check[1]]))$issues[]=$r['full_name'].' — повтор '.$check[0];
   $check[2][$check[1]]=true;
  }unset($check);
  $r['email']=$email;$jobs[$r['participant_code']]=['row'=>$r,'fingerprint'=>prodKey($r),'status'=>'pending'];$counts[$format]++;
 }
 // Hold every member of an ambiguous group instead of guessing a format.
 $emailCounts=[];$nameCounts=[];$tokenCounts=[];
 foreach($jobs as $job){$r=$job['row'];$n=preg_replace('/\\s+/u',' ',str_replace('ё','е',mb_strtolower(trim($r['full_name']))));$t=$r['participation_format']==='offline'?$r['qr_token']:$r['online_token'];$emailCounts[$r['email']]=($emailCounts[$r['email']]??0)+1;$nameCounts[$n]=($nameCounts[$n]??0)+1;$tokenCounts[$t]=($tokenCounts[$t]??0)+1;}
 foreach($jobs as $code=>$job){$r=$job['row'];$n=preg_replace('/\\s+/u',' ',str_replace('ё','е',mb_strtolower(trim($r['full_name']))));$t=$r['participation_format']==='offline'?$r['qr_token']:$r['online_token'];if($emailCounts[$r['email']]>1||$nameCounts[$n]>1||$tokenCounts[$t]>1){$excluded[]=$r['full_name'].' · '.$code.' — повтор, формат требует уточнения';unset($jobs[$code]);}}
 $issues=array_values(array_filter($issues,static fn($issue)=>!str_contains($issue,' — повтор ')));
 $counts=['offline'=>0,'online'=>0];foreach($jobs as $job)$counts[$job['row']['participation_format']]++;
 return compact('jobs','excluded','issues','counts');
}
function prodSave(array $campaign):void{
 $tmp=PROD_FILE.'.tmp';if(file_put_contents($tmp,json_encode($campaign,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new RuntimeException('Не удалось сохранить журнал');
 chmod($tmp,0600);if(!rename($tmp,PROD_FILE))throw new RuntimeException('Не удалось обновить журнал');
}
$notice='';$plan=['jobs'=>[],'excluded'=>[],'issues'=>[],'counts'=>['offline'=>0,'online'=>0]];$campaign=null;
try{
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $rows=prodRows($pdo);$plan=prodPlan($rows);
 if(is_readable(PROD_FILE)){$campaign=json_decode(file_get_contents(PROD_FILE),true);if(!is_array($campaign))throw new RuntimeException('Журнал повреждён');}
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($_SESSION['prod_reminder_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Обновите страницу');
  if((new DateTimeImmutable('now',new DateTimeZone('Europe/Moscow')))->format('Y-m-d')!=='2026-10-06')throw new RuntimeException('Напоминание рассчитано на 6 октября');
  $lock=fopen(PROD_FILE.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Рассылка уже выполняется');
  try{
   $campaign=is_readable(PROD_FILE)?json_decode(file_get_contents(PROD_FILE),true):null;
   if(($_POST['action']??'')==='start'&&!$campaign){
    if($plan['issues'])throw new RuntimeException('Проверка выявила ошибки. Отправка заблокирована.');
    $snapshot=hash('sha256',json_encode($plan['jobs'],JSON_UNESCAPED_UNICODE));
    if(!hash_equals($snapshot,(string)($_POST['snapshot']??'')))throw new RuntimeException('Список изменился. Проверьте свежий состав.');
    require_once SMTP_AUTOLOAD_PATH;
    if(!class_exists(Endroid\QrCode\Writer\PngWriter::class)||!function_exists('imagecreatetruecolor')||(new ReflectionFunction('sendConfiguredMail'))->getNumberOfParameters()<5)throw new RuntimeException('Отправка QR не готова');
    // Verify every actual QR before any production message is sent.
    foreach($plan['jobs'] as $job){$r=$job['row'];if($r['participation_format']!=='offline')continue;$qr=Endroid\QrCode\QrCode::create('https://rclsmo.ru/participant.php?t='.$r['qr_token'])->setSize(320)->setMargin(12);$png=(new Endroid\QrCode\Writer\PngWriter())->write($qr)->getString();if(substr($png,0,8)!=="\x89PNG\r\n\x1a\n")throw new RuntimeException('Не удалось проверить QR');}
    $campaign=['created_at'=>date('c'),'jobs'=>$plan['jobs'],'counts'=>$plan['counts'],'excluded'=>$plan['excluded']];prodSave($campaign);$notice='Проверка завершена. Состав рассылки зафиксирован.';
   }elseif(($_POST['action']??'')==='batch'&&$campaign){
    require_once SMTP_AUTOLOAD_PATH;
    $current=[];foreach(prodRows($pdo) as $r){$r['email']=mb_strtolower(trim($r['email']));$current[$r['participant_code']]=$r;}
    $processed=0;
    foreach($campaign['jobs'] as $code=>&$job){
     if($job['status']!=='pending')continue;if($processed>=10)break;$processed++;
     if(!isset($current[$code])||!hash_equals($job['fingerprint'],prodKey($current[$code]))){$job['status']='changed';prodSave($campaign);continue;}
     $r=$current[$code];$format=$r['participation_format'];$token=$format==='offline'?$r['qr_token']:$r['online_token'];$inline=[];
     if($format==='offline'){$qr=Endroid\QrCode\QrCode::create('https://rclsmo.ru/participant.php?t='.$token)->setSize(320)->setMargin(12)->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::Medium);$inline=['participant_qr'=>['data'=>(new Endroid\QrCode\Writer\PngWriter())->write($qr)->getString()]];}
     $html=reminderBody($r,$format,$token);if(str_contains($html,'ТЕСТ')||str_contains($html,'тестовой'))throw new RuntimeException('Тестовый текст в боевом письме');
     $job['status']='sending';$job['attempted_at']=date('c');prodSave($campaign);
     $ok=sendConfiguredMail($r['email'],'Уже завтра — Форум лабораторных инноваций, 7 октября', $html,[],$inline);
     $job['status']=$ok?'accepted':'failed';$job['finished_at']=date('c');prodSave($campaign);
    }unset($job);$notice='Партия обработана: '.$processed.'. Повторная отправка исключена журналом.';
   }
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}catch(Throwable $e){$notice=$e instanceof PDOException?'Ошибка базы. Отправка остановлена.':$e->getMessage();}
$stats=['pending'=>0,'accepted'=>0,'failed'=>0,'sending'=>0,'changed'=>0];if($campaign)foreach($campaign['jobs'] as $j)$stats[$j['status']]++;
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Боевое напоминание — 7 октября</title><style>body{font:17px/1.5 Arial;color:#173126;margin:32px;background:#f4f7f5}main{background:white;padding:25px;border:1px solid #dbe6df;border-radius:14px;max-width:1200px}td,th{padding:10px;border-bottom:1px solid #dbe6df;text-align:left}button{font:inherit;background:#214f3b;color:white;border:0;border-radius:8px;padding:14px;margin:15px 0}.summary{padding:18px;background:#e5f4ea;border-radius:10px;font-size:19px}.error{color:#a22}</style><main><h1>Боевое напоминание — форум 7 октября</h1><p><?=rh($notice)?></p>
<?php if(!$campaign):?><p>Предварительная проверка: очно <?=$plan['counts']['offline']?> · онлайн <?=$plan['counts']['online']?> · всего <?=count($plan['jobs'])?> адресатов.</p><p>Исключены: <?=count($plan['excluded'])?>. Ошибок: <?=count($plan['issues'])?>.</p><?php foreach($plan['issues'] as $i):?><p class="error"><?=rh($i)?></p><?php endforeach?><form method="post"><input type="hidden" name="csrf" value="<?=rh($_SESSION['prod_reminder_csrf'])?>"><input type="hidden" name="snapshot" value="<?=rh(hash('sha256',json_encode($plan['jobs'],JSON_UNESCAPED_UNICODE)))?>"><button name="action" value="start">Зафиксировать проверенный список</button></form>
<?php else:?><div class="summary">Всего: <?=count($campaign['jobs'])?> · Очно: <?=$campaign['counts']['offline']?> · Онлайн: <?=$campaign['counts']['online']?><br>Принято SMTP: <?=$stats['accepted']?> · Осталось: <?=$stats['pending']?> · Ошибки: <?=$stats['failed']?> · Требуют сверки: <?=$stats['sending']+$stats['changed']?></div><p>Состав зафиксирован: <?=rh($campaign['created_at'])?>. Исключены: <?=count($campaign['excluded'])?>.</p><?php if($stats['pending']):?><form method="post"><input type="hidden" name="csrf" value="<?=rh($_SESSION['prod_reminder_csrf'])?>"><button name="action" value="batch">Отправить следующую партию — до 10 писем</button></form><?php else:?><h2>Рассылка завершена</h2><?php endif?><p>Статус означает приём почтовым сервером, а не подтверждение прочтения.</p><?php endif?>
<details><summary>Исключённые записи</summary><?php foreach(($campaign['excluded']??$plan['excluded']) as $x):?><p><?=rh($x)?></p><?php endforeach?></details><table><tr><th>Участник</th><th>Email</th><th>Формат</th><th>Статус</th></tr><?php foreach(($campaign['jobs']??$plan['jobs']) as $job):$r=$job['row'];?><tr><td><?=rh($r['full_name'])?></td><td><?=rh($r['email'])?></td><td><?=$r['participation_format']==='offline'?'Очно':'Онлайн'?></td><td><?=rh($job['status'])?></td></tr><?php endforeach?></table></main></html>