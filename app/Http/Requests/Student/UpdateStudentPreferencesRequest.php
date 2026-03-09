<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    public function rules(): array
    {
        return [
            'desired_role_tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'hours_per_week_min' => ['nullable', 'integer', 'min:1', 'max:168'],
            'hours_per_week_max' => ['nullable', 'integer', 'min:1', 'max:168', 'gte:hours_per_week_min'],
            'max_distance_km' => ['nullable', 'integer', 'min:1'],
            'has_drivers_license' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

