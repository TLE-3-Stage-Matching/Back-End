<?php

namespace App\Http\Requests\Api;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateVacancyRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'location_id' => ['nullable', 'integer', 'exists:company_locations,id'],
            'hours_per_week' => ['nullable', 'integer', 'min:1', 'max:168'],
            'description' => ['nullable', 'string'],
            'offer_text' => ['nullable', 'string'],
            'expectations_text' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:32'],

            'tags' => ['sometimes', 'array'],
            'tags.*.id' => ['nullable', 'integer', 'exists:tags,id'],
            'tags.*.name' => ['required_without:tags.*.id', 'nullable', 'string', 'max:255'],
            'tags.*.tag_type' => ['required_without:tags.*.id', 'nullable', 'string', 'max:32'],
            'tags.*.requirement_type' => ['nullable', 'string', 'max:16'],
            'tags.*.importance' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }

    /** Maximum number of skill tags per vacancy. */
    public const MAX_SKILL_TAGS = 6;

    /** Maximum number of trait tags per vacancy. */
    public const MAX_TRAIT_TAGS = 4;

    /** Maximum number of major tags per vacancy. */
    public const MAX_MAJOR_TAGS = 5;

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('tags')) {
                return;
            }
            $tags = $this->input('tags', []);
            $majorCount = $this->countMajorTagsInVacancyTags($tags);
            if ($majorCount > self::MAX_MAJOR_TAGS) {
                $validator->errors()->add('tags', 'A vacancy may have at most ' . self::MAX_MAJOR_TAGS . ' major tags.');
            }
            [$skillCount, $traitCount] = $this->countSkillAndTraitTagsInVacancyTags($tags);
            if ($skillCount > self::MAX_SKILL_TAGS) {
                $validator->errors()->add('tags', 'A vacancy may have at most ' . self::MAX_SKILL_TAGS . ' skill tags.');
            }
            if ($traitCount > self::MAX_TRAIT_TAGS) {
                $validator->errors()->add('tags', 'A vacancy may have at most ' . self::MAX_TRAIT_TAGS . ' trait tags.');
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $tags
     * @return array{0: int, 1: int} [skill count, trait count]
     */
    protected function countSkillAndTraitTagsInVacancyTags(array $tags): array
    {
        $ids = [];
        $skillCount = 0;
        $traitCount = 0;
        foreach ($tags as $t) {
            if (! empty($t['id'])) {
                $ids[] = (int) $t['id'];
            } else {
                $type = isset($t['tag_type']) ? (string) $t['tag_type'] : null;
                if ($type === 'skill') {
                    $skillCount++;
                } elseif ($type === 'trait') {
                    $traitCount++;
                }
            }
        }
        if ($ids !== []) {
            $byType = Tag::whereIn('id', $ids)->selectRaw('tag_type, count(*) as c')->groupBy('tag_type')->pluck('c', 'tag_type');
            $skillCount += (int) ($byType['skill'] ?? 0);
            $traitCount += (int) ($byType['trait'] ?? 0);
        }
        return [$skillCount, $traitCount];
    }

    /**
     * @param  array<int, array<string, mixed>>  $tags
     */
    protected function countMajorTagsInVacancyTags(array $tags): int
    {
        $ids = [];
        $majorCount = 0;
        foreach ($tags as $t) {
            if (! empty($t['id'])) {
                $ids[] = (int) $t['id'];
            } else {
                if (isset($t['tag_type']) && (string) $t['tag_type'] === 'major') {
                    $majorCount++;
                }
            }
        }
        if ($ids !== []) {
            $majorCount += Tag::whereIn('id', $ids)->where('tag_type', 'major')->count();
        }

        return $majorCount;
    }
}
