You are the sole senior engineer responsible for building a complete, production-quality portfolio application from an empty or minimally initialized Git repository.

Build the application autonomously from start to finish. Do not stop after scaffolding, planning, creating partial CRUD pages, or writing placeholders. Continue implementing, running, testing, reviewing, and fixing the application until it is complete and demonstrably functional.

# PROJECT IDENTITY

Application name:

**NusaHR — Human Resource Information System**

NusaHR is a professional internal HR management application for small-to-medium companies. It must look and behave like a real company application rather than a tutorial project.

The final repository will be used as a Laravel portfolio project. Code quality, architecture, authorization, data integrity, automated tests, UI consistency, documentation, and installation reliability are all important.

# PRIMARY OBJECTIVE

Deliver a complete HRIS application containing:

1. Authentication
2. Role-based access control
3. Employee management
4. Organization structure
5. Leave management and approval
6. Attendance management
7. Simplified payroll management
8. Announcements
9. Employee document management
10. Dashboard and analytics
11. Notifications
12. Audit logs
13. Reports and CSV exports
14. Application settings
15. Demo data
16. Automated testing
17. Complete documentation

The application must be runnable, seeded, tested, and ready to demonstrate.

# AUTONOMOUS EXECUTION REQUIREMENT

Work without expecting additional user interaction.

Make reasonable professional decisions independently.

Do not ask questions for choices that can be resolved using:

- Laravel conventions
- Sensible HRIS conventions
- Security best practices
- The requirements in this specification
- The simplest maintainable implementation

If a non-critical detail is ambiguous, choose the simplest professional solution and document the decision.

Do not stop merely because one optional dependency, external service, or enhancement is unavailable. Use a stable fallback and continue.

Do not provide a final response until you have:

1. Implemented the required modules
2. Installed dependencies
3. Configured the application
4. Created migrations
5. Migrated the database
6. Seeded demo data
7. Built frontend assets
8. Run automated tests
9. Run static checks and formatting
10. Fixed all failures reasonably discoverable in the environment
11. Reviewed permissions and security
12. Completed the README and supporting documentation

# REQUIRED TECHNOLOGY STACK

Use the latest stable, mutually compatible versions available in the environment.

Backend:

- Laravel
- PHP supported by the selected Laravel version
- PostgreSQL
- Eloquent ORM
- Laravel Fortify through the official Laravel starter kit
- Laravel queues
- Laravel scheduler
- Laravel notifications
- Laravel events and listeners
- Laravel policies
- Laravel form requests
- Laravel API Resources only where useful
- Pest for automated testing
- Laravel Pint for formatting

Frontend:

- Official Laravel React Starter Kit
- React
- TypeScript
- Inertia.js
- Tailwind CSS
- shadcn/ui
- Lucide icons
- Recharts for dashboard charts when compatible
- React Hook Form only if it integrates cleanly with Inertia; otherwise use Inertia forms
- Zod only where frontend schema validation materially improves the UX

Authorization:

- Spatie Laravel Permission

File handling:

- Laravel filesystem
- Local public disk by default
- Storage-link based access
- Secure authorization before viewing employee documents

Development:

- Composer
- npm
- Vite
- Docker Compose
- PostgreSQL-compatible configuration
- Mailpit for local email testing if practical

Do not use Blade as the main application UI.

Blade may be used only where Laravel technically requires it, such as:

- Root Inertia bootstrap template
- Email templates
- PDF rendering if implemented
- Minimal framework-level integration

Do not create a separate standalone React application or separate API-only backend. React must be integrated into Laravel through Inertia.

# DATABASE CONFIGURATION

A Neon PostgreSQL database connection will be provided securely through the environment variable:

DATABASE_URL

Never hard-code database credentials.

Never print or expose the full connection string.

Never place real credentials in:

- Source code
- `.env.example`
- Tests
- Logs
- Documentation
- Git commits
- Pull request descriptions
- Screenshots

Configure Laravel to support Neon PostgreSQL using `DATABASE_URL`.

Requirements:

- Parse and use `DATABASE_URL` safely through Laravel configuration.
- Ensure SSL is supported.
- Preserve compatibility with normal PostgreSQL environment variables as a fallback.
- Use PostgreSQL-compatible column types and SQL.
- Do not depend on MySQL-specific behavior.
- Do not reset or delete unrelated databases.
- Verify the target database before running destructive operations.
- Only run `migrate:fresh --seed` when the database is clearly dedicated to this application and the environment is non-production.
- Prefer normal migrations when database ownership is uncertain.
- Never run destructive commands against production.

The committed `.env.example` must use placeholders such as:

DATABASE_URL=
DB_CONNECTION=pgsql
APP_URL=http://localhost:8000

Do not include the real Neon URL.

# ARCHITECTURAL PRINCIPLES

Use a pragmatic Laravel architecture.

Use:

- Thin controllers
- Form Request classes
- Policies
- Service or Action classes for multi-step business workflows
- Eloquent models and relationships
- PHP enums for statuses and fixed domain values
- Database transactions for state-changing workflows
- Events and listeners where decoupling adds value
- Jobs for asynchronous work
- Notifications for user-facing alerts
- Query scopes for reusable filtering
- Dedicated report/export classes where useful
- Inertia page components
- Shared TypeScript types where practical
- Reusable shadcn/ui components
- Route model binding

Do not create unnecessary abstraction.

Specifically:

- Do not add a generic repository layer around Eloquent.
- Do not place business logic in React components.
- Do not place substantial business logic in controllers.
- Do not place authorization only in the UI.
- Do not rely on client-side validation alone.
- Do not use route closures for business features.
- Do not create god services.
- Do not introduce microservices.
- Do not create a separate authentication system.
- Do not overengineer with CQRS, event sourcing, or domain-driven layers.

Create focused Action or Service classes for workflows such as:

- ApproveLeaveRequest
- RejectLeaveRequest
- CancelLeaveRequest
- RecordAttendance
- CorrectAttendance
- GeneratePayrollPeriod
- CalculateEmployeePayroll
- PublishPayrollPeriod
- PublishAnnouncement
- StoreEmployeeDocument

Use strict typing where practical.

# SOURCE-CODE ORGANIZATION

Use clear folders, for example:

- `app/Actions`
- `app/Enums`
- `app/Events`
- `app/Http/Controllers`
- `app/Http/Requests`
- `app/Http/Resources`
- `app/Jobs`
- `app/Listeners`
- `app/Models`
- `app/Notifications`
- `app/Policies`
- `app/Services`
- `app/Support`
- `app/Console/Commands`
- `database/factories`
- `database/seeders`
- `resources/js/components`
- `resources/js/components/ui`
- `resources/js/layouts`
- `resources/js/pages`
- `resources/js/types`
- `resources/js/hooks`
- `resources/js/lib`
- `tests/Feature`
- `tests/Unit`
- `docs`

Adapt names to the selected Laravel version, but retain clear separation of responsibilities.

# USER ROLES

Implement these roles:

1. Super Admin
2. HR Admin
3. Manager
4. Employee

Use Spatie Laravel Permission.

## Super Admin

Can:

