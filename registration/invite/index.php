<?php

declare(strict_types=1);

const DB_CONFIG_PATH = '/home/c/cx314477/public_html/.private/db.php';
require_once dirname(__DIR__, 2) . '/api/invite-access.php';

function invitePageFail(int $status, string $title, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Robots-Tag: noindex, nofollow,noarchive');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head><body style="margin:0;background:#07111f;color:#f4fbff;font-family:Arial,sans-serif"><main style="min-height:100vh;display:grid;place-items:center;padding:24px"><section style="width:min(620px,100%);padding:32px;border:1px solid rgba(118,235,251,.18);border-radius:22px;background:#0c1e32"><div style="font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#42dff5">Форум лабораторных инноваций Московской области — 2026</div><h1 style="font-size:32px;margin:16px 0 12px">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p style="color:#b8c7d9;line-height:1.65;margin:0">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></section></main></body></html>';
    exit;
}

$inviteToken = trim((string)($_GET['t'] ?? ''));
if (!inviteTokenIsKnown($inviteToken)) invitePageFail(404, 'Приглашение не найдено', 'Проверьте персональную ссылку или обратитесь к организаторам.');
if (!inviteTokenIsActive($inviteToken)) invitePageFail(410, 'Срок приглашения истёк', 'Резерв места действовал до 23 сентября 2026 года. Для уточнения возможности участия свяжитесь с организаторами.');

try {
    $pdo = require DB_CONFIG_PATH;
    if (!$pdo instanceof PDO) throw new RuntimeException('Database config invalid');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if (inviteIsUsed($pdo, inviteTokenHash($inviteToken))) {
        invitePageFail(410, 'Приглашение уже использовано', 'Регистрация по этой персональной ссылке уже была завершена. Подтверждение участия направлено на электронную почту, указанную при регистрации.');
    }
} catch (Throwable $e) {
    invitePageFail(503, 'Регистрация временно недоступна', 'Попробуйте открыть персональную ссылку ещё раз через несколько минут.');
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow,noarchive');

$templatePath = dirname(__DIR__) . '/index.html';
$html = is_readable($templatePath) ? (string)file_get_contents($templatePath) : '';
if ($html === '') invitePageFail(503, 'Регистрация временно недоступна', 'Не удалось загрузить форму регистрации.');

$headMarker = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
$html = str_replace($headMarker, $headMarker . "\n    <meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n    <meta name=\"referrer\" content=\"no-referrer\">", $html);
$html = str_replace('<link rel="canonical" href="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<meta property="og:url" content="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<title>Регистрация — Форум лабораторных инноваций Московской области 2026</title>', '<title>Персональное приглашение — Форум лабораторных инноваций Московской области 2026</title>', $html);

$cleaner = <<<'HTML'
    <script>
        if (window.location.pathname !== '/registration/' || window.location.search) {
            window.history.replaceState(null, '', '/registration/' + window.location.hash);
        }
    </script>
HTML;
$html = str_replace($cleaner, '', $html);
$html = str_replace('<script src="js/url-cleaner.js" defer></script>', '', $html);
$html = str_replace('data-registration-state="closed"', 'data-registration-state="open"', $html);
$html = str_replace('Форма подготовлена к запуску. После открытия будут доступны очный и онлайн-форматы участия.', 'Для вас зарезервировано очное место по персональному приглашению. Заполните данные участника для подтверждения.', $html);
$html = str_replace('<strong>Регистрация ещё не открыта</strong>', '<strong>Персональное приглашение</strong>', $html);
$html = str_replace('Публичный приём заявок будет включён после завершения внутренней проверки формы и базы данных.', 'Очное место зарезервировано до 23 сентября 2026 года включительно. После этой даты неиспользованный резерв возвращается в общий пул.', $html);
$html = str_replace('Фамилия, имя, отчество, должность, медицинская организация, формат участия и контакт для подтверждения.', 'Фамилия, имя, отчество, должность, медицинская организация и контакт для подтверждения очного участия.', $html);
$html = str_replace('<strong>Форма готова к внутренней проверке</strong>', '<strong>Персональная регистрация доступна</strong>', $html);
$html = str_replace('Публичная регистрация пока отключена. На этой странице персональные данные участников не принимаются.', 'Заполните форму ниже. После регистрации на почту придёт подтверждение и QR-билет для очного участия.', $html);

$formatBlock = <<<'HTML'
                    <fieldset class="r26-field r26-format-field">
                        <legend>Формат участия <span class="r26-required" aria-hidden="true">*</span></legend>
                        <div class="r26-format-options">
                            <label class="r26-format-option"><input type="radio" name="participationFormat" value="offline" required><span><strong>Очно</strong><small>Красногорск</small></span></label>
                            <label class="r26-format-option"><input type="radio" name="participationFormat" value="online" required><span><strong>Онлайн</strong><small>Персональная ссылка</small></span></label>
                        </div>
                        <p class="r26-form__message" data-offline-availability>Количество мест для очного участия ограничено.</p>
                    </fieldset>
HTML;
$offlineBlock = <<<'HTML'
                    <div class="r26-field r26-format-field r26-format-field--invite" aria-label="Формат участия">
                        <span class="r26-format-label">Формат участия</span>
                        <input type="hidden" name="participationFormat" value="offline">
                        <div class="r26-format-static">
                            <strong>Очное участие</strong>
                            <small>Дом Правительства Московской области · Красногорск</small>
                        </div>
                        <p class="r26-form__message" data-offline-availability>Место зарезервировано по персональному приглашению.</p>
                    </div>
HTML;
$html = str_replace($formatBlock, $offlineBlock, $html);

$oldConfig = <<<'HTML'
        window.REGISTRATION_CONFIG = {
            state: 'closed',
            endpoint: '/api/register-safe.php',
            availabilityEndpoint: '/api/registration-availability.php',
            eventId: 'forum-lab-innovations-2026-10-07'
        };
HTML;
$endpoint = '/api/register-invite-safe.php?invite=' . rawurlencode($inviteToken);
$availabilityEndpoint = '/api/registration-invite-availability.php?t=' . rawurlencode($inviteToken);
$newConfig = "        window.REGISTRATION_CONFIG = {\n            state: 'open',\n            endpoint: " . json_encode($endpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ",\n            availabilityEndpoint: " . json_encode($availabilityEndpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ",\n            eventId: 'forum-lab-innovations-2026-10-07'\n        };\n";
$html = str_replace($oldConfig, $newConfig, $html);

$banner = <<<'HTML'
        <section style="padding:14px 0;background:rgba(66,223,245,.08);border-bottom:1px solid rgba(118,235,251,.18);">
            <div class="container" style="font-size:14px;line-height:1.55;color:#d7e7ef;"><strong style="color:#76ebfb;">Персональное приглашение.</strong> Очное место зарезервировано до 23 сентября 2026 года включительно. Ссылка одноразовая и предназначена только для приглашённого участника.</div>
        </section>
HTML;
$html = str_replace('    <main>', $banner . "\n    <main>", $html);

echo $html;
