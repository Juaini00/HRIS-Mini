<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveBalance extends Model { protected $fillable=['employee_id','leave_type_id','year','entitled','used','pending']; public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); } protected function casts(): array { return ['entitled'=>'decimal:2','used'=>'decimal:2','pending'=>'decimal:2']; } }
