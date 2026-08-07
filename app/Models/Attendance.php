<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Attendance extends Model { protected $fillable=['employee_id','date','checked_in_at','checked_out_at','worked_minutes','late_minutes','status','correction_reason']; protected function casts(): array { return ['date'=>'date','checked_in_at'=>'datetime','checked_out_at'=>'datetime']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
