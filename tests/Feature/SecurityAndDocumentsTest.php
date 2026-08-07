<?php

use App\Actions\Audit\WriteAuditLog;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function documentTestEmployee(UserRole $role = UserRole::Employee): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create(['role' => $role]);

    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('DOC-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => today(), 'basic_salary' => 1]);
}

test('HR can upload a private employee document and its owner can download it', function () {
    Storage::fake('local');
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = documentTestEmployee();

    $this->actingAs($hr)->post(route('employee-documents.store', $employee), [
        'name' => 'Employment Contract',
        'category' => 'contract',
        'document' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $document = EmployeeDocument::firstOrFail();
    Storage::disk('local')->assertExists($document->getRawOriginal('path'));
    $this->actingAs($employee->user)->get(route('employee-documents.show', $document))->assertSuccessful();
});

test('unrelated employee cannot download another employee document', function () {
    Storage::fake('local');
    $owner = documentTestEmployee();
    $unrelated = documentTestEmployee();
    $path = UploadedFile::fake()->create('private.pdf', 10, 'application/pdf')->store("employee-documents/{$owner->id}", 'local');
    $document = EmployeeDocument::create(['employee_id' => $owner->id, 'uploaded_by' => $owner->user_id, 'name' => 'Private', 'category' => 'identity', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => 10]);

    $this->actingAs($unrelated->user)->get(route('employee-documents.show', $document))->assertForbidden();
});

test('audit metadata masks secrets recursively', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $request = Request::create('/audit-test', 'POST');
    $request->setUserResolver(fn () => $admin);

    $log = app(WriteAuditLog::class)->handle($request, 'security.test', metadata: ['password' => 'unsafe', 'nested' => ['token' => 'unsafe']]);

    expect($log->metadata)->toBe(['password' => '[REDACTED]', 'nested' => ['token' => '[REDACTED]']]);
});

test('normal employees cannot view company settings or audit logs', function () {
    $employee = documentTestEmployee();

    $this->actingAs($employee->user)->get(route('company-settings.edit'))->assertForbidden();
    $this->actingAs($employee->user)->get(route('audit-logs.index'))->assertForbidden();
});

test('super admin updates validated settings and the change is audited', function () {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($admin)->put(route('company-settings.update'), [
        'company_name' => 'Nusa Teknologi',
        'work_starts_at' => '08:00',
        'work_ends_at' => '17:00',
        'late_tolerance_minutes' => 10,
        'currency' => 'IDR',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('settings', ['key' => 'company_name', 'value' => 'Nusa Teknologi']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'settings.updated']);
});
