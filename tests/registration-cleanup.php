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
$dir = sys_get_temp_dir() . '/cleanup-test-' . bin2hex(random_bytes(6));
$file = cleanupBackup($dir, $ops);
$backup = json_decode(file_get_contents($dir . '/' . $file), true, 512, JSON_THROW_ON_ERROR);
expect($backup['operations']['delete'][1]['participant_code'] === $rows[0]['participant_code'], 'Backup retains full deleted row');
expect((fileperms($dir . '/' . $file) & 0777) === 0600, 'Private backup permissions');
unlink($dir . '/' . $file); unlink($dir . '/.htaccess'); rmdir($dir);
echo "Registration cleanup checks passed\n";
