<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\UserRole;
use App\Enums\WorkScheduleType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Fictional workforce for the demo environment.
 *
 * Deterministic: Faker is seeded so repeated runs produce the same people, which keeps
 * screenshots, docs, and manual testing stable.
 */
class PeopleSeeder extends Seeder
{
    /**
     * Documented demo password. Local/demo environments only — never a production default.
     */
    public const DEMO_PASSWORD = 'password';

    private const EMPLOYEE_COUNT = 40;

    public function run(): void
    {
        fake()->seed(20260807);

        $company = Company::current();
        $departments = Department::where('code', '!=', 'EXE')->get();
        $locations = Location::where('is_active', true)->get();
        $employmentTypes = EmploymentType::where('is_active', true)->get()->keyBy('name');
        $leaveTypes = LeaveType::where('is_active', true)->whereNotNull('code')->get();
        $components = SalaryComponent::whereIn('code', ['TRANS', 'MEAL', 'POS', 'PENS'])->get();

        $chief = $this->createPerson(
            email: 'admin@nusahr.test',
            name: 'Ratna Wijaya',
            role: UserRole::SuperAdmin,
            position: Position::where('code', 'CEO')->firstOrFail(),
            department: Department::where('code', 'EXE')->firstOrFail(),
            location: $locations->first(),
            employmentType: $employmentTypes['Permanent'],
            salary: 55_000_000,
            joinedAt: '2019-02-01',
            managerId: null,
        );

        $hrLead = $this->createPerson(
            email: 'hr@nusahr.test',
            name: 'Dewi Kartika',
            role: UserRole::HrAdmin,
            position: Position::where('code', 'HRM')->firstOrFail(),
            department: Department::where('code', 'HR')->firstOrFail(),
            location: $locations->first(),
            employmentType: $employmentTypes['Permanent'],
            salary: 22_000_000,
            joinedAt: '2020-06-15',
            managerId: $chief->id,
        );

        $this->createPerson(
            email: 'hr.admin@nusahr.test',
            name: 'Bayu Pratama',
            role: UserRole::HrAdmin,
            position: Position::where('code', 'HRA')->firstOrFail(),
            department: Department::where('code', 'HR')->firstOrFail(),
            location: $locations->first(),
            employmentType: $employmentTypes['Permanent'],
            salary: 11_000_000,
            joinedAt: '2022-03-07',
            managerId: $hrLead->id,
        );

        $managers = $this->createManagers($chief, $departments, $locations, $employmentTypes);
        $this->createEmployees($managers, $departments, $locations, $employmentTypes);

        $this->assignSalaryComponents($components);
        $this->seedLeaveBalances($leaveTypes, $company->default_annual_leave_days);
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, Location>  $locations
     * @param  Collection<string, EmploymentType>  $employmentTypes
     * @return list<Employee>
     */
    private function createManagers(Employee $chief, $departments, $locations, $employmentTypes): array
    {
        $managerPositions = ['EM', 'PM', 'FIA', 'MKS', 'AE', 'REC'];
        $managers = [];

        foreach ($managerPositions as $index => $code) {
            $position = Position::where('code', $code)->firstOrFail();
            $email = $index === 0 ? 'manager@nusahr.test' : "manager{$index}@nusahr.test";

            $managers[] = $this->createPerson(
                email: $email,
                name: fake()->name(),
                role: UserRole::Manager,
                position: $position,
                department: $this->departmentFor($departments, $position),
                location: $locations[$index % $locations->count()],
                employmentType: $employmentTypes['Permanent'],
                salary: fake()->numberBetween(18_000_000, 30_000_000),
                joinedAt: fake()->dateTimeBetween('-6 years', '-2 years')->format('Y-m-d'),
                managerId: $chief->id,
            );
        }

        return $managers;
    }

    /**
     * @param  list<Employee>  $managers
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, Location>  $locations
     * @param  Collection<string, EmploymentType>  $employmentTypes
     */
    private function createEmployees(array $managers, $departments, $locations, $employmentTypes): void
    {
        $icPositions = Position::whereIn('code', ['SSE', 'SWE', 'QA', 'UXD', 'ACC', 'HRA', 'MKS', 'AE'])->get();

        // A spread of statuses so dashboards and reports have something to show.
        $statuses = array_merge(
            array_fill(0, 31, EmploymentStatus::Active),
            array_fill(0, 4, EmploymentStatus::Probation),
            array_fill(0, 2, EmploymentStatus::OnLeave),
            [EmploymentStatus::Suspended, EmploymentStatus::Resigned, EmploymentStatus::Terminated],
        );

        for ($i = 0; $i < self::EMPLOYEE_COUNT; $i++) {
            $position = $icPositions[$i % $icPositions->count()];
            $status = $statuses[$i];
            $manager = $managers[$i % count($managers)];
            $joinedAt = Carbon::parse(fake()->dateTimeBetween('-4 years', '-1 month')->format('Y-m-d'));
            $employmentType = $status === EmploymentStatus::Probation
                ? $employmentTypes['Probation']
                : $employmentTypes[fake()->randomElement(['Permanent', 'Permanent', 'Contract', 'Part-time'])];

            $this->createPerson(
                email: $i === 0 ? 'employee@nusahr.test' : "employee{$i}@nusahr.test",
                name: fake()->name(),
                role: UserRole::Employee,
                position: $position,
                department: $this->departmentFor($departments, $position),
                location: $locations[$i % $locations->count()],
                employmentType: $employmentType,
                salary: fake()->numberBetween(8_000_000, 24_000_000),
                joinedAt: $joinedAt->toDateString(),
                managerId: $manager->id,
                status: $status,
            );
        }
    }

    /**
     * The department a position belongs to, falling back to the first available one so
     * seeding never breaks on a position that was created without a department.
     *
     * @param  Collection<int, Department>  $departments
     */
    private function departmentFor(Collection $departments, Position $position): Department
    {
        return $departments->firstWhere('id', $position->department_id) ?? $departments->firstOrFail();
    }

    private function createPerson(
        string $email,
        string $name,
        UserRole $role,
        Position $position,
        Department $department,
        Location $location,
        EmploymentType $employmentType,
        int $salary,
        string $joinedAt,
        ?int $managerId,
        EmploymentStatus $status = EmploymentStatus::Active,
    ): Employee {
        $user = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make(self::DEMO_PASSWORD),
            'role' => $role,
            'is_active' => $status->isCurrentlyEmployed(),
            'email_verified_at' => now(),
        ]);
        $user->syncRoles([$role->value]);

