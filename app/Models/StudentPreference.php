<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPreference extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'student_user_id',
        'desired_role_tag_id',
        'hours_per_week_min',
        'hours_per_week_max',
        'max_distance_km',
        'has_drivers_license',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'has_drivers_license' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function desiredRoleTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'desired_role_tag_id');
    }
}
