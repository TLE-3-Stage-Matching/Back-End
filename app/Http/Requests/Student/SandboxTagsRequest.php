<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SandboxTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    public function rules(): array
    {
        return [
            'tags' => ['present', 'array'],
            'tags.*.tag_id' => ['required', 'integer', 'distinct', 'exists:tags,id'],
            'tags.*.weight' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tags = $this->input('tags', []);
            if (!is_array($tags) || $tags === []) {
                return;
            }

            $tagIds = array_values(array_unique(array_map(
                fn ($t) => (int) ($t['tag_id'] ?? 0),
                $tags
            )));

            $tagTypesById = Tag::query()
                ->whereIn('id', $tagIds)
                ->pluck('tag_type', 'id')
                ->all();

            $skillCount = 0;
            $traitCount = 0;

            foreach ($tags as $t) {
                $tagId = (int) ($t['tag_id'] ?? 0);
                $tagType = $tagTypesById[$tagId] ?? null;

                if (!in_array($tagType, ['skill', 'trait'], true)) {
                    $validator->errors()->add('tags', 'Sandbox mode only allows skill and trait tags.');
                    return;
                }

                if ($tagType === 'skill') {
                    $skillCount++;
                } else {
                    $traitCount++;
                }
            }

            $limits = (array) config('matching.tag_type_limits', []);
            $maxSkills = (int) ($limits['skill'] ?? SyncStudentTagsRequest::MAX_ACTIVE_SKILLS);
            $maxTraits = (int) ($limits['trait'] ?? SyncStudentTagsRequest::MAX_ACTIVE_TRAITS);

            if ($skillCount > $maxSkills) {
                $validator->errors()->add('tags', 'You may have at most ' . $maxSkills . ' skill tags in sandbox mode.');
            }
            if ($traitCount > $maxTraits) {
                $validator->errors()->add('tags', 'You may have at most ' . $maxTraits . ' trait tags in sandbox mode.');
            }
        });
    }
}

