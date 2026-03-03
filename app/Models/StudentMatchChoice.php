<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMatchChoice extends Model
{
    protected $fillable = [
        'student_user_id',
        'vacancy_id',
        'source_run_id',
        'source_match_score_id',
        'status',
        'student_note',
        'decided_by_user_id',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function sourceRun(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'source_run_id');
    }

    public function sourceMatchScore(): BelongsTo
    {
        return $this->belongsTo(MatchVacancyScore::class, 'source_match_score_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
