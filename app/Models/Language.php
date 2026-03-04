<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    protected $fillable = ['name'];

    public function studentLanguages(): HasMany
    {
        return $this->hasMany(StudentLanguage::class);
    }
}
