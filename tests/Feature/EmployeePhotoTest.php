<?php

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $this->employee = Employee::factory()->create();
});

it('stores a profile photo and audits the change', function () {
    $this->actingAs($this->hr)
        ->post(route('employees.photo.update', $this->employee), [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 400, 400),
        ])
        ->assertRedirect();

    $path = $this->employee->refresh()->photo_path;

    expect($path)->toStartWith('employee-photos/')
        // The stored name is generated, never the name the client supplied.
        ->and($path)->not->toContain('portrait.jpg')
        ->and(Storage::disk('public')->exists($path))->toBeTrue()
        ->and(AuditLog::where('event', 'employee.photo-updated')->exists())->toBeTrue();
});

it('deletes the previous photo when a new one replaces it', function () {
    $this->actingAs($this->hr)->post(route('employees.photo.update', $this->employee), [
        'photo' => UploadedFile::fake()->image('first.jpg', 400, 400),
    ]);
    $first = $this->employee->refresh()->photo_path;

    $this->actingAs($this->hr)->post(route('employees.photo.update', $this->employee), [
        'photo' => UploadedFile::fake()->image('second.jpg', 400, 400),
    ]);
    $second = $this->employee->refresh()->photo_path;

    expect($second)->not->toBe($first)
        ->and(Storage::disk('public')->exists($first))->toBeFalse()
        ->and(Storage::disk('public')->exists($second))->toBeTrue();
});

it('rejects a non-image disguised with an image extension', function () {
    // A PHP payload renamed to .jpg: the MIME check must catch it, not the extension.
    $this->actingAs($this->hr)
        ->post(route('employees.photo.update', $this->employee), [
            'photo' => UploadedFile::fake()->createWithContent('payload.jpg', '<?php echo "pwned";'),
        ])
        ->assertSessionHasErrors('photo');

    expect($this->employee->refresh()->photo_path)->toBeNull();
});

it('rejects an oversized image', function () {
    $this->actingAs($this->hr)
        ->post(route('employees.photo.update', $this->employee), [
            'photo' => UploadedFile::fake()->image('huge.jpg', 900, 900)->size(4096),
        ])
        ->assertSessionHasErrors('photo');
});

it('refuses a photo upload from an employee', function () {
    $other = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($other)
        ->post(route('employees.photo.update', $this->employee), [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 400, 400),
        ])
        ->assertForbidden();
});
