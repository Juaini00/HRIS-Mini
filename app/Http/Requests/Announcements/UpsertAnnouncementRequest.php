<?php

namespace App\Http\Requests\Announcements;

use Illuminate\Foundation\Http\FormRequest;

class UpsertAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdministrator(); }
    public function rules(): array { return ['title' => ['required','string','max:160'], 'body' => ['required','string','max:5000'], 'audience' => ['required','in:all,manager,employee'], 'department_id' => ['nullable','exists:departments,id'], 'location_id' => ['nullable','exists:locations,id'], 'published_at' => ['nullable','date']]; }
}
