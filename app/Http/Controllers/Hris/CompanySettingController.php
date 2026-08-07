<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanySettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CompanySettingController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->role === \App\Enums\UserRole::SuperAdmin, 403);

        return Inertia::render('hris/settings', [
            'settings' => Setting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request, WriteAuditLog $audit): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated() as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => (string) $value, 'is_public' => in_array($key, ['company_name', 'currency'], true)]);
            }
        });
        $audit->handle($request, 'settings.updated', metadata: ['keys' => array_keys($request->validated())]);

        return back()->with('success', 'Pengaturan perusahaan diperbarui.');
    }
}
