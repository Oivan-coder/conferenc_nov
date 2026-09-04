<?php

const REGISTRATION_EVENT_ID = 'forum-lab-innovations-2026-10-07';
const REGISTRATION_HALL_CAPACITY = 94;
const REGISTRATION_PUBLIC_OFFLINE_LIMIT = 50;

function registrationEnsureSourceColumn(PDO $pdo): void
{
    $column = $pdo->query("SHOW COLUMNS FROM participants LIKE 'registration_source'")->fetchColumn();
    if ($column !== false) return;

    try {
        // До открытия публичной формы все рабочие записи создавались через закрытый контур.
        $pdo->exec("ALTER TABLE participants ADD COLUMN registration_source VARCHAR(20) NOT NULL DEFAULT 'invited' AFTER registration_status");
    } catch (PDOException $error) {
        $driverCode = (int)($error->errorInfo[1] ?? 0);
        if ($driverCode !== 1060) throw $error;
    }
}

function registrationEffectiveHallCapacity(array $settings): int
{
    $configured = max(0, (int)($settings['hall_capacity'] ?? 0));
    if ($configured === 0) return REGISTRATION_HALL_CAPACITY;
    return min($configured, REGISTRATION_HALL_CAPACITY);
}

function registrationEffectivePublicOfflineLimit(array $settings): int
{
    $configured = max(0, (int)($settings['public_offline_limit'] ?? 0));
    if ($configured === 0) return REGISTRATION_PUBLIC_OFFLINE_LIMIT;
    return min($configured, REGISTRATION_PUBLIC_OFFLINE_LIMIT, registrationEffectiveHallCapacity($settings));
}

function registrationIsLegacyTestOrganization(string $organization): bool
{
    $normalized = mb_strtolower(trim($organization));
    return $normalized === 'тестовая мо' || in_array($normalized, ['ovan', 'oivan'], true);
}

function registrationSourceLabel(string $source): string
{
    return match ($source) {
        'public' => 'Публичная регистрация',
        'invited' => 'По приглашению',
        'test' => 'Тест',
        default => 'Не указано',
    };
}
