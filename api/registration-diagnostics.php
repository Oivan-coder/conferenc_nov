<?php
require_once __DIR__ . '/registration-config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

const PREVIEW_TOKEN_HASHES = [
    '39e70b738bd910aa9296407b8e856e4c0ec58f3e420fd523d67d88bc6cee6cc1',
    '6fbe6025563f098ca8756103aa6cb93f4ac2c5bbb5f769e42aff0de2de2c14b9',
    '65b8c5c44e5fdba2620f30c5e434f0893258809bc1a9d2650de8316b48a6f324',
];
const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
const TEST_KEY_PATH = '/home/c/cx314477/public_html/.private/registration_test_key';
const SMTP_PASSWORD_PATH = '/home/c/cx314477/public_html/.private/smtp_pass';
const AUTOLOAD_PATH = '/home/c/cx314477/public_html/.private/vendor/autoload.php';
const LIVE_EMBED_URL_PATH = '/home/c/cx314477/public_html/.private/live_embed_url';
const EVENT_ID = 'forum-lab-innovations-2026-10-07';

function tokenIsValid(string $token): bool {
    if ($token === '') return false;
    $hash = hash('sha256', $token);
    foreach (PREVIEW_TOKEN_HASHES as $allowedHash) {
        if (hash_equals($allowedHash, $hash)) return true;
    }
    return false;
}

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function databaseErrorCategory(Throwable $error): string {
    if (!$error instanceof PDOException) return 'unexpected_error';
    $driverCode = (int)($error->errorInfo[1] ?? 0);
    return match ($driverCode) {
        1045 => 'authentication_failed',
        1049 => 'database_not_found',
        2002, 2003 => 'server_unreachable',
        2005 => 'host_not_found',
        2006, 2013 => 'connection_lost',
        default => 'database_connection_failed',
    };
}

$token = trim((string)($_GET['key'] ?? ''));
if (!tokenIsValid($token)) respond(404, ['ok' => false, 'error' => 'not_found']);

$result = [
    'ok' => false,
    'runtime' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
    ],
    'private_files' => [
        'database_config' => is_readable(DB_CONFIG_PATH),
        'registration_test_key' => is_readable(TEST_KEY_PATH),
        'smtp_password' => is_readable(SMTP_PASSWORD_PATH),
        'qr_library' => is_readable(AUTOLOAD_PATH),
        'live_embed_url' => is_readable(LIVE_EMBED_URL_PATH),
    ],
    'database' => [
        'connected' => false,
        'event_settings' => false,
        'required_columns' => false,
        'unique_participant_code' => false,
        'unique_qr_token' => false,
        'unique_online_token' => false,
    ],
    'test_data' => [
        'ovan_records' => null,
        'qa_records' => null,
    ],
];

if (!is_readable(DB_CONFIG_PATH)) respond(503, $result);

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Invalid database object');
    registrationEnsureSourceColumn($pdo);
    $pdo->query('SELECT 1');
    $result['database']['connected'] = true;

    $settingsStmt = $pdo->prepare('SELECT event_id FROM event_registration_settings WHERE event_id = :event_id LIMIT 1');
    $settingsStmt->execute([':event_id' => EVENT_ID]);
    $result['database']['event_settings'] = (bool)$settingsStmt->fetchColumn();

    $requiredColumns = [
        'event_id', 'participant_code', 'qr_token', 'online_token', 'last_name', 'first_name',
        'full_name', 'position', 'organization', 'email', 'email_normalized', 'phone_normalized',
        'participation_format', 'registration_status', 'registration_source', 'privacy_consent', 'consent_version',
        'consent_at', 'created_at', 'check_in_at', 'online_watch_seconds',
    ];
    $columns = $pdo->query('SHOW COLUMNS FROM participants')->fetchAll(PDO::FETCH_COLUMN);
    $result['database']['required_columns'] = count(array_diff($requiredColumns, $columns)) === 0;

    $indexes = $pdo->query('SHOW INDEX FROM participants')->fetchAll(PDO::FETCH_ASSOC);
    $uniqueIndexes = [];
    foreach ($indexes as $index) {
        if ((int)($index['Non_unique'] ?? 1) !== 0) continue;
        $indexName = (string)($index['Key_name'] ?? '');
        $sequence = (int)($index['Seq_in_index'] ?? 0);
        if ($indexName === '' || $sequence < 1) continue;
        $uniqueIndexes[$indexName][$sequence] = (string)($index['Column_name'] ?? '');
    }
    $singleUniqueColumns = [];
    foreach ($uniqueIndexes as $indexColumns) {
        ksort($indexColumns);
        if (count($indexColumns) === 1) $singleUniqueColumns[] = reset($indexColumns);
    }
    $result['database']['unique_participant_code'] = in_array('participant_code', $singleUniqueColumns, true);
    $result['database']['unique_qr_token'] = in_array('qr_token', $singleUniqueColumns, true);
    $result['database']['unique_online_token'] = in_array('online_token', $singleUniqueColumns, true);

    $testDataStmt = $pdo->prepare('SELECT
        SUM(LOWER(TRIM(organization)) = "ovan") AS ovan_records,
        SUM(organization = :test_org) AS qa_records
        FROM participants
        WHERE event_id = :event_id');
    $testDataStmt->execute([':test_org' => 'Тестовая МО', ':event_id' => EVENT_ID]);
    $testData = $testDataStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $result['test_data']['ovan_records'] = (int)($testData['ovan_records'] ?? 0);
    $result['test_data']['qa_records'] = (int)($testData['qa_records'] ?? 0);

    $result['ok'] = $result['database']['connected']
        && $result['database']['event_settings']
        && $result['database']['required_columns']
        && $result['database']['unique_participant_code']
        && $result['database']['unique_qr_token']
        && $result['database']['unique_online_token']
        && $result['private_files']['registration_test_key']
        && $result['private_files']['smtp_password']
        && $result['private_files']['qr_library'];

    respond($result['ok'] ? 200 : 503, $result);
} catch (Throwable $e) {
    $result['database']['error_type'] = get_class($e);
    $result['database']['error_category'] = databaseErrorCategory($e);
    respond(503, $result);
}