- Access all modules
- Manage users
- Manage roles and permissions
- Manage organization master data
- Manage employees
- Manage attendance
- Manage leave
- Manage payroll
- Manage announcements
- View reports
- View audit logs
- Manage settings
- Impersonate users only if implemented securely and clearly audited

User impersonation is optional. Do not prioritize it over required features.

## HR Admin

Can:

- Manage employee records
- Manage departments, positions, employment types, and office locations
- Manage attendance records and corrections
- Manage leave types, balances, and requests
- Generate and publish payroll
- Manage announcements
- Manage employee documents
- View HR reports
- View relevant audit logs

Cannot:

- Modify Super Admin permissions
- Perform unrestricted system-level administration unless explicitly permitted

## Manager

Can:

- View employees who report directly or indirectly to them, according to the implemented organization structure
- View their team dashboard
- Review leave requests from direct reports
- Approve or reject leave requests assigned to them
- View team attendance summaries
- View team leave calendar
- Read announcements
- Manage their own profile
- View their own attendance, leave, payslips, and documents

Managers must not see confidential payroll details of employees unless specifically authorized. By default, managers may not see employee salary or payslip values.

## Employee

Can:

- View and update permitted profile fields
- View their own employee data
- Check in and check out
- View their own attendance history
- Submit leave requests
- Cancel eligible pending leave requests
- View leave balances
- View their own payslips
- Download their own payslips
- Read announcements
- View permitted personal documents
- Receive notifications

Employees must never access another employee's confidential data.

# AUTHENTICATION AND ACCOUNT SECURITY

Use the official Laravel starter kit authentication foundation.

Implement:

- Login
- Logout
- Forgot password
- Reset password
- Email verification
- Profile page
- Update profile
- Change password
- Session management when supported by the starter kit
- Remember me
- Rate limiting
- CSRF protection
- Secure password hashing
- Account active/inactive enforcement

Public registration must be disabled.

Only authorized administrators may create employee accounts.

When an employee is created:

- Generate or accept a unique employee number
- Create the related user account
- Assign the Employee role by default
- Allow HR to assign Manager or HR Admin when authorized
- Optionally send an account activation or password setup notification
- In the local/demo environment, make the account usable through documented demo credentials

Inactive users must not be able to log in.

Use Laravel's existing security mechanisms rather than custom authentication.

# ORGANIZATION MODULE

Implement the following entities.

## Company

Support one company in the MVP while keeping the model reasonably extensible.

Fields:

- Name
- Legal name
- Company code
- Email
- Phone
- Address
- City
- Province/state
- Postal code
- Country
- Logo
- Timezone
- Default currency
- Attendance start time
- Attendance end time
- Attendance grace period in minutes
- Default annual leave allowance
- Payroll cutoff day
- Active status

Use a settings interface appropriate for a single-company application.

## Departments

Fields:

- Code
- Name
- Description
- Parent department, nullable
- Department manager, nullable
- Active status
- Created by
- Updated by
- Timestamps

Rules:

- Department code must be unique.
- Circular parent relationships must be prevented.
- Inactive departments cannot be selected for new employees.
- Existing employee history must be preserved.

## Positions

Fields:

- Code
- Name
- Description
- Department, optional if positions are global
- Level
- Minimum salary, optional
- Maximum salary, optional
- Active status
- Timestamps

Salary range fields are administrative and must not be exposed to normal employees or managers without permission.

## Office Locations

Fields:

- Code
- Name
- Address
- City
- Province/state
- Country
- Timezone
- Latitude, optional
- Longitude, optional
- Attendance radius in meters, optional
- Active status

Do not require geolocation-based attendance for the MVP. Store fields for possible future enhancement.

## Employment Types

Seed at minimum:

- Permanent
- Contract
- Probation
- Internship
- Part-time

Allow HR to manage them.

Fields:

- Code
- Name
- Description
- Active status

# EMPLOYEE MANAGEMENT MODULE

Create an Employee entity separate from User.

A User is the login identity.

An Employee is the HR record.

Employee fields:

- User ID
- Employee number
- First name
- Last name
- Preferred name, optional
- Work email
- Personal email, optional
- Phone
- Gender, optional enum
- Date of birth, optional
- Place of birth, optional
- Nationality, optional
- Marital status, optional enum
- Profile photo, optional
- Department
- Position
- Office location
- Employment type
- Manager, nullable self-reference
- Join date
- Probation end date, optional
- Contract start date, optional
- Contract end date, optional
- Employment status
- Termination date, optional
- Work schedule type
- Basic salary
- Bank name, optional
- Bank account number, optional
- Bank account holder, optional
- Tax identification number, optional
- Address
- City
- Province/state
- Postal code
- Country
- Emergency contact name
- Emergency contact relationship
- Emergency contact phone
- Notes, restricted
- Created by
- Updated by
- Timestamps

Employment statuses:

- Active
- Probation
- On leave
- Suspended
- Resigned
- Terminated

Work schedule types:

- Office
- Remote
- Hybrid

Sensitive employee fields must be protected by authorization.

At minimum, protect:

- Basic salary
- Bank details
- Tax number
- Private notes
- Personal documents
- Personal email
- Personal address
- Emergency contact data

## Employee Features

Implement:

- Employee listing
- Employee creation
- Employee detail
- Employee update
- Employee activation/deactivation
- Employment status changes
- Search
- Filters
- Sorting
- Pagination
- Profile photo upload
- Organization information
- Employment information
- Contact information
- Emergency contact information
- Compensation information
- Documents
- Attendance summary
- Leave summary
- Payroll summary
- Activity timeline

Use a tabbed employee detail page.

Do not permanently delete employees who have transactional history.

Use deactivation or employment status changes instead.

## Employee Number

Generate readable employee numbers automatically when not provided.

Example:

EMP-2026-0001

The sequence must avoid duplicates under concurrent creation.

A manual value may be accepted only for authorized HR users and must remain unique.

## Manager Relationship

An employee may report to another employee.

Prevent:

- An employee reporting to themselves
- Circular reporting chains
- Assigning inactive managers where inappropriate

Build:

- Direct reports view
- Team members view
- Basic organization hierarchy display

# EMPLOYEE DOCUMENTS

Implement secure employee document management.

Document categories:

- Identity
- Contract
- Education
- Certification
- Tax
- Bank
- Other

Fields:

- Employee
- Category
- Title
- File path
- Original filename
- MIME type
- File size
- Expiration date, optional
- Description, optional
- Visibility
- Uploaded by
- Timestamps

Visibility values:

- HR only
- Employee and HR
- Public internal profile, optional

Rules:

- Validate file types and size.
- Do not trust file extensions alone.
- Use generated storage filenames.
- Prevent directory traversal.
- Never expose raw private file paths.
- Serve protected files through authorized controller actions.
- Employees may only access their own permitted documents.
- Managers cannot access confidential employee documents by default.
- Audit uploads, downloads, replacements, and deletions.
- Preserve metadata when appropriate.

Allowed document formats may include:

- PDF
- JPG
- JPEG
- PNG
- DOC
- DOCX

Use a reasonable maximum upload size and document it.

# LEAVE MANAGEMENT MODULE

## Leave Types

Fields:

- Name
- Code
- Description
- Paid status
- Annual allowance
- Maximum consecutive days, optional
- Minimum notice days
- Requires attachment
- Allows negative balance
- Carry forward enabled
- Maximum carry-forward days, optional
- Active status
- Color
- Timestamps

