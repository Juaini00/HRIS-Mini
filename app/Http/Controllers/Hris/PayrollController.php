<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Actions\Payroll\CalculateEmployeePayroll;
use App\Actions\Payroll\GeneratePayrollPeriod;
use App\Enums\PayrollPeriodStatus;
use App\Events\PayrollPublished;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\StorePayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryComponent;
use App\Notifications\PayrollPublishedNotification;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $employeeId = $user->employee?->id;
        $query = PayrollPeriod::query()->with(['records' => fn ($records) => $user->isAdministrator() ? $records->with(['employee.user', 'items']) : $records->where('employee_id', $employeeId ?? 0)->with('items')]);
        if (! $user->isAdministrator()) {
            $query->where('status', PayrollPeriodStatus::Published);
        }

        return Inertia::render('hris/payroll', [
            'periods' => $query->latest('ends_on')->paginate(12),
            'canManage' => $user->isAdministrator(),
            'components' => $user->isAdministrator() ? SalaryComponent::query()->orderBy('name')->get() : [],
            'employees' => $user->isAdministrator() ? Employee::query()->with('user:id,name')->whereNull('ended_at')->orderBy('employee_number')->get(['id', 'user_id', 'employee_number']) : [],
        ]);
    }

    public function store(Request $request, GeneratePayrollPeriod $generate, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('manage', PayrollPeriod::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on']]);
        $period = $generate->handle($data);
        $audit->handle($request, 'payroll.generated', $period, ['records' => $period->records->count()]);

        return back()->with('success', 'Payroll berhasil dibuat.');
    }

    public function adjustment(StorePayrollAdjustmentRequest $request, PayrollRecord $payrollRecord, CalculateEmployeePayroll $calculator, WriteAuditLog $audit): RedirectResponse
    {
        $payrollRecord->items()->create([...$request->validated(), 'is_manual' => true, 'created_by' => $request->user()->id]);
        $calculator->handle($payrollRecord->period, $payrollRecord->employee);
        $audit->handle($request, 'payroll.adjusted', $payrollRecord, ['name' => $request->input('name'), 'type' => $request->input('type'), 'amount' => $request->input('amount')]);

        return back()->with('success', 'Penyesuaian payroll ditambahkan.');
    }

    public function component(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('manage', PayrollPeriod::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'type' => ['required', 'in:earning,deduction'], 'calculation_type' => ['required', 'in:fixed,percentage'], 'value' => ['required', 'numeric', 'min:0'], 'is_taxable' => ['boolean']]);
        $component = SalaryComponent::create($data);
        $audit->handle($request, 'salary-component.created', $component);

        return back()->with('success', 'Komponen gaji ditambahkan.');
    }

    public function assignComponent(Request $request, SalaryComponent $salaryComponent, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('manage', PayrollPeriod::class);
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id'], 'override_value' => ['nullable', 'numeric', 'min:0'], 'effective_from' => ['required', 'date'], 'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from']]);
        $employee = Employee::query()->whereKey($data['employee_id'])->firstOrFail();
        $employee->salaryComponents()->attach($salaryComponent->id, Arr::except($data, 'employee_id'));
        $audit->handle($request, 'salary-component.assigned', $salaryComponent, ['employee_id' => $employee->id]);

        return back()->with('success', 'Komponen gaji ditetapkan.');
    }

    public function publish(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize('manage', PayrollPeriod::class);
        DB::transaction(function () use ($request, $payrollPeriod): void {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($payrollPeriod->id);
            abort_if($period->status === PayrollPeriodStatus::Published, 422);
            $period->update(['status' => PayrollPeriodStatus::Published, 'published_at' => now(), 'published_by' => $request->user()->id]);
        });
        $payrollPeriod->records()->with('employee.user')->get()->each(fn (PayrollRecord $record) => $record->employee->user->notify(new PayrollPublishedNotification($payrollPeriod)));
        PayrollPublished::dispatch($payrollPeriod->refresh(), $request->user());

        return back()->with('success', 'Payroll dipublikasikan.');
    }

    public function export(Request $request, PayrollPeriod $payrollPeriod): StreamedResponse
    {
        Gate::authorize('manage', PayrollPeriod::class);

        return response()->streamDownload(function () use ($payrollPeriod): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, ['Employee Number', 'Name', 'Basic Salary', 'Earnings', 'Deductions', 'Net Salary']);
            $payrollPeriod->records()->with('employee.user')->orderBy('employee_id')->each(fn (PayrollRecord $record) => fputcsv($out, [
                Csv::safe($record->employee->employee_number),
                Csv::safe($record->employee->user->name),
                $record->basic_salary,
                $record->earnings,
                $record->deductions,
                $record->net_salary,
            ]));
            fclose($out);
        }, "payroll-{$payrollPeriod->id}.csv", ['Content-Type' => 'text/csv']);
    }
}
