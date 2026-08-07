<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','employee_number','department_id','position_id','location_id','manager_id','employment_type_id','phone','joined_at','ended_at','basic_salary','bank_account','emergency_contact'];
    protected $hidden = ['basic_salary','bank_account','emergency_contact'];
    protected function casts(): array { return ['joined_at'=>'date','ended_at'=>'date','basic_salary'=>'decimal:2','bank_account'=>'encrypted','emergency_contact'=>'array']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function employmentType(): BelongsTo { return $this->belongsTo(EmploymentType::class); }
    public function manager(): BelongsTo { return $this->belongsTo(self::class,'manager_id'); }
    public function reports(): HasMany { return $this->hasMany(self::class,'manager_id'); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
    public function documents(): HasMany { return $this->hasMany(EmployeeDocument::class); }
    public function salaryComponents(): BelongsToMany { return $this->belongsToMany(SalaryComponent::class, 'employee_salary_components')->withPivot(['override_value','effective_from','effective_to'])->withTimestamps(); }
    public function salaryHistories(): HasMany { return $this->hasMany(SalaryHistory::class); }
}
