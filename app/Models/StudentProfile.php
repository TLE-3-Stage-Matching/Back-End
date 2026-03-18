<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'headline',
        'bio',
        'address_line',
        'postal_code',
        'city',
        'country',
        'searching_status',
        'exclude_demographics',
        'exclude_location',
    ];

    protected function casts(): array
    {
        return [
            'exclude_demographics' => 'boolean',
            'exclude_location' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
