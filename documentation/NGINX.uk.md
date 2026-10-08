# nginx + PHP-FPM — Build 2.0.4

[English](NGINX.md) · [Усі інструкції](README.uk.md) · [Спільнота](https://t.me/+tUZNEgY3aUk4MGIy)

Apache використовує комплектний `.htaccess`; nginx потребує відповідних серверних правил. Конфігурація нижче перевірена з nginx 1.24 та PHP-FPM 8.3 для SEO/мовних префіксів, sitemap rewriting і захисту сховища/шаблонів/SQL. Інші комбінації сервера/PHP потребують окремої перевірки хостингу.

Замініть приклад домену, кореня сайту й PHP-FPM socket власними значеннями. Налаштуйте HTTPS-сертифікати й перевірте конфігурацію сервера перед перезавантаженням. За можливості розмістіть постійне сховище поза коренем сайту. Перед змінами створіть резервну копію сайту/БД та конфігурації сервера. Коментарі в конфігурації залишені англійською, самі правила ідентичні оригіналу.

```nginx
server {
    listen 80;  # add "listen 443 ssl http2;" and certificates for production
    server_name example.com www.example.com;
    root /var/www/example.com;  # directory that contains index.php
    index index.php;
    client_max_body_size 64m;

    # Never serve runtime storage, VCS metadata or sensitive files.
    location ~* ^/(system/storage|storage)(/|$) { deny all; }
    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(tpl|twig|ini|log|sql|bak|old|orig|dist|sh|swp|yml|yaml|lock)$ { deny all; }
    location ~* (?<!robots)\.txt$ { deny all; }

    # Sitemaps (mirrors .htaccess).
    rewrite ^/sitemap\.xml$ /index.php?route=extension/feed/google_sitemap&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-(products|categories|manufacturers|information)-([A-Za-z0-9-]+)-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=$1&lang=$2&page=$3&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-blog-categories-([A-Za-z0-9-]+)-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=blog_categories&lang=$1&page=$2&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-blog-articles-([A-Za-z0-9-]+)-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=blog_articles&lang=$1&page=$2&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-(products|categories|manufacturers|information)-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=$1&page=$2&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-blog-categories-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=blog_categories&page=$1&_codecart_sitemap_clean=1 last;
    rewrite "^/(?:[A-Za-z0-9-]+/)?sitemap-blog-articles-([1-9][0-9]*)\.xml$" /index.php?route=extension/feed/google_sitemap&type=blog_articles&page=$1&_codecart_sitemap_clean=1 last;
    rewrite "^/(google-merchant(?:-[a-zA-Z0-9-]+)?\.xml)$" /index.php?_route_=$1 last;
    rewrite ^/googlebase\.xml$ /index.php?route=extension/feed/google_base last;

    location ~* \.(?:ico|gif|jpe?g|png|webp|avif|svg|js|css|woff2?|ttf|webmanifest)$ {
        try_files $uri =404;
        expires 30d;
        access_log off;
    }

    location / {
        try_files $uri $uri/ @codecart;
    }

    location @codecart {
        rewrite ^/(.+)$ /index.php?_route_=$1 last;
    }

    location ~ \.php$ {
        # Language-prefixed URLs such as /en/index.php?route=... are resolved by
        # the SEO router exactly like Apache's RewriteCond !-f rule.
        try_files $uri @codecart;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;  # or 127.0.0.1:9000
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }
}
```

Cron: [Планувальник](SCHEDULER.uk.md). PHP-FPM дозволяє завершити відповідь відвідувачу перед heartbeat через fastcgi_finish_request.

Підтримка: [support@codecartpro.com](mailto:support@codecartpro.com) · [Спільнота CodeCart PRO](https://t.me/+tUZNEgY3aUk4MGIy)
