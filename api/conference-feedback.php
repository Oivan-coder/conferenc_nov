<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/feedback-functions.php';

function feedbackReply(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    feedbackReply(405, ['ok' => false, 'message' => 'Используйте форму отзыва на странице форума.']);
}
$origin = rtrim((string)($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
if ($origin !== '' && !in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true)) {
    feedbackReply(403, ['ok' => false, 'message' => 'Отправьте отзыв со страницы форума.']);
}
if (strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')) === 'cross-site') {
    feedbackReply(403, ['ok' => false, 'message' => 'Отправьте отзыв со страницы форума.']);
}
if (!preg_match('/^application\/json(?:\s*;|$)/i', (string)($_SERVER['CONTENT_TYPE'] ?? ''))) {
    feedbackReply(415, ['ok' => false, 'message' => 'Обновите страницу и повторите отправку.']);
}
$raw = file_get_contents('php://input', false, null, 0, 32769);
if ($raw === false || strlen($raw) > 32768) feedbackReply(413, ['ok' => false, 'message' => 'Отзыв слишком длинный. Сократите текст.']);
try {
    $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($data) || array_is_list($data)) throw new InvalidArgumentException('Обновите страницу и повторите отправку.');
    if (!empty($data['website'])) feedbackReply(422, ['ok' => false, 'message' => 'Не удалось отправить отзыв. Обновите страницу.']);
    $feedback = feedbackValidate($data);
} catch (JsonException | InvalidArgumentException $e) {
    feedbackReply(422, ['ok' => false, 'message' => $e instanceof JsonException ? 'Некорректный запрос.' : $e->getMessage()]);
}
try {
    // No participant token, login session, IP address or user agent is stored.
    $pdo = require '/home/c/cx314477/public_html/.private/db.php';
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    feedbackSave($pdo, $feedback);
} catch (InvalidArgumentException $e) {
    feedbackReply(409, ['ok' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    feedbackReply(503, ['ok' => false, 'message' => 'Приём отзывов временно недоступен. Ваш текст остался в форме — попробуйте отправить позже.']);
}
feedbackReply(200, ['ok' => true, 'message' => 'Спасибо! Ваш отзыв сохранён и передан организаторам.']);
