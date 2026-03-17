<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class CoordinatorTagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }
        if ($request->filled('tag_type')) {
            $query->where('tag_type', $request->input('tag_type'));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $tags = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $tags->items(),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
            'links' => [
                'self' => url('/api/v2/coordinator/tags'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tag_type' => ['required', 'string', 'max:32'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->validateUniqueTag($request, $data['name'], $data['tag_type']);

        $tag = Tag::create([
            'name' => $data['name'],
            'tag_type' => $data['tag_type'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $tag,
            'links' => [
                'self' => url("/api/v2/coordinator/tags/{$tag->id}"),
                'collection' => url('/api/v2/coordinator/tags'),
            ],
        ], 201);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json([
            'data' => $tag,
            'links' => [
                'self' => url("/api/v2/coordinator/tags/{$tag->id}"),
                'collection' => url('/api/v2/coordinator/tags'),
            ],
        ]);
    }

    public function update(Request $request, Tag $tag): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'tag_type' => ['sometimes', 'string', 'max:32'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $nextName = $data['name'] ?? $tag->name;
        $nextType = $data['tag_type'] ?? $tag->tag_type;
        $this->validateUniqueTag($request, $nextName, $nextType, $tag->id);

        $tag->update($data);

        return response()->json([
            'data' => $tag->fresh(),
            'links' => [
                'self' => url("/api/v2/coordinator/tags/{$tag->id}"),
                'collection' => url('/api/v2/coordinator/tags'),
            ],
        ]);
    }

    public function destroy(Tag $tag)
    {
        if ($tag->vacancyRequirements()->exists() ||
            $tag->studentTags()->exists() ||
            $tag->studentPreferences()->exists() ||
            $tag->companies()->exists() ||
            $tag->matchVacancyFactors()->exists()) {
            return response()->json([
                'message' => 'Tag is in use and cannot be deleted. Deactivate it instead by setting is_active=false.',
            ], 422);
        }

        $tag->delete();

        return response()->noContent();
    }

    private function validateUniqueTag(Request $request, string $name, string $tagType, ?int $ignoreId = null): void
    {
        $rule = Rule::unique('tags')->where(fn ($query) => $query
            ->where('name', $name)
            ->where('tag_type', $tagType)
        );

        if ($ignoreId !== null) {
            $rule = $rule->ignore($ignoreId);
        }

        validator(
            ['name' => $name],
            ['name' => [$rule]],
            ['name.unique' => 'A tag with this name and tag_type already exists.']
        )->validate();
    }
}

