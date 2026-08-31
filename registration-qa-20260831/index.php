<?php
const QA_TOKEN_HASH = '50d629e1bc7e5a348ba3e81d03c4e0cd190493e3753f2c146e78cf77e5d92c9b';

$qaToken = trim((string)($_GET['key'] ?? ''));
if ($qaToken === '' || !hash_equals(QA_TOKEN_HASH, hash('sha256', $qaToken))) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>Страница не найдена</title></head><body style="font-family:Arial,sans-serif;background:#07111f;color:#fff;padding:40px;">Страница не найдена.</body></html>';
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$templatePath = dirname(__DIR__) . '/registration/index.html';
$html = is_readable($templatePath) ? (string)file_get_contents($templatePath) : '';
if ($html === '') {
    http_response_code(503);
    echo 'QA registration unavailable';
    exit;
}

$headMarker = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
$html = str_replace($headMarker, $headMarker . "\n    <meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n    <meta name=\"referrer\" content=\"no-referrer\">", $html);
$html = str_replace('<link rel="canonical" href="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<meta property="og:url" content="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<title>Регистрация — Форум лабораторных инноваций Московской области 2026</title>', '<title>Финальный тест обычной регистрации — Форум 2026</title>', $html);

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
$html = str_replace('Форма подготовлена к запуску. После открытия будут доступны очный и онлайн-форматы участия.', 'Финальный закрытый тест обычной регистрации. Форма и сценарий соответствуют будущему публичному запуску; регистрация реально записывается в рабочую БД.', $html);
$html = str_replace('<strong>Регистрация ещё не открыта</strong>', '<strong>Финальный тестовый режим</strong>', $html);
$html = str_replace('Публичный приём заявок будет включён после завершения внутренней проверки формы и базы данных.', 'Публичная регистрация остаётся закрытой. Здесь проверяем полный рабочий маршрут перед открытием.', $html);
$html = str_replace('<strong>Форма готова к внутренней проверке</strong>', '<strong>Тест обычной регистрации активен</strong>', $html);
$html = str_replace('Публичная регистрация пока отключена. На этой странице персональные данные участников не принимаются.', 'Заполните форму как реальный участник. Будут проверены запись в БД, письмо, QR-билет и последующая регистрация на стойке.', $html);

$endpoint = '/api/register-qa-20260831.php?key=' . rawurlencode($qaToken);
$oldConfig = <<<'HTML'
        window.REGISTRATION_CONFIG = {
            state: 'closed',
            endpoint: '/api/register-safe.php',
            availabilityEndpoint: '/api/registration-availability.php',
            eventId: 'forum-lab-innovations-2026-10-07'
        };
HTML;
$newConfig = "        window.REGISTRATION_CONFIG = {\n            state: 'open',\n            endpoint: " . json_encode($endpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ",\n            availabilityEndpoint: '/api/registration-availability.php',\n            eventId: 'forum-lab-innovations-2026-10-07'\n        };\n";
$html = str_replace($oldConfig, $newConfig, $html);

$banner = <<<'HTML'
        <section style="padding:14px 0;background:rgba(255,178,46,.10);border-bottom:1px solid rgba(255,178,46,.25);">
            <div class="container" style="font-size:14px;line-height:1.55;color:#d7e7ef;"><strong style="color:#ffcb72;">Финальный QA.</strong> Публичная регистрация закрыта. Эта форма пишет в рабочую базу и отправляет настоящее подтверждение — после теста запись удалим.</div>
        </section>
HTML;
$html = str_replace('    <main>', $banner . "\n    <main>", $html);

echo $html;
