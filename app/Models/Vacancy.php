<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacancy extends Model
{
    protected $fillable = [
        'company_id',
        'location_id',
        'title',
        'hours_per_week',
        'description',
        'offer_text',
        'expectations_text',
        'status',
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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(CompanyLocation::class, 'location_id');
    }

    public function vacancyRequirements(): HasMany
    {
        return $this->hasMany(VacancyRequirement::class);
    }

    public function studentSavedVacancies(): HasMany
    {
        return $this->hasMany(StudentSavedVacancy::class);
    }

    public function matchVacancyScores(): HasMany
    {
        return $this->hasMany(MatchVacancyScore::class);
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

    public function studentMatchChoices(): HasMany
    {
        return $this->hasMany(StudentMatchChoice::class);
    }

    public function vacancyComments(): HasMany
    {
        return $this->hasMany(VacancyComment::class);
    }
}
