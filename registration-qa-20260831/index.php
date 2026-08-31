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
    echo 'Регистрация временно недоступна';
    exit;
}

$headMarker = '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">';
$html = str_replace($headMarker, $headMarker . "\n    <meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n    <meta name=\"referrer\" content=\"no-referrer\">", $html);
$html = str_replace('<link rel="canonical" href="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<meta property="og:url" content="https://rclsmo.ru/registration/">', '', $html);
$html = str_replace('<title>Регистрация — Форум лабораторных инноваций Московской области 2026</title>', '<title>Регистрация по приглашению — Форум лабораторных инноваций Московской области 2026</title>', $html);
$html = str_replace('<meta name="description" content="Статус регистрации на Форум лабораторных инноваций Московской области 7 октября 2026 года. Очное и онлайн-участие.">', '<meta name="description" content="Регистрация по приглашению на Форум лабораторных инноваций Московской области 7 октября 2026 года.">', $html);
$html = str_replace('<meta property="og:title" content="Регистрация — Форум лабораторных инноваций Московской области 2026">', '<meta property="og:title" content="Регистрация по приглашению — Форум лабораторных инноваций Московской области 2026">', $html);
$html = str_replace('<meta property="og:description" content="7 октября 2026 · очное и онлайн-участие · регистрация готовится к открытию">', '<meta property="og:description" content="7 октября 2026 · Дом Правительства Московской области · очное участие">', $html);

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
$html = str_replace('Форма подготовлена к запуску. После открытия будут доступны очный и онлайн-форматы участия.', 'Заполните форму для подтверждения очного участия в Форуме лабораторных инноваций Московской области.', $html);
$html = str_replace('<strong>Регистрация ещё не открыта</strong>', '<strong>Регистрация по приглашению</strong>', $html);
$html = str_replace('Публичный приём заявок будет включён после завершения внутренней проверки формы и базы данных.', 'После регистрации на указанную электронную почту будет направлено подтверждение участия и QR-билет.', $html);
$html = str_replace('Фамилия, имя, отчество, должность, медицинская организация, формат участия и контакт для подтверждения.', 'Фамилия, имя, отчество, должность, медицинская организация и контакт для подтверждения.', $html);
$html = str_replace('<strong>Форма готова к внутренней проверке</strong>', '<strong>Регистрация доступна по приглашению</strong>', $html);
$html = str_replace('Публичная регистрация пока отключена. На этой странице персональные данные участников не принимаются.', 'Заполните форму ниже. После успешной регистрации подтверждение и QR-билет будут направлены на электронную почту.', $html);

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
                            <small>7 октября 2026 · Дом Правительства Московской области, Красногорск</small>
                        </div>
                        <p class="r26-form__message" data-offline-availability>Количество мест для очного участия ограничено.</p>
                    </div>
HTML;
$html = str_replace($formatBlock, $offlineBlock, $html);

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
        <section style="padding:14px 0;background:rgba(66,223,245,.08);border-bottom:1px solid rgba(118,235,251,.18);">
            <div class="container" style="font-size:14px;line-height:1.55;color:#d7e7ef;"><strong style="color:#76ebfb;">Регистрация по приглашению.</strong> Ссылка предназначена для приглашённых участников Форума лабораторных инноваций Московской области.</div>
        </section>
HTML;
$html = str_replace('    <main>', $banner . "\n    <main>", $html);

echo $html;
