<?php

namespace App\Listeners;

use App\Actions\Audit\WriteAuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class AuditAuthentication
{
    public function __construct(private WriteAuditLog $audit, private Request $request) {}

    public function handle(Login|Logout $event): void
    {
        $this->audit->handle($this->request, $event instanceof Login ? 'auth.login' : 'auth.logout', $event->user);
    }
}
