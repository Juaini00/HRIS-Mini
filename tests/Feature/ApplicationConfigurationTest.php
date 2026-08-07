<?php

use Laravel\Fortify\Features;

test('application uses the NusaHR regional defaults', function () {
    expect(config('app.name'))->toBe('NusaHR')
        ->and(config('app.timezone'))->toBe('Asia/Makassar')
        ->and(config('database.default'))->toBe('sqlite');
});

test('public registration is disabled', function () {
    expect(Features::enabled(Features::registration()))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

test('example environment is safe and ready for PostgreSQL', function () {
    $exampleEnvironment = file_get_contents(base_path('.env.example'));

    expect($exampleEnvironment)
        ->toContain('APP_NAME=NusaHR')
        ->toContain('APP_TIMEZONE=Asia/Makassar')
        ->toContain("DATABASE_URL=\n")
        ->toContain('DB_CONNECTION=pgsql')
        ->toContain('FILESYSTEM_DISK=public')
        ->not->toMatch('/DATABASE_URL=\S+/');
});
