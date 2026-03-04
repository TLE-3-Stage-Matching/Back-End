<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'role',
        'email',
        'password_hash',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'profile_photo_url',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function setPasswordHashAttribute($value): void
    {
        $this->attributes['password_hash'] = $value ? bcrypt($value) : null;
    }

    public function companyUser(): HasOne
    {
        return $this->hasOne(CompanyUser::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class, 'user_id');
    }

    public function studentExperiences(): HasMany
    {
        return $this->hasMany(StudentExperience::class, 'student_user_id');
    }

    public function studentTags(): HasMany
    {
        return $this->hasMany(StudentTag::class, 'student_user_id');
    }

    public function studentLanguages(): HasMany
    {
        return $this->hasMany(StudentLanguage::class, 'student_user_id');
    }

    public function studentPreferences(): HasOne
    {
        return $this->hasOne(StudentPreference::class, 'student_user_id');
    }

    public function studentFavoriteCompanies(): HasMany
    {
        return $this->hasMany(StudentFavoriteCompany::class, 'student_user_id');
    }

    public function studentSavedVacancies(): HasMany
    {
        return $this->hasMany(StudentSavedVacancy::class, 'student_user_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by_user_id');
    }
}
