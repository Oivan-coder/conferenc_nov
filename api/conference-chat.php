<?php

declare(strict_types=1);

require dirname(__DIR__) . '/qa/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);

function chat_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function chat_same_origin(): bool
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin === '') return true;
    return in_array($origin, ['https://rclsmo.ru', 'https://www.rclsmo.ru'], true);
}

function chat_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || strlen($raw) > 12000) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    chat_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}
if ($method === 'POST' && !chat_same_origin()) {
    chat_json(['ok' => false, 'error' => 'origin_not_allowed'], 403);
}

$body = $method === 'POST' ? chat_body() : [];
$token = strtolower(trim((string)($method === 'GET' ? ($_GET['t'] ?? '') : ($body['token'] ?? ''))));

try {
    $pdo = qa_pdo();
    qa_ensure_schema($pdo);

    $participant = null;

    if (preg_match('/^[a-f0-9]{64}$/', $token)) {
        $participantStmt = $pdo->prepare(
            "SELECT id, participant_code, full_name, organization, participation_format
             FROM participants
             WHERE online_token = :token
               AND participation_format = 'online'
               AND registration_status = 'confirmed'
             LIMIT 1"
        );
        $participantStmt->execute([':token' => $token]);
        $participant = $participantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$participant && isset($_SESSION['conference_attendee_id'])) {
        $hallId = filter_var($_SESSION['conference_attendee_id'], FILTER_VALIDATE_INT);
        if ($hallId) {
            $participantStmt = $pdo->prepare(
                "SELECT id, participant_code, full_name, organization, participation_format
                 FROM participants
                 WHERE id = :id
                   AND participation_format = 'offline'
                   AND registration_status = 'confirmed'
                 LIMIT 1"
            );
            $participantStmt->execute([':id' => $hallId]);
            $participant = $participantStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }

    if (!$participant) {
        chat_json(['ok' => false, 'error' => 'participant_not_found'], 401);
    }

    if ($method === 'GET') {
        $session = qa_current_session($pdo);
        $stmt = $pdo->prepare(
            "SELECT
                m.id,
                m.participant_id,
                m.participant_name,
                m.organization,
                m.session_id,
                m.message_type,
                m.reply_to_id,
                m.message_text,
                m.status,
                m.created_at,
                s.title AS session_title,
                s.speaker_name,
                r.participant_name AS reply_name,
                LEFT(r.message_text, 180) AS reply_text,
                (SELECT COUNT(*) FROM conference_message_votes v WHERE v.message_id = m.id) AS votes,
                EXISTS(SELECT 1 FROM conference_message_votes vm WHERE vm.message_id = m.id AND vm.participant_id = :participant_id) AS liked_by_me
             FROM conference_messages m
             LEFT JOIN conference_sessions s ON s.id = m.session_id
             LEFT JOIN conference_messages r ON r.id = m.reply_to_id
             WHERE m.event_id = :event_id
               AND m.status <> 'hidden'
             ORDER BY m.id DESC
             LIMIT 100"
        );
        $stmt->execute([
            ':participant_id' => (int)$participant['id'],
            ':event_id' => QA_EVENT_ID,
        ]);
        $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        chat_json([
            'ok' => true,
            'participant' => [
                'id' => (int)$participant['id'],
                'name' => $participant['full_name'],
                'organization' => $participant['organization'],
                'format' => $participant['participation_format'],
            ],
            'session' => $session,
            'messages' => $messages,
            'server_time' => date('c'),
        ]);
    }

    $action = trim((string)($body['action'] ?? ''));

    if ($action === 'send') {
        $messageType = trim((string)($body['message_type'] ?? 'chat'));
        $messageText = trim((string)($body['message_text'] ?? ''));
        $replyToId = isset($body['reply_to_id']) ? filter_var($body['reply_to_id'], FILTER_VALIDATE_INT) : null;

        if (!in_array($messageType, ['chat', 'question'], true)) {
            chat_json(['ok' => false, 'error' => 'invalid_message_type'], 422);
        }
        $length = mb_strlen($messageText);
        if ($length < 1 || $length > 1000) {
            chat_json(['ok' => false, 'error' => 'invalid_message_length'], 422);
        }

        $rateStmt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt, MAX(created_at) AS last_at
             FROM conference_messages
             WHERE event_id = :event_id
               AND participant_id = :participant_id
               AND created_at >= (NOW() - INTERVAL 5 MINUTE)"
        );
        $rateStmt->execute([
            ':event_id' => QA_EVENT_ID,
            ':participant_id' => (int)$participant['id'],
        ]);
        $rate = $rateStmt->fetch(PDO::FETCH_ASSOC) ?: ['cnt' => 0, 'last_at' => null];
        if ((int)$rate['cnt'] >= 30) {
            chat_json(['ok' => false, 'error' => 'rate_limited', 'message' => 'Слишком много сообщений. Подождите несколько минут.'], 429);
        }
        if (!empty($rate['last_at']) && (time() - strtotime((string)$rate['last_at'])) < 2) {
            chat_json(['ok' => false, 'error' => 'too_fast', 'message' => 'Подождите пару секунд перед следующим сообщением.'], 429);
        }

        if ($replyToId) {
            $replyStmt = $pdo->prepare(
                "SELECT id FROM conference_messages
                 WHERE id = :id AND event_id = :event_id AND status <> 'hidden'
                 LIMIT 1"
            );
            $replyStmt->execute([':id' => $replyToId, ':event_id' => QA_EVENT_ID]);
            if (!$replyStmt->fetchColumn()) $replyToId = null;
        }

        $session = qa_current_session($pdo);
        $status = $messageType === 'question' ? 'new' : 'visible';
        $insert = $pdo->prepare(
            "INSERT INTO conference_messages
                (event_id, participant_id, participant_name, organization, session_id, message_type, reply_to_id, message_text, status)
             VALUES
                (:event_id, :participant_id, :participant_name, :organization, :session_id, :message_type, :reply_to_id, :message_text, :status)"
        );
        $insert->execute([
            ':event_id' => QA_EVENT_ID,
            ':participant_id' => (int)$participant['id'],
            ':participant_name' => $participant['full_name'],
            ':organization' => $participant['organization'],
            ':session_id' => $session['id'] ?? null,
            ':message_type' => $messageType,
            ':reply_to_id' => $replyToId ?: null,
            ':message_text' => $messageText,
            ':status' => $status,
        ]);

        chat_json(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'vote') {
        $messageId = filter_var($body['message_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$messageId) {
            chat_json(['ok' => false, 'error' => 'invalid_message_id'], 422);
        }

        $messageStmt = $pdo->prepare(
            "SELECT id FROM conference_messages
             WHERE id = :id
               AND event_id = :event_id
               AND message_type = 'question'
               AND status <> 'hidden'
             LIMIT 1"
        );
        $messageStmt->execute([':id' => $messageId, ':event_id' => QA_EVENT_ID]);
        if (!$messageStmt->fetchColumn()) {
            chat_json(['ok' => false, 'error' => 'message_not_found'], 404);
        }

        $pdo->beginTransaction();
        $existsStmt = $pdo->prepare(
            'SELECT 1 FROM conference_message_votes WHERE message_id = :message_id AND participant_id = :participant_id LIMIT 1 FOR UPDATE'
        );
        $existsStmt->execute([
            ':message_id' => $messageId,
            ':participant_id' => (int)$participant['id'],
        ]);
        $liked = (bool)$existsStmt->fetchColumn();

        if ($liked) {
            $delete = $pdo->prepare('DELETE FROM conference_message_votes WHERE message_id = :message_id AND participant_id = :participant_id');
            $delete->execute([':message_id' => $messageId, ':participant_id' => (int)$participant['id']]);
            $liked = false;
        } else {
            $insertVote = $pdo->prepare('INSERT IGNORE INTO conference_message_votes (message_id, participant_id) VALUES (:message_id, :participant_id)');
            $insertVote->execute([':message_id' => $messageId, ':participant_id' => (int)$participant['id']]);
            $liked = true;
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM conference_message_votes WHERE message_id = :message_id');
        $countStmt->execute([':message_id' => $messageId]);
        $votes = (int)$countStmt->fetchColumn();
        $pdo->commit();

        chat_json(['ok' => true, 'liked' => $liked, 'votes' => $votes]);
    }

    chat_json(['ok' => false, 'error' => 'unknown_action'], 422);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    chat_json(['ok' => false, 'error' => 'server_error'], 500);
}
