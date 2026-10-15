# Публікація вихідного коду GitHub
[English](GITHUB.md) · [Усі інструкції](README.uk.md) · [Спільнота](https://t.me/+tUZNEgY3aUk4MGIy)

Основний репозиторій: [CodeCartPro/CodeCartPro-3.0.6.0](https://github.com/CodeCartPro/CodeCartPro-3.0.6.0). Команди публікують наявну локальну `main`, не створюючи нової гілки та не змінюючи `origin`.

Перед commit перевірте змінені/невідстежувані файли. Не включайте реальні `config.php`, `admin/config.php`, паролі, API/ліцензійні ключі, runtime-сховище, логи, сесії або дані клієнтів. `.gitignore` не вилучає вже відстежувані секрети. Додавайте в commit лише явно перевірені файли. До публікації виконайте обов'язкові release-перевірки.

```sh
git switch main
git status --short
git diff --check
git diff
git remote -v
```

Якщо remote `codecartpro` ще не існує:

```sh
git remote add codecartpro https://github.com/CodeCartPro/CodeCartPro-3.0.6.0.git
```

Перед push перевірте історію призначення:

```sh
git fetch codecartpro
git log --oneline --all -10
git push codecartpro main
```

Якщо Git відхиляє non-fast-forward push, зупиніться та перевірте історію репозиторію. Не використовуйте force-push і не перезаписуйте чужу історію. Обліковий запис GitHub повинен мати право запису.

Вихідний код не включає встановлені залежності Composer. Для підготовки використовуйте lock-файл репозиторію:

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php tools/release_check.php
```

Вихідний «Download ZIP» не є production ZIP. Під час production-складання залежності vendor встановлюються та включаються до пакета. Релізи мають посилатися на перевірені commit у `main`; публікація коду сама по собі не створює перевірений production-реліз.

## Оновлення через браузерний GitHub Codespaces

Відкрийте Codespace потрібного репозиторію та **Terminal → New Terminal**. Перевірте гілку, remote й незбережені зміни:

```sh
pwd
git remote -v
git status --short
git switch main
git pull --ff-only origin main
```

Якщо `git status --short` показує зміни, **зупиніться**: спочатку збережіть або відкладіть власну роботу. Не перезаписуйте невідомі файли.

Перетягніть перевірений ZIP **GitHub Source Update** у провідник файлів Codespaces у корінь репозиторію. Усередині мають бути шляхи `upload/`, `README.md`, `documentation/`, а не повний production-інсталятор. Запустіть у терміналі, вказавши точну назву ZIP:

```sh
unzip -o CodeCart_3.0.6_Source_Update_2.0.8_to_2.1.0.zip -d .
git status --short
git diff --check
git diff --stat
```

Перевірте зміни в редакторі, додайте лише файли оновлення та виконайте commit/push:

```sh
git add CHANGELOG.md README.md README.uk.md README_FIRST.txt documentation upload/admin/view/template/catalog/product_list.twig upload/catalog/view/theme/codecart/template/extension/module/category_wall.twig upload/system/config/codecart_integrity.json
git add upload/admin/controller/catalog/product.php upload/admin/controller/catalog/public_document.php upload/admin/view/stylesheet/codecart-product-files.css upload/admin/view/template/catalog/download_list.twig upload/admin/view/template/catalog/product_form.twig upload/admin/view/template/catalog/public_document_form.twig upload/admin/view/template/catalog/public_document_list.twig upload/catalog/controller/common/header.php upload/system/library/seopro.php
git diff --cached --check
git diff --cached --stat
git commit -m "Update CodeCart 3.0.6"
git push origin main
```

Після використання видаліть ZIP із робочого каталогу. Це лише архів вихідних файлів для GitHub; для встановлення на сервері потрібен окремий повний production-пакет. Якщо зміни вже внесені, не створюйте порожній commit.

Підтримка: [support@codecartpro.com](mailto:support@codecartpro.com) · [Спільнота CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy)