Seed:

- Annual Leave
- Sick Leave
- Unpaid Leave
- Maternity Leave
- Paternity Leave
- Compassionate Leave

Use reasonable default rules suitable for demonstration. Clearly document that statutory policies vary by jurisdiction and the app uses configurable demo rules.

## Leave Balances

Fields:

- Employee
- Leave type
- Year
- Entitled days
- Carried forward days
- Used days
- Pending days
- Adjustment days
- Remaining days, preferably calculated consistently
- Last recalculated timestamp

Implement reliable balance calculation.

Prevent invalid balance changes under concurrency using database transactions and row locking where appropriate.

## Leave Requests

Fields:

- Request number
- Employee
- Leave type
- Start date
- End date
- Start session
- End session
- Total requested days
- Reason
- Attachment, optional
- Status
- Current approver
- Approved by
- Approved at
- Rejected by
- Rejected at
- Rejection reason
- Cancelled by
- Cancelled at
- Cancellation reason
- Timestamps

Sessions:

- Full day
- First half
- Second half

Statuses:

- Draft, optional
- Pending
- Approved
- Rejected
- Cancelled

A draft state may be omitted if it complicates the MVP without enough value.

## Leave Workflow

Rules:

- Employees submit their own leave requests.
- HR may submit a request on behalf of an employee, with an audit record.
- Start date must not be after end date.
- Calculate working days excluding weekends.
- Also exclude active public holidays.
- Support half-day requests.
- Prevent overlapping pending or approved leave requests for the same employee.
- Prevent requests exceeding available balance unless the leave type allows negative balance.
- Pending days must reserve the requested balance.
- Rejection or cancellation must release pending balance.
- Approval must move days from pending to used.
- Cancellation after approval requires HR authorization and must restore the balance.
- Leave types requiring attachments must enforce attachment validation.
- Requests requiring advance notice must enforce or explicitly allow HR override with an audited reason.
- Employees can cancel only their own pending requests.
- Managers can approve or reject requests assigned to them.
- HR and Super Admin may approve or reject according to permissions.
- Approvers cannot approve their own leave request.
- Every status transition must be validated server-side.
- Approval and rejection must be transactional.
- Send relevant notifications.
- Record audit logs.
- Preserve complete request history.

## Approval Strategy

For the MVP, use a simple one-level approval workflow:

1. Employee submits request.
2. Direct manager becomes the approver.
3. If no manager exists, assign the request to HR.
4. Manager or HR reviews it.
5. HR may override only with the relevant permission and an audit reason.

Design the code so additional approval levels could be added later, but do not overengineer a generic workflow engine.

## Public Holidays

Implement:

- Holiday name
- Date
- Description, optional
- Recurring yearly flag
- Office location, nullable for company-wide
- Active status

Seed sample holidays for demo purposes and clearly identify them as demo data.

## Leave Calendar

Create:

- Personal leave calendar
- Team leave calendar for managers
- Company leave calendar for HR
- Filters by department, leave type, employee, and status
- Privacy-conscious display

Normal employees should not see confidential leave reasons for other employees.

# ATTENDANCE MANAGEMENT MODULE

## Attendance Records

Fields:

- Employee
- Attendance date
- Check-in timestamp
- Check-out timestamp
- Work duration in minutes
- Break duration in minutes
- Status
- Work mode
- Office location, optional
- Check-in notes, optional
- Check-out notes, optional
- Late minutes
- Overtime minutes
- Source
- Created by
- Updated by
- Correction status
- Timestamps

Statuses:

- Present
- Late
- Absent
- On leave
- Holiday
- Weekend
- Incomplete

Work modes:

- Office
- Remote
- Hybrid

Sources:

- Self service
- HR entry
- Import
- System

Rules:

- One primary attendance record per employee per date.
- An employee cannot check in twice without checking out.
- An employee cannot check out before checking in.
- Check-out must occur after check-in.
- Calculate work duration consistently.
- Calculate late minutes based on company work start time and grace period.
- Mark incomplete records when check-out is missing.
- Approved leave must be represented appropriately in attendance summaries.
- Holidays and weekends must not be counted as unexplained absences.
- Store timestamps in UTC and display in the configured company or location timezone.
- Prevent normal employees from editing raw attendance timestamps after recording.
- HR corrections require a reason and an audit record.

## Employee Attendance UI

Implement:

- Prominent check-in/check-out card
- Current attendance state
- Today's check-in time
- Today's check-out time
- Worked duration
- Current local company time
- Recent attendance history
- Monthly attendance summary
- Filters by month and status

## HR Attendance UI

Implement:

- Daily attendance overview
- Monthly attendance overview
- Employee attendance detail
- Missing check-out list
- Late employee list
- Absence list
- Manual attendance creation
- Attendance correction
- Correction reason
- Search
- Filters
- Pagination
- CSV export

## Attendance Correction

Support:

- Employee may submit a correction request, optional but recommended.
- HR may correct a record directly with a required reason.
- Preserve old and new values in audit logs.
- Notify employee when HR changes their attendance.

If implementing employee correction requests, use statuses:

- Pending
- Approved
- Rejected

Do not let this optional workflow block completion of core attendance.

## Scheduled Attendance Processing

Create an idempotent scheduled command that:

- Marks expected active employees as absent after the configured business day cutoff when they have no attendance and no approved leave, holiday, or weekend.
- Marks missing check-outs as incomplete when appropriate.
- Avoids creating duplicates.
- Supports a date argument for safe manual execution.
- Is covered by tests.

# SIMPLIFIED PAYROLL MODULE

This is a portfolio-grade simplified payroll system.

Do not implement country-specific tax filing, statutory reporting, or complex tax law.

Clearly state in the UI and documentation that payroll calculations are simplified and configurable for demonstration purposes.

## Salary Components

Types:

- Earning
- Deduction

Calculation methods:

- Fixed amount
- Percentage of basic salary

Fields:

- Code
- Name
- Type
- Calculation method
- Default amount, optional
- Default percentage, optional
- Taxable flag, informational
- Active status
- Description
- Timestamps

Seed components such as:

Earnings:

- Basic Salary
- Transport Allowance
- Meal Allowance
- Position Allowance
- Overtime
- Bonus

Deductions:

- Attendance Deduction
- Unpaid Leave
- Employee Loan, demo only
- Other Deduction

Do not implement loans as a full module.

## Employee Salary Components

Fields:

- Employee
- Salary component
- Fixed amount, optional
- Percentage, optional
- Effective start date
- Effective end date, optional
- Active status
- Notes
- Created by
- Updated by

Basic salary should come from the employee compensation record or a dedicated salary history table.

Prefer a salary history table if it can be implemented reliably:

- Employee
- Basic salary
- Effective date
- End date, optional
- Reason
- Approved by
- Created by
- Timestamps

Preserve salary history.

## Payroll Periods

Fields:

- Name
- Year
- Month
- Start date
- End date
- Payment date
- Status
- Generated at
- Generated by
- Published at
- Published by
- Notes
- Timestamps

Statuses:

- Draft
- Processing
- Generated
- Published
- Closed
- Failed

