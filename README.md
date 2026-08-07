# NusaHR — Human Resource Information System

An internal HR management application for small-to-medium companies: employees,
organization structure, leave, attendance, simplified payroll, announcements, reports, and
an audit trail.

## Screenshots

> Not yet captured. To add them: run the app (see below), sign in with the demo accounts,
> and save images to `docs/screenshots/`, then link them here. Useful pages: the role-aware
> dashboard, employee list, leave approvals, payroll period, and a payslip.

## Overview

NusaHR is built as a **portfolio project**. The goal is code a senior reviewer would be
comfortable inheriting: real authorization boundaries, transactional business workflows,
meaningful tests, and honest documentation — rather than a wide surface of half-working
screens.

Payroll is deliberately simplified. It models earnings, deductions, attendance-based
proration, and a publication lifecycle; it does **not** model any country's tax law or
statutory reporting.

## Features

- **Authentication** — login, logout, password reset, email verification, two-factor
  authentication, passkeys, rate limiting, and an active/inactive account gate. Public
  registration is disabled by design; only authorized administrators create accounts.
- **Role-based access control** — 4 roles and 42 granular permissions via
  spatie/laravel-permission.
- **Employees** — HR records separate from login identities, `EMP-YYYY-NNNN` numbering,
  reporting hierarchy with cycle prevention, salary history, confidential-field protection,
  a tabbed detail page (profile, employment, attendance, leave, compensation, documents,
  activity), and profile-photo upload.
- **Organization** — company profile plus per-entity CRUD screens for departments
  (hierarchical), positions with salary bands, office locations, employment types, leave
  types, and public holidays, each with search, sortable columns, and pagination.
- **Leave** — configurable leave types, balances, half-day sessions, overlap and balance
  validation, one-level approval, notifications, and personal/team/company calendars with
  holiday overlay.
- **Attendance** — check in/out with late calculation, work modes, HR corrections with
  mandatory reasons, an employee-initiated correction request workflow, and idempotent
  daily absence processing.
- **Payroll** — periods with a draft → generated → published → closed lifecycle,
  deterministic calculation, snapshotted payslip lines, and a printable payslip.
- **Announcements** — draft/scheduled/published/archived, audience targeting, read
  tracking, and scheduled publishing.
- **Dashboards** — role-aware, built from live aggregate queries, with Recharts charts.
- **Reports** — 15 reports with CSV export, permission-scoped and sanitised against
  spreadsheet formula injection.
- **Audit log** — a single writer that redacts secrets before persisting.

## Role capabilities

| | Super Admin | HR Admin | Manager | Employee |
| --- | :-: | :-: | :-: | :-: |
| All modules | ✅ | — | — | — |
| Manage roles and permissions | ✅ | — | — | — |
| Manage employees and org data | ✅ | ✅ | — | — |
| See compensation and bank details | ✅ | ✅ | — | own only |
| Approve leave | ✅ | ✅ | own team | — |
| View team attendance | ✅ | ✅ | own team | own only |
| Generate and publish payroll | ✅ | ✅ | — | — |
| View payslips | ✅ | ✅ | own only | own only |
| Reports | all 15 | all 15 | team-safe subset | — |
| Audit logs | ✅ | ✅ | — | — |

Full matrix: [`docs/roles-and-permissions.md`](docs/roles-and-permissions.md).

## Technology stack

| Layer | Choice |
| --- | --- |
| Backend | Laravel 13, PHP 8.5 |
| Database | PostgreSQL 17 (SQLite in tests) |
| Auth | Laravel Fortify (React starter kit) |
| Authorization | spatie/laravel-permission 8 |
| Frontend | Inertia.js v3, React 19, TypeScript |
| Styling | Tailwind CSS v4, shadcn/ui, Lucide |
| Charts | Recharts |
| Tests | Pest 5 |
| Static analysis | Larastan (PHPStan level 7) |
| Formatting | Laravel Pint, Prettier, ESLint |
| Build | Vite 8 |

## Architecture overview

Thin controllers, Form Requests for validation and authorization, Action classes for
multi-step workflows, policies for record-level access, and PHP enums instead of magic
strings. React is served through Inertia from the same Laravel app — no separate API, no
standalone SPA. No repository layer wraps Eloquent, and the reasoning is written down.

Details: [`docs/architecture.md`](docs/architecture.md).

## Main business workflows

Leave approval, attendance state transitions, and payroll generation/publication are
documented with Mermaid diagrams in
[`docs/business-rules.md`](docs/business-rules.md).

## Database overview

Entity relationship diagram, constraints, indexing decisions, money handling, snapshots,
and the deletion strategy: [`docs/database-design.md`](docs/database-design.md).

## Security decisions

- Authorization is enforced in the backend on every route. Hidden UI is convenience only.
- Confidential employee fields are `$hidden` on the model and unhidden only after a policy
  check. Bank account and tax numbers are encrypted at rest.
- Managers cannot see compensation. The dashboard omits the payroll figure from the
  payload entirely rather than hiding it client-side.
- Employee documents are never publicly reachable: generated filenames, hidden paths, and
  downloads through an authorized controller action.
- CSV exports sanitise cells beginning `=`, `+`, `-`, or `@` to defuse spreadsheet formula
  injection, and enforce the same permissions as the on-screen views.
- Audit entries redact passwords, tokens, 2FA secrets, bank accounts, and salaries.
- The Super Admin bypass is a deliberate `Gate::before`, so a missing seeder cannot lock
  the owner out.

## Requirements

