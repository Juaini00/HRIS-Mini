<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcements\UpsertAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Announcement::query()->with('author')->withExists(['reads as is_read' => fn ($reads) => $reads->where('user_id', $request->user()->id)])->latest();
        if (! $request->user()->isAdministrator()) {
            $employee = $request->user()->employee;
            $query->whereNotNull('published_at')->where('published_at', '<=', now())->whereIn('audience', ['all', $request->user()->role->value])
                ->where(fn ($filter) => $filter->whereNull('department_id')->orWhere('department_id', $employee?->department_id))
                ->where(fn ($filter) => $filter->whereNull('location_id')->orWhere('location_id', $employee?->location_id));
        }

        return Inertia::render('hris/announcements', ['announcements' => $query->paginate(20), 'canManage' => $request->user()->isAdministrator(), 'departments' => Department::where('is_active', true)->get(), 'locations' => Location::where('is_active', true)->get()]);
    }

    public function store(UpsertAnnouncementRequest $request, WriteAuditLog $audit): RedirectResponse
    {
        $announcement = Announcement::create([...$request->validated(), 'author_id' => $request->user()->id]);
        $audit->handle($request, 'announcement.created', $announcement);
        return back()->with('success', 'Pengumuman disimpan.');
    }

    public function update(UpsertAnnouncementRequest $request, Announcement $announcement, WriteAuditLog $audit): RedirectResponse
    {
        $announcement->update($request->validated()); $audit->handle($request, 'announcement.updated', $announcement);
        return back()->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Request $request, Announcement $announcement, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $audit->handle($request, 'announcement.deleted', $announcement); $announcement->delete();
        return back()->with('success', 'Pengumuman dihapus.');
    }

    public function read(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->published_at?->lte(now()), 404);
        $employee = $request->user()->employee;
        abort_unless(
            $request->user()->isAdministrator()
            || (in_array($announcement->audience, ['all', $request->user()->role->value], true)
                && ($announcement->department_id === null || $announcement->department_id === $employee?->department_id)
                && ($announcement->location_id === null || $announcement->location_id === $employee?->location_id)),
            403,
        );
        AnnouncementRead::firstOrCreate(['announcement_id' => $announcement->id, 'user_id' => $request->user()->id], ['read_at' => now()]);
        return back();
    }
}
