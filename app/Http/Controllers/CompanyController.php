<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Company::query()->latest()->get(),
            'links' => [
                'self' => url('/api/v1/companies'),
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
            'is_active' => ['sometimes','boolean'],
        ]);

        $company = Company::create($data);

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
