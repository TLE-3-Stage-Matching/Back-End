<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiCriteriaVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'created_by_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function aiCriteriaRules(): HasMany
    {
        return $this->hasMany(AiCriteriaRule::class, 'criteria_version_id');
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class, 'criteria_version_id');
    }
}
