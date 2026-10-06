<?php
session_start();header('Cache-Control: private, no-store');header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit;}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
if(empty($_SESSION['smtp_check_csrf']))$_SESSION['smtp_check_csrf']=bin2hex(random_bytes(32));
$results=[];$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!hash_equals($_SESSION['smtp_check_csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Обновите страницу');
  $campaign=json_decode(file_get_contents('/home/c/cx314477/public_html/.private/reminder-production-20261006.json'),true);
  require_once SMTP_AUTOLOAD_PATH;$smtp=new PHPMailer\PHPMailer\SMTP();
  if(!$smtp->connect('smtp.go2.unisender.ru',587,15)||!$smtp->hello('rclsmo.ru')||!$smtp->startTLS()||!$smtp->hello('rclsmo.ru')||!$smtp->authenticate('8332834',trim(file_get_contents(SMTP_PASSWORD_PATH))))throw new RuntimeException('Не удалось подключиться к SMTP для проверки');
  foreach($campaign['jobs'] as $job){
   if($job['status']!=='failed')continue;
   $email=$job['row']['email'];$smtp->reset();
   if(!$smtp->mail('info@rclsmo.ru'))throw new RuntimeException('Сервер не принял отправителя');
   $ok=$smtp->recipient($email);$err=$smtp->getError();
   $results[]=['name'=>$job['row']['full_name'],'email'=>$email,'result'=>$ok?'Адрес принят; письмо не отправлялось':trim(($err['smtp_code']??'').' '.($err['detail']??$err['error']??'Отказ'))];
  }
  $smtp->reset();$smtp->quit();$smtp->close();
 }catch(Throwable $e){$notice='Проверка: '.$e->getMessage();}
}
function eh($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Проверка пяти отказов SMTP</title><h1>Проверка адресов без повторной отправки</h1><p><?=eh($notice)?></p><table><?php foreach($results as $r):?><tr><td><?=eh($r['name'])?></td><td><?=eh($r['email'])?></td><td><?=eh($r['result'])?></td></tr><?php endforeach?></table><form method="post"><input type="hidden" name="csrf" value="<?=eh($_SESSION['smtp_check_csrf'])?>"><button>Проверить пять отклонённых адресов</button></form><p>Проверка SMTP RCPT без DATA: письма не отправляются.</p></html>