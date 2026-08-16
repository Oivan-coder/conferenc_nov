<?php
require __DIR__ . '/_bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

if (!qa_is_authorized()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $pdo = qa_pdo();
    qa_ensure_schema($pdo);
    $session = qa_current_session($pdo);

    $stmt = $pdo->prepare(
        "SELECT
            m.id,
            m.participant_name,
            m.organization,
            m.message_text AS question_text,
            m.created_at,
            s.title AS session_title,
            s.speaker_name
         FROM conference_messages m
         LEFT JOIN conference_sessions s ON s.id = m.session_id
         WHERE m.event_id = :event_id
           AND m.message_type = 'question'
           AND m.status = 'on_air'
         ORDER BY m.on_air_at DESC, m.id DESC
         LIMIT 1"
    );
    $stmt->execute([':event_id' => QA_EVENT_ID]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    echo json_encode([
        'ok' => true,
        'session' => $session,
        'question' => $question,
        'server_time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
