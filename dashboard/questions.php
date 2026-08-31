<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['conference_dashboard_auth'])) {
    header('Location: /dashboard/');
    exit;
}

// Дашборд и модерация вопросов используют одну служебную сессию.
$_SESSION['qa_staff_auth'] = true;
header('Location: /qa/moderator.php');
exit;
