<?php
declare(strict_types=1);

const FEEDBACK_EVENT_ID = 'forum-lab-innovations-2026-10-07';

function feedbackValidate(array $data): array {
    $result = ['event_id' => FEEDBACK_EVENT_ID];
    $key = $data['submission_key'] ?? null;
    if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/D', $key)) {
        throw new InvalidArgumentException('Обновите страницу и повторите отправку.');
    }
    $result['submission_key'] = $key;
    foreach (['overall_rating', 'program_rating', 'organization_rating'] as $field) {
        $rating = $data[$field] ?? null;
        if (!is_int($rating) || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Поставьте каждую оценку от 1 до 5.');
        }
        $result[$field] = $rating;
    }
    $format = $data['participation_format'] ?? 'unspecified';
    if (!is_string($format) || !in_array($format, ['offline', 'online', 'unspecified'], true)) {
        throw new InvalidArgumentException('Выберите формат участия.');
    }
    $result['participation_format'] = $format;
    foreach (['liked_text', 'improvements_text'] as $field) {
        $value = $data[$field] ?? '';
        if (!is_string($value) || !preg_match('//u', $value) || mb_strlen($value, 'UTF-8') > 3000) {
            throw new InvalidArgumentException('Текст каждого ответа — не более 3000 символов.');
        }
        $result[$field] = trim($value);
    }
    return $result;
}

function feedbackSave(PDO $pdo, array $feedback): void {
    $find = $pdo->prepare('SELECT overall_rating, program_rating, organization_rating, participation_format, liked_text, improvements_text FROM conference_feedback WHERE event_id = :event AND submission_key = :key LIMIT 1');
    $find->execute([':event' => $feedback['event_id'], ':key' => $feedback['submission_key']]);
    $existing = $find->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        foreach ($existing as $field => $value) {
            if ((string)$value !== (string)$feedback[$field]) {
                throw new InvalidArgumentException('Этот отзыв уже отправлен. Чтобы оставить новый, обновите страницу.');
            }
        }
        return;
    }
    $insert = $pdo->prepare('INSERT INTO conference_feedback (event_id, submission_key, overall_rating, program_rating, organization_rating, participation_format, liked_text, improvements_text) VALUES (:event_id, :submission_key, :overall_rating, :program_rating, :organization_rating, :participation_format, :liked_text, :improvements_text)');
    try {
        $insert->execute($feedback);
    } catch (PDOException $e) {
        // The unique key makes retries safe, including concurrent submissions.
        if ((string)$e->getCode() !== '23000') throw $e;
        $find->execute([':event' => $feedback['event_id'], ':key' => $feedback['submission_key']]);
        $existing = $find->fetch(PDO::FETCH_ASSOC);
        if (!$existing) throw $e;
        foreach ($existing as $field => $value) {
            if ((string)$value !== (string)$feedback[$field]) throw $e;
        }
    }
}
