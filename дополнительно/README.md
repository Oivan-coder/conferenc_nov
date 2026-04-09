# Report Hub

Небольшой статический проект под GitHub Pages для публикации еженедельных HTML-отчетов.

## Что внутри

- `index.html` — главная страница со списком отчетов
- `styles.css` — стили
- `script.js` — загрузка списка отчетов из JSON
- `reports.json` — список отчетов
- `reports/` — папка, куда вы кладете HTML-файлы отчетов

## Как обновлять каждую неделю

1. Положите новый отчет в папку `reports/`
2. Добавьте новый объект в `reports.json`
3. Сделайте commit и push в GitHub
4. GitHub Pages обновит страницу

## Пример записи в reports.json

```json
{
  "title": "Еженедельный отчет по проекту",
  "period": "Период: 06.04.2026 — 10.04.2026",
  "description": "Краткое описание отчета",
  "file": "./reports/weekly_report_2026-04-10.html",
  "publishDate": "10.04.2026",
  "badge": "Свежий",
  "year": 2026,
  "tags": ["ВКС+", "еженедельный"]
}
```

## Как включить GitHub Pages

1. Загрузите проект в репозиторий GitHub
2. Откройте `Settings` → `Pages`
3. В блоке `Build and deployment` выберите:
   - `Source`: `Deploy from a branch`
   - `Branch`: `main` и `/ (root)`
4. Сохраните
5. Через 1–2 минуты страница появится по ссылке вида:
   `https://username.github.io/repository-name/`
