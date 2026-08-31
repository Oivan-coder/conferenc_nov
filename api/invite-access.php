<?php

declare(strict_types=1);

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

const INVITE_EVENT_ID = 'forum-lab-innovations-2026-10-07';
const INVITE_EXPIRES_AT = '2026-09-23 23:59:59';

/**
 * Only SHA-256 hashes and token-encrypted display names are kept in the public
 * repository. Raw invitation tokens and readable guest names are not stored in Git.
 */
function inviteRegistry(): array
{
    return [
        '9f4114a629fb403f7a97aeb1c258f2b816dc965d19646ac09c2d2b10fd508713' => ['slot' => 1, 'name_hash' => 'aa1b505cce6684684c2685614f406189f8f68a3fa84a0805154f5bccff596f8b', 'short_name_hash' => '844c7786f1c05964fdb2c8d55075d6ac8f5942884019f3ec054063158c420073', 'name_enc' => 'EF4Hx7F-DdXzVDGMP3AIgePb1Lhw0N8VKOHfLoZAwWY-gIi1vQqk2xXpmBiV3qG3FXuFLJV5o-wfCedx-sRIwJC9cqIZZeYklCt01lBN'],
        'c585dadadeb3b90dc63f7a4180f731bcb3434b6d786a7ac0d13e1025e21f8606' => ['slot' => 2, 'name_hash' => 'f2f89529d56fe12016847c7a145410d8a7db3674a1798e8cca71d87cc5f6fbfb', 'short_name_hash' => 'c091bea263ce2b1f6627a7642c26ec9a0e1d19a1afb557f8c6cded5c9874623c', 'name_enc' => 'QOVyOYF94McMarmQU9660tzc-BAt6SPU2b6oSaeS31Uo0TDA6i0oboZ20Do1wH4mhrmzcZPm5i6n4AAenZqF0dKbvuIAJgZ2Uo8'],
        'b89fe5929b284b91702e818171cf36fda0c2801ba024b7c0925cb6e2197593b7' => ['slot' => 3, 'name_hash' => '09f62ffebb218777eee12cfce1c50cf4e2df64cc698184a684c973db311bfed0', 'short_name_hash' => '70d13b7c0d4af77b9b7bbb9403e8372937399e0ddcab25ebea39f8d46f4280a7', 'name_enc' => 'JTRnaBbKIFGJp_NrG4m96jf4MPrQNjiUlrGQInx8JfIKCqiuWdbTJOQCAhnkwavb6eF-COVV-CXyQAljyxX4agrz2LQhcqf5FKI'],
        '92ae4b8a23ac2daf285c7a001262de7542264d1668ef027f61756822ff930954' => ['slot' => 4, 'name_hash' => '27ce69695172b1ff3cca6b1638bd665a1c5463ea197200c098aa0022b9c4949f', 'short_name_hash' => '3a7117c6f42be622fcc3fee323a64eb9f54d6f2e7b0def6cff20e8b15ca0f50e', 'name_enc' => '6a1wIyk3COXNirDRG6oMHc0My8y8Ha3qMwEAiw46T74_DVt1DsOQjVonK5HJq6kVmcSYBkSzZ5x1SH5hLAgEm3FE3pGVJIcl_jpbEQ'],
        '2df834378ae4a3157b77db9b9ccc3772d59e0eb7987da32e3aec079d660d3df7' => ['slot' => 5, 'name_hash' => '64d933ee47a960bdf6a999fc4e6b30f79a81bd32a65401700eed33d034aa46d0', 'short_name_hash' => '03c24897b8d0b38092a43ea76f5fd5c58ecda4f45ae3bdc21200db395ac2f395', 'name_enc' => 'DwuP9vspcb1DtNG615kIKojOtjZS8D1bhLyyuNCBI3g_SoQlKCue1rKZOaIpK_VXNUPjOQvwn9NMTqNq21_qkx_mfJuOArJOFVgsKhhLqqdiLw'],
        'e4c82cb37984601890f637e4573d8d83c1927123009195f5e5d1515a6639f047' => ['slot' => 6, 'name_hash' => '0393e4602627db45605e4bb0b95ee36274733542d1b681ac6b9ab87e2b46da20', 'short_name_hash' => 'ceb31d0f836cf1b7ada3cc1fe5713231518b92dc9d13edb227f0fe13db9390de', 'name_enc' => 'xnCjRNjS3OsePlLn6eb6OeNF9p-S3vpb33wEWaDeDxsWSfThjoITPWxa-vtg2iSAVfZOyDEa10bVgWjN5-5GUpxWvEFVuBezV_4'],
        'd92707160eb2eb8bf0ca9bb969af671a6945fa33684f5bb74bae0d690f3a8f4f' => ['slot' => 7, 'name_hash' => 'b9e8183cf6cdba4093e38a7cf16a5d4151da9d0b121e3707ad544bba933c1dbb', 'short_name_hash' => 'da683fe4ffb440051ccb6f2eb0598febf7672725289eee1ddb589513b8a9ab0c', 'name_enc' => 'HPWpWGvoJZq0ji1XY5f4m0-dVP_JIBkiUBrnIYNdjNZu3p4IBNnOgfB7Wy5-qpj99_u1tWAipXX5Arx0EDoM7ya5njPOJg'],
        '2dd9b44ae4f9a39a59c3cc0d19d1cfb0417616b171366be35e0d9e9319b2580e' => ['slot' => 8, 'name_hash' => 'af7c698a8bb4aa666119eb0112a0bc5ec1901a2d2c51f7063b0fdd53659050b3', 'short_name_hash' => '1ea0e919fc612dada5745c385086d338409ac38133e02f8c25a65b228f95453d', 'name_enc' => 'esIAjzg17dZdgcsmO8T_Jhprz-D7y3Y9Idv7p6JPzAexT446_Q9WAtyyk3rxouoAkK4zDoTuMsYFL4zyMvLfSSdpPhhihApsxVo1-4nZZ8w'],
        '78135570992b5c27375121ce0efdb4e404028f0ebba2d3d98c2ef105215eb818' => ['slot' => 9, 'name_hash' => '42a68b34d37231fc7c8eba30b9934aa8d9d094cc1bfcb764b5ec8e624231fb4c', 'short_name_hash' => '0caa05a54fa340b7d76b0608e6ab19986c380dd23bfc676a0ea50248947aece6', 'name_enc' => 'A0YLzkVGKYI1Xapx-U6m8C0RC7FkJU9VYLI4_qdF72GcxBH_6R34VyKAArD2FFjS7rMFjUw7MaDA6yT4xHr-rjxWz5Xjp336gXA'],
        'f1d3148c4152fee24d449cfb85f55a5304210e5278bf6f583afa0d3ce0ab0c4e' => ['slot' => 10, 'name_hash' => '10a3d899ce747754220ba267ded43fa94818d28fbf9a7e386ef0e7b32024f3ca', 'short_name_hash' => '10a3d899ce747754220ba267ded43fa94818d28fbf9a7e386ef0e7b32024f3ca', 'name_enc' => 'u9NrlnpMQFRREmVYyG0z6ahojPkQ4V9IFtGup9ldKnFgkmPdmRJS28Gk04Gi4BKgA5Mfebgf4A'],
        'acd96d57c933b01242e5f37990cb60df56f4b1c67abc50284fe423981f28fd87' => ['slot' => 11, 'name_hash' => 'ec280e78e4d9ab8e3f13ae9b49a0fc705f96cbfc658ecbad7735dbe6248994c7', 'short_name_hash' => 'e2d1d163b22439df44d82a0acf958336f53e2794f8fff2a5b5e30d7aaefc0246', 'name_enc' => 'lFwjwXzuCq6k1FaLDPGN4JhTZOPyDykaOrueAI4Au_DcDsautLVtG6aQ7aBLMTXdnKOat5YsA-fplu1-vQbm88eVZOGUJuDP'],
        'a6b5bcf16ddb6edb3d979101c8966d909d333ce6481929e2ea1f0c594b19075d' => ['slot' => 12, 'name_hash' => '0dae0a30dcbdd49765ae94ab872d8086579344d8e0e2d24dfb416a04a3822c46', 'short_name_hash' => '0dae0a30dcbdd49765ae94ab872d8086579344d8e0e2d24dfb416a04a3822c46', 'name_enc' => '99jnoK2quXLNfj_l02pJjBEj4WZs29jBO_1WoGDGvz-bqi0k5-YgfxI4zhozQGrfEalO'],
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
    if (!$record || empty($record['short_name_hash'])) return false;

    $normalized = inviteNormalizeExpectedName($fullName);
    $parts = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) < 2) return false;

    $shortHash = hash('sha256', $parts[0] . ' ' . $parts[1]);
    return hash_equals((string)$record['short_name_hash'], $shortHash);
}

function inviteBase64UrlDecode(string $value): string|false
{
    $value = strtr($value, '-_', '+/');
    $padding = strlen($value) % 4;
    if ($padding !== 0) $value .= str_repeat('=', 4 - $padding);
    return base64_decode($value, true);
}

function inviteExpectedName(string $token): ?string
{
    $record = inviteRecord($token);
    if (!$record || empty($record['name_enc']) || !function_exists('openssl_decrypt')) return null;

    $blob = inviteBase64UrlDecode((string)$record['name_enc']);
    if ($blob === false || strlen($blob) < 29) return null;

    $iv = substr($blob, 0, 12);
    $tag = substr($blob, -16);
    $ciphertext = substr($blob, 12, -16);
    $key = hash('sha256', 'invite-prefill|' . trim($token), true);
    $name = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if (!is_string($name) || trim($name) === '') return null;
    return trim($name);
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
