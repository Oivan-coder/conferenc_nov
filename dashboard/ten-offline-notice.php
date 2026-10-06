<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
if(isset($_GET['ticket_code'])){
 $code=(string)$_GET['ticket_code'];
 if($code!=='LEE312A03C'){http_response_code(400);exit('Некорректный код');}
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';
 $q=$pdo->prepare('SELECT full_name,email,qr_token,participation_format,registration_status FROM participants WHERE event_id=? AND participant_code=?');
 $q->execute(['forum-lab-innovations-2026-10-07',$code]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
 if(count($rows)!==1){http_response_code(404);exit('Участник не найден');}
 $r=$rows[0];
 if($r['full_name']!=='Коршенко Сергей Анатольевич'||strtolower(trim($r['email']))!=='s.korshenko@ilslab.ru'||$r['participation_format']!=='offline'||$r['registration_status']!=='confirmed'||!preg_match('/^[a-f0-9]{64}$/',(string)$r['qr_token'])){http_response_code(409);exit('Данные требуют сверки');}
 $u='https://rclsmo.ru/participant.php?t='.rawurlencode($r['qr_token']);
 echo '<!doctype html><html lang="ru"><meta charset="utf-8"><title>Билет Коршенко</title><h1>Коршенко Сергей Анатольевич</h1><p>s.korshenko@ilslab.ru · LEE312A03C · Очно · Подтверждено</p><a href="'.htmlspecialchars($u,ENT_QUOTES,'UTF-8').'">Открыть персональный билет</a></html>';exit;
}
const TEN_EVENT='forum-lab-innovations-2026-10-07';
const TEN_LOG='/home/c/cx314477/public_html/.private/ten-offline-20261006.json';
function mailShell(string $title, string $body): string {
    return '<!doctype html><html lang="ru"><body style="margin:0;padding:0;background:#f3f6f4;font-family:Arial,sans-serif;color:#173126;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f6f4;"><tr><td align="center" style="padding:24px 12px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:620px;background:#ffffff;border:1px solid #dfe8e2;border-radius:14px;overflow:hidden;">'
        . '<tr><td style="background:#214f3b;color:#ffffff;padding:26px 28px;"><div style="font-size:12px;line-height:1.5;letter-spacing:.06em;text-transform:uppercase;opacity:.82;">Референс-центр лабораторной службы Московской области</div><div style="font-size:24px;line-height:1.25;font-weight:700;margin-top:8px;">' . $title . '</div></td></tr>'
        . '<tr><td style="padding:28px;">' . $body . '</td></tr>'
        . '<tr><td style="padding:17px 28px;background:#f8faf9;border-top:1px solid #e8eeea;font-size:13px;color:#66776f;">По вопросам регистрации: <a href="mailto:info@rclsmo.ru" style="color:#214f3b;">info@rclsmo.ru</a></td></tr>'
        . '</table></td></tr></table></body></html>';
}


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
   }elseif($action==='send'||$action==='styled_send'||$action==='exact_send'){
    $styled=$action==='styled_send';
    $exact=$action==='exact_send';
    $statusKey=$exact?'exact_mail_status':($styled?'styled_mail_status':'mail_status');
    $r=tenRow($pdo);
    if($r['participation_format']!=='offline'||!preg_match('/^[a-f0-9]{64}$/',(string)$r['qr_token']))throw new RuntimeException('Сначала переведите регистрацию в очный формат');
    if(isset($log[$statusKey]))throw new RuntimeException('Попытка отправки уже зарегистрирована: '.$log[$statusKey].'. Повторная отправка заблокирована.');
    require_once SMTP_AUTOLOAD_PATH;
    if((new ReflectionFunction('sendConfiguredMail'))->getNumberOfParameters()<5)throw new RuntimeException('Почтовый модуль не поддерживает встроенный QR');
    $url='https://rclsmo.ru/participant.php?t='.rawurlencode($r['qr_token']);
    $qr=Endroid\QrCode\QrCode::create($url)->setSize(320)->setMargin(12);
    if($exact)$qr->setErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::Medium);
    $png=(new Endroid\QrCode\Writer\PngWriter())->write($qr)->getString();
    if(substr($png,0,8)!=="\x89PNG\r\n\x1a\n")throw new RuntimeException('Не удалось сформировать QR');
    $body='<p>Уважаемая Евгения Александровна!</p><p>По Вашей просьбе формат участия в Форуме лабораторных инноваций Московской области изменён на <strong>очный</strong>. Ваша регистрация подтверждена. Повторно регистрироваться не нужно.</p><p><strong>Дата:</strong> 7 октября 2026 года.<br><strong>Начало программы:</strong> 10:00 (московское время).<br><strong>Место проведения:</strong> Дом Правительства Московской области, Красногорск, бульвар Строителей, 1.</p><p><strong>Пожалуйста, возьмите паспорт для прохода на территорию.</strong></p><p>На регистрации покажите персональный QR-код:</p><p><img src="cid:participant_qr" alt="QR-код участника" width="280" height="280"></p><p><a href="'.th($url).'">Открыть Ваш билет</a></p><p>Код участника: <strong>LE1D2DEA66</strong>. Билет можно сохранить на телефоне или распечатать.</p><p>До встречи на форуме!</p><p>С уважением,<br>Организационный комитет Форума лабораторных инноваций Московской области<br>Референс-центр лабораторной службы МО<br>info@rclsmo.ru</p>';
    if($styled){
     $body=mailShell('Очное участие подтверждено', '<p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Уважаемая Евгения Александровна!</p><p style="font-size:16px;line-height:1.6;margin:0 0 22px;">По Вашей просьбе формат участия изменён на <strong>очный</strong>. Ваша регистрация подтверждена. Повторно регистрироваться не нужно.</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f1f6f3;border-radius:12px;margin-bottom:22px;"><tr><td style="padding:18px;"><div style="font-size:12px;color:#607268;margin-bottom:5px;">Код участника</div><div style="font-size:24px;font-weight:700;letter-spacing:.05em;color:#214f3b;">LE1D2DEA66</div></td></tr></table><p style="font-size:15px;line-height:1.7;"><strong>Форум лабораторных инноваций Московской области — 2026</strong><br><strong>Дата:</strong> 7 октября 2026 года<br><strong>Начало программы:</strong> 10:00 (московское время)<br><strong>Место:</strong> Дом Правительства Московской области, Красногорск, бульвар Строителей, 1</p><p style="font-size:15px;line-height:1.7;"><strong>Возьмите паспорт для прохода на территорию.</strong></p><div style="text-align:center;margin:24px 0;"><img src="cid:participant_qr" alt="QR-код участника" width="280" height="280"><br><a href="'.th($url).'" style="display:inline-block;background:#214f3b;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:13px 20px;border-radius:9px;margin-top:16px;">Открыть мой билет</a></div><p style="font-size:14px;line-height:1.6;color:#607268;">Покажите QR-код на регистрации. Билет можно сохранить на телефоне или распечатать.</p><p style="font-size:15px;line-height:1.7;">До встречи на форуме!<br>Организационный комитет<br>Референс-центр лабораторной службы МО</p>');
    }
    if($exact)$body=reminderBody($r,'offline',$r['qr_token']);
    $log[$statusKey]='sending';$log['attempted_at']=date('c');$log['to']=$r['email'];tenSave($log);
    $ok=sendConfiguredMail($r['email'],$exact?'Уже завтра — Форум лабораторных инноваций, 7 октября':'Очное участие подтверждено — Ваш билет на Форум 7 октября',$body,($styled||$exact)?['info@rclsmo.ru']:[],['participant_qr'=>['data'=>$png]]);
    $log[$statusKey]=$ok?'accepted':'failed';$log['finished_at']=date('c');tenSave($log);
    $notice=$ok?($exact?'Письмо по шаблону общей рассылки с персональным QR и билетом принято SMTP-сервером. Копия: info@rclsmo.ru.':($styled?'Письмо в общем стиле с QR-кодом и билетом принято SMTP-сервером. Копия: info@rclsmo.ru.':'Письмо с QR-кодом и ссылкой на билет принято SMTP-сервером.')):'Не удалось отправить письмо.';
   }else{throw new RuntimeException('Неизвестная операция');}
  }finally{flock($lock,LOCK_UN);fclose($lock);}
  $r=tenRow($pdo);
 }
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$notice=$e instanceof PDOException?'Ошибка базы. Изменения отменены.':$e->getMessage();}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Тен — очное участие и билет</title><style>body{font:17px/1.5 Arial;margin:36px;color:#173126}main{max-width:800px}button{font:inherit;background:#214f3b;color:white;border:0;border-radius:8px;padding:14px;margin:8px}p{margin:14px 0}</style><main><a href="/dashboard/">Дашборд</a><h1>Тен Евгения Александровна</h1><p><?=th($notice)?></p><?php if($r):?><p>Адрес: <?=th($r['email'])?><br>Код: <?=th($r['participant_code'])?><br>Формат: <?=$r['participation_format']==='offline'?'Очно':'Онлайн'?><br>Регистрация: подтверждена<br>Письмо: <?=th($log['mail_status']??'не отправлено')?><br>Шаблон общей рассылки и копия: <?=th($log['exact_mail_status']??'не отправлены')?><br>Оформленное письмо и копия: <?=th($log['styled_mail_status']??'не отправлены')?></p><form method="post"><input type="hidden" name="csrf" value="<?=th($_SESSION['ten_csrf'])?>"><?php if($r['participation_format']!=='offline'):?><button name="action" value="change">Перевести в очный формат</button><?php elseif(!isset($log['mail_status'])):?><button name="action" value="send">Отправить билет и QR на ferleo@mail.ru</button><?php endif?><?php if($r['participation_format']==='offline'&&!isset($log['styled_mail_status'])):?><button name="action" value="styled_send">Отправить оформленный билет Тен и копию на info@rclsmo.ru</button><?php endif?><?php if($r['participation_format']==='offline'&&!isset($log['exact_mail_status'])):?><button name="action" value="exact_send">Отправить Тен шаблон общей рассылки и копию</button><?php endif?></form><p>Отправитель: info@rclsmo.ru. Письмо содержит подтверждение очного участия, дату и место форума, напоминание о паспорте, встроенный QR и персональную ссылку на билет.</p><?php endif?></main></html>
