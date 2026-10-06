<?php
session_start();header('Cache-Control: private, no-store');header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit('Требуется вход организатора');}
require_once dirname(__DIR__).'/api/smtp-mailer.php';
const RECEIPT='/home/c/cx314477/public_html/.private/belous-notice-20261006.json';
if(empty($_SESSION['belous_csrf']))$_SESSION['belous_csrf']=bin2hex(random_bytes(32));
function bh($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$notice='';$sent=false;
if(is_readable(RECEIPT)){$x=json_decode(file_get_contents(RECEIPT),true);$sent=is_array($x)&&!empty($x['sent']);}
if($_SERVER['REQUEST_METHOD']==='POST'&&!$sent){
 if(!hash_equals($_SESSION['belous_csrf'],(string)($_POST['csrf']??'')))$notice='Обновите страницу.';
 else{
  $body='<p>Здравствуйте, Николай!</p><p>Мы удалили две повторные регистрации и оставили одну актуальную регистрацию в очном формате.</p><p>Действующая регистрация:</p><ul><li><strong>ФИО:</strong> Белоус Николай Николаевич</li><li><strong>Формат:</strong> очное участие</li><li><strong>Код участника:</strong> LEF3EEEE17</li></ul><p>Повторно регистрироваться не нужно. До встречи на Форуме лабораторных инноваций 7 октября!</p><p>С уважением,<br>Организационный комитет<br>Референс-центр лабораторной службы МО</p>';
  $ok=sendConfiguredMail('nikolay.belous@quidelortho.com','Актуальная регистрация на Форум лабораторных инноваций',$body);
  file_put_contents(RECEIPT,json_encode(['sent'=>$ok,'at'=>date('c'),'to'=>'nikolay.belous@quidelortho.com'],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX);chmod(RECEIPT,0600);
  $sent=$ok;$notice=$ok?'Письмо принято SMTP-сервером.':'Ошибка отправки.';
 }
}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Уведомление Белоусу</title><style>body{font:17px Arial;color:#173126;margin:40px}button{font:inherit;background:#214f3b;color:#fff;border:0;border-radius:8px;padding:14px}</style><h1>Уведомление Белоусу</h1><p><?=bh($notice)?></p><p>Адрес: nikolay.belous@quidelortho.com</p><p>Сообщение: две повторные записи удалены, одна очная оставлена.</p><?php if(!$sent):?><form method="post"><input type="hidden" name="csrf" value="<?=bh($_SESSION['belous_csrf'])?>"><button>Отправить письмо</button></form><?php else:?><p>Письмо уже отправлено, повторная отправка заблокирована.</p><?php endif?></html>