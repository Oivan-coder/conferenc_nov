<?php
declare(strict_types=1);
require_once __DIR__ . '/organization-analytics.php';

function cleanupNameKey(string $name): string {
    return mb_strtolower(trim((string)preg_replace('/\s+/u', ' ', $name)), 'UTF-8');
}

function cleanupFingerprint(array $rows): string {
    return hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function cleanupPlan(array $rows): array {
    $plan = ['organizations' => [], 'duplicates' => [], 'tests' => [], 'checkins' => []];
    $groups = [];
    $staff = array_map('cleanupNameKey', ['Щеблыкина-Монастырёва Ирина Владимировна', 'Гольцев Иван', 'Гольцев Иван Михайлович', 'Довгаль Лада Алексеевна', 'Шоль Елизавета Викторовна']);
    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $name = cleanupNameKey((string)$row['full_name']);
        if ($name === 'тест тест' && mb_strtolower(trim((string)$row['organization'])) === 'тест'
            && mb_strtolower(trim((string)$row['email'])) === 'ivangoltsev8@gmail.com') {
            $plan['tests'][$id] = $row;
            continue;
        }
        $info = dashboardOrganizationInfo((string)$row['organization']);
        // Unknown names must not be replaced with a shortened display containing ellipsis.
        if ($info['known'] && $info['display'] !== $row['organization']) {
            $plan['organizations'][$id] = ['row' => $row, 'after' => $info['display']];
        }
        if ($row['registration_status'] === 'confirmed' && $name !== '') $groups[$name][] = $row;
        if (in_array($name, $staff, true) && !empty($row['check_in_at']) && $row['check_in_at'] < '2026-10-07 00:00:00') {
            $plan['checkins'][$id] = $row;
        }
    }
    foreach ($groups as $name => $group) {
        if (count($group) > 1) $plan['duplicates'][hash('sha256', $name)] = $group;
    }
    return $plan;
}

function cleanupOperations(array $plan, array $input): array {
    $ops = ['delete' => [], 'organizations' => [], 'checkins' => []];
    foreach (($input['keep'] ?? []) as $key => $id) {
        if ($id === '') continue;
        $group = $plan['duplicates'][$key] ?? null;
        if (!$group || !preg_match('/^[0-9]+$/', (string)$id) || !in_array((int)$id, array_map(static fn($r) => (int)$r['id'], $group), true)) {
            throw new RuntimeException('Некорректный выбор дубля. Обновите страницу.');
        }
        foreach ($group as $row) if ((int)$row['id'] !== (int)$id) $ops['delete'][(int)$row['id']] = $row;
    }
    foreach (($input['tests'] ?? []) as $id) {
        if (!isset($plan['tests'][$id])) throw new RuntimeException('Тестовая запись изменилась.');
        $ops['delete'][(int)$id] = $plan['tests'][$id];
    }
    foreach (($input['checkins'] ?? []) as $id) {
        if (!isset($plan['checkins'][$id])) throw new RuntimeException('Отметка входа изменилась.');
        if (!isset($ops['delete'][(int)$id])) $ops['checkins'][(int)$id] = $plan['checkins'][$id];
    }
    if (($input['normalize'] ?? '') === 'yes') {
        foreach ($plan['organizations'] as $id => $change) if (!isset($ops['delete'][$id])) $ops['organizations'][$id] = $change;
    }
    return $ops;
}

function cleanupBackup(string $directory, array $operations): string {
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Не удалось создать резервную копию. Изменения отменены.');
    $name = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.json';
    $path = $directory . '/' . $name;
    $oldMask = umask(0077);
    try {
        $handle = fopen($path, 'x');
        if (!$handle) throw new RuntimeException('Не удалось создать резервную копию.');
        try {
            $json = json_encode(['event' => 'forum-lab-innovations-2026-10-07', 'created_at' => gmdate('c'), 'operations' => $operations], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) throw new RuntimeException('Резервная копия записана не полностью.');
        } finally { fclose($handle); }
    } finally { umask($oldMask); }
    return $name;
}
