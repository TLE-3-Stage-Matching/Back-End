<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentMatchChoiceRequest;
use App\Http\Requests\Student\UpdateStudentMatchChoiceRequest;
use App\Models\StudentMatchChoice;
use App\Models\Vacancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentMatchChoiceController extends Controller
{
    /**
     * List the authenticated student's match choices.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = StudentMatchChoice::query()
            ->with(['vacancy.company:id,name'])
            ->where('student_user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('vacancy_id')) {
            $query->where('vacancy_id', $request->integer('vacancy_id'));
        }

        $choices = $query->get();
        $data = $choices->map(fn (StudentMatchChoice $c) => $this->formatChoice($c));

        return response()->json([
            'data' => $data,
            'links' => ['self' => url('/api/v2/student/match-choices')],
        ]);
    }

    /**
     * Create a match choice (vacancy + optional student note). One choice per student-vacancy pair.
     */
    public function store(StoreStudentMatchChoiceRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $vacancyId = (int) $request->validated()['vacancy_id'];
        $studentNote = $request->validated()['student_note'] ?? null;

        $vacancy = Vacancy::query()->with('company')->find($vacancyId);
        if (! $vacancy || ! $vacancy->company || ! $vacancy->company->is_active) {
            return response()->json(['message' => 'Vacancy not found or company is not active.'], 422);
        }

        $exists = StudentMatchChoice::query()
            ->where('student_user_id', $user->id)
            ->where('vacancy_id', $vacancyId)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'A choice for this vacancy already exists.'], 422);
        }

        $choice = StudentMatchChoice::create([
            'student_user_id' => $user->id,
            'vacancy_id' => $vacancyId,
            'status' => 'requested',
            'student_note' => $studentNote,
        ]);

        $choice->load(['vacancy.company:id,name']);

        return response()->json([
            'message' => 'Match choice created successfully.',
            'data' => $this->formatChoice($choice),
            'links' => [
                'self' => url("/api/v2/student/match-choices/{$choice->id}"),
                'collection' => url('/api/v2/student/match-choices'),
            ],
        ], 201);
    }

    /**
     * Show a single match choice (own only).
     */
    public function show(StudentMatchChoice $choice): JsonResponse
    {
        $user = auth('api')->user();
        if ((int) $choice->student_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Match choice not found.'], 404);
        }

        $choice->load(['vacancy.company:id,name']);

        return response()->json([
            'data' => $this->formatChoice($choice),
            'links' => [
                'self' => url("/api/v2/student/match-choices/{$choice->id}"),
                'collection' => url('/api/v2/student/match-choices'),
            ],
        ]);
    }

    /**
     * Update student_note or withdraw (only when not yet decided).
     */
    public function update(UpdateStudentMatchChoiceRequest $request, StudentMatchChoice $choice): JsonResponse
    {
        $user = auth('api')->user();
        if ((int) $choice->student_user_id !== (int) $user->id) {
            return response()->json(['message' => 'Match choice not found.'], 404);
        }
        if ($choice->decided_at !== null) {
            return response()->json(['message' => 'This choice has already been decided and cannot be updated.'], 403);
        }
        if (! in_array($choice->status, ['requested', 'shortlisted'], true)) {
            return response()->json(['message' => 'Only requested or shortlisted choices can be updated.'], 403);
        }

        $validated = $request->validated();
        if (array_key_exists('student_note', $validated)) {
            $choice->student_note = $validated['student_note'];
        }
        if (! empty($validated['status']) && $validated['status'] === 'withdrawn') {
            $choice->status = 'withdrawn';
        }
        $choice->save();

        $choice->load(['vacancy.company:id,name']);

        return response()->json([
            'message' => 'Match choice updated successfully.',
            'data' => $this->formatChoice($choice),
            'links' => [
                'self' => url("/api/v2/student/match-choices/{$choice->id}"),
                'collection' => url('/api/v2/student/match-choices'),
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
        return $data;
    }
}
