<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->role === \App\Enums\UserRole::SuperAdmin, 403);

        return Inertia::render('hris/audit-logs', [
            'logs' => AuditLog::query()->with('user:id,name,email')->latest()->paginate(30)->withQueryString(),
        ]);
    }
}
