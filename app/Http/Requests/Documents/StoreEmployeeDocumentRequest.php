<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:contract,identity,certificate,other'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ];
    }
}
