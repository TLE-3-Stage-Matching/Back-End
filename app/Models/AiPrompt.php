<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiPrompt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'template_text',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class, 'prompt_id');
    }
}
