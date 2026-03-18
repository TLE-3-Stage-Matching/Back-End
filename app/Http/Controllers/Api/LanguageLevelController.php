<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LanguageLevel;
use Illuminate\Http\JsonResponse;

class LanguageLevelController extends Controller
{
    /**
     * List all available language levels (master lookup for student language selection etc.).
     */
    public function index(): JsonResponse
    {
        $levels = LanguageLevel::query()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $levels,
            'links' => ['self' => url()->current()],
        ]);
    }
}
