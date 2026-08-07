<?php
namespace App\Models;
use App\Enums\LeaveStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LeaveRequest extends Model { protected $fillable=['employee_id','leave_type_id','start_date','end_date','days','reason','attachment_path','status','reviewed_by','reviewed_at','review_notes']; protected $attributes=['status'=>'pending']; protected function casts(): array { return ['start_date'=>'date','end_date'=>'date','days'=>'decimal:2','status'=>LeaveStatus::class,'reviewed_at'=>'datetime']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } public function leaveType(): BelongsTo { return $this->belongsTo(LeaveType::class); } }
