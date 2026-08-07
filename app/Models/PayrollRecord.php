<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollRecord extends Model { protected $fillable=['payroll_period_id','employee_id','basic_salary','earnings','deductions','net_salary','breakdown']; protected function casts(): array { return ['basic_salary'=>'decimal:2','earnings'=>'decimal:2','deductions'=>'decimal:2','net_salary'=>'decimal:2','breakdown'=>'array']; } public function employee(): BelongsTo { return $this->belongsTo(Employee::class); } }
