<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'person_id',
    'email',
    'password',
    'role',
    'is_active',
    'deactivated_at',
    'first_name',
    'middle_name',
    'last_name',
    'extension_name',
    'birthdate',
    'sex',
    'civil_status',
    'google_email',
    'google_access_token',
    'google_refresh_token',
    'google_token_expires_at',
    'google_calendar_connected_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
            'google_calendar_connected_at' => 'datetime',
        ];
    }

    public function handledApplications()
    {
        return $this->hasMany(Application::class, 'social_worker_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function hasGoogleCalendarConnection(): bool
    {
        return filled($this->google_refresh_token);
    }
}
