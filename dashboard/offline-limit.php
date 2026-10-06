<?php
session_start();
header('Cache-Control: private, no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer');
if (empty($_SESSION['conference_dashboard_auth'])) { http_response_code(403); exit('Требуется вход организатора'); }
require_once dirname(__DIR__).'/api/registration-config.php';
if (empty($_SESSION['limit_csrf'])) $_SESSION['limit_csrf']=bin2hex(random_bytes(32));
function lh($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$notice='';$settings=[];$public=0;
try {
 $pdo=require '/home/c/cx314477/public_html/.private/db.php';
 $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['limit_csrf'],(string)($_POST['csrf']??''))) throw new RuntimeException('Обновите страницу');
  $pdo->beginTransaction();
  $q=$pdo->prepare('SELECT hall_capacity,public_offline_limit FROM event_registration_settings WHERE event_id=? FOR UPDATE');
  $q->execute([REGISTRATION_EVENT_ID]);$row=$q->fetch(PDO::FETCH_ASSOC);
  if (!$row) throw new RuntimeException('Настройки мероприятия не найдены');
  $reserve=max(0,(int)$row['hall_capacity']-(int)$row['public_offline_limit']);
  $capacity=min(REGISTRATION_HALL_CAPACITY,max((int)$row['hall_capacity'],100+$reserve));
  $pdo->prepare('UPDATE event_registration_settings SET public_offline_limit=100,hall_capacity=?,offline_registration_open=1 WHERE event_id=?')->execute([$capacity,REGISTRATION_EVENT_ID]);
  $pdo->commit();$notice='Лимит публичной очной регистрации — 100. Очная регистрация открыта.';
 }
 $q=$pdo->prepare('SELECT hall_capacity,public_offline_limit,offline_registration_open FROM event_registration_settings WHERE event_id=?');$q->execute([REGISTRATION_EVENT_ID]);$settings=$q->fetch(PDO::FETCH_ASSOC)?:[];
 $q=$pdo->prepare("SELECT COUNT(*) FROM participants WHERE event_id=? AND participation_format='offline' AND registration_status='confirmed' AND registration_source='public' AND organization<>'Тестовая МО' AND LOWER(TRIM(organization)) NOT IN ('ovan','oivan')");$q->execute([REGISTRATION_EVENT_ID]);$public=(int)$q->fetchColumn();
} catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();$notice=$e instanceof PDOException?'Ошибка базы. Изменения отменены.':$e->getMessage();}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Лимит очной регистрации</title><style>body{font:18px/1.6 Arial;margin:40px;color:#173126}main{max-width:800px;padding:30px;border:1px solid #dbe6df;border-radius:16px}button{padding:16px;background:#214f3b;color:white;border:0;border-radius:10px;font:inherit}a{color:#214f3b}</style><main><h1>Лимит очной регистрации</h1><p><?=lh($notice)?></p><p>Публичный лимит: <strong><?=registrationEffectivePublicOfflineLimit($settings)?></strong><br>Зарегистрировано публично очно: <strong><?=$public?></strong><br>Вместимость зала: <strong><?=registrationEffectiveHallCapacity($settings)?></strong><br>Очная регистрация: <strong><?=!empty($settings['offline_registration_open'])?'открыта':'закрыта'?></strong></p><form method="post"><input type="hidden" name="csrf" value="<?=lh($_SESSION['limit_csrf'])?>"><button>Открыть очную регистрацию до 100</button></form><p><a href="/dashboard/">Вернуться к дашборду</a></p></main></html>