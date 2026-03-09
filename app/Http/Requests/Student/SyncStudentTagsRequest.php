<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SyncStudentTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    public function rules(): array
    {
        return [
            'tags' => ['required', 'array'],
            'tags.*.tag_id' => ['required', 'integer', 'exists:tags,id'],
            'tags.*.is_active' => ['nullable', 'boolean'],
            'tags.*.weight' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }
}

