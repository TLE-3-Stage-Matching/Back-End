<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVacancyRequest;
use App\Http\Requests\Api\UpdateVacancyRequest;
use App\Models\Tag;
use App\Models\Vacancy;
use App\Models\VacancyComment;
use App\Models\VacancyRequirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VacancyController extends Controller
{
    /**
     * List vacancies for the authenticated company user's company.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->companyUser->company;
        $vacancies = Vacancy::query()
            ->where('company_id', $company->id)
            ->with(['location', 'vacancyRequirements.tag'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $vacancies,
            'links' => ['self' => url('/api/v1/company/vacancies')],
        ]);
    }

    /**
     * Create a vacancy with tags (existing by id or new by name + tag_type).
     */
    public function store(StoreVacancyRequest $request): JsonResponse
    {
        $company = $request->user()->companyUser->company;

        if ($request->filled('location_id')) {
            $locationExists = $company->locations()->where('id', $request->input('location_id'))->exists();
            if (!$locationExists) {
                return response()->json(['message' => 'Location does not belong to your company.'], 422);
            }
        }

        $vacancy = DB::transaction(function () use ($request, $company) {
            $vacancy = Vacancy::create([
                'company_id' => $company->id,
                'location_id' => $request->input('location_id'),
                'title' => $request->input('title'),
                'hours_per_week' => $request->input('hours_per_week'),
                'description' => $request->input('description'),
                'offer_text' => $request->input('offer_text'),
                'expectations_text' => $request->input('expectations_text'),
                'status' => $request->input('status'),
                'is_active' => false,
            ]);

            $tags = $request->input('tags', []);
            foreach ($tags as $tagInput) {
                if (!empty($tagInput['id'])) {
                    $tag = Tag::find($tagInput['id']);
                } else {
                    $tag = Tag::firstOrCreate(
                        [
                            'name' => $tagInput['name'],
                            'tag_type' => $tagInput['tag_type'],
                        ],
                        ['is_active' => true]
                    );
                }

                if ($tag) {
                    VacancyRequirement::create([
                        'vacancy_id' => $vacancy->id,
                        'tag_id' => $tag->id,
                        'requirement_type' => $tagInput['requirement_type'] ?? 'skill',
                        'importance' => $tagInput['importance'] ?? 3,
                    ]);
                }
            }

            return $vacancy->load(['location', 'vacancyRequirements.tag']);
        });

        return response()->json([
            'data' => $vacancy,
            'links' => ['self' => url("/api/v1/company/vacancies/{$vacancy->id}")],
        ], 201);
    }

    /**
     * Get a single vacancy (must belong to the authenticated company).
     */
    public function show(Request $request, Vacancy $vacancy): JsonResponse
    {
        $this->ensureVacancyBelongsToCompany($request, $vacancy);

        $vacancy->load(['location', 'vacancyRequirements.tag']);

        return response()->json([
            'data' => $vacancy,
            'links' => [
                'self' => url("/api/v1/company/vacancies/{$vacancy->id}"),
                'collection' => url('/api/v1/company/vacancies'),
            ],
        ]);
    }

    /**
     * Update a vacancy (and optionally replace its tags).
     */
    public function update(UpdateVacancyRequest $request, Vacancy $vacancy): JsonResponse
    {
        $company = $request->user()->companyUser->company;
        $this->ensureVacancyBelongsToCompany($request, $vacancy);

        if ($request->filled('location_id')) {
            $locationExists = $company->locations()->where('id', $request->input('location_id'))->exists();
            if (!$locationExists) {
                return response()->json(['message' => 'Location does not belong to your company.'], 422);
            }
        }

        $vacancy = DB::transaction(function () use ($request, $vacancy) {
            $fillable = [
                'title', 'location_id', 'hours_per_week', 'description',
                'offer_text', 'expectations_text', 'status',
            ];
            $updates = [];
            foreach ($fillable as $key) {
                if ($request->has($key)) {
                    $updates[$key] = $request->input($key);
                }
            }
            $vacancy->update($updates);

            if ($request->has('tags')) {
                $vacancy->vacancyRequirements()->delete();
                $tags = $request->input('tags', []);
                foreach ($tags as $tagInput) {
                    if (!empty($tagInput['id'])) {
                        $tag = Tag::find($tagInput['id']);
                    } else {
                        $tag = Tag::firstOrCreate(
                            [
                                'name' => $tagInput['name'],
                                'tag_type' => $tagInput['tag_type'],
                            ],
                            ['is_active' => true]
                        );
                    }
                    if ($tag) {
                        VacancyRequirement::create([
                            'vacancy_id' => $vacancy->id,
                            'tag_id' => $tag->id,
                            'requirement_type' => $tagInput['requirement_type'] ?? 'skill',
                            'importance' => $tagInput['importance'] ?? 3,
                        ]);
                    }
                }
            }

            return $vacancy->fresh(['location', 'vacancyRequirements.tag']);
        });

        return response()->json([
            'data' => $vacancy,
            'links' => [
                'self' => url("/api/v1/company/vacancies/{$vacancy->id}"),
                'collection' => url('/api/v1/company/vacancies'),
            ],
        ]);
    }

    /**
     * Delete a vacancy (must belong to the authenticated company).
     */
    public function destroy(Request $request, Vacancy $vacancy): Response
    {
        $this->ensureVacancyBelongsToCompany($request, $vacancy);
        $vacancy->delete();

        return response()->noContent();
    }

    private function ensureVacancyBelongsToCompany(Request $request, Vacancy $vacancy): void
    {
        $company = $request->user()->companyUser->company;
        if ($vacancy->company_id !== $company->id) {
            abort(404);
        }
    }

    public function updateComment(Request $request, VacancyComment $comment): JsonResponse
    {
        $companyUser = auth()->user()->companyUser;

        if ($comment->vacancy->company_id !== $companyUser->company_id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $validated = $request->validate([
            'comment' => ['required', 'string']
        ]);

        $comment->update([
            'comment' => $validated['comment']
        ]);

        return response()->json([
            'message' => 'Comment updated successfully.',
            'data' => $comment,
            'links' => [
                'self' => url("/api/v1/company/vacancies/comments/{$comment->id}")
            ]
        ]);
    }

    public function listComments(Vacancy $vacancy): JsonResponse
    {
        $companyUser = auth()->user()->companyUser;

        if ($vacancy->company_id !== $companyUser->company_id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $comments = $vacancy->vacancyComments()->with('author')->get();

        return response()->json([
            'data' => $comments,
            'links' => [
                'self' => url("/api/v1/company/vacancies/{$vacancy->id}/comments")
            ]
        ]);
    }

    public function destroyComment(VacancyComment $comment): JsonResponse
    {
        $companyUser = auth()->user()->companyUser;

        if ($comment->vacancy->company_id !== $companyUser->company_id) {
            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }

        $commentId = $comment->id;

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.',
            'links' => [
                'self' => url("/api/v1/company/vacancies/comments/{$commentId}")
            ]
        ]);
    }
}
