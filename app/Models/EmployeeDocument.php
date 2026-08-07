<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use Database\Factories\EmployeeDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property int $uploaded_by
 * @property string $name
 * @property string|null $title
 * @property DocumentCategory|null $category
 * @property string $path
 * @property string|null $original_filename
 * @property string $mime_type
 * @property int $size
 * @property DocumentVisibility $visibility
 * @property string|null $description
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 */
class EmployeeDocument extends Model
{
    /** @use HasFactory<EmployeeDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id', 'uploaded_by', 'name', 'title', 'category', 'path',
        'original_filename', 'mime_type', 'size', 'visibility', 'description', 'expires_at',
    ];

    /**
     * The storage path is never exposed: files are streamed through an authorized
     * controller action so a raw URL can't be shared or guessed.
     */
    protected $hidden = ['path'];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'visibility' => DocumentVisibility::class,
            'size' => 'integer',
            'expires_at' => 'date:Y-m-d',
        ];
    }
}
