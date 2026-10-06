<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
const REMINDER_EVENT='forum-lab-innovations-2026-10-07';
const REMINDER_RECEIPT='/home/c/cx314477/public_html/.private/reminder-test-20261006.json';
$targets=['LE5E2F6BBD'=>['Гольцев Иван Михайлович','ge.vo.m@mail.ru'],'LE6CD9C2C4'=>['Довгаль Лада Алексеевна','ladadovgal@yandex.ru'],'LE0909BA20'=>['Шоль Елизавета Викторовна','kaynovaelizaveta@mail.ru']];
if(empty($_SESSION['reminder_csrf']))$_SESSION['reminder_csrf']=bin2hex(random_bytes(32));
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
 return '<!doctype html><html lang="ru"><body style="margin:0;background:#f3f6f4;font:16px/1.6 Arial;color:#173126"><table role="presentation" width="100%"><tr><td align="center" style="padding:24px 12px"><table role="presentation" width="620" style="max-width:100%;background:white;border:1px solid #dbe6df"><tr><td style="background:#214f3b;color:white;padding:24px"><div style="font-size:12px">Референс-центр лабораторной службы МО</div><h1 style="font-size:25px;margin:8px 0">Уже завтра — Форум лабораторных инноваций</h1></td></tr><tr><td style="padding:24px"><p style="font-size:13px;color:#85600b">ТЕСТ рассылки для РЦ · '.($format==='offline'?'очный вариант':'онлайн-вариант с тестовой ссылкой; ваша очная регистрация сохраняется').'</p>'.$p.'<p>До встречи на форуме!</p></td></tr><tr><td style="padding:18px 24px;background:#f8faf9">Организационный комитет<br>Референс-центр лабораторной службы МО<br><a href="mailto:info@rclsmo.ru">info@rclsmo.ru</a></td></tr></table></td></tr></table></body></html>';
}
$rows=[];$receipts=[];$notice='';
try{
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 $q=$pdo->prepare('SELECT participant_code,full_name,email,participation_format,registration_status,qr_token,organization FROM participants WHERE event_id=? AND participant_code=?');
 foreach($targets as $code=>$expected){$q->execute([REMINDER_EVENT,$code]);$r=$q->fetch(PDO::FETCH_ASSOC);if(!$r||$r['full_name']!==$expected[0]||mb_strtolower($r['email'])!==$expected[1]||$r['participation_format']!=='offline'||$r['registration_status']!=='confirmed'||!preg_match('/^[a-f0-9]{64}$/',$r['qr_token']))throw new RuntimeException('Данные сотрудника изменились. Отправка остановлена.');$rows[$code]=$r;}
 $q=$pdo->prepare('SELECT online_token FROM participants WHERE event_id=? AND participant_code=? AND organization="Тестовая МО" AND participation_format="online" AND registration_status="confirmed"');$q->execute([REMINDER_EVENT,'LE73BA108F']);$testToken=$q->fetchColumn();
 if(!is_string($testToken)||!preg_match('/^[a-f0-9]{64}$/',$testToken))throw new RuntimeException('Тестовая онлайн-ссылка не найдена. Отправка остановлена.');
 $receipts=is_readable(REMINDER_RECEIPT)?json_decode(file_get_contents(REMINDER_RECEIPT),true):[];if(!is_array($receipts))throw new RuntimeException('Не удалось прочитать журнал отправки');
 if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!hash_equals($_SESSION['reminder_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Обновите страницу');
  if(date('Y-m-d')!=='2026-10-06')throw new RuntimeException('Этот тест рассчитан на 6 октября');
  require_once SMTP_AUTOLOAD_PATH;
  $images=[];
  foreach($rows as $code=>$r){
   $qr=Endroid\QrCode\QrCode::create('https://rclsmo.ru/participant.php?t='.$r['qr_token'])->setSize(320)->setMargin(12)->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::Medium);
   $images[$code]=(new Endroid\QrCode\Writer\PngWriter())->write($qr)->getString();
  }
  $jobs=[];foreach($rows as $code=>$r)$jobs[$code.':offline']=[$r,'offline',$r['qr_token'],['participant_qr'=>['data'=>$images[$code]]]];
  $jobs['LE5E2F6BBD:online']=[$rows['LE5E2F6BBD'],'online',$testToken,[]];
  $lock=fopen(REMINDER_RECEIPT.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Отправка уже выполняется');
  try{
   $receipts=is_readable(REMINDER_RECEIPT)?json_decode(file_get_contents(REMINDER_RECEIPT),true):[];
   foreach($jobs as $key=>[$r,$format,$token,$inline]){
    if(isset($receipts[$key]))continue;
    $receipts[$key]=['status'=>'sending','at'=>date('c'),'to'=>$r['email']];
    if(file_put_contents(REMINDER_RECEIPT,json_encode($receipts,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new RuntimeException('Журнал отправки недоступен');
    chmod(REMINDER_RECEIPT,0600);
    $ok=sendConfiguredMail($r['email'],'[ТЕСТ: '.($format==='offline'?'ОЧНО':'ОНЛАЙН').'] Уже завтра — Форум лабораторных инноваций, 7 октября',reminderBody($r,$format,$token),[],$inline);
    $receipts[$key]['status']=$ok?'accepted':'failed';
    if(file_put_contents(REMINDER_RECEIPT,json_encode($receipts,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)throw new RuntimeException('Не удалось сохранить результат. Не повторяйте отправку');
   }
  }finally{flock($lock,LOCK_UN);fclose($lock);}
  $notice='Тест завершён. Статусы ниже означают приём SMTP-сервером; получение и оформление проверьте в почте.';
 }
}catch(Throwable $e){$notice=$e instanceof PDOException?'Ошибка базы. Отправка остановлена.':$e->getMessage();}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Тест напоминания — РЦ</title><style>body{font:17px/1.5 Arial;color:#173126;margin:40px}td,th{padding:14px;border-bottom:1px solid #dbe6df;text-align:left}button{font:inherit;background:#214f3b;color:white;border:0;border-radius:8px;padding:16px;margin:20px 0}</style><h1>Тест напоминания — только РЦ</h1><p><?=rh($notice)?></p><p>Три очных письма с персональными QR-кодами и паспортом. Ивану дополнительно — онлайн-вариант с существующей тестовой ссылкой. Форматы регистраций не меняются.</p><table><tr><th>Кому</th><th>Почта</th><th>Вариант</th><th>Статус</th></tr><?php foreach($rows as $code=>$r):foreach($code==='LE5E2F6BBD'?['offline','online']:['offline'] as $f):$status=$receipts[$code.':'.$f]['status']??'none';?><tr><td><?=rh($r['full_name'])?></td><td><?=rh($r['email'])?></td><td><?=$f==='offline'?'Очно: QR + паспорт':'Онлайн: тестовая ссылка'?></td><td><?=rh(['accepted'=>'Принято SMTP-сервером','failed'=>'Ошибка отправки','sending'=>'Результат требует проверки','none'=>'Не отправлено'][$status]??$status)?></td></tr><?php endforeach;endforeach?></table><form method="post"><input type="hidden" name="csrf" value="<?=rh($_SESSION['reminder_csrf'])?>"><button>Отправить четыре тестовых письма</button></form><p>Общая рассылка здесь отсутствует. Повторное нажатие не отправляет письма повторно.</p></html>