<?php
session_start();header('Cache-Control: private, no-store');header('X-Robots-Tag: noindex, nofollow');
if(empty($_SESSION['conference_dashboard_auth'])){http_response_code(403);exit;}
$paths=array_unique(array_filter([ini_get('error_log'),'/home/c/cx314477/public_html/api/error_log','/home/c/cx314477/public_html/error_log','/home/c/cx314477/public_html/dashboard/error_log']));
$lines=[];
foreach($paths as $path){if(!str_starts_with($path,'/')||!is_readable($path)||!is_file($path))continue;$f=fopen($path,'rb');$size=filesize($path);if($size>200000)fseek($f,-200000,SEEK_END);$data=stream_get_contents($f);fclose($f);foreach(explode("\n",$data) as $line){if(str_contains($line,'SMTP send failed:') && str_contains($line,'06-Oct-2026'))$lines[]=$line;}}
?><!doctype html><html lang="ru"><meta charset="utf-8"><title>Проверка ошибок отправки</title><h1>Ошибки SMTP за 6 октября</h1><p>Найдено: <?=count($lines)?></p><pre><?=htmlspecialchars(implode("\n",array_slice($lines,-20)),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')?></pre></html>