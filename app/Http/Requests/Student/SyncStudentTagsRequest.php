<?php

namespace App\Http\Requests\Student;

use App\Enums\UserRole;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SyncStudentTagsRequest extends FormRequest
{
    /** Maximum number of active skill tags per student. */
    public const MAX_ACTIVE_SKILLS = 6;

    /** Maximum number of active trait tags per student. */
    public const MAX_ACTIVE_TRAITS = 4;

    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Student;
    }

    public function rules(): array
    {
        return [
            'tags' => ['nullable', 'array'],
            'tags.*.tag_id' => ['integer', 'exists:tags,id'],
            'tags.*.is_active' => ['nullable', 'boolean'],
            'tags.*.weight' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tags = $this->input('tags', []);
            if ($tags === []) {
                return;
            }
            $tagIds = array_map(fn($t) => (int)$t['tag_id'], $tags);
            $tagTypes = Tag::whereIn('id', $tagIds)->pluck('tag_type', 'id')->all();

            $majorCount = 0;
            $activeSkillCount = 0;
            $activeTraitCount = 0;
            foreach ($tags as $t) {
                $tagId = (int)$t['tag_id'];
                $isActive = !array_key_exists('is_active', $t) || $t['is_active'];
                $type = $tagTypes[$tagId] ?? null;
                if ($type === null) {
                    continue;
                }
                if ($type === 'major') {
                    $majorCount++;
                }
                if ($isActive && $type === 'skill') {
                    $activeSkillCount++;
                }
                if ($isActive && $type === 'trait') {
                    $activeTraitCount++;
                }
            }
            if ($majorCount > 1) {
                $validator->errors()->add('tags', 'You may select at most one major.');
            }
            if ($activeSkillCount > self::MAX_ACTIVE_SKILLS) {
                $validator->errors()->add('tags', 'You may have at most ' . self::MAX_ACTIVE_SKILLS . ' active skills.');
            }
            if ($activeTraitCount > self::MAX_ACTIVE_TRAITS) {
                $validator->errors()->add('tags', 'You may have at most ' . self::MAX_ACTIVE_TRAITS . ' active traits.');
            }
        });
    }
}

