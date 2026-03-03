<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'model_id',
        'prompt_id',
        'run_type',
        'criteria_version_id',
        'created_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(AiPrompt::class, 'prompt_id');
    }

    public function criteriaVersion(): BelongsTo
    {
        return $this->belongsTo(AiCriteriaVersion::class, 'criteria_version_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function matchVacancyScores(): HasMany
    {
        return $this->hasMany(MatchVacancyScore::class, 'run_id');
    }

    public function studentMatchChoices(): HasMany
    {
        return $this->hasMany(StudentMatchChoice::class, 'source_run_id');
    }
}
