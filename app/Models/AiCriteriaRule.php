<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCriteriaRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'criteria_version_id',
        'feature_type',
        'weight',
        'min_required',
        'penalty_if_missing',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'double',
            'penalty_if_missing' => 'double',
            'created_at' => 'datetime',
        ];
    }

    public function criteriaVersion(): BelongsTo
    {
        return $this->belongsTo(AiCriteriaVersion::class, 'criteria_version_id');
    }
}