Rules:

- Only one payroll period for a given company, year, and month.
- Published and closed payroll data must not be casually modified.
- Recalculation is allowed only in safe statuses.
- Publishing must be transactional.
- Employees can see payslips only after publication.

## Payroll Records

Fields:

- Payroll period
- Employee
- Basic salary
- Total earnings
- Total deductions
- Gross salary
- Net salary
- Working days
- Present days
- Paid leave days
- Unpaid leave days
- Absent days
- Overtime minutes or hours
- Calculation snapshot JSON
- Status
- Generated at
- Timestamps

Ensure one payroll record per employee per payroll period.

## Payroll Items

Fields:

- Payroll record
- Salary component code
- Salary component name
- Type
- Quantity, optional
- Rate, optional
- Amount
- Description, optional
- Calculation metadata JSON, optional
- Timestamps

Store snapshot values so historical payslips do not change when master components are edited.

## Payroll Calculation Rules

Implement understandable calculations:

1. Determine active employees eligible for the period.
2. Obtain effective basic salary.
3. Add fixed and percentage earnings.
4. Calculate an attendance deduction based on unpaid absences using a configurable daily-rate formula.
5. Calculate unpaid leave deduction.
6. Add manually entered bonus or adjustment items.
7. Apply configured deductions.
8. Gross salary equals basic salary plus additional earnings.
9. Net salary equals gross salary minus deductions.
10. Do not allow a negative net salary without an explicit HR override.
11. Store a transparent calculation snapshot.
12. Ensure recalculation is deterministic and idempotent for draft/generated periods.
13. Use decimal arithmetic, never floating-point values for money.
14. Use consistent rounding.
15. Use database transactions.
16. Lock or guard records to prevent duplicate generation.

Default daily-rate formula:

Basic salary / configured working days in the payroll period

Document the calculation clearly.

## Payroll Workflow

Implement:

- Create payroll period
- Generate payroll
- Review payroll employee list
- View payroll calculation details
- Apply manual earning or deduction adjustment
- Recalculate an employee
- Recalculate the full period
- Publish payroll
- Close payroll
- View employee payslip
- Download or print payslip
- CSV export payroll summary

Publishing payroll must:

- Validate the period
- Validate that payroll records exist
- Lock historical snapshots
- Make payslips visible to employees
- Record publisher and timestamp
- Send notifications
- Audit the action

## Payslip

Create a professional printable payslip page containing:

- Company identity
- Payroll period
- Employee identity
- Department
- Position
- Basic salary
- Earnings breakdown
- Deductions breakdown
- Gross salary
- Net salary
- Payment date
- Generation timestamp
- Confidentiality notice

Support browser print styling.

PDF export is optional. Implement it only if a stable package can be installed and tested. A polished print page is sufficient when PDF generation would destabilize the project.

# ANNOUNCEMENTS MODULE

Fields:

- Title
- Slug
- Summary
- Content
- Audience type
- Publish date
- Expiration date, optional
- Pinned flag
- Status
- Created by
- Updated by
- Timestamps

Audience types:

- All employees
- Selected departments
- Selected employment types
- Selected employees

Statuses:

- Draft
- Scheduled
- Published
- Archived

Features:

- Rich but safely rendered content
- Do not allow unsafe HTML
- Publish immediately
- Schedule publication
- Archive
- Pin important announcements
- Track read status
- Employee announcement feed
- Announcement detail
- Unread indicator
- Notification on publication
- Search and filters for HR
- Responsive cards and list views

Create an idempotent scheduled command for publishing scheduled announcements.

# NOTIFICATIONS

Use Laravel database notifications.

Use queued notifications where appropriate.

Implement notifications for:

- Account created or activated
- Leave request submitted
- Leave request approved
- Leave request rejected
- Leave request cancelled
- Leave request awaiting manager review
- Attendance record corrected
- Missing check-out reminder
- Payroll published
- New announcement published
- Employee document approaching expiration, optional
- Employment contract approaching expiration
- Probation ending soon

Notification UI:

- Notification bell
- Unread count
- Notification dropdown
- Notification page
- Mark one as read
- Mark all as read
- Link each notification to the relevant authorized page
- Do not leak private data in notification payloads

Configure queue defaults so the application works locally.

For a simple portfolio environment, the database queue driver is acceptable.

Create queue tables and document worker commands.

When no queue worker is available during setup or tests, ensure critical state changes still complete safely; only the secondary notification may remain queued.

# DASHBOARDS

Build role-aware dashboards.

## Super Admin and HR Dashboard

Metrics:

- Total active employees
- Employees by employment status
- New hires this month
- Employees with contracts ending soon
- Employees with probation ending soon
- Present today
- Late today
- Absent today
- Employees currently on leave
- Pending leave requests
- Payroll status for current month
- Total monthly payroll value, restricted by permission

Charts:

- Employees by department
- Employees by employment type
- Attendance trend for the last 30 days
- Leave usage by type
- Monthly headcount trend
- Payroll cost trend, restricted

Lists:

- Pending leave approvals
- Recent hires
- Birthdays this month
- Contract expirations
- Recent announcements
- Recent HR activity

## Manager Dashboard

Metrics:

- Direct reports
- Team present today
- Team absent today
- Team on leave
- Pending leave approvals
- Upcoming team leave

Lists:

- Requests awaiting approval
- Team attendance exceptions
- Upcoming leave
- Team birthdays
- Recent announcements

Do not expose salaries.

## Employee Dashboard

Metrics:

- Today's attendance status
- Leave balance
- Pending leave requests
- Current month's attendance summary
- Latest payslip availability

Widgets:

- Check-in/check-out
- Recent attendance
- Leave balance cards
- Upcoming approved leave
- Latest announcements
- Recent notifications
- Employment summary
- Quick links

Dashboard queries must be reasonably efficient.

Avoid N+1 queries.

# REPORTS AND EXPORTS

Implement reports with date filters, organization filters, pagination, and CSV export.

Required reports:

1. Employee directory
2. Employee headcount by department
3. Employee contract expiration
4. Employee probation expiration
5. Daily attendance
6. Monthly attendance
7. Late attendance
8. Absence report
9. Leave requests
10. Leave usage
11. Leave balances
12. Payroll summary
13. Payroll employee details
14. Announcement readership, simple
15. Audit activity

Authorization:

- Employees cannot access HR reports.
- Managers can access only approved team-level reports.
- Payroll reports require explicit payroll permissions.
- CSV exports must enforce the same authorization and filters as screen views.

CSV exports must:

- Stream or generate efficiently
- Use clear headers
- Use UTF-8
- Avoid formula-injection vulnerabilities by sanitizing spreadsheet-sensitive values
- Use stable filenames
- Audit exports of sensitive data when appropriate

# AUDIT LOG

Implement an application audit log.

Record important actions:

- User created
- User activated or deactivated
- Roles changed
- Employee created
- Employee updated
- Employment status changed
- Salary changed
- Document uploaded or deleted
- Attendance created
- Attendance corrected
- Leave submitted
- Leave approved
- Leave rejected
- Leave cancelled
- Leave balance adjusted
- Payroll generated
- Payroll recalculated
- Payroll published
- Payroll closed
- Announcement created
- Announcement published
- Settings changed
- Sensitive report exported

