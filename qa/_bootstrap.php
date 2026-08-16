<?php

declare(strict_types=1);

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow, noarchive', true);
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

const QA_EVENT_ID = 'forum-lab-innovations-2026-10-07';
const QA_DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const QA_PIN_PATHS = [
    '/home/c/cx314477/public_html/.private/qa_pin',
    '/home/c/cx314477/public_html/.private/checkin_pin',
    '/home/c/cx314477/public_html/.private/dashboard_pass',
];

function qa_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function qa_expected_pin(): string
{
    foreach (QA_PIN_PATHS as $path) {
        if (!is_readable($path)) continue;
        $value = trim((string)file_get_contents($path));
        if ($value !== '') return $value;
    }
    return '';
}

function qa_is_authorized(): bool
{
    return (bool)($_SESSION['qa_staff_auth'] ?? false);
}

function qa_process_auth(string $redirectPath): array
{
    if (isset($_POST['qa_logout'])) {
        unset($_SESSION['qa_staff_auth']);
        session_regenerate_id(true);
        header('Location: ' . $redirectPath);
        exit;
    }

    $expected = qa_expected_pin();
    $error = '';

    if (!qa_is_authorized() && isset($_POST['qa_pin'])) {
        $provided = trim((string)$_POST['qa_pin']);
        if ($expected !== '' && hash_equals($expected, $provided)) {
            session_regenerate_id(true);
            $_SESSION['qa_staff_auth'] = true;
            header('Location: ' . $redirectPath);
            exit;
        }
        usleep(350000);
        $error = $expected === '' ? 'PIN для Q&A ещё не настроен.' : 'Неверный PIN.';
    }

    return [qa_is_authorized(), $expected !== '', $error];
}

function qa_csrf_token(): string
{
    if (!isset($_SESSION['qa_csrf']) || !is_string($_SESSION['qa_csrf']) || strlen($_SESSION['qa_csrf']) < 32) {
        $_SESSION['qa_csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['qa_csrf'];
}

function qa_verify_csrf(): void
{
    $expected = (string)($_SESSION['qa_csrf'] ?? '');
    $provided = (string)($_POST['csrf'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Некорректный запрос. Обновите страницу и повторите.');
    }
}

function qa_pdo(): PDO
{
    $pdo = require QA_DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function qa_ensure_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS conference_sessions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        speaker_name VARCHAR(255) NOT NULL,
        is_current TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_event_current (event_id, is_current)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS conference_questions (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id VARCHAR(100) NOT NULL,
        participant_id BIGINT UNSIGNED NULL,
        participant_name VARCHAR(255) NOT NULL,
        organization VARCHAR(255) NOT NULL,
        session_id BIGINT UNSIGNED NULL,
        question_text TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        votes INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME NULL,
        on_air_at DATETIME NULL,
        answered_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_event_status_created (event_id, status, created_at),
        KEY idx_session_status (session_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS conference_messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_id VARCHAR(100) NOT NULL,
        participant_id BIGINT UNSIGNED NOT NULL,
        participant_name VARCHAR(255) NOT NULL,
        organization VARCHAR(255) NOT NULL,
        session_id BIGINT UNSIGNED NULL,
        message_type ENUM('chat','question') NOT NULL DEFAULT 'chat',
        reply_to_id BIGINT UNSIGNED NULL,
        message_text TEXT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'visible',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        approved_at DATETIME NULL,
        on_air_at DATETIME NULL,
        answered_at DATETIME NULL,
        hidden_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY idx_event_created (event_id, created_at),
        KEY idx_event_type_status (event_id, message_type, status, created_at),
        KEY idx_session_type_status (session_id, message_type, status),
        KEY idx_participant_created (participant_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS conference_message_votes (
        message_id BIGINT UNSIGNED NOT NULL,
        participant_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id, participant_id),
        KEY idx_participant (participant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function qa_current_session(PDO $pdo): ?array
{
    $stmt = $pdo->prepare('SELECT id, title, speaker_name, created_at FROM conference_sessions WHERE event_id = :event_id AND is_current = 1 ORDER BY id DESC LIMIT 1');
    $stmt->execute([':event_id' => QA_EVENT_ID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function qa_login_markup(bool $pinConfigured, string $error, string $title = 'Q&A'): string
{
    $notice = !$pinConfigured
        ? '<div class="notice">PIN не найден. Можно создать <code>.private/qa_pin</code>; иначе используется существующий <code>checkin_pin</code> или <code>dashboard_pass</code>.</div>'
        : '';
    if ($error !== '') $notice .= '<div class="notice">' . qa_h($error) . '</div>';

    return '<div class="login-card"><div class="brand">Форум лабораторных инноваций 2026</div><h1>' . qa_h($title) . '</h1>' . $notice . '<form method="post" autocomplete="off"><label for="qa_pin">PIN доступа</label><input id="qa_pin" name="qa_pin" type="password" inputmode="numeric" autocomplete="off" autofocus required><button type="submit">Войти</button></form></div>';
}
