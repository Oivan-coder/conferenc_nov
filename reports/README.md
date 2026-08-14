# Reports

Папка для еженедельных HTML-отчетов.

## Как добавлять новый отчет

1. Положите новый HTML-файл в эту директорию.
2. Используйте понятное имя вида `weekly-report-YYYY-MM-DD.html`.
3. При желании добавьте в `<head>` служебные теги:

```html
<meta name="report-title" content="Еженедельный отчет по проекту">
<meta name="report-period" content="Период: 06.04.2026 — 10.04.2026">
<meta name="report-description" content="Краткое описание отчета">
<meta name="report-publish-date" content="2026-04-10">
<meta name="report-tags" content="ВКС+, еженедельный, отчетность">
<meta name="report-badge" content="Свежий">
```

Если эти теги не заданы, генератор постарается собрать данные автоматически из `<title>`, первого `<h1>`, описания и имени файла.

## Что происходит дальше

- GitHub Action запускает `scripts/generate_reports_index.py`
- обновляется `reports/reports.json`
- автоматически дополняется `sitemap.xml`
- страница [`reports.html`](/Users/ivangolcev/Desktop/LabHome-main/reports.html) показывает новый отчет без ручной правки индекса
