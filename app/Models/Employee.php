<?php

namespace App\Models;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\WorkScheduleType;
use App\Policies\EmployeePolicy;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The HR record for a person. Distinct from {@see User}, which is the login identity.
 *
 * @property int $id
 * @property int $user_id
 * @property string $employee_number
 * @property int|null $department_id
 * @property int|null $position_id
 * @property int|null $location_id
 * @property int|null $manager_id
 * @property int|null $employment_type_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $preferred_name
 * @property string|null $work_email
 * @property string|null $personal_email
 * @property string|null $phone
 * @property Gender|null $gender
 * @property Carbon|null $date_of_birth
 * @property string|null $place_of_birth
 * @property string|null $nationality
 * @property MaritalStatus|null $marital_status
 * @property string|null $photo_path
 * @property Carbon $joined_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $probation_ends_on
 * @property Carbon|null $contract_starts_on
 * @property Carbon|null $contract_ends_on
 * @property EmploymentStatus $employment_status
 * @property Carbon|null $terminated_on
 * @property WorkScheduleType $work_schedule_type
 * @property string $basic_salary
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_account_holder
 * @property string|null $tax_number
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_relationship
 * @property string|null $emergency_contact_phone
 * @property array<string, mixed>|null $emergency_contact
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Department|null $department
 * @property-read Position|null $position
 * @property-read Location|null $location
 * @property-read EmploymentType|null $employmentType
 * @property-read self|null $manager
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'employee_number', 'department_id', 'position_id', 'location_id',
        'manager_id', 'employment_type_id', 'first_name', 'last_name', 'preferred_name',
        'work_email', 'personal_email', 'phone', 'gender', 'date_of_birth', 'place_of_birth',
        'nationality', 'marital_status', 'photo_path', 'joined_at', 'ended_at',
        'probation_ends_on', 'contract_starts_on', 'contract_ends_on', 'employment_status',
        'terminated_on', 'work_schedule_type', 'basic_salary', 'bank_name', 'bank_account',
        'bank_account_holder', 'tax_number', 'address', 'city', 'province', 'postal_code',
        'country', 'emergency_contact_name', 'emergency_contact_relationship',
        'emergency_contact_phone', 'emergency_contact', 'notes', 'created_by', 'updated_by',
    ];

    /**
     * Confidential columns. Anything here must be explicitly unhidden by a policy-checked
     * controller before it reaches the frontend — see {@see EmployeePolicy}.
     */
    protected $hidden = [
        'basic_salary', 'bank_account', 'bank_name', 'bank_account_holder', 'tax_number',
        'emergency_contact', 'emergency_contact_name', 'emergency_contact_relationship',
        'emergency_contact_phone', 'personal_email', 'address', 'city', 'province',
        'postal_code', 'notes',
    ];

    /**
     * Full name, preferring the discrete HR name fields and falling back to the login name.
     */
    public function fullName(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $name !== '' ? $name : (string) $this->getRelationValue('user')?->name;
    }

    public function displayName(): string
    {
        return $this->preferred_name ?: $this->fullName();
    }

    /**
     * Employees who still count as workforce for headcount, payroll, and absence processing.
     *
     * @param  Builder<self>  $query
     */
    public function scopeCurrentlyEmployed(Builder $query): void
    {
        $query->whereIn('employment_status', EmploymentStatus::employedValues())
            ->where(fn (Builder $inner) => $inner->whereNull('ended_at')->orWhere('ended_at', '>=', today()->toDateString()));
    }

    /**
     * Every employee at or below this one in the reporting tree.
     *
     * Walks the hierarchy in PHP because the depth is small (a few levels) and a recursive
     * CTE would tie the query to PostgreSQL, while tests run on SQLite.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $collected = [];
        $frontier = [$this->id];

        while (count($frontier) > 0) {
            $children = self::query()->whereIn('manager_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($children, $collected, [$this->id]));
            $collected = array_merge($collected, $frontier);
        }

        return $collected;
    }

    /**
     * Guard against an employee reporting to themselves or to one of their own reports.
     */
    public function wouldCreateReportingCycle(?int $managerId): bool
    {
        if ($managerId === null) {
            return false;
        }

        if ($managerId === $this->id) {
            return true;
        }

        return in_array($managerId, $this->descendantIds(), true);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<EmploymentType, $this> */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /** @return HasMany<Employee, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** @return HasMany<LeaveBalance, $this> */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** @return HasMany<EmployeeDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /** @return HasMany<PayrollRecord, $this> */
    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /** @return BelongsToMany<SalaryComponent, $this> */
    public function salaryComponents(): BelongsToMany
    {
        return $this->belongsToMany(SalaryComponent::class, 'employee_salary_components')
            ->withPivot(['override_value', 'effective_from', 'effective_to'])
            ->withTimestamps();
    }

    /** @return HasMany<SalaryHistory, $this> */
    public function salaryHistories(): HasMany
    {
        return $this->hasMany(SalaryHistory::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'date:Y-m-d',
            'ended_at' => 'date:Y-m-d',
            'date_of_birth' => 'date:Y-m-d',
            'probation_ends_on' => 'date:Y-m-d',
            'contract_starts_on' => 'date:Y-m-d',
            'contract_ends_on' => 'date:Y-m-d',
            'terminated_on' => 'date:Y-m-d',
            'basic_salary' => 'decimal:2',
            'bank_account' => 'encrypted',
            'tax_number' => 'encrypted',
            'emergency_contact' => 'array',
            'gender' => Gender::class,
            'marital_status' => MaritalStatus::class,
            'employment_status' => EmploymentStatus::class,
            'work_schedule_type' => WorkScheduleType::class,
        ];
    }
}
