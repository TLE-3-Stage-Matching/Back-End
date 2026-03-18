<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    /**
     * List all available languages (master lookup for student language selection etc.).
     */
    public function index(): JsonResponse
    {
        $languages = Language::query()->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'data' => $languages,
            'links' => ['self' => url()->current()],
        ]);
    }
}
