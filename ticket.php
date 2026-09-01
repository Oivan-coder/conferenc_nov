<?php
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
$token = strtolower(trim((string)($_GET['t'] ?? '')));
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {http_response_code(404);exit('Билет не найден');}
header('Location: /participant.php?t=' . rawurlencode($token), true, 302);
exit;
