# Публікація вихідного коду GitHub — Build 2.0.4

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

Підтримка: [support@codecartpro.com](mailto:support@codecartpro.com) · [Спільнота CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy)
