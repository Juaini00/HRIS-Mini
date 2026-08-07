# Testing strategy

Feature tests use the isolated SQLite configuration in `phpunit.xml`; they never consume the developer's Neon `DATABASE_URL`. A dedicated PostgreSQL CI service validates migrations and seeding before the Pest suite. Shared or production databases must never be used for `RefreshDatabase` tests.

Coverage is organized around authentication, role authorization, employee lifecycle and hierarchy, private documents, leave balance transitions, attendance processing and corrections, payroll calculation/publication/payslip access, announcement audiences/read tracking, reports, settings, notifications, and audit masking. Factories create coherent relationship graphs and demo seeding creates all four roles.

Run the complete gate before merging:

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

During development, run the narrowest feature file first. Browser smoke testing should cover all main routes under each role, mobile and desktop navigation, light/dark themes, form error states, check-in/out, leave approval, payroll publication, secure document access, and payslip printing. Never weaken an assertion or switch a destructive test to Neon merely to make CI pass.