Audit fields:

- Actor user ID, nullable for system
- Action
- Event category
- Subject type
- Subject ID
- Description
- Old values JSON
- New values JSON
- Metadata JSON
- IP address
- User agent
- Timestamp

Requirements:

- Redact secrets and passwords.
- Do not log raw document contents.
- Mask bank account numbers and tax numbers in audit displays.
- Restrict audit log access.
- Add search and filters.
- Add pagination.
- Allow viewing audit details.
- Do not allow normal users to modify audit records.

Use a clear domain event or reusable audit service rather than scattering inconsistent log creation everywhere.

# APPLICATION SETTINGS

Create an authorized settings area.

Sections:

- Company profile
- Branding
- Locale
- Timezone
- Currency
- Attendance schedule
- Grace period
- Payroll settings
- Leave defaults
- Notification settings
- File upload limits, display only if configuration-backed
- Security settings, where appropriate

Use safe defaults.

For the demo:

- Locale: `en`
- Timezone: `Asia/Makassar`
- Currency: `IDR`
- Work start: `09:00`
- Work end: `17:00`
- Grace period: `15` minutes

The interface may be English to maximize portfolio readability. Use consistent English throughout the application.

Store mutable settings in a structured settings table or company record. Do not create an unmaintainable key/value system unless implemented cleanly.

# ROLE AND PERMISSION MATRIX

Create granular permissions, at minimum:

- users.view
- users.create
- users.update
- users.activate
- roles.manage
- employees.view
- employees.create
- employees.update
- employees.view-sensitive
- employees.manage-compensation
- employees.manage-documents
- departments.manage
- positions.manage
- locations.manage
- employment-types.manage
- attendance.view-own
- attendance.view-team
- attendance.view-all
- attendance.record-own
- attendance.create
- attendance.correct
- attendance.export
- leave.view-own
- leave.view-team
- leave.view-all
- leave.submit
- leave.approve
- leave.override
- leave-types.manage
- leave-balances.manage
- payroll.view-own
- payroll.view-all
- payroll.manage
- payroll.publish
- payroll.export
- announcements.view
- announcements.manage
- reports.view-team
- reports.view-hr
- audit-logs.view
- settings.manage

Seed a sensible role-permission matrix.

Every backend route and action must enforce authorization.

Frontend navigation must hide inaccessible pages, but backend authorization remains mandatory.

Create tests for permission boundaries.

# SEARCH, FILTERS, SORTING, AND PAGINATION

Implement server-side search, filtering, sorting, and pagination where appropriate.

Required listing pages:

- Employees
- Departments
- Positions
- Locations
- Employment types
- Attendance
- Leave requests
- Leave types
- Leave balances
- Payroll periods
- Payroll records
- Announcements
- Documents
- Users
- Audit logs
- Reports

Requirements:

- Preserve filters in query parameters.
- Allow bookmarked filter URLs.
- Validate sortable columns to prevent SQL injection.
- Use debounced search where useful.
- Provide a reset-filters action.
- Show active filter state.
- Use efficient eager loading.
- Avoid unbounded queries.
- Use a consistent default page size.
- Support configurable page size within safe limits where practical.

# UI AND DESIGN REQUIREMENTS

Create a polished responsive internal business application.

Use shadcn/ui consistently.

Visual direction:

- Professional
- Clean
- Modern
- Neutral
- Enterprise-oriented
- Accessible
- Not excessively decorative

Use:

- Responsive sidebar
- Mobile navigation
- Top navigation
- Breadcrumbs
- Page titles and descriptions
- Dashboard cards
- Data tables
- Status badges
- Tabs
- Dropdown menus
- Tooltips
- Dialogs
- Alert dialogs
- Sheets or drawers where appropriate
- Toast notifications
- Skeleton loading states where useful
- Empty states
- Error states
- Accessible forms
- Consistent spacing
- Consistent typography
- Lucide icons
- Recharts for analytics

Implement:

- Light mode
- Dark mode
- System theme preference
- Persistent theme selection

Navigation groups:

- Dashboard
- People
- Attendance
- Leave
- Payroll
- Announcements
- Reports
- Administration

## UI Quality Rules

- Do not leave default starter-kit pages visually inconsistent with the application.
- Do not use raw browser confirmation dialogs.
- Do not use arbitrary colors for every status.
- Ensure tables work on smaller screens.
- Ensure forms have labels and error messages.
- Provide confirmation for destructive or irreversible actions.
- Disable buttons during submission.
- Prevent duplicate submissions.
- Display friendly success and error messages.
- Use semantic HTML.
- Support keyboard navigation.
- Maintain reasonable contrast.
- Add an accessible 403 page.
- Add a useful 404 page.
- Add a useful 500 page where practical.
- Do not show stack traces in production.
- Do not expose database errors to users.

# INERTIA AND REACT REQUIREMENTS

Use TypeScript.

Create reusable types for:

- Auth user
- Permissions
- Pagination
- Employee summaries
- Department
- Position
- Attendance
- Leave request
- Payroll period
- Notification
- Shared page props

Use Laravel-provided route helpers or the official starter-kit convention.

Avoid manually concatenating URLs throughout React code.

Use Inertia forms correctly.

Handle:

- Validation errors
- Form processing states
- Form reset
- Preserve scroll where useful
- Preserve state where useful
- Flash messages
- Partial reloads where useful

Do not duplicate critical backend validation in frontend-only code.

Frontend validation exists for UX; backend validation is authoritative.

# DATABASE DESIGN REQUIREMENTS

Create well-designed migrations.

Use:

- Foreign keys
- Unique constraints
- Composite unique constraints
- Appropriate indexes
- Check constraints where supported and maintainable
- Decimal columns for money
- Date columns for dates
- Timestamp columns for events
- JSONB for snapshots and metadata where suitable
- Explicit safe delete behavior

Use `restrict`, `cascade`, `set null`, or soft deletion intentionally.

Do not cascade-delete transactional HR history accidentally.

Prefer preserving records when an employee, department, position, or user becomes inactive.

Potential tables include, but are not limited to:

- users
- employees
- companies
- departments
- positions
- office_locations
- employment_types
- employee_salary_histories
- employee_documents
- leave_types
- leave_balances
- leave_requests
- public_holidays
- attendance_records
- attendance_corrections
- salary_components
- employee_salary_components
- payroll_periods
- payroll_records
- payroll_items
- payroll_adjustments
- announcements
- announcement_audiences
- announcement_reads
- notifications
- jobs
- failed_jobs
- audit_logs
- settings or company settings
- Spatie permission tables

Adjust the final schema when a simpler design is more reliable.

Create indexes for:

- Employee number
- Employee names where useful
- Work email
- Department
- Manager
- Employment status
- Attendance employee/date
- Leave employee/status/date
- Payroll period/status
- Notification read state
- Audit actor/action/subject/date

Ensure concurrent operations cannot create:

- Duplicate employee numbers
- Duplicate attendance for the same employee/date
- Duplicate leave balances per employee/type/year
- Duplicate payroll periods per year/month
- Duplicate payroll records per employee/period
- Duplicate announcement read records

# DOMAIN ENUMS

Use PHP backed enums for suitable states, including:

