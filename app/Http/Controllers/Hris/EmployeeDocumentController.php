<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Actions\Documents\StoreEmployeeDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentController extends Controller
{
    public function store(StoreEmployeeDocumentRequest $request, Employee $employee, StoreEmployeeDocument $store, WriteAuditLog $audit): RedirectResponse
    {
        $document = $store->handle($employee, $request->user(), $request->file('document'), $request->validated());
        $audit->handle($request, 'employee.document.created', $document, ['employee_id' => $employee->id, 'category' => $document->category]);

        return back()->with('success', 'Dokumen berhasil disimpan.');
    }

    public function show(Request $request, EmployeeDocument $employeeDocument): StreamedResponse
    {
        abort_unless($request->user()->isAdministrator() || $request->user()->employee?->id === $employeeDocument->employee_id, 403);

        return Storage::disk('local')->download($employeeDocument->path, $employeeDocument->name, ['Content-Type' => $employeeDocument->mime_type]);
    }

    public function destroy(Request $request, EmployeeDocument $employeeDocument, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        Storage::disk('local')->delete($employeeDocument->path);
        $audit->handle($request, 'employee.document.deleted', $employeeDocument, ['employee_id' => $employeeDocument->employee_id, 'category' => $employeeDocument->category]);
        $employeeDocument->delete();

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
