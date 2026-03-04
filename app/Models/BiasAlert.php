<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiasAlert extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alert_type',
        'company_id',
        'vacancy_id',
        'must_have_snapshot',
        'remaining_candidates',
        'bias_tip',
        'status',
        'coordinator_user_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }
}
