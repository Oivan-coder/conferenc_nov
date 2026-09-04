<?php

const REGISTRATION_DUPLICATE_GUARD_DB_PATH = '/home/c/cx314477/public_html/.private/db.php';

function registrationDuplicateGuardNormalizeName(string $value): string {
    $value = trim((string)preg_replace('/\s+/u', ' ', $value));
    return str_replace('ё', 'е', mb_strtolower($value));
}

function registrationDuplicateGuard(
    string $eventId,
    string $lastName,
    string $firstName,
    string $middleName,
    string $organization,
    string $emailNormalized,
    string $phoneNormalized
): ?array {
    if ($phoneNormalized === '' || $emailNormalized === '') return null;

    try {
        $pdo = require REGISTRATION_DUPLICATE_GUARD_DB_PATH;
        if (!$pdo instanceof PDO) return null;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Serialize requests with the same contact pair so two simultaneous POSTs
        // cannot both pass the duplicate check before either INSERT completes.
        $lockName = 'rclsmo_reg_' . substr(hash('sha256', $eventId . '|' . $emailNormalized . '|' . $phoneNormalized), 0, 40);
        $lockStmt = $pdo->prepare('SELECT GET_LOCK(:lock_name, 3)');
        $lockStmt->execute([':lock_name' => $lockName]);
        if ((int)$lockStmt->fetchColumn() !== 1) return null;

        // Keep the PDO connection alive until the request exits; MySQL then
        // releases the named lock automatically.
        static $lockHolders = [];
        $lockHolders[] = $pdo;

        $stmt = $pdo->prepare(
            'SELECT last_name, first_name, COALESCE(middle_name, "") AS middle_name, organization
             FROM participants
             WHERE event_id = :event_id
               AND registration_status <> "cancelled"
               AND email_normalized = :email
               AND phone_normalized = :phone
             LIMIT 1'
        );
        $stmt->execute([
            ':event_id' => $eventId,
            ':email' => $emailNormalized,
            ':phone' => $phoneNormalized,
        ]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) return null;

        $samePerson = registrationDuplicateGuardNormalizeName((string)$existing['last_name']) === registrationDuplicateGuardNormalizeName($lastName)
            && registrationDuplicateGuardNormalizeName((string)$existing['first_name']) === registrationDuplicateGuardNormalizeName($firstName)
            && registrationDuplicateGuardNormalizeName((string)$existing['middle_name']) === registrationDuplicateGuardNormalizeName($middleName)
            && mb_strtolower(trim((string)$existing['organization'])) === mb_strtolower(trim($organization));

        // Email + phone on the same active registration is considered a hard
        // duplicate even if the submitted name was deliberately changed.
        return [
            'hard' => true,
            'same_person' => $samePerson,
            // Include all three markers so the existing UI takes the non-overridable
            // "already registered" path without changing the form itself.
            'reasons' => ['same_person', 'email', 'phone'],
        ];
    } catch (Throwable $e) {
        // Duplicate protection must never take the whole registration form down.
        error_log('Registration duplicate guard unavailable: ' . $e->getMessage());
        return null;
    }
}
