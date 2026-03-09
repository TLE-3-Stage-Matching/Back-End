<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /**
     * Scope to only active (approved) companies.
     * Use for any listing that should show companies to students/public.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected $fillable = [
        'name',
        'industry_tag_id',
        'email',
        'phone',
        'size_category',
        'photo_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function industryTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'industry_tag_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(CompanyLocation::class);
    }

    public function companyUsers(): HasMany
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class);
    }

    public function studentFavoriteCompanies(): HasMany
    {
        return $this->hasMany(StudentFavoriteCompany::class);
    }

    public function matchFlags(): HasMany
    {
        return $this->hasMany(MatchFlag::class);
    }

    public function biasAlerts(): HasMany
    {
        return $this->hasMany(BiasAlert::class);
    }

    public function matchOverrides(): HasMany
    {
        return $this->hasMany(MatchOverride::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