- EmploymentStatus
- Gender
- MaritalStatus
- WorkScheduleType
- AttendanceStatus
- AttendanceSource
- WorkMode
- LeaveRequestStatus
- LeaveSession
- PayrollPeriodStatus
- SalaryComponentType
- SalaryCalculationMethod
- AnnouncementStatus
- AnnouncementAudienceType
- DocumentVisibility
- DocumentCategory

Provide labels and, where useful, badge variants.

Do not duplicate enum values as uncoordinated strings throughout the codebase.

# VALIDATION

Create dedicated Form Request classes.

Validate all inputs server-side.

Include:

- Unique employee numbers and emails
- Valid dates
- Date ordering
- Valid enum values
- Valid foreign keys
- Active master-data selections
- Decimal money limits
- File MIME types
- File sizes
- Image dimensions where useful
- Leave overlap
- Leave balances
- Attendance transition validity
- Payroll state transitions
- Announcement publication dates
- Role assignment restrictions

Use clear human-readable validation messages.

Do not accept mass-assigned sensitive fields without explicit validation and authorization.

# TRANSACTIONS AND CONCURRENCY

Use database transactions for:

- Employee and user creation
- Employee number generation
- Leave submission
- Leave approval
- Leave rejection
- Leave cancellation
- Leave balance adjustment
- Attendance correction
- Payroll generation
- Payroll recalculation
- Payroll publication
- Salary changes
- Announcement publication when audience records are created

Use locking where needed to protect:

- Leave balances
- Employee number sequence
- Payroll period generation
- Attendance uniqueness

Do not hold transactions open during slow file uploads or external calls.

# PERFORMANCE

Prevent obvious performance problems.

Requirements:

- Avoid N+1 queries.
- Eager load relationships.
- Paginate listings.
- Use aggregate queries for dashboard metrics.
- Add indexes.
- Do not load full payroll or attendance history unnecessarily.
- Cache only stable data where it provides clear value.
- Invalidate caches correctly.
- Use queue jobs for expensive non-critical operations.
- Keep demo data volume large enough to reveal N+1 problems.

Use Laravel debug facilities in development where available, but do not commit unsafe debug configuration for production.

# SECURITY REVIEW REQUIREMENTS

Before completion, audit the application for:

- Broken access control
- Insecure direct object references
- Mass assignment
- CSRF
- XSS
- SQL injection
- Unsafe file upload
- Path traversal
- Sensitive data exposure
- Authorization gaps in exports
- Notification data leakage
- User enumeration
- Open redirects
- Weak password handling
- Missing rate limits
- Debug mode exposure
- Secret leakage
- CSV formula injection
- Unsafe HTML rendering
- Privilege escalation through role assignment
- Access to unpublished payslips
- Access to other employees' documents
- Manager access outside their team

Fix discovered issues.

Never depend solely on hidden UI controls for security.

# EVENTS, LISTENERS, JOBS, AND SCHEDULER

Use domain events where valuable, such as:

- EmployeeCreated
- LeaveRequestSubmitted
- LeaveRequestApproved
- LeaveRequestRejected
- AttendanceCorrected
- PayrollPublished
- AnnouncementPublished

Listeners may:

- Create notifications
- Write audit entries
- Dispatch follow-up jobs

Create idempotent scheduled processes for:

- Daily absence generation
- Incomplete attendance handling
- Scheduled announcement publication
- Contract expiration reminders
- Probation end reminders
- Document expiration reminders, optional
- Upcoming leave reminders, optional

Document scheduler execution:

`php artisan schedule:work`

Also document cron-based production scheduling.

Ensure scheduled jobs can safely run more than once.

# DEMO DATA

Create realistic factories and seeders.

Use fictional data only.

Never use real personal data.

Seed at minimum:

- One company
- Eight departments
- Fifteen positions
- Three office locations
- Five employment types
- One Super Admin
- Two HR Admins
- Six Managers
- At least forty Employees
- Reporting relationships
- Several employment statuses
- Salary histories
- Employee salary components
- Leave types
- Leave balances for current year
- Public holidays
- Leave requests in pending, approved, rejected, and cancelled states
- Attendance records for at least the previous sixty days
- Present, late, absent, leave, incomplete, weekend, and holiday examples
- Salary components
- At least three payroll periods
- Draft, generated, published, and closed payroll examples where valid
- Payroll records and payslips
- At least ten announcements
- Announcement read states
- Employee documents using safe generated sample files or metadata
- Notifications
- Audit logs

Seed data must be coherent.

Examples:

- Approved leave must affect leave balances.
- Published payroll must have records.
- Attendance dates must align with leave and holidays.
- Managers must have team members.
- Employee accounts must map correctly to employee records.

## Demo Credentials

Create documented development-only credentials.

Use a common safe demo password such as:

`password`

Example accounts:

- `admin@nusahr.test`
- `hr@nusahr.test`
- `manager@nusahr.test`
- `employee@nusahr.test`

Ensure all demo accounts are verified and active.

Clearly state that credentials are for local/demo environments only.

Do not use these credentials as production defaults.

# TESTING REQUIREMENTS

Use Pest.

Write meaningful automated tests.

Prioritize authorization, business rules, state transitions, and data integrity.

## Authentication Tests

Test:

- Login succeeds with valid credentials
- Login fails with invalid credentials
- Inactive user cannot log in
- Email verification behavior
- Password reset flow
- Protected pages require authentication

## Authorization Tests

Test:

- Employee cannot access HR administration
- Employee cannot access another employee's profile
- Employee cannot download another employee's document
- Employee cannot view another employee's payslip
- Manager can access direct reports where allowed
- Manager cannot access employees outside their team
- Manager cannot view salary data by default
- HR can manage employee records
- HR cannot change Super Admin permissions without authorization
- Super Admin has expected access
- Export endpoints enforce permissions

## Employee Tests

Test:

- Employee and user are created transactionally
- Employee number is unique
- Employee number generation works
- Inactive master data cannot be selected
- Self-manager relationship is rejected
- Circular manager hierarchy is rejected
- Sensitive fields are hidden without permission
- Employee deactivation disables login where intended

## Leave Tests

Test:

- Employee can submit valid leave request
- Cannot submit overlapping leave
- Cannot exceed balance
- Paid and unpaid leave behave appropriately
- Required attachment is enforced
- Working-day calculation excludes weekends
- Working-day calculation excludes holidays
- Half-day calculation works
- Pending balance is reserved
- Manager can approve direct-report leave
- Manager cannot approve own request
- Manager cannot approve unrelated employee request
- Rejection releases pending balance
- Cancellation releases pending balance
- Approved cancellation restores balance when allowed
- Duplicate approval is prevented
- Concurrent approval cannot corrupt balance
- Notifications are dispatched

## Attendance Tests

Test:

- Employee can check in
- Duplicate check-in is rejected
- Cannot check out before check-in
- Check-out calculates duration
- Late minutes are calculated correctly
- One attendance record per employee/date
- HR correction requires reason
- Correction is audited
- Daily absence command is idempotent
- Approved leave is not marked absent
- Holiday is not marked absent
- Weekend is not marked absent
- Missing check-out becomes incomplete

## Payroll Tests

Test:

