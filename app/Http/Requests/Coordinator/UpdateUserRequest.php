<?php

namespace App\Http\Requests\Coordinator;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');
        $userId = $user instanceof \App\Models\User ? $user->id : $user;
        $rules = [
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:6'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];

        $routeUser = $this->route('user');
        $user = $routeUser instanceof \App\Models\User ? $routeUser : ($routeUser ? \App\Models\User::find($routeUser) : null);
        if ($user && $user->role === UserRole::Company) {
            $rules['company_id'] = ['sometimes', 'integer', 'exists:companies,id'];
            $rules['job_title'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
