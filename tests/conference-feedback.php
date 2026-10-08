<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/feedback-functions.php';
function check(bool $value, string $label): void { if (!$value) throw new RuntimeException($label); }
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE conference_feedback (id INTEGER PRIMARY KEY AUTOINCREMENT, event_id TEXT NOT NULL, submission_key TEXT NOT NULL, overall_rating INTEGER NOT NULL, program_rating INTEGER NOT NULL, organization_rating INTEGER NOT NULL, participation_format TEXT NOT NULL, liked_text TEXT NOT NULL, improvements_text TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(event_id, submission_key))');
$payload = ['submission_key' => str_repeat('a', 32), 'overall_rating' => 5, 'program_rating' => 4, 'organization_rating' => 3, 'participation_format' => 'online', 'liked_text' => '  Полезные доклады  ', 'improvements_text' => '<script>alert(1)</script>', 'participant_id' => 42, 'email' => 'ignore@example.com', 'ip_address' => '192.0.2.1'];
$feedback = feedbackValidate($payload);
check(count($feedback) === 8, 'Only anonymous fields are persisted');
check($feedback['liked_text'] === 'Полезные доклады', 'Trim text');
feedbackSave($pdo, $feedback);
feedbackSave($pdo, $feedback);
check((int)$pdo->query('SELECT COUNT(*) FROM conference_feedback')->fetchColumn() === 1, 'Retry must not duplicate');
check((string)$pdo->query('SELECT improvements_text FROM conference_feedback')->fetchColumn() === '<script>alert(1)</script>', 'Store literal text');
$changed = $feedback; $changed['overall_rating'] = 1;
try { feedbackSave($pdo, $changed); throw new RuntimeException('Must reject key reuse for different content'); } catch (InvalidArgumentException $expected) {}
$new = $payload; $new['submission_key'] = str_repeat('b', 32); $new['participation_format'] = 'offline';
feedbackSave($pdo, feedbackValidate($new));
check((int)$pdo->query('SELECT COUNT(*) FROM conference_feedback')->fetchColumn() === 2, 'New anonymous submission');
foreach ([['overall_rating' => 0], ['program_rating' => 6], ['organization_rating' => '5'], ['liked_text' => ['x']], ['improvements_text' => str_repeat('я', 3001)], ['submission_key' => '../bad'], ['participation_format' => 'invalid']] as $bad) {
    try { feedbackValidate(array_replace($payload, $bad)); throw new RuntimeException('Invalid payload accepted'); } catch (InvalidArgumentException $expected) {}
}
$stats = $pdo->query('SELECT COUNT(*) AS total, AVG(overall_rating) AS average FROM conference_feedback')->fetch(PDO::FETCH_ASSOC);
check((int)$stats['total'] === 2 && (float)$stats['average'] === 5.0, 'Dashboard aggregate');
echo "PASS: anonymous fields, text validation, repeat safety, unique submissions, aggregates\n";
