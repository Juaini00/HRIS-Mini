<?php

namespace App\Providers;

use App\Listeners\AuditAuthentication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->grantSuperAdminEverything();
        Event::listen(Login::class, AuditAuthentication::class);
        Event::listen(Logout::class, AuditAuthentication::class);
    }

    /**
     * The Super Admin passes every ability check without needing an explicit grant.
     *
     * This is deliberate: the role is defined as unrestricted, and tying its access to
     * rows in the permission table means a missing or un-run seeder would lock the owner
     * out of their own installation. Returning null for everyone else leaves the normal
     * policy and permission checks untouched.
     */
    protected function grantSuperAdminEverything(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
