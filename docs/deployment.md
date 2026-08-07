# Deployment

## Runtime requirements

- PHP 8.3+ with PDO PostgreSQL, mbstring, OpenSSL, fileinfo, intl, tokenizer, XML, ctype, and JSON support.
- PostgreSQL 15+ or Neon PostgreSQL.
- Composer 2, Node 22+, and npm for the production asset build.
- HTTPS-capable web server, persistent application storage, a queue process supervisor, cron, and encrypted backups.

## Required process

1. Install locked dependencies with `composer install --no-dev --classmap-authoritative` and `npm ci`.
2. Build assets with `npm run build`.
3. Inject `APP_KEY`, `DATABASE_URL`, mail settings, and other secrets through the hosting secret manager.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and the canonical HTTPS `APP_URL`.
5. Run `php artisan migrate --force`; never use `migrate:fresh` against production or an uncertain/shared Neon database.
6. Run `php artisan storage:link` for intentionally public assets. Private HR documents remain outside that public link.
7. Run `php artisan optimize` after environment injection.
8. Supervise `php artisan queue:work --sleep=3 --tries=3 --max-time=3600` and restart workers after releases.
9. Execute `php artisan schedule:run` every minute from cron.
10. Verify `/up`, login, queues, storage, email, and database connectivity after deployment.

## Neon

Set the complete pooled Neon URL only in `DATABASE_URL`; retain `sslmode=require`. Do not commit, log, screenshot, or place it in a build argument. Use a dedicated branch/database for previews and tests. Apply forward migrations normally and confirm the target database name before any destructive operation.

## Operations

Use encrypted daily PostgreSQL backups with restore drills. Monitor failed jobs, scheduler execution, authentication throttling, application errors, disk use, and expiring documents. Rotate application, database, and mail credentials after exposure. Clear cached configuration after environment changes. Restrict log access because logs may contain employee identifiers even though audit metadata is redacted.
