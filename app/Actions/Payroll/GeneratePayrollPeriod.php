<?php
namespace App\Actions\Payroll;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
class GeneratePayrollPeriod { public function handle(array $data): PayrollPeriod { return DB::transaction(function () use ($data) { $period=PayrollPeriod::create($data); Employee::query()->whereNull('ended_at')->each(function (Employee $employee) use ($period) { $salary=round((float)$employee->basic_salary,2); $period->records()->create(['employee_id'=>$employee->id,'basic_salary'=>$salary,'earnings'=>0,'deductions'=>0,'net_salary'=>$salary,'breakdown'=>['basic_salary'=>$salary]]); }); return $period->load('records'); }); } }
