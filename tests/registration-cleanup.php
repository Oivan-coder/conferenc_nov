<?php
require_once __DIR__ . '/../dashboard/cleanup-functions.php';
function expect($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function row($id, $name, $org, $email = 'a@example.org', $format = 'online', $checkin = null) {
    return ['id' => $id, 'participant_code' => 'LE0000000' . $id, 'full_name' => $name, 'organization' => $org, 'email' => $email, 'participation_format' => $format, 'registration_status' => 'confirmed', 'check_in_at' => $checkin, 'created_at' => '2026-10-05 10:00:00', 'online_watch_seconds' => 0];
}
$rows = [row(1, 'Филатова Марина Николаевна', '#Одинцовская ОБ#'), row(2, 'филатова  марина николаевна', 'Одинцовская ОБ'), row(3, 'Тест Тест', 'Тест', 'ivangoltsev8@gmail.com'), row(4, 'Неизвестная Ольга', str_repeat('Неизвестное длинное название ', 5)), row(5, 'Шоль Елизавета Викторовна', 'РЦЛСМО', 's@example.org', 'offline', '2026-10-05 12:00:00'), row(6, 'Довгаль Лада Алексеевна', 'РЦЛСМО', 'd@example.org', 'offline', '2026-10-07 12:00:00')];
$plan = cleanupPlan($rows);
expect(count($plan['duplicates']) === 1, 'Case and whitespace duplicate detection');
expect(array_keys($plan['tests']) === [3], 'Exact test record selection');
expect(array_keys($plan['checkins']) === [5], 'Only early staff checkins');
expect(array_keys($plan['organizations']) === [1], 'Unknown organizations must stay intact');
$ops = cleanupOperations($plan, []);
expect(!$ops['delete'] && !$ops['organizations'] && !$ops['checkins'], 'Preview must not preselect operations');
$key = array_key_first($plan['duplicates']);
$ops = cleanupOperations($plan, ['keep' => [$key => '2'], 'normalize' => 'yes', 'tests' => [3], 'checkins' => [5]]);
expect(array_keys($ops['delete']) === [1, 3], 'Keep selected ticket and delete only other selected records');
expect(!$ops['organizations'], 'Do not normalize deleted records');
expect(array_keys($ops['checkins']) === [5], 'Reset chosen staff checkin');
try { cleanupOperations($plan, ['keep' => [$key => '99']]); throw new LogicException('Invalid keep accepted'); } catch (RuntimeException $e) { expect(!$e instanceof LogicException, 'Invalid keep rejected'); }
expect(cleanupFingerprint($rows) !== cleanupFingerprint(array_reverse($rows)), 'Stale snapshot detection');
expect(dashboardCanonicalOrganization('Внктор-Бест-Европа') === 'Вектор-Бест-Европа', 'Typo alias');
expect(dashboardOrganizationCategory('ООО Новая организация')[0] === 'private', 'Commercial category');
expect(dashboardOrganizationCategory('ФГБУ Новая организация')[0] === 'government', 'Government category');
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
cleanupEnsureBackups($pdo);
$pdo->beginTransaction();
$id = cleanupBackup($pdo, $ops);
$backup = json_decode($pdo->query('SELECT payload FROM registration_cleanup_backups')->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
expect($backup['operations']['delete'][1]['participant_code'] === $rows[0]['participant_code'], 'Backup retains full deleted row');
$pdo->rollBack();
expect((int)$pdo->query('SELECT COUNT(*) FROM registration_cleanup_backups')->fetchColumn() === 0, 'Failed operations roll back backup too');
$pdo->beginTransaction();
cleanupBackup($pdo, $ops); $pdo->commit();
expect((int)$pdo->query('SELECT COUNT(*) FROM registration_cleanup_backups')->fetchColumn() === 1, 'Committed backup is durable');
$pdo->exec('CREATE TABLE conference_messages (id INTEGER PRIMARY KEY, participant_id INTEGER, event_id TEXT, reply_to_id INTEGER, message_text TEXT)');
$pdo->exec('CREATE TABLE conference_message_votes (message_id INTEGER, participant_id INTEGER)');
$test = row(3, 'Тест Тест', 'Тест', 'ivangoltsev8@gmail.com'); $test['participant_code'] = 'LE2AF0502A';
$pdo->exec("INSERT INTO conference_messages VALUES (1,3,'forum-lab-innovations-2026-10-07',NULL,'test one'),(2,3,'forum-lab-innovations-2026-10-07',NULL,'test two'),(3,3,'forum-lab-innovations-2026-10-07',NULL,'test three'),(4,99,'forum-lab-innovations-2026-10-07',NULL,'real message')");
$pdo->exec('INSERT INTO conference_message_votes VALUES (4,3)');
$history = cleanupTestHistory($pdo, $test);
expect(count($history['messages']) === 3 && count($history['votes']) === 1, 'Capture all approved test history');
$pdo->exec('INSERT INTO conference_message_votes VALUES (1,99)');
try { cleanupTestHistory($pdo, $test); throw new LogicException('Foreign reaction accepted'); } catch (RuntimeException $e) { expect(!$e instanceof LogicException, 'Foreign reaction blocks removal'); }
$pdo->exec('DELETE FROM conference_message_votes WHERE participant_id = 99');
$pdo->exec('UPDATE conference_messages SET reply_to_id = 1 WHERE id = 4');
try { cleanupTestHistory($pdo, $test); throw new LogicException('Foreign reply accepted'); } catch (RuntimeException $e) { expect(!$e instanceof LogicException, 'Foreign reply blocks removal'); }
$pdo->exec('UPDATE conference_messages SET reply_to_id = NULL WHERE id = 4');
$pdo->beginTransaction();
cleanupBackup($pdo, ['test_history' => [$history]]);
cleanupDeleteTestHistory($pdo, $history);
expect((int)$pdo->query('SELECT COUNT(*) FROM conference_messages')->fetchColumn() === 1, 'Keep real message');
expect((int)$pdo->query('SELECT COUNT(*) FROM conference_message_votes')->fetchColumn() === 0, 'Remove only test vote');
$pdo->rollBack();
expect((int)$pdo->query('SELECT COUNT(*) FROM conference_messages')->fetchColumn() === 4, 'Restore history on rollback');
$pdo->exec('DELETE FROM conference_messages WHERE id = 3');
try { cleanupTestHistory($pdo, $test); throw new LogicException('Changed history accepted'); } catch (RuntimeException $e) { expect(!$e instanceof LogicException, 'Changed history counts block removal'); }
echo "Registration cleanup checks passed\n";
