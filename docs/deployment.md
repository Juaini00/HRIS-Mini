# Deployment

Sediakan PHP 8.3+, ekstensi PDO PostgreSQL, mbstring, OpenSSL, fileinfo, Composer, Node, HTTPS, web server, PostgreSQL, dan persistent storage. Injeksi APP_KEY serta DATABASE_URL melalui secret manager. Gunakan `APP_DEBUG=false`, secure cookies, backup terenkripsi, `php artisan migrate --force`, `storage:link`, queue worker terawasi, cron `* * * * * php artisan schedule:run`, kemudian `npm ci && npm run build` dan cache config/routes/events. Jangan menjalankan `migrate:fresh` pada database shared atau production.