PHP 8.3+ with `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`,
`pdo_pgsql`, `tokenizer`, `xml`, `zip`; Composer; Node 20+; PostgreSQL 14+ (or Docker).

## Local installation

```bash
git clone <repository-url> && cd HRIS-Mini
cp .env.example .env
# configure the database (see below)
composer install
npm install
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
npm run build
php artisan serve
```

Then open <http://localhost:8000>.

Convenience wrappers exist in the `Makefile` (`make setup`, `make test`, `make lint`,
`make dev`, `make fresh`); each one just calls the underlying Artisan and npm commands.

### Database options

**Docker (no host PostgreSQL password needed):**

```bash
docker run -d --name nusahr-pg \
  -e POSTGRES_DB=nusahr -e POSTGRES_USER=nusahr -e POSTGRES_PASSWORD=nusahr \
  -p 5433:5432 postgres:17-alpine
```

Then in `.env`: `DB_HOST=127.0.0.1`, `DB_PORT=5433`, database/user/password `nusahr`.
Port 5433 avoids clashing with a PostgreSQL service already on 5432.

**Local PostgreSQL:**

```sql
CREATE ROLE nusahr LOGIN PASSWORD 'nusahr' CREATEDB;
CREATE DATABASE nusahr OWNER nusahr;
```

**Managed PostgreSQL:** set `DATABASE_URL` and `DB_SSLMODE=require`. If your provider
offers a pooled and a direct endpoint, use the **direct** one — poolers in transaction mode
break migrations with `SQLSTATE 25P02`.

## Docker Compose

`compose.yaml` provides the app, PostgreSQL 17, and Mailpit:

```bash
docker compose up -d
docker compose exec app php artisan migrate --seed
```

Mailpit's inbox is at <http://localhost:8025>. Docker is not required if you point
`DATABASE_URL` at a hosted database.

## Queue setup

Notifications are queued on the `database` driver:

```bash
php artisan queue:work --tries=3
```

The application works without a worker — critical state changes complete inside their
transactions — but notifications will sit in the queue.

## Scheduler setup

```bash
php artisan schedule:work        # local, foreground
```

Production uses one cron entry:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands, all idempotent: `nusahr:process-absences`,
`nusahr:publish-announcements`, `nusahr:notify-expiring-documents`.

## Mail testing

`MAIL_MAILER=log` writes mail to `storage/logs/laravel.log`. With Docker Compose, point
`MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025` and read it in the Mailpit UI.

## Demo data

`php artisan migrate:fresh --seed` builds a coherent fictional company in about 20 seconds:

| | |
| --- | --- |
| Company / departments / positions | 1 / 8 / 15 |
| Locations / employment types / leave types | 3 / 5 / 6 |
| Users | 49 — 1 Super Admin, 2 HR, 6 managers, 40 employees |
| Attendance | ~2,800 rows over 60 days, covering all seven statuses |
| Leave requests | 30 across pending, approved, rejected, cancelled |
| Payroll periods | 3 — closed, published, draft |
| Announcements | 10 across all statuses, with read tracking |
| Audit log entries | 60 |

Faker is seeded with a fixed value, so the same data is produced every run.

All names, addresses, and identifiers are fictional.

## Demo credentials

**Local and demo environments only. Never use these as production defaults.**

| Email | Password | Role |
| --- | --- | --- |
| `admin@nusahr.test` | `password` | Super Admin |
| `hr@nusahr.test` | `password` | HR Admin |
| `manager@nusahr.test` | `password` | Manager |
| `employee@nusahr.test` | `password` | Employee |

Additional accounts follow the same password: `hr.admin@nusahr.test`,
`manager1`–`manager5@nusahr.test`, `employee1`–`employee39@nusahr.test`.

## Testing

```bash
php artisan test --compact
```

Tests run against in-memory SQLite and never touch your configured database. See
[`docs/testing.md`](docs/testing.md) — including why `phpunit.xml` blanks `DB_URL`.

## Code-quality commands

```bash
vendor/bin/phpstan analyse    # Larastan level 7
vendor/bin/pint               # PHP formatting (--test to check only)
npm run types:check           # tsc --noEmit
npm run lint:check            # ESLint
npm run format:check          # Prettier
```

## Production build

```bash
npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Full sequence: [`docs/deployment.md`](docs/deployment.md).

## Troubleshooting

**`Unable to locate file in Vite manifest`** — assets are not built. Run `npm run build`,
or `npm run dev` while developing.

**PHPStan dies with `reached configured PHP memory limit`** — raise `memory_limit` in
`php.ini` to `1G`, or pass `--memory-limit=1G`.

**Migrations fail with `SQLSTATE 25P02`** — you are connected through a transaction-mode
connection pooler. Use the direct database endpoint.

**Frontend changes do not appear** — run `npm run build`, or `composer run dev` for the
full development stack.

**Seeder appears to hang** — `today()` returns a `CarbonImmutable` in this application, so
`$date->addDay()` inside a loop does not advance the cursor. Reassign: `$date = $date->addDay()`.

## Known limitations

Implemented and verified are the features listed above. Not yet built, stated plainly
rather than implied:

- PDF payslip export is not implemented; the payslip page is print-styled instead.
- Geolocation-based attendance is not implemented, though the location columns exist.

## Future improvements

Multi-level leave approval, employee self-service correction requests, PDF export, richer
announcement editing with sanitised HTML, per-department dashboards for managers,
scheduled report delivery by email, and an org-chart visualisation.

## License

MIT.
