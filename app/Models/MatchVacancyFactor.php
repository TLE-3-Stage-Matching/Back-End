<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchVacancyFactor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'match_vacancy_score_id',
        'factor_label',
        'impact',
        'polarity',
        'factor_type',
        'tag_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function matchVacancyScore(): BelongsTo
    {
        return $this->belongsTo(MatchVacancyScore::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
