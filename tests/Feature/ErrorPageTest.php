<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders a 404 through the application shell', function () {
    $this->get('/definitely-not-a-route')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('error')->where('status', 404));
});

it('renders a 403 through the application shell', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($employee)
        ->get(route('audit-logs.index'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('error')->where('status', 403));
});

it('returns JSON rather than an Inertia page for API-style requests', function () {
    $this->getJson('/definitely-not-a-route')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');
});
