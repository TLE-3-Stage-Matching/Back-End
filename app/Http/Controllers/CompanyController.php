<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query()->latest();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }
        if ($request->filled('industry_tag_id')) {
            $query->where('industry_tag_id', $request->input('industry_tag_id'));
        }
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $companies = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $companies->items(),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
            'links' => [
                'self' => url('/api/v1/coordinator/companies'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'industry_tag_id' => ['nullable','integer','exists:tags,id'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:50'],
            'size_category' => ['nullable','string','max:50'],
            'photo_url' => ['nullable','string'],
            'banner_url' => ['nullable','string','max:512'],
            'description' => ['nullable','string'],
            'is_active' => ['sometimes','boolean'],
        ]);

        $company = Company::create(array_merge(['is_active' => true], $data));

        return response()->json(['data' => $company], 201);
    }

    public function show(Company $company)
    {
        return response()->json([
            'data' => $company,
            'links' => [
                'self' => url("/api/v1/companies/{$company->id}"),
                'collection' => url('/api/v1/companies'),
            ],
        ]);
    }

    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name' => ['sometimes','string','max:255'],
            'industry_tag_id' => ['nullable','integer','exists:tags,id'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:50'],
            'size_category' => ['nullable','string','max:50'],
            'photo_url' => ['nullable','string'],
            'banner_url' => ['nullable','string','max:512'],
            'description' => ['nullable','string'],
            'is_active' => ['sometimes','boolean'],
        ]);

        $company->update($data);

        return response()->json(['data' => $company->fresh()]);
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return response()->noContent();
    }
}
