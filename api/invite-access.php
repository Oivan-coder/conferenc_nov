<?php

declare(strict_types=1);

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

const INVITE_EVENT_ID = 'forum-lab-innovations-2026-10-07';
const INVITE_EXPIRES_AT = '2026-09-23 23:59:59';

/**
 * Only SHA-256 hashes are kept in the public repository. Raw invitation tokens
 * are shared directly with invited guests and are never stored in Git.
 */
function inviteRegistry(): array
{
    return [
        '9f4114a629fb403f7a97aeb1c258f2b816dc965d19646ac09c2d2b10fd508713' => 1,
        'c585dadadeb3b90dc63f7a4180f731bcb3434b6d786a7ac0d13e1025e21f8606' => 2,
        'b89fe5929b284b91702e818171cf36fda0c2801ba024b7c0925cb6e2197593b7' => 3,
        '92ae4b8a23ac2daf285c7a001262de7542264d1668ef027f61756822ff930954' => 4,
        '2df834378ae4a3157b77db9b9ccc3772d59e0eb7987da32e3aec079d660d3df7' => 5,
        'e4c82cb37984601890f637e4573d8d83c1927123009195f5e5d1515a6639f047' => 6,
        'd92707160eb2eb8bf0ca9bb969af671a6945fa33684f5bb74bae0d690f3a8f4f' => 7,
        '2dd9b44ae4f9a39a59c3cc0d19d1cfb0417616b171366be35e0d9e9319b2580e' => 8,
        '78135570992b5c27375121ce0efdb4e404028f0ebba2d3d98c2ef105215eb818' => 9,
    ];
}

function inviteTokenHash(string $token): string
{
    return hash('sha256', trim($token));
}

function inviteTokenIsKnown(string $token): bool
{
    if ($token === '') return false;
    $hash = inviteTokenHash($token);
    foreach (inviteRegistry() as $allowedHash => $_slot) {
        if (hash_equals($allowedHash, $hash)) return true;
    }
    return false;
}

function inviteDeadline(): DateTimeImmutable
{
    return new DateTimeImmutable(INVITE_EXPIRES_AT, new DateTimeZone('Europe/Moscow'));
}

function inviteTokenIsActive(string $token): bool
{
    if (!inviteTokenIsKnown($token)) return false;
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    return $now <= inviteDeadline();
}

function inviteEnsureSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS registration_invite_usage (
        event_id VARCHAR(100) NOT NULL,
        token_hash CHAR(64) NOT NULL,
        participant_id BIGINT UNSIGNED NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (event_id, token_hash),
        KEY idx_invite_participant (participant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function inviteUsage(PDO $pdo, string $tokenHash, bool $forUpdate = false): ?array
{
    inviteEnsureSchema($pdo);
    $sql = 'SELECT event_id, token_hash, participant_id, used_at FROM registration_invite_usage WHERE event_id = :event_id AND token_hash = :token_hash LIMIT 1';
    if ($forUpdate) $sql .= ' FOR UPDATE';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':event_id' => INVITE_EVENT_ID, ':token_hash' => $tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function inviteIsUsed(PDO $pdo, string $tokenHash, bool $forUpdate = false): bool
{
    $row = inviteUsage($pdo, $tokenHash, $forUpdate);
    return $row !== null && $row['participant_id'] !== null;
}

function inviteMarkUsed(PDO $pdo, string $tokenHash, int $participantId): void
{
    inviteEnsureSchema($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO registration_invite_usage (event_id, token_hash, participant_id, used_at, created_at)
         VALUES (:event_id, :token_hash, :participant_id, NOW(), NOW())
         ON DUPLICATE KEY UPDATE participant_id = VALUES(participant_id), used_at = NOW()'
    );
    $stmt->execute([
        ':event_id' => INVITE_EVENT_ID,
        ':token_hash' => $tokenHash,
        ':participant_id' => $participantId,
    ]);
}

function inviteActiveUnusedCount(PDO $pdo): int
{
    $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow'));
    if ($now > inviteDeadline()) return 0;

    inviteEnsureSchema($pdo);
    $hashes = array_keys(inviteRegistry());
    if (!$hashes) return 0;

    $placeholders = implode(',', array_fill(0, count($hashes), '?'));
    $params = array_merge([INVITE_EVENT_ID], $hashes);
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM registration_invite_usage
         WHERE event_id = ? AND token_hash IN ($placeholders) AND participant_id IS NOT NULL"
    );
    $stmt->execute($params);
    $used = (int)$stmt->fetchColumn();
    return max(0, count($hashes) - $used);
}
