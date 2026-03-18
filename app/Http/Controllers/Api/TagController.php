<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * List tags (for company users to select when creating vacancies).
     * Optional filter by tag_type.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()->where('is_active', true);

        if ($request->filled('tag_type')) {
            $query->where('tag_type', $request->input('tag_type'));
        }

        $tags = $query->orderBy('tag_type')->orderBy('name')->get();

        return response()->json([
            'data' => $tags,
            'links' => ['self' => url('/api/v1/tags')],
        ]);
    }
}
