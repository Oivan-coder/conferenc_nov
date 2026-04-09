#!/bin/bash
# Скрипт для обновления всех ссылок .html на всех страницах

# Список HTML файлов
FILES=(
    "conf_nov2025.html"
    "conf_sen2025.html"
    "conf_mart2026.html"
    "registration.html"
    "privacy.html"
    "ask.html"
)

# Функция для замены ссылок
replace_links() {
    local file=$1
    echo "Обновляю ссылки в $file..."
    
    # Заменяем ссылки в навигации
    sed -i '' 's/href="index\.html"/href="\/"/g' "$file"
    sed -i '' 's/href="about\.html"/href="\/about"/g' "$file"
    sed -i '' 's/href="conf_nov2025\.html"/href="\/conf_nov2025"/g' "$file"
    sed -i '' 's/href="conf_sen2025\.html"/href="\/conf_sen2025"/g' "$file"
    sed -i '' 's/href="conf_mart2026\.html"/href="\/conf_mart2026"/g' "$file"
    sed -i '' 's/href="registration\.html"/href="\/registration"/g' "$file"
    sed -i '' 's/href="privacy\.html"/href="\/privacy"/g' "$file"
    
    # Добавляем скрипт url-cleaner.js перед другими скриптами
    if ! grep -q "url-cleaner.js" "$file"; then
        sed -i '' '/<script src=".*\.js">/a\
    <script src="js/url-cleaner.js"><\/script>
' "$file"
    fi
}

# Обновляем все файлы
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        replace_links "$file"
    fi
done

echo "Готово! Все ссылки обновлены."
