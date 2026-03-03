<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLocation extends Model
{
    protected $fillable = [
        'company_id',
        'address_line',
        'postal_code',
        'city',
        'country',
        'lat',
        'lon',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'double',
            'lon' => 'double',
            'is_primary' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function vacancies(): HasMany
    {
        return $this->hasMany(Vacancy::class, 'location_id');
    }
}