        $joined = Carbon::parse($joinedAt);
        [$firstName, $lastName] = $this->splitName($name);
        $ended = in_array($status, [EmploymentStatus::Resigned, EmploymentStatus::Terminated], true)
            ? $joined->copy()->addYears(2)
            : null;

        $employee = $user->employee()->updateOrCreate([], [
            'employee_number' => 'EMP-'.$joined->year.'-'.str_pad((string) ($user->id), 4, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'work_email' => $email,
            'personal_email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+62 8## #### ####'),
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-22 years')->format('Y-m-d'),
            'place_of_birth' => fake()->city(),
            'nationality' => 'Indonesian',
            'marital_status' => fake()->randomElement(MaritalStatus::cases()),
            'department_id' => $department->id,
            'position_id' => $position->id,
            'location_id' => $location->id,
            'employment_type_id' => $employmentType->id,
            'manager_id' => $managerId,
            'joined_at' => $joined->toDateString(),
            'ended_at' => $ended?->toDateString(),
            'terminated_on' => $status === EmploymentStatus::Terminated ? $ended?->toDateString() : null,
            'probation_ends_on' => $status === EmploymentStatus::Probation ? today()->addMonths(2)->toDateString() : null,
            'contract_starts_on' => $employmentType->name === 'Contract' ? $joined->toDateString() : null,
            'contract_ends_on' => $employmentType->name === 'Contract' ? $joined->copy()->addYears(2)->toDateString() : null,
            'employment_status' => $status,
            'work_schedule_type' => fake()->randomElement(WorkScheduleType::cases()),
            'basic_salary' => $salary,
            'bank_name' => fake()->randomElement(['Bank Mandiri', 'BCA', 'BNI', 'BRI']),
            'bank_account' => fake()->numerify('##########'),
            'bank_account_holder' => $name,
            'tax_number' => fake()->numerify('##.###.###.#-###.###'),
            'address' => fake()->streetAddress(),
            'city' => $location->city,
            'province' => $location->province,
            'postal_code' => fake()->numerify('#####'),
            'country' => 'Indonesia',
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling']),
            'emergency_contact_phone' => fake()->numerify('+62 8## #### ####'),
        ]);

        $employee->salaryHistories()->updateOrCreate(
            ['effective_from' => $joined->toDateString()],
            // Demo data has no real approver; attribute the record to the person it covers.
            ['amount' => $salary, 'created_by' => $user->id, 'notes' => 'Demo initial salary'],
        );

        return $employee;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [$name];
        $first = array_shift($parts);

        // Single-word names reuse the same token for both halves so neither column is blank.
        return [$first, $parts === [] ? $first : implode(' ', $parts)];
    }

    /**
     * @param  Collection<int, SalaryComponent>  $components
     */
    private function assignSalaryComponents($components): void
    {
        if ($components->isEmpty()) {
            return;
        }

        Employee::query()->currentlyEmployed()->each(function (Employee $employee) use ($components): void {
            foreach ($components as $component) {
                $employee->salaryComponents()->syncWithoutDetaching([
                    $component->id => ['effective_from' => $employee->joined_at->toDateString()],
                ]);
            }
        });
    }

    /**
     * @param  Collection<int, LeaveType>  $leaveTypes
     */
    private function seedLeaveBalances($leaveTypes, int $defaultAnnualDays): void
    {
        $year = (int) now()->year;

        Employee::query()->currentlyEmployed()->each(function (Employee $employee) use ($leaveTypes, $year, $defaultAnnualDays): void {
            foreach ($leaveTypes as $type) {
                $entitled = $type->code === 'ANN' ? $defaultAnnualDays : $type->annual_quota;

                LeaveBalance::updateOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => $year],
                    [
                        'entitled' => $entitled,
                        'carried_forward' => $type->carry_forward_enabled ? fake()->numberBetween(0, 3) : 0,
                        'used' => 0,
                        'pending' => 0,
                        'adjustment' => 0,
                        'last_recalculated_at' => now(),
                    ],
                );
            }
        });
    }
}
