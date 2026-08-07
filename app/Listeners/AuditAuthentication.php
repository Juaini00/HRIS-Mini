<?php

namespace App\Listeners;

use App\Actions\Audit\WriteAuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class AuditAuthentication
{
    public function __construct(private WriteAuditLog $audit, private Request $request) {}

    public function handle(Login|Logout $event): void
    {
        $user = $event->user;

        $this->audit->handle(
            $this->request,
            $event instanceof Login ? 'auth.login' : 'auth.logout',
            $user instanceof User ? $user : null,
        );
    }
}
