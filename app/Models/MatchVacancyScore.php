<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchVacancyScore extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'run_id',
        'student_user_id',
        'vacancy_id',
        'match_score',
        'full_analysis_text',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'run_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function matchVacancyFactors(): HasMany
    {
        return $this->hasMany(MatchVacancyFactor::class);
    }

    public function studentMatchChoices(): HasMany
    {
        return $this->hasMany(StudentMatchChoice::class, 'source_match_score_id');
    }
}
