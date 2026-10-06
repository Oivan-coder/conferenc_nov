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

    $questions = qa_questions($pdo);
    $question = null;
    foreach ($questions as $item) {
        if ($item['status'] === 'on_air') { $question = $item; break; }
    }

    echo json_encode([
        'ok' => true,
        'session' => $session,
        'question' => $question,
        'questions' => $questions,
        'server_time' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

