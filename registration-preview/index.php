<?php
const PREVIEW_TOKEN_HASH = '6fbe6025563f098ca8756103aa6cb93f4ac2c5bbb5f769e42aff0de2de2c14b9';

$previewToken = trim((string)($_GET['key'] ?? ''));
if ($previewToken === '' || !hash_equals(PREVIEW_TOKEN_HASH, hash('sha256', $previewToken))) {
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
    echo 'Preview unavailable';
    exit;
}

$headMarker = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
$html = str_replace($headMarker, $headMarker . "\n    <meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n    <meta name=\"referrer\" content=\"no-referrer\">", $html);
$html = str_replace('<link rel="canonical" href="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<meta property="og:url" content="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<title>Регистрация — Форум лабораторных инноваций Московской области 2026</title>', '<title>Закрытая проверка регистрации — Форум лабораторных инноваций 2026</title>', $html);

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
$html = str_replace('Форма подготовлена к запуску. После открытия будут доступны очный и онлайн-форматы участия.', 'Закрытый тестовый контур полностью повторяет будущую публичную регистрацию. Заявки в этом режиме реально записываются в рабочую базу данных.', $html);
$html = str_replace('<strong>Регистрация ещё не открыта</strong>', '<strong>Закрытый тестовый режим</strong>', $html);
$html = str_replace('Публичный приём заявок будет включён после завершения внутренней проверки формы и базы данных.', 'Ссылка предназначена только для внутренней проверки. После тестирования публичная регистрация останется закрытой до отдельного решения.', $html);
$html = str_replace('<strong>Форма готова к внутренней проверке</strong>', '<strong>Тестовый режим активен</strong>', $html);
$html = str_replace('Публичная регистрация пока отключена. На этой странице персональные данные участников не принимаются.', 'Используйте тестовые данные. Отправленные заявки записываются в базу и запускают обычный сценарий подтверждения регистрации.', $html);

$endpoint = '/api/register-preview.php?key=' . rawurlencode($previewToken);
$oldConfig = <<<'HTML'
        window.REGISTRATION_CONFIG = {
            state: 'closed',
            endpoint: '/api/register.php',
            availabilityEndpoint: '/api/registration-availability.php',
            eventId: 'forum-lab-innovations-2026-10-07'
        };
HTML;
$newConfig = "        window.REGISTRATION_CONFIG = {\n            state: 'open',\n            endpoint: " . json_encode($endpoint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ",\n            availabilityEndpoint: '/api/registration-availability.php',\n            eventId: 'forum-lab-innovations-2026-10-07'\n        };\n";
$html = str_replace($oldConfig, $newConfig, $html);

$banner = <<<'HTML'
        <section style="padding:14px 0;background:rgba(255,178,46,.10);border-bottom:1px solid rgba(255,178,46,.25);">
            <div class="container" style="font-size:14px;line-height:1.55;color:#d7e7ef;"><strong style="color:#ffcb72;">Внутренняя проверка.</strong> Эта страница не индексируется и не связана с публичной навигацией. Заявки здесь создаются в рабочей БД — для тестов используйте очевидные тестовые ФИО.</div>
        </section>
HTML;
$html = str_replace('    <main>', $banner . "\n    <main>", $html);

echo $html;
