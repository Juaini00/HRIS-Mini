<?php

namespace App\Actions\Documents;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreEmployeeDocument
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Employee $employee, User $uploader, UploadedFile $file, array $data): EmployeeDocument
    {
        $path = $file->store("employee-documents/{$employee->id}", 'local');

        return DB::transaction(fn () => EmployeeDocument::create([
            'employee_id' => $employee->id,
            'uploaded_by' => $uploader->id,
            'name' => $data['name'],
            'category' => $data['category'],
            'path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'expires_at' => $data['expires_at'] ?? null,
        ]));
    }
}
