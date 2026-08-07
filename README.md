# NusaHR — Human Resource Information System

NusaHR is a production-oriented Laravel portfolio application for managing employees, organization structure, leave, attendance, simplified payroll, announcements, private documents, notifications, reports, settings, and audit history. The UI uses React + TypeScript through Inertia rather than a separate API application.

## Screenshots

Screenshot assets are intentionally not committed until they can be captured from a fully seeded build. After running the setup, capture desktop and mobile views of the dashboard, employee directory, leave approval, attendance, payroll review, and payslip into `docs/screenshots/`, then replace this clearly marked placeholder with the real images.

## Features

- Fortify login, password reset, verified email, 2FA, passkeys, throttling, disabled public registration, and inactive-account blocking.
- Four role experiences: Super Admin, HR Admin, Manager, and Employee, with backend policies and role-aware navigation.
- Employee lifecycle, generated employee numbers, manager hierarchy validation, encrypted bank data, salary history, deactivation, and private documents.
- Working-day-aware paid/unpaid and half-day leave, overlap prevention, attachments, reserved balances, review, cancellation, notifications, and audit trail.
- Check-in/out, configurable schedule tolerance, late/worked minutes, HR correction, direct-team visibility, idempotent absence processing, leave/holiday/weekend handling, and incomplete checkout detection.
- Effective-dated salary components, fixed/percentage calculations, absence/unpaid-leave deductions, manual adjustments, deterministic draft regeneration, transactional publication, CSV export, and printable private payslips.
- Draft/scheduled announcements, role/department/location audiences, read tracking, scheduled notifications, and a notification center.
- Real dashboard metrics, employee/attendance/leave/payroll CSV reports, Super Admin settings, masked audit viewer, and coherent demo data.
- Neon-compatible `DATABASE_URL`, local Docker application/PostgreSQL/Mailpit/queue services, scheduler commands, CI PostgreSQL service, Pest coverage, Larastan, Pint, ESLint, Prettier, TypeScript, and Vite scripts.

## Stack

Laravel 13, PHP 8.3+, Fortify, Inertia 3, React 19, TypeScript, Tailwind CSS 4, shadcn-style Radix components, Wayfinder, Pest 5, PostgreSQL/Neon, database queues/cache/sessions, Vite, and Docker Compose. Exact selected versions are locked in `composer.lock` and `package-lock.json`.

## Architecture

Controllers handle HTTP coordination; Form Requests validate and authorize input; policies protect records; focused actions own transactional state transitions; Eloquent owns persistence; queued database notifications and scheduled commands handle asynchronous work. No generic repository layer is used. See [architecture](docs/architecture.md), [database design](docs/database-design.md), [business rules](docs/business-rules.md), and the [role matrix](docs/roles-and-permissions.md).

## Requirements

- PHP 8.3+ with `pdo_pgsql`, mbstring, OpenSSL, fileinfo, intl, and standard Laravel extensions.
- Composer 2, Node 22+, npm, and PostgreSQL 15+ or a Neon database.

## Installation from a clean clone

```bash
cp .env.example .env
composer install
npm ci
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

In separate processes:

```bash
php artisan queue:work --tries=3
php artisan schedule:work
```

`make setup`, `make test`, `make lint`, `make build`, and `make dev` expose the same commands as optional shortcuts.

## Neon PostgreSQL

Place the complete pooled Neon URL only in the untracked `.env` or deployment secret manager:

```dotenv
DATABASE_URL=postgresql://USER:PASSWORD@HOST/DATABASE?sslmode=require
DB_CONNECTION=pgsql
```

The URL takes precedence while conventional `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` remain local PostgreSQL fallbacks. Never commit, print, log, or screenshot a real URL. Verify the target database before migrations and never use `migrate:fresh` against shared or production Neon.

## Docker path

Generate an application key in `.env`, then run:

```bash
docker compose up --build -d
docker compose exec app php artisan migrate --seed
```

The application is exposed at `http://localhost:8000`, PostgreSQL at port `5432`, and Mailpit at `http://localhost:8025`. The queue service starts with the stack. Docker is optional when using Neon.

## Demo accounts

All local demo users initially use `NusaHR123!`:

| Role | Email |
|---|---|
| Super Admin | `admin@nusahr.test` |
| HR Admin | `hr@nusahr.test` |
| Manager | `manager@nusahr.test` |
| Employee | `employee@nusahr.test` |

Change or remove demo credentials outside a local portfolio environment.

## Scheduler

- `nusahr:process-absences` — weekdays at 23:30.
- `nusahr:publish-announcements` — every five minutes.
- `nusahr:notify-expiring-documents` — daily at 08:00.

Production cron must invoke `php artisan schedule:run` every minute.

## Testing and code quality

```bash
php artisan test --compact
vendor/bin/pint --format agent
composer types:check
npm run types:check
npm run lint:check
npm run format:check
npm run build
composer audit
npm audit --omit=dev
```

Tests use isolated SQLite by default; CI additionally boots PostgreSQL for clean migration and seed validation. They must never point at shared Neon credentials. See [testing](docs/testing.md).

## Security decisions

Private HR files are served only through authorized controllers from a non-public disk. Salary and bank fields are hidden from default serialization; bank data is encrypted. Leave/payroll transitions use transactions and row locks. Published payroll cannot be regenerated or adjusted. Audit metadata recursively redacts secrets and sensitive employee values. Backend policy enforcement never relies on hidden frontend links.

## Troubleshooting

- Missing Vite manifest: run `npm ci && npm run build`, or `npm run dev` locally.
- PostgreSQL driver errors: enable PHP `pdo_pgsql`.
- Neon connection failures: retain `sslmode=require`, confirm the pooled hostname, and ensure outbound TLS access.
- Queued notifications not appearing: run `php artisan queue:work` and inspect `php artisan queue:failed`.
- Uploaded public assets unavailable: run `php artisan storage:link`; private employee documents intentionally have no public URL.

## Known product boundaries

Payroll intentionally models portfolio-grade simplified gross calculations; statutory Indonesian PPh 21/BPJS compliance and bank disbursement integrations are future extensions. PDF generation is optional; the implemented payslip uses an authorized print view. Advanced geofencing, biometric devices, SSO, and user impersonation are outside the required core.

## Future improvements

Statutory payroll plugins, SSO/SCIM, configurable multi-step approval chains, object-storage antivirus scanning, payroll bank integrations, advanced workforce forecasting, and browser visual-regression coverage.

See [deployment](docs/deployment.md) for production operations. Licensed under MIT.
