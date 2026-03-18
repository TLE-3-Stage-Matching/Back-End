<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'tag_type',
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

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'industry_tag_id');
    }

    public function vacancyRequirements(): HasMany
    {
        return $this->hasMany(VacancyRequirement::class);
    }

    public function studentTags(): HasMany
    {
        return $this->hasMany(StudentTag::class);
    }

    public function studentPreferences(): HasMany
    {
        return $this->hasMany(StudentPreference::class, 'desired_role_tag_id');
    }

    public function matchVacancyFactors(): HasMany
    {
        return $this->hasMany(MatchVacancyFactor::class);
    }
}
