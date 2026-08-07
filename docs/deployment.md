# Deployment

Generic guidance. No production target is configured in this repository, and nothing here
should be run against production without credentials supplied separately.

## Requirements

- PHP 8.3+ (developed against 8.5) with: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`,
  `filter`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_pgsql`, `session`, `tokenizer`,
  `xml`, `zip`. `intl` and `gd` are recommended.
- PostgreSQL 14+ (developed against 17).
- Node 20+ to build assets. Node is a build-time dependency only.
- A web server pointing its document root at `public/`.

## Environment

Set at minimum:

```
APP_NAME=NusaHR
APP_ENV=production
APP_KEY=            # php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain
APP_TIMEZONE=Asia/Makassar

DATABASE_URL=       # or the discrete DB_* variables below
DB_CONNECTION=pgsql
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_FROM_ADDRESS=no-reply@your-domain
```

`APP_DEBUG=false` is not optional — debug mode exposes stack traces, environment values,
and query contents. `DB::prohibitDestructiveCommands()` is already active in production, so
`migrate:fresh` and `db:wipe` refuse to run there.

### Managed PostgreSQL

`DATABASE_URL` is supported and takes precedence over the discrete variables. Set
`DB_SSLMODE=require` for any hosted provider.

If your provider offers both a pooled and a direct endpoint, **run migrations against the
direct endpoint**. Poolers running PgBouncer in transaction mode break multi-statement DDL
transactions and migrations fail with `SQLSTATE 25P02`.

Never commit real credentials. `.env` is git-ignored; `.env.example` carries placeholders
only.

## Deploy sequence

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Clear the caches (`config:clear`, `route:clear`, `view:clear`) before re-running with
changed configuration.

Do not seed production. The seeders create fictional demo people and well-known
credentials.

## Queue worker

Notifications are queued. Run a supervised worker:

```bash
php artisan queue:work --tries=3 --max-time=3600
```

Use systemd, Supervisor, or your platform's process manager to restart it, and run
`php artisan queue:restart` after each deploy so workers pick up new code.

Without a worker the application still functions — critical state changes complete inside
their transactions — but notifications stay queued.

## Scheduler

One cron entry drives everything:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Locally, `php artisan schedule:work` runs it in the foreground.

Scheduled commands are idempotent, so a double run is harmless:

- `nusahr:process-absences` — daily absence and incomplete-checkout processing
- `nusahr:publish-announcements` — promotes scheduled announcements
- `nusahr:notify-expiring-documents` — document expiry reminders

## HTTPS and cookies

Terminate TLS at the load balancer or web server and redirect HTTP to HTTPS. Set
`SESSION_SECURE_COOKIE=true` and `SESSION_ENCRYPT=true`. If you run behind a proxy,
configure `TrustProxies` so Laravel sees the real scheme and client IP — audit logs record
that IP.

## Storage and backups

Employee documents live on the `local` disk under `storage/app`, served only through an
authorized controller. `storage/` and `bootstrap/cache/` must be writable by the web user.

Back up both the database and `storage/app`. A database backup alone loses every uploaded
document. Test restores; an untested backup is a hypothesis.

## Health check

`/up` is registered as a health endpoint for load balancers and uptime monitoring.
