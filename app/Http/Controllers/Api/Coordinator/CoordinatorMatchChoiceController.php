<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MatchChoiceDecisionRequest;
use App\Models\StudentMatchChoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorMatchChoiceController extends Controller
{
    private const DECIDABLE_STATUSES = ['requested', 'shortlisted'];

    /**
     * List match choices with filters. Paginated.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StudentMatchChoice::query()
            ->with(['student:id,email,first_name,last_name', 'vacancy.company:id,name', 'decidedByUser:id,first_name,last_name'])
            ->orderByDesc('created_at');

        if ($request->filled('student_user_id')) {
            $query->where('student_user_id', $request->integer('student_user_id'));
        }
        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', $request->integer('vacancy_id'));
        }
        if ($request->filled('company_id')) {
            $query->whereHas('vacancy', fn ($q) => $q->where('company_id', $request->integer('company_id')));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginator = $query->paginate($request->integer('per_page', 15));
        $items = collect($paginator->items())->map(fn (StudentMatchChoice $c) => $this->formatChoice($c))->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => ['self' => url('/api/v2/coordinator/match-choices')],
        ]);
    }

    /**
     * Approve a match choice. Only when status is requested or shortlisted.
     */
    public function approve(MatchChoiceDecisionRequest $request, StudentMatchChoice $choice): JsonResponse
    {
        if (! in_array($choice->status, self::DECIDABLE_STATUSES, true)) {
            return response()->json(['message' => 'This choice cannot be approved (already decided or withdrawn).'], 403);
        }

        $choice->update([
            'status' => 'approved',
            'decided_by_user_id' => auth('api')->id(),
            'decided_at' => now(),
            'decision_note' => $request->validated()['decision_note'],
        ]);

        $choice->load(['student:id,email,first_name,last_name', 'vacancy.company:id,name', 'decidedByUser:id,first_name,last_name']);

        return response()->json([
            'message' => 'Match choice approved.',
            'data' => $this->formatChoice($choice),
            'links' => [
                'self' => url("/api/v2/coordinator/match-choices/{$choice->id}"),
                'collection' => url('/api/v2/coordinator/match-choices'),
            ],
        ]);
    }

    /**
     * Reject a match choice. Only when status is requested or shortlisted.
     */
    public function reject(MatchChoiceDecisionRequest $request, StudentMatchChoice $choice): JsonResponse
    {
        if (! in_array($choice->status, self::DECIDABLE_STATUSES, true)) {
            return response()->json(['message' => 'This choice cannot be rejected (already decided or withdrawn).'], 403);
        }

        $choice->update([
            'status' => 'rejected',
            'decided_by_user_id' => auth('api')->id(),
            'decided_at' => now(),
            'decision_note' => $request->validated()['decision_note'],
        ]);

        $choice->load(['student:id,email,first_name,last_name', 'vacancy.company:id,name', 'decidedByUser:id,first_name,last_name']);

        return response()->json([
            'message' => 'Match choice rejected.',
            'data' => $this->formatChoice($choice),
            'links' => [
                'self' => url("/api/v2/coordinator/match-choices/{$choice->id}"),
                'collection' => url('/api/v2/coordinator/match-choices'),
            ],
        ]);
    }

    private function formatChoice(StudentMatchChoice $choice): array
    {
        $data = [
            'id' => $choice->id,
            'student_user_id' => $choice->student_user_id,
            'vacancy_id' => $choice->vacancy_id,
            'status' => $choice->status,
            'student_note' => $choice->student_note,
            'decided_by_user_id' => $choice->decided_by_user_id,
            'decided_at' => $choice->decided_at?->toIso8601String(),
            'decision_note' => $choice->decision_note,
            'created_at' => $choice->created_at->toIso8601String(),
            'updated_at' => $choice->updated_at->toIso8601String(),
        ];
        if ($choice->relationLoaded('student') && $choice->student) {
            $data['student'] = [
                'id' => $choice->student->id,
                'email' => $choice->student->email,
                'first_name' => $choice->student->first_name,
                'last_name' => $choice->student->last_name,
            ];
        }
        if ($choice->relationLoaded('vacancy') && $choice->vacancy) {
            $data['vacancy'] = [
                'id' => $choice->vacancy->id,
                'title' => $choice->vacancy->title,
                'company_id' => $choice->vacancy->company_id,
            ];
            if ($choice->vacancy->relationLoaded('company') && $choice->vacancy->company) {
                $data['vacancy']['company'] = [
                    'id' => $choice->vacancy->company->id,
                    'name' => $choice->vacancy->company->name,
                ];
            }
        }
        if ($choice->relationLoaded('decidedByUser') && $choice->decidedByUser) {
            $data['decided_by_user'] = [
                'id' => $choice->decidedByUser->id,
                'first_name' => $choice->decidedByUser->first_name,
                'last_name' => $choice->decidedByUser->last_name,
            ];
        }
        return $data;
    }
}
