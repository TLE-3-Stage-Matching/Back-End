<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreVacancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->companyUser;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:company_locations,id'],
            'hours_per_week' => ['nullable', 'integer', 'min:1', 'max:168'],
            'description' => ['nullable', 'string'],
            'offer_text' => ['nullable', 'string'],
            'expectations_text' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],

            'tags' => ['nullable', 'array'],
            'tags.*.id' => ['nullable', 'integer', 'exists:tags,id'],
            'tags.*.name' => ['required_without:tags.*.id', 'nullable', 'string', 'max:255'],
            'tags.*.tag_type' => ['required_without:tags.*.id', 'nullable', 'string', 'max:32'],
            'tags.*.requirement_type' => ['nullable', 'string', 'max:16'],
            'tags.*.importance' => ['nullable', 'integer'],
        ];
    }
}