- Payroll period uniqueness
- Payroll generation includes eligible employees
- Effective basic salary is selected correctly
- Fixed earning calculation
- Percentage earning calculation
- Absence deduction
- Unpaid leave deduction
- Manual adjustment
- Decimal rounding
- No duplicate payroll records
- Regeneration is deterministic
- Employee cannot view draft payslip
- Employee can view own published payslip
- Employee cannot view another payslip
- Manager cannot view team salaries without permission
- Publication is transactional
- Published period cannot be edited improperly
- Payroll notification is dispatched

## Announcement Tests

Test:

- Draft is not visible
- Scheduled announcement is not visible early
- Scheduled publishing command works
- Audience filtering works
- Employee can mark announcement as read
- Duplicate read records are prevented
- Unauthorized users cannot manage announcements

## Audit Tests

Test:

- Important actions create audit entries
- Passwords and secrets are not recorded
- Sensitive values are masked appropriately
- Normal employee cannot view audit logs

## UI/Route Smoke Tests

Test:

- Main pages return successful responses for authorized roles
- Inertia components exist
- Dashboard routes work by role
- Invalid IDs return appropriate responses
- 403 behavior is correct

## Test Database

Use a safe test database configuration.

Prefer:

- A dedicated PostgreSQL test database when available
- SQLite only if all tested features remain compatible

Do not run destructive test migrations against the configured shared Neon database.

Never use production credentials for automated tests.

If the cloud environment does not provide a separate PostgreSQL test database, configure tests to use SQLite where compatible and add PostgreSQL-specific integration checks that do not destroy shared data.

Run:

- Full Pest suite
- Focused tests during development
- Laravel Pint
- Frontend type checking
- Frontend linting
- Production frontend build

Fix failures before completion.

# FRONTEND QUALITY CHECKS

Configure and run:

- TypeScript type checking
- ESLint if included by the starter kit
- Production Vite build
- Formatting if configured

Remove:

- Unused imports
- `any` types where avoidable
- Console debugging
- Dead components
- Temporary mock data
- Placeholder text
- Unused routes
- Broken navigation links

Ensure React lists have stable keys.

Ensure all forms display backend validation errors.

# DOCKER AND LOCAL DEVELOPMENT

Provide a Docker Compose setup suitable for local development.

Include services where practical:

- Application
- PostgreSQL
- Mailpit
- Optional Redis only if actually used

Do not make Docker mandatory for Neon usage.

Support two clear setup paths:

## Local PostgreSQL path

Developers can use Docker Compose with a local PostgreSQL instance.

## Neon path

Developers can provide `DATABASE_URL` in `.env`.

Document both.

Docker requirements:

- Reasonable PHP image
- Composer support
- Node build process or documented host-node workflow
- Correct file permissions
- Persistent local PostgreSQL volume
- Health checks where useful
- No embedded secrets
- Clear commands

Do not spend disproportionate time on complex container orchestration. Reliability is more important than elaborate infrastructure.

# ENVIRONMENT FILE

Create a complete `.env.example`.

Include placeholders for:

- APP_NAME
- APP_ENV
- APP_KEY
- APP_DEBUG
- APP_URL
- APP_TIMEZONE
- DATABASE_URL
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD
- SESSION_DRIVER
- CACHE_STORE
- QUEUE_CONNECTION
- FILESYSTEM_DISK
- MAIL_MAILER
- MAIL_HOST
- MAIL_PORT
- MAIL_FROM_ADDRESS
- MAIL_FROM_NAME

Use safe defaults.

Do not include real secrets.

# INSTALLATION RELIABILITY

The documented setup must work from a clean clone.

Target commands should resemble:

1. Clone repository
2. Copy `.env.example` to `.env`
3. Configure local PostgreSQL or `DATABASE_URL`
4. `composer install`
5. `npm install`
6. `php artisan key:generate`
7. `php artisan storage:link`
8. `php artisan migrate --seed`
9. `npm run build`
10. Start Laravel
11. Start queue worker
12. Start scheduler

Adapt commands to the actual implementation.

Provide optional convenience commands through a Makefile or Composer scripts, for example:

- `make setup`
- `make test`
- `make lint`
- `make dev`
- `make fresh`

Do not make convenience commands obscure the underlying Laravel commands.

# README REQUIREMENTS

Create a professional `README.md`.

Include:

1. Project title
2. Screenshots or screenshot placeholders
3. Project overview
4. Portfolio purpose
5. Main features
6. Role capabilities
7. Technology stack
8. Architecture overview
9. Main business workflows
10. Database overview
11. Security decisions
12. Requirements
13. Local installation
14. Neon PostgreSQL configuration
15. Docker setup
16. Queue setup
17. Scheduler setup
18. Mail testing
19. Demo data
20. Demo credentials
21. Testing
22. Code-quality commands
23. Production build
24. Common troubleshooting
25. Known limitations
26. Future improvements
27. License

Do not claim features that are not implemented.

Include screenshots only if they can actually be produced. Otherwise include clearly marked screenshot placeholders and instructions for adding them.

# ADDITIONAL DOCUMENTATION

Create:

- `docs/architecture.md`
- `docs/database-design.md`
- `docs/business-rules.md`
- `docs/roles-and-permissions.md`
- `docs/deployment.md`
- `docs/testing.md`

## Architecture Documentation

Explain:

- Laravel and Inertia architecture
- React integration
- Authentication
- Authorization
- Service and action boundaries
- Events and notifications
- Queue usage
- Scheduler usage
- File security
- Audit logging
- Why no generic repository layer was used

## Database Documentation

Include:

- Main entities
- Relationship explanations
- Important constraints
- Indexing decisions
- Money handling
- Historical snapshots
- Deletion strategy

Add a Mermaid ER diagram.

The diagram does not need every framework table, but it must show the important domain entities and relationships.

## Business Rules Documentation

Document:

- Employee lifecycle
- Manager hierarchy
- Leave balance calculation
- Leave approval
- Working-day calculation
- Attendance state transitions
- Absence processing
- Payroll calculation
- Payroll publication
- Announcement audience handling
- Sensitive data permissions

Add Mermaid workflow diagrams for:

- Leave approval
- Daily attendance
- Payroll generation and publication

## Roles Documentation

Provide a role-permission matrix.

## Deployment Documentation

Document generic deployment requirements, including:

- PHP extensions
- Web server
- Environment variables
- App key
- PostgreSQL
- Migrations
- Storage linking
- Queue worker
- Scheduler
- Production asset build
- Cache commands
- HTTPS
- Secure cookies
- Debug mode
- Backups

Do not deploy to production unless the task environment explicitly supports a safe deployment target and credentials are provided separately.

# CODE QUALITY

Requirements:

- Follow Laravel conventions.
- Use descriptive names.
- Avoid duplicated business logic.
- Add PHPDoc only where useful.
- Keep methods focused.
- Use dependency injection.
- Use readonly properties where suitable.
- Use enums rather than magic strings.
- Avoid deeply nested conditionals.
- Use early returns where clearer.
- Use database transactions intentionally.
- Keep controllers thin.
- Keep React pages manageable.
- Extract reusable components.
- Do not create excessive tiny components.
- Remove commented-out code.
- Remove generated example code that is no longer relevant.
- Do not leave TODO markers for required features.
- Do not suppress errors instead of fixing them.

