<?php

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Support\Facades\Notification;

function announcementEmployee(UserRole $role = UserRole::Employee): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]); $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]); $user = User::factory()->create(['role' => $role]);
    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('ANN-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => today(), 'basic_salary' => 1]);
}

test('draft scheduled and unrelated audience announcements are hidden', function () {
    $employee = announcementEmployee(); $author = User::factory()->create(['role' => UserRole::HrAdmin]);
    Announcement::create(['author_id' => $author->id, 'title' => 'Draft', 'body' => 'Hidden', 'audience' => 'all']);
    Announcement::create(['author_id' => $author->id, 'title' => 'Future', 'body' => 'Hidden', 'audience' => 'all', 'published_at' => now()->addDay()]);
    Announcement::create(['author_id' => $author->id, 'title' => 'Manager only', 'body' => 'Hidden', 'audience' => 'manager', 'published_at' => now()]);
    $this->actingAs($employee->user)->get(route('announcements.index'))->assertInertia(fn ($page) => $page->has('announcements.data', 0));
});

test('scheduled publishing notifies matching audience once', function () {
    Notification::fake(); $employee = announcementEmployee(); $manager = announcementEmployee(UserRole::Manager); $author = User::factory()->create(['role' => UserRole::HrAdmin]);
    $announcement = Announcement::create(['author_id' => $author->id, 'title' => 'For employees', 'body' => 'Published', 'audience' => 'employee', 'published_at' => now()->subMinute()]);
    $this->artisan('nusahr:publish-announcements')->assertSuccessful(); $this->artisan('nusahr:publish-announcements')->assertSuccessful();
    Notification::assertSentToTimes($employee->user, AnnouncementPublishedNotification::class, 1); Notification::assertNotSentTo($manager->user, AnnouncementPublishedNotification::class);
    expect($announcement->refresh()->notified_at)->not->toBeNull();
});

test('announcement read tracking is idempotent', function () {
    $employee = announcementEmployee(); $author = User::factory()->create(['role' => UserRole::HrAdmin]);
    $announcement = Announcement::create(['author_id' => $author->id, 'title' => 'Read me', 'body' => 'Published', 'audience' => 'all', 'published_at' => now()]);
    $this->actingAs($employee->user)->post(route('announcements.read', $announcement))->assertRedirect();
    $this->actingAs($employee->user)->post(route('announcements.read', $announcement))->assertRedirect();
    expect(AnnouncementRead::count())->toBe(1);
});
