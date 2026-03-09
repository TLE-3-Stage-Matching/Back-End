<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Company registration is a public endpoint
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company.name' => ['required','string','max:255'],
            'company.industry_tag_id' => ['nullable','integer','exists:tags,id'],
            'company.email' => ['nullable','email','max:255'],
            'company.phone' => ['nullable','string','max:50'],
            'company.size_category' => ['nullable','string','max:50'],
            'company.photo_url' => ['nullable','string'],
            'company.banner_url' => ['nullable','string','max:512'],
            'company.description' => ['nullable','string'],

            'location.city' => ['required','string','max:255'],
            'location.country' => ['required','string','max:255'],
            'location.address_line' => ['nullable','string'],
            'location.postal_code' => ['nullable','string','max:32'],
            'location.lat' => ['nullable','numeric'],
            'location.lon' => ['nullable','numeric'],

            'user.email' => ['required','email','max:255','unique:users,email'],
            'user.first_name' => ['required','string','max:100'],
            'user.middle_name' => ['nullable','string','max:100'],
            'user.last_name' => ['required','string','max:100'],
            'user.phone' => ['nullable','string','max:50'],

            'password' => ['required','string','min:12','confirmed'],
        ];
    }
}
