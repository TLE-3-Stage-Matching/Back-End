<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentMatchChoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_note' => ['nullable', 'string', 'max:65535'],
            'status' => ['nullable', 'string', 'in:withdrawn'],
        ];
    }
}
