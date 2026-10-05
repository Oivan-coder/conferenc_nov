<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
if (empty($_SESSION['conference_dashboard_auth'])) { header('Location: /dashboard/'); exit; }
require_once __DIR__ . '/cleanup-functions.php';
const CLEANUP_EVENT = 'forum-lab-innovations-2026-10-07';
const CLEANUP_PRIVATE = '/home/c/cx314477/public_html/.private';
function ch($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function cleanupRows(PDO $pdo, bool $lock = false): array {
    $stmt = $pdo->prepare('SELECT * FROM participants WHERE event_id = :event ORDER BY id' . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute([':event' => CLEANUP_EVENT]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
if (empty($_SESSION['dashboard_csrf'])) $_SESSION['dashboard_csrf'] = bin2hex(random_bytes(32));
$error = ''; $notice = (string)($_SESSION['cleanup_notice'] ?? ''); unset($_SESSION['cleanup_notice']);
$rows = []; $plan = cleanupPlan([]); $related = [];
try {
    $pdo = require CLEANUP_PRIVATE . '/db.php';
    if (!$pdo instanceof PDO) throw new RuntimeException('База недоступна.');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals((string)$_SESSION['dashboard_csrf'], (string)($_POST['csrf'] ?? ''))) {
            http_response_code(403); throw new RuntimeException('Обновите страницу: запрос не прошёл проверку.');
        }
        if (($_POST['confirm'] ?? '') !== 'yes') throw new RuntimeException('Подтвердите применение выбранных изменений.');
        cleanupEnsureBackups($pdo);
        $pdo->beginTransaction();
        $rows = cleanupRows($pdo, true);
        if (!hash_equals(cleanupFingerprint($rows), (string)($_POST['snapshot'] ?? ''))) throw new RuntimeException('Регистрации изменились после открытия страницы. Проверьте обновлённый список и повторите выбор.');
        $plan = cleanupPlan($rows);
        $ops = cleanupOperations($plan, $_POST);
        if (!$ops['delete'] && !$ops['organizations'] && !$ops['checkins']) throw new RuntimeException('Изменения не выбраны.');
        // Never delete records with conference activity, including references with cascading FKs.
        $tables = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'participant_id'")->fetchAll(PDO::FETCH_ASSOC);
        $foreign = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'participants' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ops['delete'] as $id => $row) {
            if (!empty($row['check_in_at']) || (int)($row['online_watch_seconds'] ?? 0) > 0) throw new RuntimeException('У удаляемой записи есть посещение. Сохраните её или сначала разберите посещение отдельно.');
            foreach (array_merge($tables, $foreign) as $ref) {
                $table = str_replace('`', '``', $ref['TABLE_NAME']); $column = str_replace('`', '``', $ref['COLUMN_NAME']);
                $stmt = $pdo->prepare("SELECT 1 FROM `$table` WHERE `$column` = :id LIMIT 1 FOR UPDATE");
                $stmt->execute([':id' => $id]);
                if ($stmt->fetchColumn()) throw new RuntimeException('У удаляемой записи есть связанные данные. Удаление отменено.');
            }
        }
        $backup = cleanupBackup($pdo, $ops);
        foreach ($ops['organizations'] as $id => $change) {
            $pdo->prepare('UPDATE participants SET organization = :organization WHERE id = :id AND event_id = :event')->execute([':organization' => $change['after'], ':id' => $id, ':event' => CLEANUP_EVENT]);
        }
        foreach ($ops['checkins'] as $id => $row) {
            $pdo->prepare('UPDATE participants SET check_in_at = NULL WHERE id = :id AND event_id = :event')->execute([':id' => $id, ':event' => CLEANUP_EVENT]);
        }
        foreach ($ops['delete'] as $id => $row) {
            $pdo->prepare('DELETE FROM participants WHERE id = :id AND event_id = :event')->execute([':id' => $id, ':event' => CLEANUP_EVENT]);
        }
        $pdo->commit();
        $_SESSION['cleanup_notice'] = 'Обновлено организаций: ' . count($ops['organizations']) . '. Удалено записей: ' . count($ops['delete']) . '. Сброшено входов: ' . count($ops['checkins']) . '. Резервная копия: ' . $backup;
        header('Location: /dashboard/cleanup.php'); exit;
    }
    $rows = cleanupRows($pdo); $plan = cleanupPlan($rows);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    $error = $e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : 'Не удалось выполнить операцию. Изменения отменены.';
    if (isset($pdo) && $pdo instanceof PDO) {
        try { $rows = cleanupRows($pdo); $plan = cleanupPlan($rows); } catch (Throwable $ignored) {}
    }
}
// Read-only preflight: explain blockers without attempting another deletion.
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $refs = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'participant_id'")->fetchAll(PDO::FETCH_ASSOC);
        $fks = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = 'participants' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(PDO::FETCH_ASSOC);
        $candidates = $plan['tests'];
        foreach ($plan['duplicates'] as $group) foreach ($group as $row) $candidates[(int)$row['id']] = $row;
        $seen = [];
        foreach (array_merge($refs, $fks) as $ref) {
            $key = $ref['TABLE_NAME'] . '.' . $ref['COLUMN_NAME'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $table = str_replace('`', '``', $ref['TABLE_NAME']);
            $column = str_replace('`', '``', $ref['COLUMN_NAME']);
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = :id");
            foreach ($candidates as $id => $row) {
                $stmt->execute([':id' => $id]);
                $count = (int)$stmt->fetchColumn();
                if ($count) $related[] = ['row' => $row, 'source' => $key, 'count' => $count];
            }
        }
    } catch (Throwable $ignored) { $error = 'Не удалось проверить связанные данные. Удаление следует отложить.'; }
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Очистка регистраций</title>
<style> *{box-sizing:border-box}body{margin:0;background:#f4f7f5;color:#173126;font:16px/1.5 Arial,sans-serif}main{max-width:1200px;margin:auto;padding:24px}a{color:#214f3b}section{background:white;border:1px solid #dbe6df;border-radius:14px;padding:20px;margin:20px 0}h1{font-size:28px}h2{font-size:21px}h3{font-size:17px}.table{overflow:auto}table{border-collapse:collapse;width:100%;font-size:14px}td,th{text-align:left;padding:10px;border-bottom:1px solid #dbe6df;vertical-align:top}.error{background:#fff0ef;padding:14px}.notice{background:#e5f4ea;padding:14px}button{background:#214f3b;color:white;border:0;border-radius:10px;padding:14px 20px;font:inherit;cursor:pointer}label{display:block;margin:12px 0}select{font:inherit;max-width:100%;padding:8px}small{font-size:14px;color:#65786b}</style></head><body><main>
<a href="/dashboard/">Вернуться к регистрациям</a><h1>Очистка регистраций</h1>
<p>Выберите изменения. Для дублей укажите запись, которую нужно оставить: её код и билет сохранятся. Совпадение ФИО само по себе не подтверждает дубль.</p>
<?php if ($error): ?><p class="error"><?= ch($error) ?></p><?php endif ?>
<?php if ($notice): ?><p class="notice"><?= ch($notice) ?></p><?php endif ?>
<?php if ($related): ?><section><h2>Связанные данные: удаление заблокировано</h2><p>История этих регистраций сохраняется. Перед удалением нужно отдельно разобрать связи.</p><table><tr><th>Участник / код</th><th>Связь</th><th>Записей</th></tr><?php foreach ($related as $link): ?><tr><td><?= ch($link['row']['full_name'] . ' · ' . $link['row']['participant_code']) ?></td><td><?= ch($link['source']) ?></td><td><?= (int)$link['count'] ?></td></tr><?php endforeach ?></table></section><?php endif ?>
<form method="post"><input type="hidden" name="csrf" value="<?= ch($_SESSION['dashboard_csrf']) ?>"><input type="hidden" name="snapshot" value="<?= ch(cleanupFingerprint($rows)) ?>">
<section><h2>Названия организаций · <?= count($plan['organizations']) ?></h2><p>Показаны только точные соответствия словарю. Неизвестные названия не сокращаются и не заменяются автоматически.</p>
<label><input type="checkbox" name="normalize" value="yes"> Применить все показанные исправления организаций</label>
<div class="table"><table><tr><th>Участник</th><th>Было</th><th>Станет</th></tr><?php foreach ($plan['organizations'] as $change): ?><tr><td><?= ch($change['row']['full_name']) ?></td><td><?= ch($change['row']['organization']) ?></td><td><?= ch($change['after']) ?></td></tr><?php endforeach ?></table></div></section>
<section><h2>Повторы по ФИО · <?= count($plan['duplicates']) ?></h2>
<?php foreach ($plan['duplicates'] as $key => $group): ?><h3><?= ch($group[0]['full_name']) ?></h3><div class="table"><table><tr><th>Код / дата</th><th>Формат</th><th>Организация / должность</th><th>Контакты</th><th>Посещение</th></tr><?php foreach ($group as $row): ?><tr><td><?= ch($row['participant_code']) ?><br><?= ch($row['created_at']) ?></td><td><?= $row['participation_format'] === 'offline' ? 'Очно' : 'Онлайн' ?></td><td><?= ch($row['organization']) ?><br><?= ch($row['position']) ?></td><td><?= ch($row['email']) ?><br><?= ch($row['phone'] ?? $row['phone_normalized'] ?? '') ?></td><td><?= ch($row['check_in_at'] ?? '') ?><br><?= (int)($row['online_watch_seconds'] ?? 0) ?> сек. онлайн</td></tr><?php endforeach ?></table></div>
<label>Оставить <select name="keep[<?= ch($key) ?>]"><option value="">Не менять эту группу</option><?php foreach ($group as $row): ?><option value="<?= (int)$row['id'] ?>"><?= ch($row['participant_code'] . ' · ' . $row['created_at'] . ' · ' . ($row['participation_format'] === 'offline' ? 'Очно' : 'Онлайн')) ?></option><?php endforeach ?></select></label><small>Остальные записи этой группы будут удалены. При наличии посещения или связанных данных удаление блокируется.</small><?php endforeach ?></section>
<section><h2>Тестовая регистрация</h2><?php foreach ($plan['tests'] as $id => $row): ?><label><input type="checkbox" name="tests[]" value="<?= $id ?>"> Удалить <?= ch($row['full_name'] . ' · ' . $row['participant_code'] . ' · ' . $row['email']) ?></label><?php endforeach ?></section>
<section><h2>Тестовые входы до 7 октября</h2><p>Сбрасываются только выбранные ранние отметки организаторов.</p><?php foreach ($plan['checkins'] as $id => $row): ?><label><input type="checkbox" name="checkins[]" value="<?= $id ?>"> <?= ch($row['full_name'] . ' · ' . $row['participant_code'] . ' · ' . $row['check_in_at']) ?></label><?php endforeach ?></section>
<section><p>Перед изменениями сохраняется резервная копия затронутых записей. Все выбранные изменения выполняются вместе; при ошибке ни одно не применяется.</p><label><input type="checkbox" name="confirm" value="yes" required> Подтверждаю показанные исправления и удаление выбранных записей</label><button type="submit">Применить выбранные изменения</button></section>
</form></main></body></html>
