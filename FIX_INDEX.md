# Исправление проблемы с главной страницей на GitHub Pages

## Проблема
Главная страница не открывается по корневому URL: `https://oivan-coder.github.io/TEST_REPO_NAME/` (404 ошибка)

## Решение

На GitHub Pages есть два варианта:

### Вариант 1: Использовать Jekyll (рекомендуется)

1. Убедитесь, что файл называется именно `index.html` (с маленькой буквы)
2. В настройках GitHub Pages должен быть выбран источник: `Deploy from a branch` → `main` → `/ (root)`
3. Подождите 1-2 минуты после пуша

### Вариант 2: Отключить Jekyll (если Вариант 1 не работает)

Создайте файл `.nojekyll` в корне репозитория:

```bash
touch .nojekyll
```

Или создайте файл вручную с именем `.nojekyll` (без расширения, начинается с точки).

**ВАЖНО:** Если используете `.nojekyll`, то:
- Jekyll не будет обрабатывать файлы
- Все ссылки должны иметь `.html` (что мы уже сделали)
- `index.html` будет работать по `/`

### Вариант 3: Проверить настройки репозитория

1. Зайдите в Settings → Pages
2. Убедитесь, что:
   - Source: `Deploy from a branch`
   - Branch: `main` (или `master`)
   - Folder: `/ (root)`
3. Сохраните

### Вариант 4: Проверить, что index.html загружен

```bash
cd /Users/ivangolcev/Desktop/conferenc_nov-main

# Проверьте, что index.html есть
ls -la index.html

# Проверьте статус Git
git status

# Если index.html не добавлен, добавьте его
git add index.html
git commit -m "Add index.html"
git push
```

## После исправления

Проверьте:
- `https://oivan-coder.github.io/TEST_REPO_NAME/` - должна открываться главная
- `https://oivan-coder.github.io/TEST_REPO_NAME/index.html` - тоже должна работать
