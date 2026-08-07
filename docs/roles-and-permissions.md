# Roles and permissions

Authorization uses [spatie/laravel-permission](https://spatie.be/docs/laravel-permission).
The `users.role` enum stays the single source of truth for "what kind of user is this";
Spatie owns the permission fan-out. `App\Models\User` syncs the Spatie role whenever the
enum changes, so the two cannot drift.

The whole catalogue and matrix live in one place: `app/Support/Permissions.php`. The
seeder, the tests, and this document all read from it.

## Super Admin

`Gate::before` grants the Super Admin every ability without an explicit row. This is
deliberate: tying the owner's access to seeded data means an un-run seeder would lock them
out of their own installation.

## Matrix

| Permission | Super Admin | HR Admin | Manager | Employee |
| --- | :-: | :-: | :-: | :-: |
| `users.view` | ✅ | ✅ | — | — |
| `users.create` | ✅ | ✅ | — | — |
| `users.update` | ✅ | ✅ | — | — |
| `users.activate` | ✅ | ✅ | — | — |
| `roles.manage` | ✅ | — | — | — |
| `employees.view` | ✅ | ✅ | ✅ | — |
| `employees.create` | ✅ | ✅ | — | — |
| `employees.update` | ✅ | ✅ | — | — |
| `employees.view-sensitive` | ✅ | ✅ | — | — |
| `employees.manage-compensation` | ✅ | ✅ | — | — |
| `employees.manage-documents` | ✅ | ✅ | — | — |
| `departments.manage` | ✅ | ✅ | — | — |
| `positions.manage` | ✅ | ✅ | — | — |
| `locations.manage` | ✅ | ✅ | — | — |
| `employment-types.manage` | ✅ | ✅ | — | — |
| `holidays.manage` | ✅ | ✅ | — | — |
| `attendance.view-own` | ✅ | ✅ | ✅ | ✅ |
| `attendance.view-team` | ✅ | ✅ | ✅ | — |
| `attendance.view-all` | ✅ | ✅ | — | — |
| `attendance.record-own` | ✅ | ✅ | ✅ | ✅ |
| `attendance.create` | ✅ | ✅ | — | — |
| `attendance.correct` | ✅ | ✅ | — | — |
| `attendance.export` | ✅ | ✅ | — | — |
| `leave.view-own` | ✅ | ✅ | ✅ | ✅ |
| `leave.view-team` | ✅ | ✅ | ✅ | — |
| `leave.view-all` | ✅ | ✅ | — | — |
| `leave.submit` | ✅ | ✅ | ✅ | ✅ |
| `leave.approve` | ✅ | ✅ | ✅ | — |
| `leave.override` | ✅ | ✅ | — | — |
| `leave-types.manage` | ✅ | ✅ | — | — |
| `leave-balances.manage` | ✅ | ✅ | — | — |
| `payroll.view-own` | ✅ | ✅ | ✅ | ✅ |
| `payroll.view-all` | ✅ | ✅ | — | — |
| `payroll.manage` | ✅ | ✅ | — | — |
| `payroll.publish` | ✅ | ✅ | — | — |
| `payroll.export` | ✅ | ✅ | — | — |
| `announcements.view` | ✅ | ✅ | ✅ | ✅ |
| `announcements.manage` | ✅ | ✅ | — | — |
| `reports.view-team` | ✅ | ✅ | ✅ | — |
| `reports.view-hr` | ✅ | ✅ | — | — |
| `audit-logs.view` | ✅ | ✅ | — | — |
| `settings.manage` | ✅ | ✅ | — | — |

**Totals:** Super Admin 42, HR Admin 41, Manager 11, Employee 6.

HR Admin holds everything except `roles.manage` — HR runs people operations but does not
administer the permission system itself.

## Notable boundaries

**Managers never see compensation.** There is no manager entry for
`employees.manage-compensation`, `payroll.view-all`, or `payroll.export`, and the dashboard
sends `canSeePayrollValue: false` so the figure is never in the payload at all — not merely
hidden in the UI.

**Team scoping is computed, not trusted.** A manager's reach is
`Employee::descendantIds()`, derived server-side from the reporting tree on every request.

**Reports carry two gates.** `reports.view-hr` unlocks the full catalogue;
`reports.view-team` unlocks only the reports flagged team-safe, narrowed to the manager's
own subtree. Payroll reports additionally require `payroll.export`.

## Where authorization is enforced

Backend, always. Policies (`EmployeePolicy`, `LeaveRequestPolicy`, `PayrollPeriodPolicy`,
`PayrollRecordPolicy`) plus `Gate::authorize()` and `abort_unless()` in controllers.

The frontend hides links the user cannot follow, but that is a usability affordance only —
every route re-checks. `tests/Feature/ReportAuthorizationTest.php` asserts all 15 reports
against each role for exactly this reason.
