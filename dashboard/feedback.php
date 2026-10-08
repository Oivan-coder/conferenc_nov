<?php
declare(strict_types=1);
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
if (empty($_SESSION['conference_dashboard_auth'])) {
    header('Location: /dashboard/');
    exit;
}
session_write_close();
require_once dirname(__DIR__) . '/api/feedback-functions.php';
function fh($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$page = max(1, (int)($_GET['page'] ?? 1));
$format = is_string($_GET['format'] ?? null) ? $_GET['format'] : '';
if (!in_array($format, ['offline', 'online', 'unspecified'], true)) $format = '';
$ratings = ['overall_rating' => 'Общее впечатление', 'program_rating' => 'Программа', 'organization_rating' => 'Организация'];
$formats = ['offline' => 'Очно', 'online' => 'Онлайн', 'unspecified' => 'Не указан'];
$error = '';
$rows = []; $summary = []; $total = 0; $pages = 1;
try {
    $pdo = require '/home/c/cx314477/public_html/.private/db.php';
    if (!$pdo instanceof PDO) throw new RuntimeException('DB unavailable');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $where = 'event_id = :event';
    $params = [':event' => FEEDBACK_EVENT_ID];
    if ($format !== '') { $where .= ' AND participation_format = :format'; $params[':format'] = $format; }
    $stats = $pdo->prepare('SELECT COUNT(*) AS total, AVG(overall_rating) AS overall_rating, AVG(program_rating) AS program_rating, AVG(organization_rating) AS organization_rating FROM conference_feedback WHERE ' . $where);
    $stats->execute($params);
    $summary = $stats->fetch(PDO::FETCH_ASSOC) ?: [];
    $total = (int)($summary['total'] ?? 0);
    $pages = max(1, (int)ceil($total / 30));
    $page = min($page, $pages);
    $offset = ($page - 1) * 30;
    $list = $pdo->prepare('SELECT id, overall_rating, program_rating, organization_rating, participation_format, liked_text, improvements_text, created_at FROM conference_feedback WHERE ' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 30 OFFSET ' . $offset);
    $list->execute($params);
    $rows = $list->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    http_response_code(503);
    $error = 'Отзывы пока недоступны. Проверьте создание таблицы conference_feedback и обновите страницу.';
}
function feedbackPageLink(int $page, string $format): string {
    return '/dashboard/feedback.php?' . http_build_query(['page' => $page, 'format' => $format]);
}
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Отзывы · Форум 2026</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#183126;font-family:Arial,sans-serif}main{max-width:1180px;margin:auto;padding:32px 20px}header{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}h1{font-size:32px;margin:8px 0}p{line-height:1.6}.muted{color:#64766c;font-size:14px}.btn{display:inline-block;padding:12px 16px;border:1px solid #cddbd3;border-radius:12px;background:#fff;color:#214f3b;text-decoration:none;font:inherit;cursor:pointer}.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:24px 0}.stat,.review,.empty{background:#fff;border:1px solid #dce7e1;border-radius:18px;padding:22px}.stat strong{display:block;font-size:32px;margin-bottom:6px}.stat span{font-size:14px;color:#64766c}.filters{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:22px}select{font:inherit;padding:10px;border:1px solid #cddbd3;border-radius:10px;background:#fff}.review{margin-bottom:16px}.review-head{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}.scores{display:flex;gap:10px;flex-wrap:wrap;margin:16px 0}.score{background:#edf4ef;border-radius:8px;padding:8px 12px;font-size:14px}.texts{display:grid;grid-template-columns:1fr 1fr;gap:24px}.texts h3{font-size:15px;margin:10px 0}.texts p{white-space:pre-wrap;overflow-wrap:anywhere;margin:0}.pagination{display:flex;gap:16px;align-items:center;justify-content:center;margin:24px 0}.error{background:#fff2df;color:#745018;border-radius:12px;padding:18px}@media(max-width:700px){.stats{grid-template-columns:1fr 1fr}.texts{grid-template-columns:1fr;gap:12px}h1{font-size:27px}}
</style></head><body><main>
<header><div><div class="muted">Форум лабораторных инноваций МО · 7 октября 2026</div><h1>Анонимные отзывы</h1><p class="muted">Оценки и пожелания участников. Отзывы не связаны с регистрациями и доступны только организаторам.</p></div><a class="btn" href="/dashboard/">← В дашборд</a></header>
<?php if ($error !== ''): ?><p class="error"><?= fh($error) ?></p><?php else: ?>
<form class="filters" method="get"><label for="format">Формат участия</label><select id="format" name="format"><option value="">Все отзывы</option><?php foreach ($formats as $key => $label): ?><option value="<?= fh($key) ?>" <?= $format === $key ? 'selected' : '' ?>><?= fh($label) ?></option><?php endforeach; ?></select><button class="btn" type="submit">Показать</button><a class="btn" href="/dashboard/feedback.php">Обновить / сбросить</a></form>
<section class="stats" aria-label="Сводка по выбранным отзывам"><div class="stat"><strong><?= $total ?></strong><span>Отзывов</span></div><?php foreach ($ratings as $key => $label): ?><div class="stat"><strong><?= $total ? fh(number_format((float)$summary[$key], 1, ',', '')) . ' / 5' : '—' ?></strong><span><?= fh($label) ?></span></div><?php endforeach; ?></section>
<?php if (!$rows): ?><div class="empty">Пока нет отзывов<?= $format !== '' ? ' с выбранным форматом участия' : '' ?>. Ссылка на форму: <a href="/conference-2026/#feedback">страница форума</a>.</div><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="review"><div class="review-head"><strong>Отзыв №<?= (int)$row['id'] ?> · <?= fh($formats[$row['participation_format']] ?? 'Не указан') ?></strong><span class="muted"><?= fh(date('d.m.Y H:i', strtotime($row['created_at']))) ?></span></div><div class="scores"><?php foreach ($ratings as $key => $label): ?><span class="score"><?= fh($label) ?>: <strong><?= (int)$row[$key] ?>/5</strong></span><?php endforeach; ?></div><div class="texts"><div><h3>Что понравилось</h3><p><?= $row['liked_text'] !== '' ? fh($row['liked_text']) : '—' ?></p></div><div><h3>Что улучшить</h3><p><?= $row['improvements_text'] !== '' ? fh($row['improvements_text']) : '—' ?></p></div></div></article><?php endforeach; ?>
<?php if ($pages > 1): ?><nav class="pagination" aria-label="Страницы отзывов"><?php if ($page > 1): ?><a class="btn" href="<?= fh(feedbackPageLink($page - 1, $format)) ?>">← Назад</a><?php endif; ?><span><?= $page ?> / <?= $pages ?></span><?php if ($page < $pages): ?><a class="btn" href="<?= fh(feedbackPageLink($page + 1, $format)) ?>">Далее →</a><?php endif; ?></nav><?php endif; ?>
<?php endif; ?></main></body></html>
