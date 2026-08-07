# NusaHR architecture

## Request lifecycle

NusaHR is a single Laravel application. The browser loads React and TypeScript through Inertia; it is not a separate API application. Named Laravel routes resolve controllers, Form Requests validate and authorize input, policies protect records, actions execute multi-step business workflows, and Eloquent persists PostgreSQL-compatible data. The root Blade template only bootstraps Inertia.

## Authentication and authorization

Fortify owns login, password reset, email verification, two-factor authentication, passkeys, and rate limiting. Public registration is disabled. Inactive users are rejected during authentication. Four fixed roles drive navigation, while backend policies and request authorization remain the security boundary; hiding a menu is never treated as authorization.

Sensitive employee salary, bank, document, payroll, settings, and audit endpoints are checked server-side. Managers can see only themselves and direct reports. Employees can see only their own published payslips and private documents. Super Admin-only boundaries protect settings and audit history.

## Actions and transactions

Controllers coordinate HTTP concerns and delegate state transitions to focused actions:

- leave submission, review, cancellation, and balance locking;
- attendance check-in, check-out, and correction;
- deterministic employee payroll calculation and period generation;
- private document storage;
- redacted audit logging.

Transactions and `lockForUpdate()` protect balances, attendance uniqueness, payroll publication, and other workflows vulnerable to duplicate requests. There is deliberately no generic repository layer: Eloquent already provides the required persistence abstraction, and an additional layer would hide useful query semantics.

## Events, notifications, queues, and scheduler

Database notifications are queued for leave review, payroll publication, scheduled announcements, and expiring documents. Login/logout events feed the audit trail. The scheduler runs absence processing, announcement publication, and expiration checks. Queue workers must remain active outside local synchronous testing.

## File security

Employee documents and leave attachments use the local private disk, random framework-generated paths, MIME plus extension validation, size limits, and authorized controller downloads. Stored paths are hidden during model serialization. Public URLs are never generated for private HR records.

## Audit logging

Important employee, leave, attendance, payroll, announcement, document, settings, and authentication operations create audit records. Nested sensitive keys such as passwords, bank accounts, salary, tokens, and two-factor secrets are redacted before persistence.

## Frontend boundaries

Pages live under `resources/js/pages`, shared shadcn-style primitives under `resources/js/components/ui`, and the persistent layout owns role-aware navigation. Pages provide responsive tables/cards, light and dark theme compatibility, backend validation feedback, empty states, and print-specific payslip output.