Run Laravel Pint before finishing.

# GIT REQUIREMENTS

If the environment supports commits:

- Use logical commits.
- Do not commit secrets.
- Do not commit `.env`.
- Do not commit generated vendor dependencies.
- Do not commit `node_modules`.
- Do not commit runtime logs.
- Do not commit user-uploaded demo artifacts unless intentionally generated and safe.
- Keep lock files committed.
- Use a useful `.gitignore`.

Suggested logical commit groups:

1. Initialize Laravel React starter kit
2. Add organization and employee domain
3. Add roles and permissions
4. Add leave management
5. Add attendance management
6. Add payroll
7. Add announcements and notifications
8. Add reports and audit logs
9. Add tests
10. Add documentation and Docker setup
11. Final fixes and polish

Do not sacrifice application completion merely to create perfect commit history.

# IMPLEMENTATION PHASES

Execute the work in this order.

## Phase 1: Environment and Foundation

1. Inspect repository.
2. Inspect available PHP, Composer, Node, npm, and PostgreSQL tooling.
3. Initialize the latest compatible Laravel application if needed.
4. Install the official React starter kit with Inertia, TypeScript, Tailwind, and shadcn/ui.
5. Install Spatie Laravel Permission.
6. Install Pest if not already included.
7. Configure PostgreSQL and `DATABASE_URL`.
8. Configure authentication.
9. Disable public registration.
10. Configure application layout, theme, navigation, and shared props.
11. Confirm the app boots.
12. Run baseline tests and frontend build.

## Phase 2: Database and Organization

1. Design migrations.
2. Add company settings.
3. Add departments.
4. Add positions.
5. Add locations.
6. Add employment types.
7. Add employee and user relationship.
8. Add manager hierarchy.
9. Add salary history.
10. Add secure documents.
11. Add policies and requests.
12. Add React pages.
13. Add tests.

## Phase 3: Leave Management

1. Add leave types.
2. Add holidays.
3. Add balances.
4. Add requests.
5. Add working-day calculator.
6. Add approval actions.
7. Add notifications.
8. Add personal, team, and HR pages.
9. Add calendar.
10. Add tests.

## Phase 4: Attendance

1. Add attendance records.
2. Add check-in/check-out actions.
3. Add late and duration calculations.
4. Add HR correction.
5. Add daily views.
6. Add monthly views.
7. Add scheduled absence processing.
8. Add reports.
9. Add tests.

## Phase 5: Payroll

1. Add salary components.
2. Add employee components.
3. Add payroll periods.
4. Add payroll records and items.
5. Implement deterministic calculator.
6. Add review and adjustment UI.
7. Add publication workflow.
8. Add employee payslips.
9. Add print view.
10. Add CSV export.
11. Add tests.

## Phase 6: Announcements and Notifications

1. Add announcement management.
2. Add audience targeting.
3. Add read tracking.
4. Add scheduled publishing.
5. Add notification center.
6. Add tests.

## Phase 7: Reports, Audit, and Settings

1. Add required reports.
2. Add secure exports.
3. Add audit logging.
4. Add audit viewer.
5. Add settings interface.
6. Add dashboard analytics.
7. Add tests.

## Phase 8: Demo Data and Documentation

1. Create coherent factories.
2. Create seeders.
3. Seed demo database.
4. Verify demo credentials.
5. Complete README.
6. Complete docs.
7. Add Docker Compose.
8. Complete `.env.example`.

## Phase 9: Verification and Completion

Run and fix:

- Migrations
- Seeders
- Pest tests
- Laravel Pint
- TypeScript checking
- ESLint
- Vite production build
- Route inspection
- Scheduler command inspection
- Queue configuration inspection

Manually review, as far as the environment allows:

- Every sidebar link
- Every role dashboard
- Main CRUD workflows
- Leave approval
- Attendance check-in/out
- Payroll generation
- Payslip authorization
- Announcement visibility
- Document authorization
- CSV exports
- Error pages
- Mobile layout

# DEFINITION OF DONE

The project is done only when all of the following are true:

- Application installs from a clean clone.
- Application boots successfully.
- PostgreSQL configuration works.
- Neon `DATABASE_URL` is supported.
- Migrations succeed.
- Seeders succeed.
- Demo accounts can log in.
- Role navigation is correct.
- Backend permissions are enforced.
- Employee management works.
- Secure documents work.
- Leave submission and approval work.
- Leave balances remain consistent.
- Attendance check-in and check-out work.
- Attendance corrections work.
- Scheduled attendance processing is idempotent.
- Payroll can be generated.
- Payroll can be reviewed and published.
- Employees can access only their own published payslips.
- Announcements respect audiences.
- Notifications work.
- Reports and exports work.
- Audit logs work.
- Dashboard data is real, not hard-coded.
- Frontend is responsive.
- Dark and light themes work.
- Tests pass.
- Type checks pass.
- Production frontend build passes.
- Formatting passes.
- No secrets are committed.
- Documentation reflects the actual implementation.
- No required feature is represented only by a placeholder.
- No known critical authorization vulnerability remains.

# SCOPE PRIORITY IN CASE OF HARD ENVIRONMENT LIMITS

Do not casually reduce scope.

First attempt to complete everything.

If an external or environment limitation genuinely prevents part of the work, use this priority order:

1. Authentication and authorization
2. Organization and employee management
3. Leave workflow
4. Attendance workflow
5. Simplified payroll
6. Tests for authorization and workflows
7. Dashboards
8. Announcements and notifications
9. Audit logs
10. Reports and exports
11. UI refinement
12. Optional PDF generation
13. Optional employee correction-request workflow
14. Optional advanced geolocation

For any unavoidable limitation:

- Implement the safest fallback.
- Document the limitation honestly.
- Do not claim the unavailable feature works.
- Continue completing all unaffected areas.

# FINAL SELF-REVIEW

Before reporting completion, inspect the repository as a senior reviewer.

Look specifically for:

- Missing policies
- Routes without authorization
- Controllers containing too much logic
- Incorrect status transitions
- Broken leave balances
- Payroll precision bugs
- Missing indexes
- N+1 queries
- Unsafe document access
- Direct storage URLs for private documents
- Leaked salary data
- Leaked bank data
- Frontend links to unauthorized pages
- Failing tests
- Type errors
- Unused code
- Placeholder components
- Incomplete README commands
- Credentials in files
- Hard-coded dashboard numbers
- Destructive database behavior
- PostgreSQL incompatibility

Fix issues found.

# FINAL RESPONSE FORMAT

At the end, provide a concise but complete completion report containing:

1. Project summary
2. Implemented modules
3. Technology versions selected
4. Important architecture decisions
5. Database and Neon configuration status
6. Migrations and seeders executed
7. Demo account credentials
8. Automated test results, including exact totals
9. Frontend build and type-check results
10. Formatting and lint results
11. Security checks performed
12. Commands needed to run the application
13. Known limitations, if any
14. Repository branch or commit information
15. Recommended optional future improvements

Do not say the application is 100% complete unless the implementation and verification satisfy the Definition of Done.

Begin by inspecting the repository and environment, then proceed through all implementation phases autonomously.