<?php

namespace App\Http\Requests\Coordinator;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Coordinator;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'role' => ['required', Rule::enum(UserRole::class), Rule::in([UserRole::Student->value, UserRole::Company->value])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];

        if ($this->input('role') === UserRole::Company->value) {
            $rules['company_id'] = ['required', 'integer', 'exists:companies,id'];
            $rules['job_title'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
