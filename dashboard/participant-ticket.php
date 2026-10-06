<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'");
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
function hticket($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$code=trim((string)($_GET['code']??''));
$r=null;$error='';
try{
 if(!preg_match('/^LE[A-F0-9]{8}$/',$code))throw new RuntimeException('Некорректный код участника');
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';
 $q=$pdo->prepare('SELECT participant_code,full_name,email,participation_format,registration_status,qr_token,online_token FROM participants WHERE event_id=? AND participant_code=?');
 $q->execute(['forum-lab-innovations-2026-10-07',$code]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
 if(count($rows)!==1)throw new RuntimeException('Участник не найден однозначно');
 $r=$rows[0];$token=$r['participation_format']==='offline'?$r['qr_token']:$r['online_token'];
 if($r['registration_status']!=='confirmed'||!preg_match('/^[a-f0-9]{64}$/',(string)$token))throw new RuntimeException('Нет подтверждённого билета');
 $url='https://rclsmo.ru/participant.php?t='.rawurlencode($token);
}catch(Throwable $e){$error=$e instanceof PDOException?'Ошибка базы':$e->getMessage();$r=null;}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Ссылка на билет участника</title><style>body{font:17px/1.6 Arial;color:#173126;padding:30px}a{color:#214f3b}</style><h1>Ссылка на билет участника</h1><?php if($r):?><p><?=hticket($r['full_name'])?><br><?=hticket($r['email'])?><br><?=hticket($r['participant_code'])?><br>Формат: <?=$r['participation_format']==='offline'?'Очно':'Онлайн'?></p><p><a href="<?=hticket($url)?>">Открыть персональный билет</a></p><p><?=hticket($url)?></p><?php else:?><p><?=hticket($error)?></p><?php endif?></html>