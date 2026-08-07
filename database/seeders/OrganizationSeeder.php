<?php

namespace Database\Seeders;

use App\Enums\SalaryCalculationMethod;
use App\Enums\SalaryComponentType;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmploymentType;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Position;
use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

/**
 * Company profile and organization master data.
 *
 * Idempotent — every row is matched on its natural key, so re-running only fills gaps.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->company();
        $this->locations();
        $this->employmentTypes();
        $this->departments();
        $this->positions();
        $this->leaveTypes();
        $this->holidays();
        $this->salaryComponents();
    }

    private function company(): void
    {
        Company::updateOrCreate(['code' => 'NUSAHR'], [
            'name' => 'NusaHR',
            'legal_name' => 'PT NusaHR Teknologi Indonesia',
            'email' => 'people@nusahr.test',
            'phone' => '+62 411 555 0100',
            'address' => 'Jl. Penghibur No. 12',
            'city' => 'Makassar',
            'province' => 'Sulawesi Selatan',
            'postal_code' => '90111',
            'country' => 'Indonesia',
            'timezone' => 'Asia/Makassar',
            'currency' => 'IDR',
            'attendance_starts_at' => '09:00',
            'attendance_ends_at' => '17:00',
            'attendance_grace_minutes' => 15,
            'default_annual_leave_days' => 12,
            'payroll_cutoff_day' => 25,
            'is_active' => true,
        ]);
    }

    private function locations(): void
    {
        $locations = [
            ['code' => 'MKS', 'name' => 'Kantor Makassar', 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'timezone' => 'Asia/Makassar'],
            ['code' => 'JKT', 'name' => 'Kantor Jakarta', 'city' => 'Jakarta Selatan', 'province' => 'DKI Jakarta', 'timezone' => 'Asia/Jakarta'],
            ['code' => 'SBY', 'name' => 'Kantor Surabaya', 'city' => 'Surabaya', 'province' => 'Jawa Timur', 'timezone' => 'Asia/Jakarta'],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(['name' => $location['name']], [...$location, 'country' => 'Indonesia', 'is_active' => true]);
        }
    }

    private function employmentTypes(): void
    {
        $types = [
            ['code' => 'PERM', 'name' => 'Permanent', 'description' => 'Indefinite contract with full benefits.'],
            ['code' => 'CONT', 'name' => 'Contract', 'description' => 'Fixed-term contract.'],
            ['code' => 'PROB', 'name' => 'Probation', 'description' => 'Evaluation period before permanent status.'],
            ['code' => 'INTN', 'name' => 'Internship', 'description' => 'Student or graduate internship.'],
            ['code' => 'PART', 'name' => 'Part-time', 'description' => 'Reduced weekly hours.'],
        ];

        foreach ($types as $type) {
            EmploymentType::updateOrCreate(['name' => $type['name']], [...$type, 'is_active' => true]);
        }
    }

    private function departments(): void
    {
        $departments = [
            ['code' => 'EXE', 'name' => 'Executive', 'description' => 'Company leadership.'],
            ['code' => 'HR', 'name' => 'People & Culture', 'description' => 'Recruitment, people operations, and culture.'],
            ['code' => 'ENG', 'name' => 'Engineering', 'description' => 'Product engineering and platform.'],
            ['code' => 'PRD', 'name' => 'Product', 'description' => 'Product management and design.'],
            ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Accounting, payroll, and controlling.'],
            ['code' => 'MKT', 'name' => 'Marketing', 'description' => 'Brand, content, and growth.'],
            ['code' => 'SLS', 'name' => 'Sales', 'description' => 'Revenue and partnerships.'],
            ['code' => 'OPS', 'name' => 'Operations', 'description' => 'Facilities, IT support, and logistics.'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], [...$department, 'is_active' => true]);
        }

        // Everything except Executive reports up to it.
        $executive = Department::where('code', 'EXE')->first();
        if ($executive !== null) {
            Department::where('code', '!=', 'EXE')->whereNull('parent_id')->update(['parent_id' => $executive->id]);
        }
    }

    private function positions(): void
    {
        $positions = [
            ['code' => 'CEO', 'name' => 'Chief Executive Officer', 'department' => 'EXE', 'level' => 5, 'min_salary' => 45_000_000, 'max_salary' => 70_000_000],
            ['code' => 'COO', 'name' => 'Chief Operating Officer', 'department' => 'EXE', 'level' => 5, 'min_salary' => 40_000_000, 'max_salary' => 60_000_000],
            ['code' => 'HRM', 'name' => 'HR Manager', 'department' => 'HR', 'level' => 4, 'min_salary' => 18_000_000, 'max_salary' => 26_000_000],
            ['code' => 'HRA', 'name' => 'HR Administrator', 'department' => 'HR', 'level' => 2, 'min_salary' => 8_000_000, 'max_salary' => 13_000_000],
            ['code' => 'REC', 'name' => 'Recruiter', 'department' => 'HR', 'level' => 2, 'min_salary' => 8_500_000, 'max_salary' => 14_000_000],
            ['code' => 'EM', 'name' => 'Engineering Manager', 'department' => 'ENG', 'level' => 4, 'min_salary' => 25_000_000, 'max_salary' => 38_000_000],
            ['code' => 'SSE', 'name' => 'Senior Software Engineer', 'department' => 'ENG', 'level' => 3, 'min_salary' => 18_000_000, 'max_salary' => 30_000_000],
            ['code' => 'SWE', 'name' => 'Software Engineer', 'department' => 'ENG', 'level' => 2, 'min_salary' => 10_000_000, 'max_salary' => 18_000_000],
            ['code' => 'QA', 'name' => 'QA Engineer', 'department' => 'ENG', 'level' => 2, 'min_salary' => 9_000_000, 'max_salary' => 15_000_000],
            ['code' => 'PM', 'name' => 'Product Manager', 'department' => 'PRD', 'level' => 3, 'min_salary' => 17_000_000, 'max_salary' => 28_000_000],
            ['code' => 'UXD', 'name' => 'Product Designer', 'department' => 'PRD', 'level' => 2, 'min_salary' => 11_000_000, 'max_salary' => 19_000_000],
            ['code' => 'ACC', 'name' => 'Accountant', 'department' => 'FIN', 'level' => 2, 'min_salary' => 9_500_000, 'max_salary' => 15_000_000],
            ['code' => 'FIA', 'name' => 'Finance Analyst', 'department' => 'FIN', 'level' => 3, 'min_salary' => 13_000_000, 'max_salary' => 21_000_000],
            ['code' => 'MKS', 'name' => 'Marketing Specialist', 'department' => 'MKT', 'level' => 2, 'min_salary' => 8_500_000, 'max_salary' => 14_500_000],
            ['code' => 'AE', 'name' => 'Account Executive', 'department' => 'SLS', 'level' => 2, 'min_salary' => 9_000_000, 'max_salary' => 16_000_000],
        ];

        $departments = Department::pluck('id', 'code');

        foreach ($positions as $position) {
            // Matched on (department_id, name): that pair carries the table's unique index,
            // and existing rows predate the `code` column so matching on code would duplicate.
            Position::updateOrCreate([
                'department_id' => $departments[$position['department']] ?? null,
                'name' => $position['name'],
            ], [
                'code' => $position['code'],
                'level' => $position['level'],
                'min_salary' => $position['min_salary'],
                'max_salary' => $position['max_salary'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Demo leave policy. Real statutory entitlements vary by jurisdiction; these are
     * configurable defaults chosen to exercise the workflow, not legal advice.
     */
    private function leaveTypes(): void
    {
        $types = [
            ['code' => 'ANN', 'name' => 'Annual Leave', 'annual_quota' => 12, 'is_paid' => true, 'requires_attachment' => false, 'min_notice_days' => 3, 'carry_forward_enabled' => true, 'max_carry_forward_days' => 6, 'color' => '#2563eb'],
            ['code' => 'SICK', 'name' => 'Sick Leave', 'annual_quota' => 14, 'is_paid' => true, 'requires_attachment' => true, 'min_notice_days' => 0, 'allows_negative_balance' => true, 'color' => '#dc2626'],
            ['code' => 'UNPD', 'name' => 'Unpaid Leave', 'annual_quota' => 0, 'is_paid' => false, 'requires_attachment' => false, 'min_notice_days' => 7, 'allows_negative_balance' => true, 'color' => '#64748b'],
            ['code' => 'MAT', 'name' => 'Maternity Leave', 'annual_quota' => 90, 'is_paid' => true, 'requires_attachment' => true, 'min_notice_days' => 30, 'color' => '#db2777'],
            ['code' => 'PAT', 'name' => 'Paternity Leave', 'annual_quota' => 5, 'is_paid' => true, 'requires_attachment' => true, 'min_notice_days' => 7, 'color' => '#7c3aed'],
            ['code' => 'COMP', 'name' => 'Compassionate Leave', 'annual_quota' => 3, 'is_paid' => true, 'requires_attachment' => false, 'min_notice_days' => 0, 'color' => '#0f766e'],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(['name' => $type['name']], [...$type, 'is_active' => true]);
        }
    }

    /**
     * Sample Indonesian public holidays. Demo data only — verify against the official
     * government calendar before using anything like this in production.
     */
    private function holidays(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi'],
            ['date' => '2026-03-19', 'name' => 'Hari Raya Nyepi'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan Republik Indonesia'],
            ['date' => '2026-12-25', 'name' => 'Hari Raya Natal'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(['date' => $holiday['date']], [
                'name' => $holiday['name'],
                'description' => 'Demo public holiday.',
                'is_recurring' => false,
                'is_active' => true,
            ]);
        }
    }

    private function salaryComponents(): void
    {
        $components = [
            ['code' => 'TRANS', 'name' => 'Transport Allowance', 'type' => SalaryComponentType::Earning, 'calculation_type' => SalaryCalculationMethod::Fixed->value, 'value' => 500_000, 'is_taxable' => true],
            ['code' => 'MEAL', 'name' => 'Meal Allowance', 'type' => SalaryComponentType::Earning, 'calculation_type' => SalaryCalculationMethod::Fixed->value, 'value' => 750_000, 'is_taxable' => true],
            ['code' => 'POS', 'name' => 'Position Allowance', 'type' => SalaryComponentType::Earning, 'calculation_type' => SalaryCalculationMethod::PercentageOfBasic->value, 'value' => 10, 'is_taxable' => true],
            ['code' => 'BONUS', 'name' => 'Bonus', 'type' => SalaryComponentType::Earning, 'calculation_type' => SalaryCalculationMethod::Fixed->value, 'value' => 0, 'is_taxable' => true],
            ['code' => 'PENS', 'name' => 'Pension Contribution', 'type' => SalaryComponentType::Deduction, 'calculation_type' => SalaryCalculationMethod::PercentageOfBasic->value, 'value' => 2, 'is_taxable' => false],
            ['code' => 'OTHD', 'name' => 'Other Deduction', 'type' => SalaryComponentType::Deduction, 'calculation_type' => SalaryCalculationMethod::Fixed->value, 'value' => 0, 'is_taxable' => false],
        ];

        foreach ($components as $component) {
            SalaryComponent::updateOrCreate(['name' => $component['name']], [...$component, 'is_active' => true]);
        }
    }
}
