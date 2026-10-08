# CodeCart PRO 3.0.6.0 — Build 2.0.4

CodeCart PRO — платформа електронної комерції на основі OpenCart/ocStore 3.x із темою CodeCart Theme, адаптерами сумісності та необов'язковим журналом втрачених URL.

[English](README.md) · [Документація](documentation/README.uk.md) · [Спільнота CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy)

**Перед встановленням або оновленням створіть і перевірте повну резервну копію файлів сайту та бази даних.** Використовуйте production ZIP із залежностями Composer. Вихідний «Download ZIP» із GitHub не є готовим пакетом встановлення: спочатку потрібно встановити залежності через Composer.

У корінь сайту завантажуйте лише вміст `upload/`. Документацію, інструменти та службові файли репозиторію зберігайте поза публічним сайтом.

- Новий магазин: [Встановлення](documentation/INSTALL.uk.md).
- Наявний магазин: [Оновлення та відновлення](documentation/UPGRADE.uk.md); збережіть конфігурацію, власні зображення та постійне сховище.
- Хостинг: PHP 8.1–8.3 — рекомендована ціль сумісності для розширень OpenCart/ocStore. Ядро також підтримує PHP 8.4/8.5 і має CI-перевірки на цих версіях; сторонні модулі та ionCube-теми мають власні вимоги. Дивіться [nginx](documentation/NGINX.uk.md) і [UniShop2](documentation/UNISHOP2_COMPATIBILITY.uk.md).

Чисте встановлення реєструє й активує лише CodeCart Theme; файли `default` є резервом сумісності. Оновлення зберігає активну тему кожного наявного магазину та додає CodeCart Theme як доступний варіант.

Основний репозиторій: [CodeCartPro/CodeCartPro-3.0.6.0](https://github.com/CodeCartPro/CodeCartPro-3.0.6.0). Робота ведеться в `main`; [інструкція публікації GitHub](documentation/GITHUB.uk.md). Залежності зафіксовані в `composer.lock` і встановлюються в `upload/system/storage/vendor` під час складання production ZIP. Для встановлення Node.js/Vite не потрібні.

Не публікуйте реальні конфігурації, паролі, API/ліцензійні ключі, runtime-сховище, сесії та дані клієнтів.

Підтримка: [support@codecartpro.com](mailto:support@codecartpro.com) · [codecartpro.com](https://codecartpro.com) · [Приєднатися до спільноти](https://t.me/+tUZNEgY3aUk4MGIy)

[Демонстрація вітрини та адмінпанелі](documentation/DEMO.uk.md)
