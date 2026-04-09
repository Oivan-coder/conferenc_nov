# Инструкция по разделу "Отчеты"

Этот файл описывает, как работает раздел отчетов на сайте и что нужно делать, чтобы новые weekly HTML появлялись автоматически.

## Что уже сделано

- Публичная страница архива: [`reports.html`](/Users/ivangolcev/Desktop/LabHome-main/reports.html)
- Папка с отчетами: [`reports/`](/Users/ivangolcev/Desktop/LabHome-main/reports)
- Индекс отчетов: [`reports/reports.json`](/Users/ivangolcev/Desktop/LabHome-main/reports/reports.json)
- Генератор индекса: [`scripts/generate_reports_index.py`](/Users/ivangolcev/Desktop/LabHome-main/scripts/generate_reports_index.py)
- Автоматическое обновление на GitHub: [`.github/workflows/rebuild-reports-index.yml`](/Users/ivangolcev/Desktop/LabHome-main/.github/workflows/rebuild-reports-index.yml)

## Как теперь добавлять новый отчет

1. Создайте новый HTML-файл отчета.
2. Положите его в папку [`reports/`](/Users/ivangolcev/Desktop/LabHome-main/reports).
3. Назовите файл по шаблону:
   `weekly-report-YYYY-MM-DD.html`

Пример:
`weekly-report-2026-04-10.html`

4. По желанию добавьте в `<head>` служебные meta-теги. Это лучший вариант, потому что тогда карточка на сайте будет собрана красиво и без догадок:

```html
<meta name="report-title" content="Еженедельный отчет по проекту централизации лабораторной службы">
<meta name="report-period" content="Период: 06.04.2026 — 10.04.2026">
<meta name="report-description" content="Ключевые результаты недели, риски, доработки и план следующего периода.">
<meta name="report-publish-date" content="2026-04-10">
<meta name="report-tags" content="ВКС+, еженедельный, отчетность">
<meta name="report-badge" content="Свежий">
```

5. Сделайте `git add`, `commit`, `push`.

После этого GitHub Action автоматически:
- пересоберет [`reports/reports.json`](/Users/ivangolcev/Desktop/LabHome-main/reports/reports.json),
- обновит встроенные данные в [`reports.html`](/Users/ivangolcev/Desktop/LabHome-main/reports.html),
- обновит [`sitemap.xml`](/Users/ivangolcev/Desktop/LabHome-main/sitemap.xml).

## Почему раньше могло показывать 0 отчетов

Причина была в том, что страница архива опиралась только на `fetch('reports/reports.json')`.

Если:
- JSON еще не был задеплоен на GitHub Pages,
- страница открывалась локально как `file://`,
- или был временный сбой загрузки,

тогда карточки не рендерились и метрики оставались по умолчанию.

Теперь это исправлено:
- в [`reports.html`](/Users/ivangolcev/Desktop/LabHome-main/reports.html) вшит запасной JSON,
- страница сначала берет встроенные данные,
- потом пытается подтянуть свежий `reports/reports.json`,
- поэтому даже при проблемах загрузки архив не будет пустым.

## Что лучше не делать

- Не класть отчеты в папку [`дополнительно/`](/Users/ivangolcev/Desktop/LabHome-main/дополнительно), если они должны появляться на сайте.
- Не использовать хаотичные имена файлов вроде `отчет еженедельный 27 марта (1) (2).html`.
- Не оставлять weekly HTML без нормального `<title>` и без даты публикации.

## Где лежит рабочая версия

Рабочая система отчетов теперь живет здесь:

- страница архива: [`reports.html`](/Users/ivangolcev/Desktop/LabHome-main/reports.html)
- отчеты: [`reports/`](/Users/ivangolcev/Desktop/LabHome-main/reports)

Папка [`дополнительно/`](/Users/ivangolcev/Desktop/LabHome-main/дополнительно) осталась как исходный прототип и больше не является основной точкой публикации.

## Если нужно обновить индекс локально вручную

Можно запустить:

```bash
python3 scripts/generate_reports_index.py
```

Это:
- пересоберет `reports/reports.json`,
- обновит встроенные данные в `reports.html`,
- обновит `sitemap.xml`.
