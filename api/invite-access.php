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
 * and guest names are not stored in Git.
 */
function inviteRegistry(): array
{
    return [
        '9f4114a629fb403f7a97aeb1c258f2b816dc965d19646ac09c2d2b10fd508713' => ['slot' => 1, 'name_hash' => 'aa1b505cce6684684c2685614f406189f8f68a3fa84a0805154f5bccff596f8b'],
        'c585dadadeb3b90dc63f7a4180f731bcb3434b6d786a7ac0d13e1025e21f8606' => ['slot' => 2, 'name_hash' => 'f2f89529d56fe12016847c7a145410d8a7db3674a1798e8cca71d87cc5f6fbfb'],
        'b89fe5929b284b91702e818171cf36fda0c2801ba024b7c0925cb6e2197593b7' => ['slot' => 3, 'name_hash' => '09f62ffebb218777eee12cfce1c50cf4e2df64cc698184a684c973db311bfed0'],
        '92ae4b8a23ac2daf285c7a001262de7542264d1668ef027f61756822ff930954' => ['slot' => 4, 'name_hash' => '27ce69695172b1ff3cca6b1638bd665a1c5463ea197200c098aa0022b9c4949f'],
        '2df834378ae4a3157b77db9b9ccc3772d59e0eb7987da32e3aec079d660d3df7' => ['slot' => 5, 'name_hash' => '64d933ee47a960bdf6a999fc4e6b30f79a81bd32a65401700eed33d034aa46d0'],
        'e4c82cb37984601890f637e4573d8d83c1927123009195f5e5d1515a6639f047' => ['slot' => 6, 'name_hash' => '0393e4602627db45605e4bb0b95ee36274733542d1b681ac6b9ab87e2b46da20'],
        'd92707160eb2eb8bf0ca9bb969af671a6945fa33684f5bb74bae0d690f3a8f4f' => ['slot' => 7, 'name_hash' => 'b9e8183cf6cdba4093e38a7cf16a5d4151da9d0b121e3707ad544bba933c1dbb'],
        '2dd9b44ae4f9a39a59c3cc0d19d1cfb0417616b171366be35e0d9e9319b2580e' => ['slot' => 8, 'name_hash' => 'af7c698a8bb4aa666119eb0112a0bc5ec1901a2d2c51f7063b0fdd53659050b3'],
        '78135570992b5c27375121ce0efdb4e404028f0ebba2d3d98c2ef105215eb818' => ['slot' => 9, 'name_hash' => '42a68b34d37231fc7c8eba30b9934aa8d9d094cc1bfcb764b5ec8e624231fb4c'],
        '900f17bbaf5a2539247069876523222ed3e291d3cf144283d3cd4acc0489e8cf' => ['slot' => 10, 'name_hash' => '10a3d899ce747754220ba267ded43fa94818d28fbf9a7e386ef0e7b32024f3ca'],
        'f012b5a446e4019e3c1f68c031dd1f2423c98417d16836ec60624695d27301fa' => ['slot' => 11, 'name_hash' => 'e2d1d163b22439df44d82a0acf958336f53e2794f8fff2a5b5e30d7aaefc0246'],
        'cf4abc1406d2b0dfccdec588d5fabaf3512b296eb6093a163526d0c54ade654f' => ['slot' => 12, 'name_hash' => '0dae0a30dcbdd49765ae94ab872d8086579344d8e0e2d24dfb416a04a3822c46'],
    ];
}

function inviteTokenHash(string $token): string
{
    return hash('sha256', trim($token));
}

function inviteRecord(string $token): ?array
{
    if ($token === '') return null;
    $hash = inviteTokenHash($token);
    foreach (inviteRegistry() as $allowedHash => $record) {
        if (hash_equals($allowedHash, $hash)) return $record;
    }
    return null;
}

function inviteTokenIsKnown(string $token): bool
{
    return inviteRecord($token) !== null;
}

function inviteNormalizeExpectedName(string $value): string
{
    $value = str_replace('ё', 'е', mb_strtolower(trim($value), 'UTF-8'));
    return trim((string)preg_replace('/\s+/u', ' ', $value));
}

function inviteExpectedNameMatches(string $token, string $fullName): bool
{
    $record = inviteRecord($token);
    if (!$record || empty($record['name_hash'])) return false;
    $actualHash = hash('sha256', inviteNormalizeExpectedName($fullName));
    return hash_equals((string)$record['name_hash'], $actualHash);
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
