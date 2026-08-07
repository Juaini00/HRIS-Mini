<?php

namespace App\Support;

use App\Enums\UserRole;

/**
 * Central catalogue of every granular permission and the role matrix that grants them.
 *
 * Keeping the matrix in one place means the seeder, the tests, and the docs all read
 * from the same source instead of drifting apart.
 */
final class Permissions
{
    public const USERS_VIEW = 'users.view';

    public const USERS_CREATE = 'users.create';

    public const USERS_UPDATE = 'users.update';

    public const USERS_ACTIVATE = 'users.activate';

    public const ROLES_MANAGE = 'roles.manage';

    public const EMPLOYEES_VIEW = 'employees.view';

    public const EMPLOYEES_CREATE = 'employees.create';

    public const EMPLOYEES_UPDATE = 'employees.update';

    public const EMPLOYEES_VIEW_SENSITIVE = 'employees.view-sensitive';

    public const EMPLOYEES_MANAGE_COMPENSATION = 'employees.manage-compensation';

    public const EMPLOYEES_MANAGE_DOCUMENTS = 'employees.manage-documents';

    public const DEPARTMENTS_MANAGE = 'departments.manage';

    public const POSITIONS_MANAGE = 'positions.manage';

    public const LOCATIONS_MANAGE = 'locations.manage';

    public const EMPLOYMENT_TYPES_MANAGE = 'employment-types.manage';

    public const ATTENDANCE_VIEW_OWN = 'attendance.view-own';

    public const ATTENDANCE_VIEW_TEAM = 'attendance.view-team';

    public const ATTENDANCE_VIEW_ALL = 'attendance.view-all';

    public const ATTENDANCE_RECORD_OWN = 'attendance.record-own';

    public const ATTENDANCE_CREATE = 'attendance.create';

    public const ATTENDANCE_CORRECT = 'attendance.correct';

    public const ATTENDANCE_EXPORT = 'attendance.export';

    public const LEAVE_VIEW_OWN = 'leave.view-own';

    public const LEAVE_VIEW_TEAM = 'leave.view-team';

    public const LEAVE_VIEW_ALL = 'leave.view-all';

    public const LEAVE_SUBMIT = 'leave.submit';

    public const LEAVE_APPROVE = 'leave.approve';

    public const LEAVE_OVERRIDE = 'leave.override';

    public const LEAVE_TYPES_MANAGE = 'leave-types.manage';

    public const LEAVE_BALANCES_MANAGE = 'leave-balances.manage';

    public const PAYROLL_VIEW_OWN = 'payroll.view-own';

    public const PAYROLL_VIEW_ALL = 'payroll.view-all';

    public const PAYROLL_MANAGE = 'payroll.manage';

    public const PAYROLL_PUBLISH = 'payroll.publish';

    public const PAYROLL_EXPORT = 'payroll.export';

    public const ANNOUNCEMENTS_VIEW = 'announcements.view';

    public const ANNOUNCEMENTS_MANAGE = 'announcements.manage';

    public const REPORTS_VIEW_TEAM = 'reports.view-team';

    public const REPORTS_VIEW_HR = 'reports.view-hr';

    public const AUDIT_LOGS_VIEW = 'audit-logs.view';

    public const SETTINGS_MANAGE = 'settings.manage';

    public const HOLIDAYS_MANAGE = 'holidays.manage';

