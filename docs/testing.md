# Testing

The suite uses [Pest](https://pestphp.com). Priority is authorization, business rules,
state transitions, and data integrity — not line coverage for its own sake.

## Running

```bash
php artisan test --compact                      # whole suite
php artisan test --compact --filter=ReportAuth  # one file or test
composer test                                   # config clear, lint, types, tests
```

## Test database

`phpunit.xml` pins tests to an in-memory SQLite database:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="DB_URL" value=""/>
```

`DB_URL` is blanked deliberately. Without it, a `DATABASE_URL` in the environment would win
over the SQLite settings and the suite would run `RefreshDatabase` against the shared Neon
database — dropping every table in it. **Never remove that line.**

This means migrations must stay portable. PostgreSQL-only behaviour is avoided in anything
the tests exercise; where a query genuinely needs dialect-specific SQL (the birthday lookup
on the dashboard), the driver is checked explicitly.

## What is covered

| Area | File |
| --- | --- |
| Login, verification, password reset, 2FA, password confirmation | `tests/Feature/Auth/*` |
| Report permissions — all 15 reports × each role | `ReportAuthorizationTest` |
| Manager team scoping and CSV formula-injection sanitising | `ReportAuthorizationTest` |
| Employee numbering, transactional creation, reporting cycles | `EmployeeNumberTest` |
| Employee lifecycle and sensitive-field hiding | `EmployeeLifecycleTest` |
| Leave submit / approve / reject / cancel and balance movement | `LeaveLifecycleTest` |
| Check in / out, corrections, idempotent absence processing | `AttendanceLifecycleTest` |
| Payroll calculation, determinism, payslip visibility | `PayrollLifecycleTest` |
| Announcement visibility and scheduled publishing | `AnnouncementLifecycleTest` |
| Document access control | `SecurityAndDocumentsTest` |
| Error pages (403/404, JSON fallback) | `ErrorPageTest` |
| Every main page renders per role; guests redirect | `HrisRouteSmokeTest` |
| Role-aware dashboard payload | `DashboardTest` |

## Permission-dependent tests

Tests that assert HR-level access must seed the matrix first:

```php
beforeEach(fn () => $this->seed(Database\Seeders\RolePermissionSeeder::class));
```

Super Admin tests do not need this — `Gate::before` grants that role everything, by design.

## Static analysis and formatting

```bash
vendor/bin/phpstan analyse    # Larastan level 7, zero errors expected
vendor/bin/pint               # format; --test to check only
npm run types:check           # tsc --noEmit
npm run lint:check            # ESLint, includes React Compiler rules
npm run format:check          # Prettier
npm run build                 # production Vite build
```

PHPStan needs more than PHP's default memory limit. If it dies with
`reached configured PHP memory limit`, raise `memory_limit` in `php.ini` (1G is ample) or
pass `--memory-limit=1G`.

## Writing tests

Use factories, not hand-built models. Available: `UserFactory`, `EmployeeFactory`,
`DepartmentFactory`, `PositionFactory`, `LeaveTypeFactory`, `LeaveRequestFactory`,
`EmployeeDocumentFactory`.

Assert against enums, not their backing strings — model casts return enum instances:

```php
expect($attendance->status)->toBe(AttendanceStatus::OnLeave);   // yes
expect($attendance->status)->toBe('leave');                     // no
```

`toThrow()` needs a concrete class. `Throwable::class` is an interface and Pest falls back
to matching it as a message string.
