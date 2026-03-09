<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SyncStudentLanguagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    public function rules(): array
    {
        return [
            'languages' => ['required', 'array'],
            'languages.*.language_id' => ['required', 'integer', 'exists:languages,id'],
            'languages.*.language_level_id' => ['required', 'integer', 'exists:language_levels,id'],
            'languages.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}