    /**
     * Every permission the application knows about.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_ACTIVATE,
            self::ROLES_MANAGE,
            self::EMPLOYEES_VIEW,
            self::EMPLOYEES_CREATE,
            self::EMPLOYEES_UPDATE,
            self::EMPLOYEES_VIEW_SENSITIVE,
            self::EMPLOYEES_MANAGE_COMPENSATION,
            self::EMPLOYEES_MANAGE_DOCUMENTS,
            self::DEPARTMENTS_MANAGE,
            self::POSITIONS_MANAGE,
            self::LOCATIONS_MANAGE,
            self::EMPLOYMENT_TYPES_MANAGE,
            self::HOLIDAYS_MANAGE,
            self::ATTENDANCE_VIEW_OWN,
            self::ATTENDANCE_VIEW_TEAM,
            self::ATTENDANCE_VIEW_ALL,
            self::ATTENDANCE_RECORD_OWN,
            self::ATTENDANCE_CREATE,
            self::ATTENDANCE_CORRECT,
            self::ATTENDANCE_EXPORT,
            self::LEAVE_VIEW_OWN,
            self::LEAVE_VIEW_TEAM,
            self::LEAVE_VIEW_ALL,
            self::LEAVE_SUBMIT,
            self::LEAVE_APPROVE,
            self::LEAVE_OVERRIDE,
            self::LEAVE_TYPES_MANAGE,
            self::LEAVE_BALANCES_MANAGE,
            self::PAYROLL_VIEW_OWN,
            self::PAYROLL_VIEW_ALL,
            self::PAYROLL_MANAGE,
            self::PAYROLL_PUBLISH,
            self::PAYROLL_EXPORT,
            self::ANNOUNCEMENTS_VIEW,
            self::ANNOUNCEMENTS_MANAGE,
            self::REPORTS_VIEW_TEAM,
            self::REPORTS_VIEW_HR,
            self::AUDIT_LOGS_VIEW,
            self::SETTINGS_MANAGE,
        ];
    }

    /**
     * Permissions granted to each role.
     *
     * Super Admin is intentionally absent: it receives every permission via
     * {@see self::all()} so new permissions never silently lock it out.
     *
     * @return array<string, list<string>>
     */
    public static function matrix(): array
    {
        return [
            UserRole::SuperAdmin->value => self::all(),
            UserRole::HrAdmin->value => self::hrAdmin(),
            UserRole::Manager->value => self::manager(),
            UserRole::Employee->value => self::employee(),
        ];
    }

    /**
     * HR runs the people operations of the company but does not administer roles.
     *
     * @return list<string>
     */
    public static function hrAdmin(): array
    {
        return [
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_UPDATE,
            self::USERS_ACTIVATE,
            self::EMPLOYEES_VIEW,
            self::EMPLOYEES_CREATE,
            self::EMPLOYEES_UPDATE,
            self::EMPLOYEES_VIEW_SENSITIVE,
            self::EMPLOYEES_MANAGE_COMPENSATION,
            self::EMPLOYEES_MANAGE_DOCUMENTS,
            self::DEPARTMENTS_MANAGE,
            self::POSITIONS_MANAGE,
            self::LOCATIONS_MANAGE,
            self::EMPLOYMENT_TYPES_MANAGE,
            self::HOLIDAYS_MANAGE,
            self::ATTENDANCE_VIEW_OWN,
            self::ATTENDANCE_VIEW_TEAM,
            self::ATTENDANCE_VIEW_ALL,
            self::ATTENDANCE_RECORD_OWN,
            self::ATTENDANCE_CREATE,
            self::ATTENDANCE_CORRECT,
            self::ATTENDANCE_EXPORT,
            self::LEAVE_VIEW_OWN,
            self::LEAVE_VIEW_TEAM,
            self::LEAVE_VIEW_ALL,
            self::LEAVE_SUBMIT,
            self::LEAVE_APPROVE,
            self::LEAVE_OVERRIDE,
            self::LEAVE_TYPES_MANAGE,
            self::LEAVE_BALANCES_MANAGE,
            self::PAYROLL_VIEW_OWN,
            self::PAYROLL_VIEW_ALL,
            self::PAYROLL_MANAGE,
            self::PAYROLL_PUBLISH,
            self::PAYROLL_EXPORT,
            self::ANNOUNCEMENTS_VIEW,
            self::ANNOUNCEMENTS_MANAGE,
            self::REPORTS_VIEW_TEAM,
            self::REPORTS_VIEW_HR,
            self::AUDIT_LOGS_VIEW,
            self::SETTINGS_MANAGE,
        ];
    }

    /**
     * Managers see their own team only, and never compensation data.
     *
     * @return list<string>
     */
    public static function manager(): array
    {
        return [
            self::EMPLOYEES_VIEW,
            self::ATTENDANCE_VIEW_OWN,
            self::ATTENDANCE_VIEW_TEAM,
            self::ATTENDANCE_RECORD_OWN,
            self::LEAVE_VIEW_OWN,
            self::LEAVE_VIEW_TEAM,
            self::LEAVE_SUBMIT,
            self::LEAVE_APPROVE,
            self::PAYROLL_VIEW_OWN,
            self::ANNOUNCEMENTS_VIEW,
            self::REPORTS_VIEW_TEAM,
        ];
    }

    /**
     * Employees only ever reach their own records.
     *
     * @return list<string>
     */
    public static function employee(): array
    {
        return [
            self::ATTENDANCE_VIEW_OWN,
            self::ATTENDANCE_RECORD_OWN,
            self::LEAVE_VIEW_OWN,
            self::LEAVE_SUBMIT,
            self::PAYROLL_VIEW_OWN,
            self::ANNOUNCEMENTS_VIEW,
        ];
    }
}
