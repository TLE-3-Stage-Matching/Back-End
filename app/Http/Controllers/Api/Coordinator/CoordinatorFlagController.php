<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\MatchFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorFlagController extends Controller
{
    /**
     * List all flags with filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = MatchFlag::query()
            ->with(['student', 'vacancy', 'company']);

        // 🔎 Filters
        if ($request->filled('student_id')) {
            $query->where('student_user_id', $request->student_id);
        }

        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', $request->vacancy_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $flags = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $flags->map(fn($flag) => $this->formatFlag($flag))->items(),
            'meta' => [
                'current_page' => $flags->currentPage(),
                'last_page' => $flags->lastPage(),
                'per_page' => $flags->perPage(),
                'total' => $flags->total(),
            ],
            'links' => [
                'self' => url('/api/v2/coordinator/flags'),
            ],
        ]);
    }

    /**
     * Show a single flag
     */
    public function show(MatchFlag $flag): JsonResponse
    {
        $flag->load(['student', 'vacancy', 'company']);

        return response()->json([
            'data' => $this->formatFlag($flag),
            'links' => [
                'self' => url("/api/v2/coordinator/flags/{$flag->id}"),
                'collection' => url('/api/v2/coordinator/flags'),
            ],
        ]);
    }

    /**
     * Update flag status
     */
    public function updateStatus(Request $request, MatchFlag $flag): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,closed'],
        ]);

        $flag->update([
            'status' => $validated['status'],
            'coordinator_user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Flag status updated successfully.',
            'data' => $this->formatFlag($flag),
            'links' => [
                'self' => url("/api/v2/coordinator/flags/{$flag->id}"),
                'collection' => url('/api/v2/coordinator/flags'),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // 🔧 Helper: format response netjes
    // ─────────────────────────────────────────────

    private function formatFlag(MatchFlag $flag): array
    {
        return [
            'id' => $flag->id,
            'status' => $flag->status,
            'disputed_factor' => $flag->disputed_factor,
            'message' => $flag->message,
            'resolution_note' => $flag->resolution_note,
            'created_at' => $flag->created_at?->toIso8601String(),

            'student' => $flag->relationLoaded('student') && $flag->student ? [
                'id' => $flag->student->id,
                'name' => $flag->student->first_name . ' ' . $flag->student->last_name,
                'email' => $flag->student->email,
            ] : null,

            'vacancy' => $flag->relationLoaded('vacancy') && $flag->vacancy ? [
                'id' => $flag->vacancy->id,
                'title' => $flag->vacancy->title,
            ] : null,

            'company' => $flag->relationLoaded('company') && $flag->company ? [
                'id' => $flag->company->id,
                'name' => $flag->company->name,
            ] : null,
        ];
    }
}
