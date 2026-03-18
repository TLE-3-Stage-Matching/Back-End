<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentMatchChoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vacancy_id' => ['required', 'integer', 'exists:vacancies,id'],
            'student_note' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
