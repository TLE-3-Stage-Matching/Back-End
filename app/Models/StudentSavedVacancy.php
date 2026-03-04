<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSavedVacancy extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'student_user_id',
        'vacancy_id',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'removed_at' => 'datetime',
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
}
