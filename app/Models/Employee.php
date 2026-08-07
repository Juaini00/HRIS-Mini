<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model { protected $fillable=['user_id','employee_number','department_id','position_id','location_id','manager_id','phone','joined_at','ended_at','basic_salary','bank_account']; protected $hidden=['basic_salary','bank_account']; protected function casts(): array { return ['joined_at'=>'date','ended_at'=>'date','basic_salary'=>'decimal:2']; } public function user(): BelongsTo { return $this->belongsTo(User::class); } public function department(): BelongsTo { return $this->belongsTo(Department::class); } public function position(): BelongsTo { return $this->belongsTo(Position::class); } public function manager(): BelongsTo { return $this->belongsTo(self::class,'manager_id'); } public function reports(): HasMany { return $this->hasMany(self::class,'manager_id'); } public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); } public function attendances(): HasMany { return $this->hasMany(Attendance::class); } }
