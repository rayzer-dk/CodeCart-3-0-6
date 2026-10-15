# nginx + PHP-FPM
[Українська](NGINX.uk.md) · [All guides](README.md) · [Community](https://t.me/+tUZNEgY3aUk4MGIy)

Apache uses the supplied `.htaccess`; nginx requires equivalent server rules. The configuration below was verified with nginx 1.24 and PHP-FPM 8.3 for SEO/language prefixes, sitemap rewriting and protected storage/template/SQL paths. Other server/PHP combinations require their own hosting checks.

Replace the example hostname, root and PHP-FPM socket with real values. Configure HTTPS certificates and test the server configuration before reloading. Keep persistent storage outside the web root where possible. Back up the site/database and server configuration before deployment.

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

See [Scheduler](SCHEDULER.md) for cron setup. PHP-FPM can finish the visitor response before heartbeat work using fastcgi_finish_request.

Support: [support@codecartpro.com](mailto:support@codecartpro.com) · [CodeCart PRO community](https://t.me/+tUZNEgY3aUk4MGIy)
